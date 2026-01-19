<?php
/**
 * Marketing secrets helper
 * - Reads secrets from option or environment variable
 * - Avoids echoing raw values in admin UI
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_get_marketing_secret')) {
    function fasp_get_marketing_secret($key) {
        $o = get_option('fasp_marketing', []);
        $map = array(
            'gads_api_key' => 'GADS_API_KEY',
            'tiktok_api_key' => 'TIKTOK_API_KEY',
            'meta_access_token' => 'META_ACCESS_TOKEN',
            'meta_pixel_id' => 'META_PIXEL_ID',
        );
        // option key preferred
        if (!empty($o[$key])) return $o[$key];
        // env fallback
        if (!empty($map[$key]) && !empty($_ENV[$map[$key]])) return $_ENV[$map[$key]];
        return '';
    }
}

if (!function_exists('fasp_is_marketing_secret_configured')) {
    function fasp_is_marketing_secret_configured($key) {
        return !empty(fasp_get_marketing_secret($key));
    }
}