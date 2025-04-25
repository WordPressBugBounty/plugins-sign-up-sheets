<?php
/**
 * Captcha Controller
 */

namespace FDSUS\Controller;

use FDSUS\Id;
use FDSUS\Model\Settings;
use FDSUS\Lib\ReCaptcha\ReCaptcha;
use FDSUS\Model\Sheet as SheetModel;
use WP_Error;

class Captcha extends Base
{
    /**
     * Construct
     */
    public function __construct()
    {
        if (!is_admin()) {
            add_action('fdsus_enqueue_scripts_styles_on_signup', array(&$this, 'enqueue'), 10, 0);
            add_filter('fdsus_error_before_add_signup', array(&$this, 'signupValidation'), 10, 3);
            add_filter('fdsus_sign_up_form_errors_required_fields', array(&$this, 'updateRequiredFields'), 10, 3);
        }

        parent::__construct();
    }

    /**
     * Enqueue
     *
     * @return void
     */
    public function enqueue()
    {
        wp_register_script(
            'fdsus-recaptcha',
            'https://www.google.com/recaptcha/api.js',
            array(),
            Id::version()
        );

        if (Settings::isRecaptchaEnabled()) {
            wp_enqueue_script('fdsus-recaptcha');
        }
    }

    /**
     * Adjust required fields
     *
     * @param array      $missingFieldNames
     * @param SheetModel $sheet
     * @param array      $fields
     *
     * @return array
     */
    public function updateRequiredFields($missingFieldNames, $sheet, $fields)
    {
        if (!is_admin()
            && !Settings::isAllCaptchaDisabled()
            && !Settings::isRecaptchaEnabled()
            && empty($fields['spam_check'])
        ) {
            $missingFieldNames['simple_captcha'] = esc_html__('Math Question', 'sign-up-sheets');
        }

        return $missingFieldNames;
    }

    /**
     * Validation on signup
     *
     * @param string|WP_Error $errorMsg
     * @param int             $taskId
     * @param int             $taskIndex
     *
     * @return string|WP_Error
     */
    public function signupValidation($errorMsg, $taskId, int $taskIndex)
    {
        if ($taskIndex !== 0) {
            // Only run the first time (if looping through multiple tasks)
            return $errorMsg;
        }

        if (!Settings::isAllCaptchaDisabled() && Settings::isRecaptchaEnabled()
            && empty($_POST['spam_check'])
            && !isset($_POST['double_signup'])
        ) {
            $privateKey = get_option('dls_sus_recaptcha_private_key', '');
            if (empty($privateKey)) {
                return new WP_Error(
                    'fdsus-captcha-private-key-missing',
                    __('Please check that reCAPTCHA is configured correctly.', 'sign-up-sheets')
                );
            }

            $recaptcha = new ReCaptcha($privateKey);
            $resp = $recaptcha->setExpectedHostname($_SERVER['HTTP_HOST'])
                ->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
            if (!$resp->isSuccess()) {
                return new WP_Error(
                    'fdsus-captcha-error',
                    __('Please check that the reCAPTCHA field is valid.', 'sign-up-sheets')
                );
            }
        } elseif (!Settings::isRecaptchaEnabled()
            && (empty($_POST['spam_check']) || (!empty($_POST['spam_check']) && trim($_POST['spam_check']) != '8'))
            && !Settings::isAllCaptchaDisabled()
        ) {
            return new WP_Error(
                'fdsus-captcha-error', sprintf(
                /* translators: %s is replaced with the users response to the simple captcha */
                    esc_html__('Oh dear, 7 + 1 does not equal %s. Please try again.', 'sign-up-sheets'),
                    esc_attr($_POST['spam_check'])
                )
            );
        }

        return $errorMsg;
    }
}
