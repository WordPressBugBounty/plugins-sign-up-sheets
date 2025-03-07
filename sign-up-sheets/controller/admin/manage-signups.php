<?php
/**
 * Admin Page: Manage Sign-ups
 */

namespace FDSUS\Controller\Admin;

use FDSUS\Id;
use FDSUS\Model\Capabilities;
use FDSUS\Model\Data;
use FDSUS\Model\Sheet as SheetModel;
use FDSUS\Lib\Dls\Notice;
use FDSUS\Controller\TaskTable as TaskTableController;
use WP_Post;

class ManageSignups extends PageBase
{
    /** @var string */
    protected $menuSlug = 'fdsus-manage';

    public function __construct()
    {
        $this->data = new Data();
        add_action('admin_menu', array(&$this, 'menu'));
        add_action('init', array(&$this, 'maybeProcessClear'), 9);
        add_action('fdsus_edit_sheet_quick_info', array(&$this, 'addManageSheetLinkOnEditSheet'), 10, 1);
    }

    /**
     * Menu
     */
    public function menu()
    {
        $sheetCaps = new Capabilities(SheetModel::POST_TYPE);

        add_submenu_page(
            '', // Will throw notice in PHP 8.1+ due to WP core bug @see https://core.trac.wordpress.org/ticket/57579
            esc_html__('Manage Sign-ups', 'sign-up-sheets'),
            esc_html__('Manage Sign-ups', 'sign-up-sheets'),
            $sheetCaps->get('read_post'),
            $this->menuSlug,
            array(&$this, 'page')
        );
    }

    /**
     * Page
     */
    public function page()
    {
        $sheetCaps = new Capabilities(SheetModel::POST_TYPE);
        if (!current_user_can($sheetCaps->get('read_post'))) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'sign-up-sheets'));
        }

        if (empty($_GET['sheet_id']) || !is_numeric($_GET['sheet_id'])) {
            wp_die(esc_html__('Missing or invalid sheet ID.', 'sign-up-sheets'));
        }

        $sheet = new SheetModel((int)$_GET['sheet_id']);
        if (!is_object($sheet)) {
            wp_die(esc_html__('No sign-up sheet found.', 'sign-up-sheets'));
        }
        ?>

        <div class="wrap dls_sus">
            <h1>
                <?php esc_html_e('Manage Sign-ups', 'sign-up-sheets'); ?>
                <span class="fdsus-manage-h1-suffix">
                    <a href="<?php echo esc_attr(get_permalink($sheet->getData())); ?>" class="add-new-h2 page-title-action"><?php esc_html_e('View Sheet', 'sign-up-sheets'); ?></a>
                    <a href="<?php echo esc_attr(get_edit_post_link($sheet->getData())); ?>" class="add-new-h2 page-title-action"><?php esc_html_e('Edit Sheet', 'sign-up-sheets'); ?></a>
                    <?php do_action('fdsus_manage_signup_h1_suffix', $sheet); ?>
                </span>
            </h1>

            <h3><?php echo wp_kses_post($sheet->post_title); ?></h3>
            <p>
                <?php esc_html_e('Date', 'sign-up-sheets'); ?>:
                <?php echo(empty($sheet->dlssus_date)
                    ? esc_html__('N/A', 'sign-up-sheets')
                    : date(get_option('date_format'), strtotime($sheet->dlssus_date))
                ); ?>
            </p>

            <div class="dls-sus-sheet-details"><?php echo nl2br(wp_kses_post($sheet->post_content)); ?></div>

            <h4><?php esc_html_e('Sign-ups', 'sign-up-sheets'); ?></h4>

            <?php
            // Tasks
            $taskTableController = new TaskTableController($sheet);
            $taskTableController->output();
            ?>

        </div><!-- .wrap -->

        <?php
    }

    /**
     * Process clearing of spots if it needs to be
     */
    public function maybeProcessClear()
    {
        if (empty($_REQUEST['clear'])) {
            return;
        }

        $idsToClear = array();
        if (!empty($_POST['clear']) && is_array($_POST['clear'])) {
            $idsToClear = $_POST['clear'];
        } elseif (!empty($_GET['clear'])) {
            $idsToClear = (int)$_GET['clear'];
        }

        $sheetId = (int)$_GET['sheet_id'];

        if (empty($idsToClear) || empty($sheetId)) {
            return;
        }

        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'clear') {
            if ((is_array($idsToClear)
                    && (!isset($_POST['manage_signup_nonce'])
                        || !wp_verify_nonce($_POST['manage_signup_nonce'], 'clear-multiple-signups')
                    )
                )
                || (!is_array($idsToClear)
                    && (!isset($_GET['manage_signup_nonce'])
                        || !wp_verify_nonce($_GET['manage_signup_nonce'], 'clear-signup_' . $idsToClear)
                    )
                )
            ) {
                Notice::add(
                    'error', esc_html__('Manage sign-up sheet nonce not valid', 'sign-up-sheets'), false,
                    Id::PREFIX . '-manage-signup-nonce-invalid'
                );
                return;
            }
        }

        $sheet = new SheetModel($sheetId);
        if (!$sheet->isValid()) {
            Notice::add('error', esc_html__('Invalid Sheet', 'sign-up-sheets'), false, Id::PREFIX . '-sheet-invalid');
            return;
        }

        $result = $sheet->deleteSignups($idsToClear);
        if ($result) {
            Notice::add('success', esc_html__('Spot(s) cleared.', 'sign-up-sheets'), false, Id::PREFIX . '-clear-success');
        } else {
            /* translators: %d is replaced with the sheet ID */
            Notice::add('success', sprintf(esc_html__('Error clearing a spot (Sheet ID #%d)', 'sign-up-sheets'), (int)$_GET['sheet_id']), false, Id::PREFIX . '-clear-error');
        }
    }

    /**
     * Add manage sheet link on edit sheet page
     *
     * @param WP_Post $post
     */
    public function addManageSheetLinkOnEditSheet($post)
    {
        echo sprintf(
            '<a href="%s" id="dls-sus-manage-signups" class="quick-info-item">%s</a>',
            esc_url(admin_url(
                add_query_arg(
                    array(
                        'post_type' => SheetModel::POST_TYPE,
                        'page'      => $this->menuSlug,
                        'sheet_id'  => (int)$post->ID
                    ), 'edit.php'
                )
            )),
            esc_html__('Manage Sign-ups', 'sign-up-sheets')
        );
    }

}
