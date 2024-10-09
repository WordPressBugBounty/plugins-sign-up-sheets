<?php
/**
 * Cache Class
 */

namespace FDSUS\Controller;

use FDSUS\Model\Data;
use FDSUS\Model\Settings;

class Cache
{

    public $data;

    public function __construct()
    {
        $this->data = new Data();

        add_action('fdsus_after_add_signup', array(&$this, 'clearSignupCache'), 9, 2);
        add_action('fdsus_after_update_signup', array(&$this, 'clearSignupCache'), 9, 2);
        add_action('fdsus_after_delete_signup', array(&$this, 'clearSignupCache'), 9, 2);
    }

    /**
     * Clears the cache for the sheet the signup is associated with
     *
     * @param int $signupId
     * @param int $taskId
     *
     * @return void
     */
    public function clearSignupCache($signupId, $taskId = 0)
    {
        $idsToClear = array();

        if ($signupId) {
            $idsToClear[] = $signupId;

            // Gather related IDs
            if (!$taskId) {
                $taskId = wp_get_post_parent_id($signupId);
            }
            if ($taskId) {
                $idsToClear[] = $taskId;

                $sheetId = wp_get_post_parent_id($taskId);
                if ($sheetId) {
                    $idsToClear[] = $sheetId;
                }
            }

            $this->processCacheClearByIds(array_merge($idsToClear, Settings::getCacheClearOnSignupIds()));

            // Breeze - Note: Breeze doesn't allow per-ID cache clearing
            do_action('breeze_clear_all_cache');

            // W3 Total Cache - All DB Cache
            if (function_exists('w3tc_dbcache_flush')) {
                w3tc_dbcache_flush();
            }
        }
    }

    protected function processCacheClearByIds($ids)
    {
        foreach ($ids as $id) {

            // W3 Total Cache
            if (function_exists('w3tc_flush_post')) {
                w3tc_flush_post($id);
            }

            // WP Super Cache
            if (function_exists('wpsc_delete_post_cache')) {
                wpsc_delete_post_cache($id);
            }

            // WP-Optimize
            if (class_exists('WPO_Page_Cache')) {
                \WPO_Page_Cache::delete_single_post_cache($id);
            }

            // LiteSpeed Cache
            do_action('litespeed_purge_post', $id);

            // WP Fastest Cache
            if (function_exists('wpfc_clear_post_cache_by_id')) {
                wpfc_clear_post_cache_by_id($id);
            }
        }
    }

}
