<?php
/**
 * Admin Page: Edit Sign-up
 */

namespace FDSUS\Controller\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Id;
use FDSUS\Lib\Dls\Notice;
use FDSUS\Model\Capabilities as CapabilitiesModel;
use FDSUS\Model\Sheet as SheetModel;
use FDSUS\Model\Task as TaskModel;
use FDSUS\Model\Signup as SignupModel;
use FDSUS\Model\States as StatesModel;
use FDSUS\Model\SignupFormInitialValues;
use FDSUS\Model\Settings;
use WP_Screen;
use WP_User;
use FDSUS\Lib\Exception;

class EditSignupPage extends PageBase
{
    /** @var string */
    protected $menuSlug = 'fdsus-edit-signup';
    protected $parentMenuSlug = 'edit.php?post_type=' . SheetModel::POST_TYPE;
    protected $hideInParentMenu = true;

    public function __construct()
    {
        parent::__construct();
        add_action('admin_menu', array(&$this, 'menu'));
        add_action('current_screen', array(&$this, 'maybeProcessEditSignup'));
        add_action('current_screen', array(&$this, 'maybeProcessAddSignup'));
        add_action('current_screen', array(&$this, 'maybeDisplayNotice'));
        add_action('fdsus_signup_form_last_fields', array(&$this, 'addFieldsToForm'), 10, 2);
    }

    /**
     * Menu
     */
    public function menu()
    {
        $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);

