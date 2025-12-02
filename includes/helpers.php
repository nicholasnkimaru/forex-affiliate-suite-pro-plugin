<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_save_platforms')) {
    function fasp_save_platforms($p) {
        update_option('fasp_platforms', is_array($p) ? $p : array());
    }
}

if (!function_exists('fasp_normalize_platforms')) {
    function fasp_normalize_platforms($p) {
        if (!is_array($p)) { $p = array(); }
        $norm = array();
        foreach ($p as $k => $row) {
            if (!is_array($row)) { continue; }
            $slug = '';
            if (!empty($row['slug'])) { $slug = sanitize_title($row['slug']); }
            elseif (!empty($row['name'])) { $slug = sanitize_title($row['name']); }
            else { $slug = sanitize_title($k); }
            if ($slug === '') { continue; }

            // Defaults
            $def = array(
                'slug' => $slug,
                'name' => '',
                'affiliate_url' => '',
                'signup_url' => '',
                'app_id' => '',
                'client_secret' => '',
                'method' => 'none',
                'logo_url' => '',
                'oauth_redirect' => '',
                'oauth_scopes' => '',
                'regions' => '',
                'webhook_url' => '',
                'webhook_auth' => '',
                'kyc_required' => '0',
                'primary' => '0',
                'enabled' => '1',
                'show_in_dashboard' => '1'
            );
            $row = wp_parse_args($row, $def);

            // Normalize boolean-ish flags
            $row['primary'] = (!empty($row['primary']) ? '1' : '0');
            $row['enabled'] = (!isset($row['enabled']) || $row['enabled'] === '1' || $row['enabled'] === 1) ? '1' : '0';
            $row['show_in_dashboard'] = (!isset($row['show_in_dashboard']) || $row['show_in_dashboard'] === '1' || $row['show_in_dashboard'] === 1) ? '1' : '0';
            $row['kyc_required'] = (!empty($row['kyc_required']) ? '1' : '0');
            $row['method'] = isset($row['method']) ? sanitize_text_field($row['method']) : 'none';

            $norm[$slug] = $row;
        }

        // Ensure exactly one primary if any rows exist
        $primaries = array();
        foreach ($norm as $k => $v) {
            if (isset($v['primary']) && $v['primary'] === '1') { $primaries[] = $k; }
        }
        if (count($primaries) > 1) {
            $first = $primaries[0];
            foreach ($norm as $k => $v) {
                $norm[$k]['primary'] = ($k === $first) ? '1' : '0';
            }
        } elseif (count($primaries) === 0 && !empty($norm)) {
            $keys = array_keys($norm);
            $first = $keys[0];
            $norm[$first]['primary'] = '1';
        }

        return $norm;
    }
}

if (!function_exists('fasp_get_platforms')) {
    function fasp_get_platforms() {
        $p = get_option('fasp_platforms', array());
        if (!is_array($p)) { $p = array(); }
        if (empty($p)) {
            $p['deriv'] = array(
                'slug' => 'deriv',
                'name' => 'Deriv',
                'affiliate_url' => '',
                'signup_url' => '',
                'app_id' => '',
                'client_secret' => '',
                'method' => 'oauth',
                'logo_url' => '',
                'oauth_redirect' => site_url('/wp-json/fasp/v1/deriv/callback'),
                'oauth_scopes' => '',
                'regions' => '',
                'webhook_url' => '',
                'webhook_auth' => '',
                'kyc_required' => '0',
                'primary' => '1',
                'enabled' => '1',
                'show_in_dashboard' => '1'
            );
        }
        if (function_exists('fasp_normalize_platforms')) {
            $p = fasp_normalize_platforms($p);
            update_option('fasp_platforms', $p);
        }
        return $p;
    }
}

// Quiet the FASP-specific doing_it_wrong logger if present (prevents log spam from other plugins)
add_action('plugins_loaded', function () {
    if (has_action('doing_it_wrong_run', 'fasp_diag_doing_it_wrong')) {
        remove_action('doing_it_wrong_run', 'fasp_diag_doing_it_wrong', 10);
    }
}, 20);
