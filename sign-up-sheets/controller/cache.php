<?php
/**
 * Cache Class
 */

namespace FDSUS\Controller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

            // Additionally, purge server/CDN caches by URL only if a URL-only provider is present
            $urls = array();
            if ($this->hasUrlPurgeProviders()) {
                $urls = $this->getUrlsForIds($idsToClear);
                $this->processCachePurgeByUrls($urls);
            }

            /**
             * Allow sites to hook custom cache purges.
             *
             * @param int[]    $idsToClear
             * @param string[] $urls
             */
            do_action('fdsus_after_cache_purge', $idsToClear, $urls);
        }
    }

    /**
     * Clear caches by post ID for common hosts/plugins that support it.
     *
     * @param int[] $ids
     * @return void
     */
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

            // WP Rocket
            if (function_exists('rocket_clean_post')) {
                rocket_clean_post($id);
            }

            // WP Engine (per-ID)
            if (function_exists('wpe_clear_post_cache')) {
                wpe_clear_post_cache($id);
            }

            // Core object cache for the post
            clean_post_cache($id);
        }
    }

    /**
     * Build permalinks for IDs that should have their page cache purged.
     *
     * @param int[] $ids
     * @return array
     */
    protected function getUrlsForIds($ids)
    {
        $urls = array();

        foreach (array_unique(array_filter($ids)) as $id) {
            $url = get_permalink($id);
            if ($url) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Purge server/CDN caches by URL for common hosts/plugins that lack per‑ID APIs.
     *
     * @param string[] $urls
     * @return void
     */
    protected function processCachePurgeByUrls(array $urls): void
    {
        if (empty($urls)) {
            return;
        }

        // GoDaddy Managed WordPress MU System Plugin (WPaaS)
        if (class_exists('\\WPaaS\\Cache')) {
            foreach ($urls as $url) {
                if (method_exists('\\WPaaS\\Cache', 'purge_url')) {
                    \WPaaS\Cache::purge_url($url);
                }
            }
            if (method_exists('\\WPaaS\\Cache', 'purge_home')) {
                \WPaaS\Cache::purge_home();
            }
        }

        // SiteGround Optimizer
        if (has_action('sg_cachepress_purge_urls')) {
            do_action('sg_cachepress_purge_urls', $urls);
        }

        // Nginx Helper
        if (has_action('rt_nginx_helper_purge_urls')) {
            do_action('rt_nginx_helper_purge_urls', $urls);
        }

        // Kinsta
        if (has_action('kinsta-cache/purge')) {
            do_action('kinsta-cache/purge', $urls);
        }

        // Cloudflare plugin
        if (has_action('cloudflare_purge_by_url')) {
            do_action('cloudflare_purge_by_url', $urls);
        }
    }

    /**
     * Detect whether any URL-only purge providers are available.
     *
     * @return bool
     */
    protected function hasUrlPurgeProviders(): bool
    {
        if (
            // GoDaddy Managed WordPress MU System Plugin (WPaaS)
            (class_exists('\\WPaaS\\Cache') && method_exists('\\WPaaS\\Cache', 'purge_url'))

            // SiteGround Optimizer
            || has_action('sg_cachepress_purge_urls')

            // Nginx Helper
            || has_action('rt_nginx_helper_purge_urls')

            // Kinsta
            || has_action('kinsta-cache/purge')

            // Cloudflare plugin
            || has_action('cloudflare_purge_by_url')
        ) {
            return true;
        }

        return false;
    }

}
