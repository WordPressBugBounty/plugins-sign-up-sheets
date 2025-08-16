<?php
/**
 * Settings Meta Boxes Model
 */

namespace FDSUS\Model;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Id as Id;
use FDSUS\Model\Settings\SheetOrder;
use FDSUS\Model\SheetCollection as SheetCollectionModel;
use FDSUS\Model\Settings as Settings;
use WP_Roles;
use Exception;

class SettingsMetaBoxes
{
    /** @var Data */
    private $data;

    public function __construct()
    {
        $this->data = new Data();
    }

    /**
     * Get options array
     *
     * $options = array(
     *     'id'      => 'sheet',
     *     'title'   => esc_html__('Sign-up Sheet', 'sign-up-sheets'),
     *     'order'   => 10,
     *     'options' => array(
     *         'label'    => 'Display Label',
     *         'name'     => 'field_name',
     *         'type'     => 'text', // Field type
     *         'note'     => 'Optional note',
     *         'options'  => array(), // optional array for select and multi-checkbox/radio type fields
     *         'order'    => 10, // sort order
     *         'pro'      => false, // pro feature
     *         'class'    => 'some-class', // adds class to surrounding <tr> element
     *         'disabled' => false, // mark input field as disabled
     *    )
     * );
     *
     * @return array
     * @throws Exception
     */
    public function getData()
    {
        /** @global WP_Roles $wp_roles */
        global $wp_roles;
        $roles = $wp_roles->get_names();
        $rolesModel = new Roles();
        $susSpecificRoles = $rolesModel->getCustomRoles();
        unset($roles['administrator']);
        foreach ($susSpecificRoles as $roleKey => $roleValue) {
            unset($roles[$roleKey]);
        }

        $susRoles = array();

        // Sheets Listing
        $sheetSelection = array('' => esc_html__('All', 'sign-up-sheets'));
        if (Id::isPro()) {
            $sheetCollection = new SheetCollectionModel();
            $sheets = $sheetCollection->get();
            foreach ($sheets as $sheet) {
                $sheetSelection[$sheet->ID] = '#' . ($sheet->ID) . ': ' . esc_html($sheet->post_title)
                    . (!empty($sheet->dlssus_date)
                        ? ' (' . date(get_option('date_format'), strtotime($sheet->dlssus_date)) . ')'
                        : null);
            }
        }

        // Custom field types
        $fieldTypesTask = array(
            'text'       => 'text',
            'textarea'   => 'textarea',
            'checkboxes' => 'checkboxes',
            'radio'      => 'radio',
            'dropdown'   => 'dropdown'
        );
        $fieldTypesSignup = $fieldTypesTask;
        $fieldTypesSignup['date'] = 'date';

        $sheetOrder = new SheetOrder();

        $defaultEmailVariables = sprintf(
            '%s<br>
            <code>{signup_details}</code> - %s<br>
            <code>{signup_firstname}</code> - %s<br>
            <code>{signup_lastname}</code> - %s<br>
            <code>{signup_email}</code> - %s<br>
            <code>{site_name}</code> - %s<br>
            <code>{site_url}</code> - %s<br>
            <code>{sheet_url}</code> - %s<br>
            <code>{sheet_title}</code> - %s',
            esc_html__('Variables that can be used in template...', 'sign-up-sheets'),
            esc_html__('Multi-line list of sign-up details such as date, sheet title, task title', 'sign-up-sheets'),
            esc_html__('First name of user that signed up', 'sign-up-sheets'),
            esc_html__('Last name of user that signed up', 'sign-up-sheets'),
            esc_html__('Email of user that signed up', 'sign-up-sheets'),
            esc_html__('Name of site as defined in Settings > General > Site Title', 'sign-up-sheets'),
            esc_html__('URL of site', 'sign-up-sheets'),
            esc_html__('Main permalink URL for the sheet', 'sign-up-sheets'),
            esc_html__('Title of the sign-up sheet', 'sign-up-sheets')
        );

        $options['sheet'] = array(
            'id'      => 'sheet',
            'title'   => esc_html__('Sign-up Sheet', 'sign-up-sheets'),
            'order'   => 10,
            'options' => array(
                array(
                    'label'   => esc_html__('Sheet order on Front-end', 'sign-up-sheets'),
                    'name'    => 'dls_sus_sheet_order',
                    'type'    => 'dropdown',
                    'options' => $sheetOrder->options(),
                    'order'   => 10
                ),
                array(
                    'label' => esc_html__('Show All Sign-up Data Fields on Front-end', 'sign-up-sheets'),
                    'name'  => 'dls_sus_display_all',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('WARNING: Sign-up sheet table will appear much like the table when sign-ups are viewed via the admin. This option will potentially display personal user information on the frontend like email address and phone.  This option is best used if you are using the [sign_up_sheet] short code within a password protected area. (This also overrides the "Front-end Display Names" option and displays all as full names.)', 'sign-up-sheets'),
                    'order' => 20
                ),
                array(
                    'label'   => esc_html__('Front-end Display Names', 'sign-up-sheets'),
                    'name'    => 'dls_sus_display_name',
                    'type'    => 'dropdown',
                    'note'    => esc_html__('How the user\'s name should be displayed on the front-end after they sign-up', 'sign-up-sheets'),
                    'options' => array(
                        'default'   => '"John S." - first name plus first letter of last name',
                        'full'      => '"John Smith" - full name',
                        'anonymous' => '"' . esc_html__('Filled', 'sign-up-sheets') . '" - ' . esc_html__('anonymous', 'sign-up-sheets'),
                    ),
                    'th-rowspan' => 2,
                    'order'   => 30,
                    'pro'     => true
                ),
                array(
                    'label'   => false,
                    'name'    => 'fdsus_display_name_username_override',
                    'type'    => 'dropdown',
                    'note'    => '<span id="fdsus_display_name_username_override-label">' . esc_html__(
                        'For logged in users, override the Front-end Display Name with their WP username on their sign-ups.', 'sign-up-sheets'
                    ) . '</span>',
                    'options' => array(
                        ''             => esc_html__('Not Enabled', 'sign-up-sheets'),
                        'display_name' => esc_html__('Public Display Name', 'sign-up-sheets'),
                        'nickname'     => esc_html__('Nickname', 'sign-up-sheets'),
                        'user_login'   => esc_html__('Username', 'sign-up-sheets'),
                    ),
                    'aria-labelledby' => 'fdsus_display_name_username_override-label',
                    'order'   => 32,
                    'pro'     => true
                ),
                array(
                    'label' => esc_html__('Compact Sign-up Mode', 'sign-up-sheets'),
                    'name'  => 'dls_sus_compact_signups',
                    'type'  => 'dropdown',
                    'note'  => esc_html__('Show sign-up spots on one line with just # of open spots and a link to sign-up if open. Semi-Compact will also include the names of those who already signed up (assuming "Front-end Display Names" is not set to "anonymous"', 'sign-up-sheets'),
                    'options' => array(
                        'false' => esc_html__('Disabled', 'sign-up-sheets'),
                        'true'  => esc_html__('Enabled', 'sign-up-sheets'),
                        'semi'  => esc_html__('Semi-Compact', 'sign-up-sheets'),
                    ),
                    'order' => 40,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Enable task sign-up limit', 'sign-up-sheets'),
                    'name'  => 'dls_sus_task_signup_limit',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Prevent users from being able to sign-up for a task more than once.  This is checked by email address.', 'sign-up-sheets'),
                    'order' => 50,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Enable contiguous task sign-up limit', 'sign-up-sheets'),
                    'name'  => 'dls_sus_contiguous_task_signup_limit',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Prevent users from being able to sign-up for a task directly before or after a task for which they have already signed up.  This is checked by email address.', 'sign-up-sheets'),
                    'order' => 60,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Enable Task Checkboxes', 'sign-up-sheets'),
                    'name'  => 'dls_sus_enable_task_checkbox',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Allow check boxes on signup line items that allow user to sign up for multiple tasks.', 'sign-up-sheets'),
                    'order' => 70,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Enable Spot Lock', 'sign-up-sheets'),
                    'name'  => 'dls_sus_spot_lock',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Spot will be locked and held for current user for 3 minutes when they access the sign-up form page.  Spot Lock is available when signing up for a single task at a time.', 'sign-up-sheets'),
                    'order' => 80,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Hide self-removal from Sign-up Sheet', 'sign-up-sheets'),
                    'name'  => 'dls_sus_hide_removal',
                    'type'  => 'checkbox',
                    'note' => esc_html__('Hides the "Remove" link from the sign-up form if users were logged in when they signed up. This is always hidden if "Front-end Display Names" is set to "anonymous".', 'sign-up-sheets'),
                    'order' => 85,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Number of days before sheet/task date to allow users to edit their own sign-ups', 'sign-up-sheets'),
                    'name'  => 'fdsus_user_editable_signups',
                    'type'  => 'number',
                    'note'  => esc_html__('Leave blank to disable the user edit feature. Number entered will calculate based on the task date, if set, otherwise it will use the sheet date.  If no sheet and task date is set, editing will be allowed indefinitely.  Use negative numbers to allow editing after the date has passed', 'sign-up-sheets'),
                    'order' => 88,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Show Filled Spots in Admin Edit Sheet', 'sign-up-sheets'),
                    'name'  => 'dls_sus_show_filled_spots_admin_edit',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Show names and count of filled spots in the admin Edit Sheet screen.', 'sign-up-sheets'),
                    'order' => 90,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Allow Auto-Clearing Sign-ups Per Sheet', 'sign-up-sheets'),
                    'name'  => 'fdsus_allow_autoclear_signups',
                    'type'  => 'checkbox',
                    'note'  =>
                        /* translators: %s is replaced with the timestamp of the next cron scheduled */
                        sprintf(esc_html__('Next scheduled check: %s', 'sign-up-sheets'),
                            Settings::getNextScheduledCronCheck('fdsus_autoclear'))
                        . '<ul>'
                            . '<li>'
                                . esc_html__('Enabling this activates the optional setting on each Sheet under "Additional Settings" which provides the ability auto-clear all sign-ups for that sheet on a schedule.', 'sign-up-sheets')
                            . '</li><li>'
                                . esc_html__('Your site will check if there are sheets that need to be cleared that need to be sent using the', 'sign-up-sheets')
                                . ' <a href="https://developer.wordpress.org/plugins/cron/">' . esc_html__('WordPress Cron', 'sign-up-sheets') . '</a>'
                            . '</li><li>'
                                . esc_html__('If you just enabled/disabled this, you may need to refresh this page to see the updated "Next scheduled check"', 'sign-up-sheets')
                            . '</li>'
                        . '</ul>',
                    'order' => 100,
                    'pro'   => true
                ),
                array(
                    'label'   => esc_html__('Custom Task Fields', 'sign-up-sheets'),
                    'name'    => 'dls_sus_custom_task_fields',
                    'type'    => 'repeater',
                    'note'    => 'To add more fields, save this page and a new blank row will appear.<br />* Options are for checkbox, radio and dropdown fields.  Put multiple values on new lines.<br /><br /><strong>NOTE: Custom Task Fields are for display only on the frontend. To add custom fields that your users fill out, use the Custom Sign-up Fields in the "Sign-up Form" section below.</strong>', // TODO translate
                    'options' => array(
                        array('label' => 'Name', 'name' => 'name', 'type' => 'text'),
                        array('label' => 'Slug', 'name' => 'slug', 'type' => 'text'),
                        array('label' => 'Type', 'name' => 'type', 'type' => 'dropdown', 'options' => $fieldTypesTask),
                        array('label' => 'Options', 'name' => 'options', 'type' => 'textarea', 'note' => '<span aria-describedby="custom-task-fields-options-note">*</span>', 'aria-describedby' => 'custom-task-fields-options-note'),
                        array('label' => 'Sheets', 'name' => 'sheet_ids', 'type' => 'multiselect', 'options' => $sheetSelection),
                    ),
                    'order'   => 110,
                    'pro'     => true
                )
            ),
        );

        $options['form'] = array(
            'id'      => 'form',
            'title'   => esc_html__('Sign-up Form', 'sign-up-sheets'),
            'order'   => 20,
            'options' => array(
                array(
                    'label' => esc_html__('Show "Remember Me" checkbox', 'sign-up-sheets'),
                    'name'  => 'dls_sus_remember',
                    'type'  => 'checkbox',
                    'order' => 5,
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Set Phone as Optional', 'sign-up-sheets'),
                    'name'  => 'dls_sus_optional_phone',
                    'type'  => 'checkbox',
                    'order' => 10
                ),
                array(
                    'label' => esc_html__('Set Address as Optional', 'sign-up-sheets'),
                    'name' => 'dls_sus_optional_address',
                    'type' => 'checkbox',
                    'order' => 20
                ),
                array(
                    'label' => esc_html__('Set Email as Optional', 'sign-up-sheets'),
                    'name' => 'fdsus_optional_email',
                    'type' => 'checkbox',
                    'order' => 22
                ),
                array(
                    'label' => esc_html__('Hide Phone Field', 'sign-up-sheets'),
                    'name'  => 'dls_sus_hide_phone',
                    'type'  => 'checkbox',
                    'order' => 30
                ),
                array(
                    'label' => esc_html__('Hide Address Fields', 'sign-up-sheets'),
                    'name'  => 'dls_sus_hide_address',
                    'type'  => 'checkbox',
                    'order' => 40
                ),
                array(
                    'label' => esc_html__('Hide Email Field', 'sign-up-sheets'),
                    'name'  => 'dls_sus_hide_email',
                    'type'  => 'checkbox',
                    'order' => 42
                ),
                array(
                    'label' => esc_html__('Disable User Auto-populate', 'sign-up-sheets'),
                    'name'  => 'dls_sus_disable_user_autopopulate',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('By default, for users that are logged in, their name and email auto-populates on sign-up form when available. This option disables that behavior.', 'sign-up-sheets'),
                    'order' => 45
                ),
                array(
                    'label' => esc_html__('Disable Mail Check Validation', 'sign-up-sheets'),
                    'name'  => 'dls_sus_deactivate_email_validation',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Validation includes a JS check for standard email formatting, possible incorrect domains with suggestions as well as an MX record check on the domain to confirm it is setup to receive emails', 'sign-up-sheets'),
                    'order' => 50
                ),
                array(
                    'label'  => esc_html__('Sign-up link auto-scroll to sheet (hash in sign-up link)', 'sign-up-sheets'),
                    'name'   => 'fdsus_signup_link_hash',
                    'type'   => 'radio',
                    'options' => array(
                        'off' => esc_html__('Off', 'sign-up-sheets') . ' ' . esc_html__('(Default)', 'sign-up-sheets'),
                        'on' => esc_html__('On', 'sign-up-sheets'),
                    ),
                    'note'   => esc_html__('The hash on the sign-up link is useful especially on longer pages where sheets are embedded further down the page or where the sheet description is longer.  When the feature is enabled and the user clicks the sign-up link, it includes a `#` hash to and ID pointing to that same location where the sign-up form will appear on the next page.', 'sign-up-sheets'),
                    'order'  => 55
                ),
                array(
                    'label' => esc_html__('Sign-up Success Message Receipt', 'sign-up-sheets'),
                    'name'  => 'dls_sus_signup_receipt',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Displays a receipt below the sign-up success message which includes a copy of all the task details and all fields they entered in the sign-up form. Default: `unchecked`', 'sign-up-sheets'),
                    'order' => 60,
                    'pro'   => true
                ),
                array(
                    'label'   => esc_html__('Custom Sign-up Fields', 'sign-up-sheets'),
                    'name'    => 'dls_sus_custom_fields',
                    'type'    => 'repeater',
                    'note'    => 'To add more fields, save this page and a new blank row will appear.<br /><span id="custom-signup-fields-options-note">* Options are for checkbox, radio and dropdown fields.  Put multiple values on new lines.</a>', // TODO translate
                    'options' => array(
                        array('label' => 'Name', 'name' => 'name', 'type' => 'text'),
                        array('label' => 'Slug', 'name' => 'slug', 'type' => 'text'),
                        array('label' => 'Type', 'name' => 'type', 'type' => 'dropdown', 'options' => $fieldTypesSignup),
                        array('label' => 'Options', 'name' => 'options', 'type' => 'textarea', 'note' => '<span aria-describedby="custom-signup-fields-options-note">*</span>', 'aria-describedby' => 'custom-signup-fields-options-note'),
                        array('label' => 'Sheets', 'name' => 'sheet_ids', 'type' => 'multiselect', 'options' => $sheetSelection),
                        array('label' => 'Required', 'name' => 'required', 'type' => 'checkbox'),
                        array('label' => 'Results on Frontend', 'name' => 'frontend_results', 'type' => 'checkbox'),
                    ),
                    'order'   => 70,
                    'pro'     => true
                ),
            )
        );

        $options['spam'] = array(
            'id'      => 'spam',
            'title'   => esc_html__('Captcha and Spam Prevention', 'sign-up-sheets'),
            'order'   => 30,
            'options' => array(
                array(
                    'label' => esc_html__('Disable honeypot', 'sign-up-sheets'),
                    'name'  => 'dls_sus_disable_honeypot',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('A honeypot is a less-invasive technique to reduce SPAM submission using a hidden field on the sign-up form.  It can be used in place of or alongside the captcha.', 'sign-up-sheets')
                ),
                array(
                    'label' => esc_html__('Disable all Captcha', 'sign-up-sheets'),
                    'name'  => 'dls_sus_disable_captcha',
                    'type' => 'checkbox', esc_html__('Will disable all captcha even if you have reCAPTCHA enabled below', 'sign-up-sheets')),
                array('Use reCAPTCHA', 'dls_sus_recaptcha', 'checkbox', esc_html__('Will replace the default simple captcha validation', 'sign-up-sheets')),
                array('reCAPTCHA Public Key', 'dls_sus_recaptcha_public_key', 'text', esc_html__('From your account at https://www.google.com/recaptcha/', 'sign-up-sheets')),
                array('reCAPTCHA Private Key', 'dls_sus_recaptcha_private_key', 'text', esc_html__('From your account at https://www.google.com/recaptcha/', 'sign-up-sheets')),
                array('reCAPTCHA Version', 'dls_sus_recaptcha_version', 'dropdown', '', array('v3' => esc_html__('v3', 'sign-up-sheets'), 'v2-checkbox' => esc_html__('v2 Checkbox', 'sign-up-sheets'), 'v2-invisible' => esc_html__('v2 Invisible', 'sign-up-sheets'))),
            )
        );

        $options['confirmation_email'] = array(
            'id'      => 'confirmation_email',
            'title'   => esc_html__('Confirmation E-mail', 'sign-up-sheets'),
            'order'   => 40,
            'options' => array(
                array(
                    'label' => esc_html__('Enable', 'sign-up-sheets'),
                    'name'  => 'fdsus_enable_confirmation_email',
                    'type'  => 'checkbox',
                    'order' => 10,
                    'pro'   => true,
                    'value' => Settings::isConfirmationEmailEnabled() ? 'true' : ''
                ),
                array(
                    'label' => esc_html__('Subject', 'sign-up-sheets'),
                    'name'  => 'dls_sus_email_subject',
                    'type'  => 'text',
                    /* translators: %s is replaced with the default subject */
                    'note'  => esc_html(sprintf(__('If blank, defaults to... "%s"', 'sign-up-sheets'), Settings::$defaultMailSubjects['signup'])),
                    'order' => 20
                ),
                array(
                    'label' => esc_html__('From E-mail Address', 'sign-up-sheets'),
                    'name'  => 'dls_sus_email_from',
                    'type'  => 'text',
                    'note'  => esc_html__('If blank, defaults to WordPress email on file under Settings > General', 'sign-up-sheets'),
                    'order' => 30
                ),
                array(
                    'label'    => esc_html__('BCC', 'sign-up-sheets'),
                    'name'     => 'dls_sus_email_bcc',
                    'type'     => 'text',
                    'note'     => esc_html__('Comma separate for multiple email addresses', 'sign-up-sheets'),
                    'order'    => 40,
                    'pro'      => true
                ),
                array(
                    'label'    => esc_html__('Message', 'sign-up-sheets'),
                    'name'     => 'dls_sus_email_message',
                    'type'     => 'textarea',
                    'note'     => $defaultEmailVariables . sprintf(
                            '<br>
                            <code>{removal_link}</code> - %s',
                            esc_html__('Link to remove sign-up', 'sign-up-sheets')
                        ),
                    'order'    => 50,
                    'pro'      => true
                )
            )
        );

        $options['removal_confirmation_email'] = array(
            'id'      => 'removal_confirmation_email',
            'title'   => esc_html__('Removal Confirmation E-mail', 'sign-up-sheets') . (!Id::isPro() ? ' <span class="dls-sus-pro" title="Pro Feature">Pro</span>' : ''),
            'order'   => 50,
            'options' => array(
                array(
                    'label' => esc_html__('Enable', 'sign-up-sheets'),
                    'name'  => 'fdsus_enable_removal_confirmation_email',
                    'type'  => 'checkbox',
                    'order' => 10,
                    'pro'   => true,
                    'value' => Settings::isRemovalConfirmationEmailEnabled() ? 'true' : ''
                ),
                array(
                    'label' => esc_html__('Message', 'sign-up-sheets'),
                    'name'  => 'dls_sus_removed_email_message',
                    'type'  => 'textarea',
                    'note'  => $defaultEmailVariables,
                    'order' => 20,
                    'pro' => true
                ),
            )
        );

        $options['reminder_email'] = array(
            'id'      => 'reminder_email',
            'title'   => esc_html__('Reminder E-mail', 'sign-up-sheets') . (!Id::isPro() ? ' <span class="dls-sus-pro" title="' . esc_html__('Pro Feature', 'sign-up-sheets') . '">' . esc_html__('Pro', 'sign-up-sheets') . '</span>' : ''),
            'order'   => 60,
            'options' => array(
                array(
                    'label' => esc_html__('Enable Reminders', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email',
                    'type'  => 'checkbox',
                    'note'  =>
                        /* translators: %s is replaced with the timestamp of the next cron scheduled */
                        sprintf(esc_html__('Next scheduled check: %s', 'sign-up-sheets'),
                            Settings::getNextScheduledCronCheck('dls_sus_send_reminders'))
                        . '<ul>'
                            . '<li>'
                                . esc_html__('Your site will check hourly to see if there are reminders that need to be sent using the', 'sign-up-sheets')
                                . ' <a href="https://developer.wordpress.org/plugins/cron/">' . esc_html__('WordPress Cron', 'sign-up-sheets') . '</a>'
                            . '</li><li>'
                                . esc_html__('If you just enabled/disabled this, you may need to refresh this page to see the updated "Next scheduled check"', 'sign-up-sheets')
                            . '</li>'
                        . '</ul>',
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('Enable Reminders on all Auto-Clearing Sheets', 'sign-up-sheets'),
                    'name'  => 'fdsus_reminder_email_on_auto_clear',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('If Auto-Clearing is enabled, reminder emails will be sent based on the dates they will be auto-cleared.  For example, if you have a sheet that auto-clears every Friday and your reminders are set to be sent 1 day prior, those sheets will have a reminder email sent every Thursday.  Without this enabled, reminders will only be sent for sheets with a sheet or task date set.', 'sign-up-sheets'),
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('Reminder Schedule', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email_days_before',
                    'type'  => 'text',
                    'note'  => esc_html__('Number of days before the date on the sign-up sheet that the email should be sent.  Use whole numbers, for example, to remind one day before use...', 'sign-up-sheets') . ' <code>1</code> ' . esc_html__('This field is required.', 'sign-up-sheets'),
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('Subject', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email_subject',
                    'type'  => 'text',
                    /* translators: %s is replaced with the default subject */
                    'note' => esc_html(sprintf(__('If blank, defaults to... "%s"', 'sign-up-sheets'), Settings::$defaultMailSubjects['reminder'])),
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('From E-mail Address', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email_from',
                    'type'  => 'text',
                    'note'  => esc_html__('If blank, defaults to WordPress email on file under Settings > General', 'sign-up-sheets'),
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('BCC', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email_bcc',
                    'type'  => 'text',
                    'note'  => esc_html__('Comma separate for multiple email addresses', 'sign-up-sheets'),
                    'pro' => true
                ),
                array(
                    'label' => esc_html__('Message', 'sign-up-sheets'),
                    'name'  => 'dls_sus_reminder_email_message',
                    'type'  => 'textarea',
                    'note'  => $defaultEmailVariables,
                    'pro' => true
                ),
            )
        );

        $options['status_email'] = array(
            'id'      => 'status_email',
            'title'   => esc_html__('Status E-mail', 'sign-up-sheets') . (!Id::isPro() ? ' <span class="dls-sus-pro" title="Pro Feature">Pro</span>' : ''),
            'order'   => 70,
            'options' => array(
                array(
                    'label' => esc_html__('Enable Status E-mail', 'sign-up-sheets'),
                    'name'  => 'dls_sus_status_email',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('Shows all signups for a sheet.  Sent when a user adds or removes a signup from the frontend.', 'sign-up-sheets'),
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Subject', 'sign-up-sheets'),
                    'name'  => 'dls_sus_status_email_subject',
                    'type'  => 'text',
                    /* translators: %s is replaced with the default subject */
                    'note'  => esc_html(sprintf(__('If blank, defaults to... "%s"', 'sign-up-sheets'), Settings::$defaultMailSubjects['status'])),
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('From E-mail Address', 'sign-up-sheets'),
                    'name'  => 'dls_sus_status_email_from',
                    'type'  => 'text',
                    'note'  => esc_html__('If blank, defaults to WordPress email on file under Settings > General', 'sign-up-sheets'),
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Send to main admin emails', 'sign-up-sheets'),
                    'name'  => 'dls_sus_status_to_admin',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('E-mail address specified under Settings > General', 'sign-up-sheets'),
                    'pro'   => true
                ),
                array(
                    'label' => esc_html__('Send to "Sheet BCC" recipients', 'sign-up-sheets'),
                    'name'  => 'dls_sus_status_to_sheet_bcc',
                    'type'  => 'checkbox',
                    'note'  => esc_html__('These addresses will be added as a recipient only for sheets on which they are assigned.', 'sign-up-sheets'),
                    'pro'   => true
                ),
            )
        );

        $options['advanced'] = array(
            'id'      => 'advanced',
            'title'   => esc_html__('Advanced', 'sign-up-sheets'),
            'order'   => 80,
            'options' => array(
                array(
                    'label' => esc_html__('Sheet URL Slug', 'sign-up-sheets'),
                    'name' => 'dls_sus_sheet_slug',
                    'type' => 'text',
                    'note' => 'Will be used in permalinks for your frontend archive page as well as single sheets pages. Default is <code>sheet</code>  Ex: https://example.com/<code>sheet</code>/my-signup-sheet/'),
                array(
                    'label' => esc_html__('User roles that can manage sheets', 'sign-up-sheets'),
                    'name' => 'dls_sus_roles',
                    'type' => 'checkboxes',
                    'note' => esc_html__('(Note: Administrators and Sign-up Sheet Managers can always manage sheets)', 'sign-up-sheets'),
                    'options' => $roles
                ),
                array(
                    'label' => esc_html__('Disable Sign-up Sheets Roles', 'sign-up-sheets'),
                    'name' => 'fdsus_disabled_roles',
                    'type' => 'checkboxes',
                    'note' => esc_html__('(Note: These roles are added by default, but can be removed if not needed.)', 'sign-up-sheets'),
                    'options' => $susSpecificRoles
                ),
                array(
                    'label' => esc_html__('Clear Cache for these Post IDs when a sign-up is added or removed', 'sign-up-sheets'),
                    'name' => 'fdsus_cache_clear_on_signup',
                    'type' => 'text',
                    'note' => 'If using a <a href="https://www.fetchdesigns.com/doc/caching/">supported caching plugin</a>, you can specify individual post IDs to flush after a sign-up occurs. This should be a comma-separated list such as <code>123,5000</code>.  ID entered can be for a post, page or a custom post type.'),
                array(
                    'label' => esc_html__('Re-run Data Migration', 'sign-up-sheets'),
                    'name' => 'dls_sus_rerun_migrate',
                    'type' => 'button',
                    'note' => '<span id="dlssus-rerun-migrate"></span>',
                    'options' => array('href' => wp_nonce_url(
                        add_query_arg('migrate', 'rerun-2.1', $this->data->getSettingsUrl()),
                        'fdsus-migrate-rerun',
                        '_fdsus-migrate-nonce'
                    ))
                ),
                array(
                    'label' => esc_html__('Display Detailed Errors', 'sign-up-sheets'),
                    'name' => 'dls_sus_detailed_errors',
                    'type' => 'checkbox',
                    'note' => esc_html__('(Not recommended for production sites)', 'sign-up-sheets')
                ),
                array(
                    'label' => esc_html__('Reset All Settings', 'sign-up-sheets'),
                    'name' => 'fdsus_reset',
                    'type' => 'button',
                    'note' => '<span id="fdsus-reset"></span><p>' . esc_html__('This will erase any custom configurations you have made on this page and reset them back to the defaults. This action cannot be undone.', 'sign-up-sheets') . '</p>',
                    'options' => array(
                        'href' => add_query_arg('fdsus-reset', 'all',
                            wp_nonce_url($this->data->getSettingsUrl(), 'fdsus-settings-reset', '_fdsus-nonce')),
                        'onclick' => sprintf('return confirm(`%s %s`)',
                            esc_html__('Are you sure?', 'sign-up-sheets'),
                            esc_html__('This will erase any custom configurations you have made on this page and reset them back to the defaults. This action cannot be undone.', 'sign-up-sheets')
                        ),
                    )
                ),
            )
        );

        $options['text_overrides'] = array(
            'id'      => 'text_overrides',
            'title'   => esc_html__('Text Overrides', 'sign-up-sheets'),
            'order'   => 90,
            'options' => array()
        );

        foreach (Settings::$text as $key => $text) {
            $options['text_overrides']['options'][] = array($text['label'], 'dls_sus_text_' . $key, 'text', 'Default: ' . $text['default']);;
        }

        /**
         * Filter admin settings page options
         *
         * @param array $options
         *
         * @return array
         * @since 2.2
         */
        $options = apply_filters('fdsus_settings_page_options', $options);

        // Sort
        usort($options, array(&$this, 'sortByOrder'));
        foreach ($options as $key => $option) {
            // Include Pro settings
            if (!empty($option['options']) && is_array($option['options'])) {
                foreach ($option['options'] as $subKey => $subOption) {
                    if (!empty($subOption['pro'])) {
                        if (!Id::isPro()) {
                            $options[$key]['options'][$subKey]['label'] = !empty($options[$key]['options'][$subKey]['label'])
                                ? '<span class="dls-sus-pro" title="Pro Feature">Pro</span> ' . $options[$key]['options'][$subKey]['label']
                                : $options[$key]['options'][$subKey]['label'];
                            $options[$key]['options'][$subKey]['original_name'] = $options[$key]['options'][$subKey]['name'];
                            $options[$key]['options'][$subKey]['name'] = 'pro_feature_' . (int)$key . '_' . (int)$subKey;
                        }
                        $options[$key]['options'][$subKey]['disabled'] = !Id::isPro();
                        if (!isset($options[$key]['options'][$subKey]['class'])) {
                            $options[$key]['options'][$subKey]['class'] = '';
                        }
                        $options[$key]['options'][$subKey]['class'] .= Id::isPro() ? '' : 'fdsus-pro-setting';
                    }
                    if (isset($subOption['type']) && $subOption['type'] == 'repeater' && !empty($subOption['options']) && is_array($subOption['options'])) {
                        foreach ($subOption['options'] as $subSubKey => $subSubOption) {
                            if (!empty($subOption['pro'])) {
                                if (!Id::isPro()) {
                                    $options[$key]['options'][$subKey]['options'][$subSubKey]['name'] = 'pro_feature_' . (int)$key . '_' . (int)$subKey;
                                }
                                $options[$key]['options'][$subKey]['options'][$subSubKey]['disabled'] = !Id::isPro();
                                if (!isset($options[$key]['options'][$subKey]['options'][$subSubKey]['class'])) {
                                    $options[$key]['options'][$subKey]['options'][$subSubKey]['class'] = '';
                                }
                                $options[$key]['options'][$subKey]['options'][$subSubKey]['class'] .= Id::isPro() ? '' : 'fdsus-pro-setting';
                            }
                        }
                    }
                }
            }
            usort($options[$key]['options'], array(&$this, 'sortByOrder'));
        }
        reset($options);

        return $options;
    }

    /**
     * Sort by order
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function sortByOrder($a, $b)
    {
        if (!isset($a['order'])) {
            $a['order'] = 0;
        }
        if (!isset($b['order'])) {
            $b['order'] = 0;
        }
        $result = 0;
        if ($a['order'] > $b['order']) {
            $result = 1;
        } else {
            if ($a['order'] < $b['order']) {
                $result = -1;
            }
        }
        return $result;
    }
}
