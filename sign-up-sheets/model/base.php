<?php

namespace FDSUS\Model;

use FDSUS\Id;
use FDSUS\Model\Sheet as SheetModel;
use FDSUS\Model\Task as TaskModel;
use FDSUS\Model\Signup as SignupModel;
use wpdb;
use WP_Post;

class Base
{
    /** @var WPDB $wpdb */
    public $wpdb;

    /** @var bool  */
    public $detailed_errors = false;

    /** @var array  */
    public $tables = array();

    /** @var WP_Post[] */
    public $posts = array();

    /**
     * Constructor
     *
     * @param int|WP_Post $signup id or post object
     */
    public function __construct($signup = 0)
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        if (Id::DEBUG_DISPLAY || get_option('dls_sus_detailed_errors') === 'true') {
            $this->detailed_errors = true;
        }

        // Set table names
        $this->tables = array(
            'sheet' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_sheets',
                'allowed_fields' => array(
                    'title' => false,
                    'details' => false,
                    'date' => false,
                    'trash' => false,
                ),
            ),
            'task' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_tasks',
                'allowed_fields' => array(
                    'sheet_id' => false,
                    'title' => false,
                    'qty' => false,
                    'position' => false,
                    'date' => false,
                    'is_header' => false
                ),
            ),
            'signup' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_signups',
                'allowed_fields' => array(
                    'task_id' => false,
                    'firstname' => false,
                    'lastname' => false,
                    'email' => false,
                    'phone' => false,
                    'address' => false,
                    'city' => false,
                    'state' => false,
                    'zip' => false,
                    'removal_token' => false,
                ),
            ),
            'category' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_categories',
                'allowed_fields' => array(
                    'title' => false,
                ),
            ),
            'sheet_category' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_sheets_categories',
                'allowed_fields' => array(
                    'sheet_id' => false,
                    'category_id' => false,
                ),
            ),
            'field' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_fields',
                'allowed_fields' => array(
                    'entity_type' => false, // Ex: signup, sheet, task
                    'entity_id' => false,
                    'slug' => false,
                    'value' => false,
                ),
            ),
            'sheet_field' => array( // deprecated
                'name' => $this->wpdb->prefix . 'dls_sus_sheet_fields',
                'allowed_fields' => array(
                    'sheet_id' => false,
                    'slug' => false,
                    'value' => false,
                ),
            ),
            'termmeta' => array(
                'name' => $this->wpdb->prefix . Id::PREFIX . '_termmeta',
            ),
            'locks' => array(
                'name' => $this->wpdb->prefix . Id::PREFIX . '_locks',
            ),
        );
    }

    /**
     * Remove prefix from keys of an array and return records that were cleaned
     *
     * @param array       $input
     * @param bool|string $prefix Optional prefix to remove from keys
     *
     * @return array|bool records that were cleaned or false on error
     */
    protected function cleanArray($input = array(), $prefix = false)
    {
        if (!is_array($input)) {
            return false;
        }

        $cleanedFields = array();
        foreach ($input as $k => $v) {
            $cleanedKey = $k;

            // Disallow sending the user ID if not an admin with proper permissions
            $signupCaps = new Capabilities(SignupModel::POST_TYPE);
            if ($k === 'signup_user_id'
                && (
                    !is_admin()
                    || !current_user_can($signupCaps->get('edit_post'))
                )
            ) {
                continue;
            }

            if ($prefix === false || (substr($k, 0, strlen($prefix)) == $prefix)) {
                if (!empty($prefix)) {
                    $pos = strpos($k, $prefix);
                    if ($pos !== false) {
                        $cleanedKey = substr_replace($k, '', $pos, strlen($prefix));
                    }
                }

                $cleanedFields[$cleanedKey] = ($prefix == 'signup_' && !is_array($v))
                    ? sanitize_text_field($v) : $v;
            }
        }

        return $cleanedFields;
    }

    /**
     * Remove slashes from strings, arrays and objects
     *
     * @param mixed $input data
     *
     * @return   mixed   cleaned input data
     */
    public function stripslashes_full($input)
    {
        if (is_array($input)) {
            $input = array_map(
                array('FDSUS\Model\Base', 'stripslashes_full'),
                $input
            );
        } elseif (is_object($input)) {
            $vars = get_object_vars($input);
            foreach ($vars as $k => $v) {
                $input->{$k} = $this->stripslashes_full($v);
            }
        } else {
            $input = stripslashes($input);
        }

        return $input;
    }

    /**
     * Get task dates min/max and task count by sheet id
     *
     * @param int $sheetId
     * @return object|false
     *
     * @todo move into task model
     */
    public function getExtraTaskDataBySheet($sheetId)
    {
        $sql = $this->wpdb->prepare(
            "
            SELECT MIN(dates.meta_value) AS minDate,
                CASE WHEN MAX(CASE WHEN dates.meta_value IS NULL THEN 1 ELSE 0 END) = 0
                    THEN MAX(dates.meta_value)
                END AS maxDate,
                COUNT(dates.meta_id) AS count
            FROM {$this->wpdb->posts} tasks
            LEFT JOIN {$this->wpdb->postmeta} dates ON tasks.ID = dates.post_id AND dates.meta_key = 'dlssus_date'
            INNER JOIN {$this->wpdb->postmeta} header ON header.post_id = tasks.ID AND header.meta_key = 'dlssus_task_row_type' AND header.meta_value <> 'header'
            WHERE post_type = %s AND post_parent = %d;
            ",
            TaskModel::POST_TYPE,
            $sheetId
        );
        $dates = $this->wpdb->get_row($sql);
        if (empty($dates) || !is_object($dates)) return false;
        return $dates;
    }

    /**
     * Get current URL
     *
     * @param bool $htmlspecialchars escape html chars using htmlspecialchars()
     *
     * @return string url
     */
    public function getCurrentUrl($htmlspecialchars = false)
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $url = $_SERVER["REQUEST_URI"];

        return ($htmlspecialchars) ? htmlspecialchars($url, ENT_QUOTES) : $url;
    }

    /**
     * Convert options listed in string format into an array
     * Example...
     *   chicago:Chicago
     *   new_york:New York
     * Converts to...
     *   array (
     *       'chicago' => 'Chicago',
     *       'new_york' => 'New York'
     *   );
     *
     * @param string $string options as string
     *
     * @return array options as array
     */
    public function optionsStringToArray($string)
    {
        $options = array();
        if (!empty($string)) {
            $exploded_string = explode("\r\n", $string);
            foreach ($exploded_string as $option) {
                $o = explode(' : ', $option, 2);
                $options[$o[0]] = (isset($o[1])) ? $o[1] : $o[0];
            }
        }

        return $options;
    }

    /**
     * Get admin settings URL
     *
     * @return string
     */
    public function getSettingsUrl()
    {
        return admin_url(
            add_query_arg(
                array(
                    'post_type' => SheetModel::POST_TYPE,
                    'page'      => 'fdsus-settings',
                ), 'edit.php'
            )
        );
    }

    /**
     * Convert multidimensional object to an associative array
     *
     * @param $object
     *
     * @return array
     */
    public function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }

        return array_map(array(&$this, 'objectToArray'), (array)$object);
    }

    /**
     * Get days of week array
     *
     * @return string[]
     */
    public function getDaysOfWeekArray()
    {
        return array(
            '0' => __('Sunday', 'sign-up-sheets'),
            '1' => __('Monday', 'sign-up-sheets'),
            '2' => __('Tuesday', 'sign-up-sheets'),
            '3' => __('Wednesday', 'sign-up-sheets'),
            '4' => __('Thursday', 'sign-up-sheets'),
            '5' => __('Friday', 'sign-up-sheets'),
            '6' => __('Saturday', 'sign-up-sheets'),
        );
    }

    /**
     * Get the day of the week textual representation from the numeric
     *
     * @param int|string $dayOfWeekNum
     *
     * @return string
     */
    public function getDayOfWeekTextFromNumber($dayOfWeekNum)
    {
        $daysOfWeek = $this->getDaysOfWeekArray();

        return $daysOfWeek[$dayOfWeekNum];
    }
}
