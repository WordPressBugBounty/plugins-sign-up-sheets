<?php
/**
 * Utility functions for Sign-up Sheets
 */

namespace FDSUS;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Utils
{
    /**
     * Safely unserialize WordPress data with no allowed classes
     *
     * @param string $data Data that might be unserialized
     * @return mixed Unserialized data can be any type
     */
    public static function safeMaybeUnserialize($data)
    {
        if (is_serialized($data)) {
            return @unserialize($data, ['allowed_classes' => false]);
        }
        return $data;
    }
}