        add_submenu_page(
            $this->parentMenuSlug,
            esc_html__('Edit Sign-up', 'sign-up-sheets'),
            '',
            $signupCaps->get('edit_posts'),
            $this->menuSlug,
            array(&$this, 'page')
        );
    }

    /**
     * Page
     */
    public function page()
    {
        $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);

        $signup = null;

        if (!empty($_GET['signup'])) {
            $signup = new SignupModel((int)$_GET['signup']);
            if (!$signup->isValid()) {
                wp_die(__('Sign-up invalid', 'sign-up-sheets'));
            }
        }

        if ($signup && !$signup->currentUserCanEdit()) {
            wp_die(esc_html__('You do not have sufficient permissions to edit this sign-up.'));
        }

        if (!$signup && !current_user_can($signupCaps->get('create_posts'))) {
            wp_die(esc_html__('You do not have sufficient permissions to add sign-ups.'));
        }

        $task = new TaskModel(!empty($_GET['task']) ? (int)$_GET['task'] : $signup->post_parent);
        if (!$task->isValid()) {
            wp_die(__('Task invalid', 'sign-up-sheets'));
        }

        $sheet = new SheetModel($task->post_parent);
        if (!$sheet->isValid()) {
            wp_die(__('Sheet invalid', 'sign-up-sheets'));
        }
        ?>

        <div class="wrap dls_sus">
            <h1 class="wp-heading-inline">
                <?php echo $_GET['action'] === 'add'
                    ? esc_html__('Add Sign-up', 'sign-up-sheets')
                    : esc_html__('Edit Sign-up', 'sign-up-sheets');
                ?>
            </h1>

        <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content" style="position: relative;">

            <?php
            $initial = new SignupFormInitialValues($sheet, $task, $signup, $_POST);
            $initialArray = $initial->get();
            $initialArray['user_id'] = isset($signup->dlssus_user_id) ? $signup->dlssus_user_id : '';
            $states = new StatesModel;
            $args = array(
                'sheet'              => $sheet,
                'task_id'            => $task->ID,
                'signup_titles_str'  => '',
                'initial'            => $initialArray,
                'multi_tag'          => '',
                'states'             => $states->get(),
                'submit_button_text' => __('Submit', 'sign-up-sheets'),
                'go_back_url'        => '',
                'signup_link_hash'   => ''
            );

            $located = Id::getPluginPath() . 'theme-files' . DIRECTORY_SEPARATOR . 'fdsus' . DIRECTORY_SEPARATOR . 'sign-up-form.php';
            load_template($located, true, $args);
            ?>
        </div>
            <div id="postbox-container-1" class="postbox-container">
                <div class="fdsus-edit-quick-info" role="group"
                     aria-label="<?php esc_attr_e('Sheet Quick Info', 'sign-up-sheets') ?>">
                    <span class="quick-info-item quick-info-id"><strong><?php
                            esc_html_e('Sheet ID', 'sign-up-sheets')
                            ?>: </strong> <code><?php echo $sheet->ID ?></code></span>
                    <?php do_action('fdsus_edit_sheet_quick_info', $sheet->getData()); ?>
                </div>

                <div class="postbox ">
                    <div class="postbox-header"><h2><?php
                            esc_html_e('Sheet and Task Info', 'sign-up-sheets') ?></h2></div>
                    <div class="inside">
                        <dl>
                            <dt><?php esc_html_e('Sheet', 'sign-up-sheets'); ?>:</dt>
                            <dd><?php echo wp_kses_post($sheet->post_title); ?></dd>

                            <dt><?php esc_html_e('Date', 'sign-up-sheets'); ?>:</dt>
                            <dd>
                                <?php echo(empty($sheet->dlssus_date)
                                    ? esc_html__('N/A', 'sign-up-sheets')
                                    : date(get_option('date_format'), strtotime($sheet->dlssus_date))
                                ); ?>
                            </dd>

                            <dt><?php esc_html_e('Task', 'sign-up-sheets'); ?>:</dt>
                            <dd><?php esc_html_e($task->post_title); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        </div>

        </div><!-- .wrap -->
        <?php
    }

    /**
     * Maybe process display notice
     *
     * @param WP_Screen $currentScreen Current WP_Screen object.
     *
     * @return void
     */
    public function maybeDisplayNotice($currentScreen)
    {
        if (empty($_GET['notice']) || !$this->isManageSignupsScreen($currentScreen)) {
            return;
        }

        Notice::instance();

        switch($_GET['notice']) {
            case 'edited':
                Notice::add('success', esc_html__('Sign-up updated.', 'sign-up-sheets'));
                break;
            case 'added':
                Notice::add('success', esc_html__('Sign-up added.', 'sign-up-sheets'));
                break;
        }

    }

    /**
     * Maybe process edit sign-up
     *
     * @param WP_Screen $currentScreen Current WP_Screen object
     *
     * @return void
     */
    public function maybeProcessEditSignup($currentScreen)
    {
        if (empty($_POST) || empty($_GET['action']) || $_GET['action'] !== 'edit' || !$this->isCurrentScreen($currentScreen)) {
            return;
        }

        if (empty($_GET['signup'])) {
            wp_die(esc_html__('Sign-up ID missing', 'sign-up-sheets'));
        }

        if (
            !isset($_POST['signup_nonce'])
            || !wp_verify_nonce($_POST['signup_nonce'], 'fdsus_signup_submit')
        ) {
            wp_die(esc_html__('Sign-up nonce not valid.', 'sign-up-sheets'));
        }

        Notice::instance();

        // Update signup
        $signup = new SignupModel((int)$_GET['signup']);

        if (!$signup->currentUserCanEdit()) {
            wp_die(esc_html__('You do not have sufficient permissions to edit this sign-up.', 'sign-up-sheets'));
        }

        if (!$signup->isValid()) {
            Notice::add('error', esc_html__('Sign-up not found.', 'sign-up-sheets'));
            return;
        }

        try {
            $signup->update(0, $_POST, true);

            $task = new TaskModel($signup->post_parent);
            $sheet = new SheetModel($task->post_parent);

            // Error Handling
            if (is_array($missingFieldNames = SignupModel::validateRequiredFields($_POST, $sheet))) {
                throw new Exception(
                    sprintf(
                    /* translators: %s is replaced with a comma separated list of all missing required fields */
                        esc_html__('Please complete the following required fields: %s', 'sign-up-sheets'),
                        implode(', ', $missingFieldNames)
                    )
                );
            }

            wp_redirect(add_query_arg(
                array('notice' => 'edited'),
                Settings::getManageSignupsPageUrl($_GET['sheet'])
            ));
        } catch (Exception $e) {
            Notice::add('error', esc_html($e->getMessage()));
        }
    }

    /**
     * Maybe process add sign-up
     *
     * @param WP_Screen $currentScreen Current WP_Screen object
     *
     * @return void
     */
    public function maybeProcessAddSignup($currentScreen)
    {
        if (empty($_POST) || empty($_GET['action']) || $_GET['action'] !== 'add' || !$this->isCurrentScreen($currentScreen)) {
            return;
        }

        $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);
        if (!current_user_can($signupCaps->get('create_posts'))) {
            wp_die(esc_html__('You do not have sufficient permissions to add a sign-up.', 'sign-up-sheets'));
        }

        Notice::instance();

        if (empty($_GET['task'])) {
            wp_die(esc_html__('Task-up ID is missing.', 'sign-up-sheets'));
        }

        if (!isset($_POST['signup_nonce']) || !wp_verify_nonce($_POST['signup_nonce'], 'fdsus_signup_submit')) {
            Notice::add(
                'error',
                esc_html__('Sign-up nonce not valid.', 'sign-up-sheets')
            );
            return;
        }

        // Add signup
        $signup = new SignupModel();

        try {
            $signup->add($_POST, (int)$_GET['task'], true);

            $task = new TaskModel((int)$_GET['task']);
            $sheet = new SheetModel($task->post_parent);

            // Error Handling
            if (is_array($missingFieldNames = SignupModel::validateRequiredFields($_POST, $sheet))) {
                throw new Exception(
                    sprintf(
                    /* translators: %s is replaced with a comma separated list of all missing required fields */
                        esc_html__('Please complete the following required fields: %s', 'sign-up-sheets'),
                        implode(', ', $missingFieldNames)
                    )
                );
            }

            wp_redirect(
                add_query_arg(
                    array('notice' => 'added'),
                    Settings::getManageSignupsPageUrl($_GET['sheet'])
                )
            );
        } catch (Exception $e) {
            Notice::add('error', esc_html($e->getMessage()));
        }
    }

    /**
     * Action run to add additional fields to the sign-up form
     *
     * @param SheetModel $sheet
     * @param array      $args
     */
    public function addFieldsToForm($sheet, $args)
    {
        if (!is_admin()) {
            return;
        }

        /** @var WP_User[] $users */
        $users = get_users();
        ?>
        <p class="fdsus-user">
            <label for="signup_user_id" class="signup_user_id">
                <?php esc_html_e('Linked User', 'sign-up-sheets'); ?>
            </label>
            <select id="signup_user_id" class="signup_user_id" name="signup_user_id">
                <option value=""></option>
                <?php
                foreach ($users as $user) {
                    // Only output the current user if they aren't able to edit others sign-ups
                    $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);
                    if (!current_user_can($signupCaps->get('edit_others_posts')) && get_current_user_id() !== $user->ID) {
                        continue;
                    }
                    $selected = ($args['initial']['user_id'] == $user->ID) ? ' selected="selected"' : null;
                    echo sprintf('<option value="%s"%s>%s</option>', $user->ID, $selected, $user->user_login . ' (' . $user->display_name . ')');
                }
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Is the screen the manage signups screen
     *
     * @param WP_Screen $currentScreen Current WP_Screen object
     *
     * @return bool
     */
    protected function isManageSignupsScreen($currentScreen)
    {
        return $currentScreen->id === SheetModel::POST_TYPE . '_page_fdsus-manage';
    }
}
