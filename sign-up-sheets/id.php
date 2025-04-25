<?php

namespace FDSUS;

use function is_plugin_active;

if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!class_exists('\\' . __NAMESPACE__ . '\Id')):

    class Id
    {
        const PREFIX = 'dlssus';
        const NAME = 'Sign-up Sheets - WordPress Plugin';
        const AUTHOR = 'Fetch Designs';
        const URL = 'https://www.fetchdesigns.com';
        const DEBUG = false; // log detailed debug info to logs
        const DEBUG_DISPLAY = false; // display detailed debug info on screen
        const IS_LOCAL = false;
        // TODO update basename constants to use private visibility to prevent direct usage, but requires php 7.1+ so only do in larger version update
        /** @var string Fallback free basename. Don't use directly, instead use IdPro::getPluginBasename($type) */
        const FREE_PLUGIN_BASENAME = 'sign-up-sheets/sign-up-sheets.php';
        /** @var string Fallback pro basename. Don't use directly, instead use IdPro::getPluginBasename($type) */
        const PRO_PLUGIN_BASENAME = 'sign-up-sheets-pro/sign-up-sheets.php';

        /**
         * @var int
         */
        public static $lastMemory = 0;

        /**
         * Get version from main PHP file `Version:` comment header
         *
         * @param 'pro'|'free'|'' $type 'pro', 'free', or '' for the current type
         * @param bool            $fallbackAllowed set to false if it forces uses the correct non-fallback version
         *
         * @return string version number
         */
        public static function version($type = '', $fallbackAllowed = true)
        {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php');
            }

            $pluginBasename = self::getPluginBasename($type, $fallbackAllowed);

            if (!file_exists(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $pluginBasename)) {
                return '';
            }

            $pluginData = get_plugin_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $pluginBasename, false, false);

            return $pluginData && $pluginData['Version'] ? $pluginData['Version'] : '';
        }

        /**
         * Log
         *
         * @param string      $msg
         * @param string|null $filename
         * @param string      $emailSubject Leave blank to prevent email from sending
         */
        public static function log($msg, $filename = null, $emailSubject = '')
        {
            if (!is_null($filename)) {
                $filename = '-' . $filename;
            }

            if (self::DEBUG) {
                $currentMemory = memory_get_usage();
                $msg .= ' [current memory: ' . $currentMemory . ' - diff: ' . ($currentMemory - self::$lastMemory) . ']';
                self::$lastMemory = $currentMemory;
            }

            if (!empty($emailSubject)) {
                $msg .= PHP_EOL .
                    "Site URL: " . site_url() . PHP_EOL .
                    "IP: " . self::getClientIP() . PHP_EOL .
                    "Browser Data: " . $_SERVER['HTTP_USER_AGENT'];
            }

            error_log(
                date("Y-m-d H:i:s") . " - $msg" . PHP_EOL,
                3,
                WP_CONTENT_DIR . DIRECTORY_SEPARATOR . self::PREFIX . $filename . '.log'
            );

            if (!empty($emailSubject)) {
                wp_mail(
                    self::_getAlertRecipient(),
                    get_bloginfo('name') . ' - ' . $emailSubject,
                    $msg
                );
            }
        }

        /**
         * Log while debug mode is enabled
         *
         * @param string $msg
         * @param string|null $filename
         * @param string $emailSubject Leave blank to prevent email from sending
         */
        public static function debug($msg, $filename = null, $emailSubject = '')
        {
            if (!self::DEBUG) {
                return;
            }

            self::log($msg, $filename, $emailSubject);
        }

        /**
         * Get email alert recipient or default to admin value
         *
         * @return string email address
         */
        private static function _getAlertRecipient()
        {
            $email = get_option(self::PREFIX . '_alert_recipient');
            if (empty($email)) {
                $email = get_option('admin_email');
            }
            return $email;
        }

        /**
         * Get client IP
         *
         * @return string
         */
        public static function getClientIP()
        {
            $server_vars = array(
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
            );
            foreach ($server_vars as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                            return $ip;
                        }
                    }
                }
            }
            return null;
        }

        /**
         * Get plugin path
         *
         * @return string
         */
        public static function getPluginPath()
        {
            return dirname(__FILE__) . DIRECTORY_SEPARATOR;
        }

        /**
         * Get plugin basename
         *
         * @param 'pro'|'free'|'' $type
         * @param bool            $fallbackAllowed
         *
         * @return string
         */
        public static function getPluginBasename($type = '', $fallbackAllowed = true)
        {
            if ($type === '') {
                $type = self::isPro() ? 'pro' : 'free';
            }

            if ($type === 'free' && !$fallbackAllowed) {
                return self::FREE_PLUGIN_BASENAME;
            }

            return ($type === 'pro')
                ? (defined('FDSUS_PRO_PLUGIN_BASENAME') ? FDSUS_PRO_PLUGIN_BASENAME : self::PRO_PLUGIN_BASENAME)
                : (defined('FDSUS_FREE_PLUGIN_BASENAME') ? FDSUS_FREE_PLUGIN_BASENAME : self::FREE_PLUGIN_BASENAME);
        }

        /**
         * Is the Pro version running?
         */
        public static function isPro()
        {
            return is_plugin_active(Id::getPluginBasename('pro'));
        }
    }

endif;
