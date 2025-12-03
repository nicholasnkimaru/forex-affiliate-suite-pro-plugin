<?php
if (!defined('ABSPATH')) exit;

/**
 * Get user dashboard data for the current user
 *
 * @param int|null $user_id Optional user ID (defaults to current user)
 * @return array Dashboard data including platforms, verification status, progress, UTM params
 */
if (!function_exists('fasp_get_user_dashboard_data')) {
    function fasp_get_user_dashboard_data($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        $user_id = absint($user_id);
        
        $data = array(
            'user_id' => $user_id,
            'user' => get_userdata($user_id),
            'is_logged_in' => $user_id > 0,
            'is_admin' => user_can($user_id, 'manage_options'),
            'platforms' => array(),
            'verifications' => array(),
            'progress' => array(),
            'progress_count' => 0,
            'total_steps' => 5,
            'utm_params' => array(),
            'coaches' => array(),
            'resources' => array(),
            'is_preview_mode' => false,
        );
        
        if (!$data['is_logged_in']) {
            return $data;
        }
        
        // Get platforms
        $platforms_raw = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
        $platforms = function_exists('fasp_filter_platforms_for_user') ? fasp_filter_platforms_for_user($platforms_raw) : $platforms_raw;
        $data['platforms'] = is_array($platforms) ? $platforms : array();
        
        // Get verification status for each platform
        foreach ($data['platforms'] as $slug => $platform) {
            $slug_safe = sanitize_key($slug);
            $data['verifications'][$slug_safe] = get_user_meta($user_id, '_fasp_verified_' . $slug_safe, true) === '1';
        }
        
        // Get progress steps
        $progress_keys = array('verified', 'downloaded', 'booked', 'deposit', 'trade');
        $progress_meta_map = array(
            'verified' => '_fasp_verified_deriv',
            'downloaded' => '_fasp_downloaded',
            'booked' => '_fasp_booked',
            'deposit' => '_fasp_deposit',
            'trade' => '_fasp_trade'
        );
        
        $completed = 0;
        foreach ($progress_keys as $key) {
            $meta_key = isset($progress_meta_map[$key]) ? $progress_meta_map[$key] : '_fasp_' . $key;
            $is_done = get_user_meta($user_id, $meta_key, true) === '1';
            $data['progress'][$key] = $is_done;
            if ($is_done) {
                $completed++;
            }
        }
        $data['progress_count'] = $completed;
        
        // Get UTM parameters from URL
        $utm_keys = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term');
        foreach ($utm_keys as $key) {
            if (isset($_GET[$key])) {
                $value = sanitize_text_field(wp_unslash($_GET[$key]));
                // Validate UTM parameter format
                if (preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
                    $data['utm_params'][$key] = $value;
                }
            }
        }
        
        // Check preview mode
        $data['is_preview_mode'] = function_exists('fasp_is_preview_user_mode') && fasp_is_preview_user_mode();
        
        // Get recent coaches
        $data['coaches'] = get_posts(array(
            'post_type' => 'fasp_coach_event',
            'numberposts' => 6,
            'post_status' => 'publish'
        ));
        
        // Get recent resources
        $data['resources'] = get_posts(array(
            'post_type' => 'fasp_resource',
            'numberposts' => 8,
            'post_status' => 'publish'
        ));
        
        return $data;
    }
}

/**
 * Check if user is in preview mode
 *
 * @return bool
 */
if (!function_exists('fasp_is_preview_user_mode')) {
    function fasp_is_preview_user_mode() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        return isset($_GET['fasp_preview_user']) && $_GET['fasp_preview_user'] === '1';
    }
}

/**
 * Render Deriv connect button
 *
 * @param int|null $user_id Optional user ID
 * @return string HTML for the connect button
 */
if (!function_exists('fasp_render_deriv_connect_button')) {
    function fasp_render_deriv_connect_button($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        $user_id = absint($user_id);
        
        // Check if already verified
        if (get_user_meta($user_id, '_fasp_deriv_verified', true) === '1') {
            return '<span class="fasp-badge ok">' . esc_html__('Deriv Connected', 'forex-affiliate-suite-pro') . '</span>';
        }
        
        // Get Deriv OAuth URL
        $deriv_app_id = function_exists('fasp_get_option') ? fasp_get_option('deriv_app_id', '') : get_option('fasp_deriv_app_id', '');
        if (empty($deriv_app_id)) {
            return '<span class="fasp-badge muted">' . esc_html__('Deriv not configured', 'forex-affiliate-suite-pro') . '</span>';
        }
        
        $callback = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
        $deriv_url = 'https://oauth.deriv.com/oauth2/authorize?app_id=' . rawurlencode($deriv_app_id) . '&scope=read&redirect_uri=' . rawurlencode($callback);
        
        return '<a class="button fasp-button" href="' . esc_url($deriv_url) . '">' . esc_html__('Connect Deriv', 'forex-affiliate-suite-pro') . '</a>';
    }
}

/**
 * Get progress count for a user
 *
 * @param int|null $user_id Optional user ID
 * @return int Number of completed steps
 */
if (!function_exists('fasp_get_progress_count')) {
    function fasp_get_progress_count($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        $user_id = absint($user_id);
        
        $progress_meta = array(
            '_fasp_verified_deriv',
            '_fasp_downloaded',
            '_fasp_booked',
            '_fasp_deposit',
            '_fasp_trade'
        );
        
        $count = 0;
        foreach ($progress_meta as $meta_key) {
            if (get_user_meta($user_id, $meta_key, true) === '1') {
                $count++;
            }
        }
        
        return $count;
    }
}

/**
 * Render admin user dashboard (admin area)
 */
if (!function_exists('fasp_render_user_dash')) {
    function fasp_render_user_dash() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        // Table name is validated as it's derived from WordPress prefix and a constant suffix
        // Table name is constructed from safe prefix + constant, validated with esc_sql()
        $table_name = $wpdb->prefix . 'fasp_clicks';
        // Validate table name only contains alphanumeric, underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
            return $data;
        }
        
        $d30 = gmdate('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $now = gmdate('Y-m-d') . ' 23:59:59';
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (WP prefix + constant, validated above)
        $query = $wpdb->prepare(
            "SELECT action, COUNT(*) AS c FROM `" . esc_sql($table_name) . "` WHERE created_at BETWEEN %s AND %s GROUP BY action",
            $d30,
            $now
        );
        $rows30 = $wpdb->get_results($query, ARRAY_A);
        
        $map = array('click' => 0, 'lead' => 0, 'paid' => 0);
        if (is_array($rows30)) {
            foreach ($rows30 as $r) {
                if (isset($r['action']) && isset($map[$r['action']])) {
                    $map[$r['action']] = absint($r['c']);
                }
            }
        }
        
        $clicks = $map['click'];
        $leads = $map['lead'];
        $paid = $map['paid'];
        $cr1 = $clicks ? round($leads / $clicks * 100, 2) : 0;
        $cr2 = $leads ? round($paid / $leads * 100, 2) : 0;
        
        echo '<div class="wrap fasp-admin"><h1>' . esc_html__('Forex Trading Dashboard', 'forex-affiliate-suite-pro') . '</h1><div class="fasp-grid">';
        
        $cards = array(
            array(__('Clicks (30d)', 'forex-affiliate-suite-pro'), $clicks),
            array(__('Leads (30d)', 'forex-affiliate-suite-pro'), $leads),
            array(__('Paid (30d)', 'forex-affiliate-suite-pro'), $paid),
            array(__('Click→Lead CR', 'forex-affiliate-suite-pro'), $cr1 . '%'),
            array(__('Lead→Paid CR', 'forex-affiliate-suite-pro'), $cr2 . '%')
        );
        
        foreach ($cards as $c) {
            echo '<div class="fasp-card"><h2>' . esc_html($c[0]) . '</h2><p style="font-size:20px"><strong>' . esc_html($c[1]) . '</strong></p></div>';
        }
        
        echo '</div><div class="fasp-card" style="margin-top:12px"><h2>' . esc_html__('Next Steps', 'forex-affiliate-suite-pro') . '</h2><ol class="fasp-muted" style="line-height:1.8">';
        echo '<li>' . sprintf(
            /* translators: %s: link to UTM Builder */
            esc_html__('Create a tracking link in %s.', 'forex-affiliate-suite-pro'),
            '<a href="' . esc_url(admin_url('admin.php?page=fasp_tools_utm')) . '">' . esc_html__('UTM Builder', 'forex-affiliate-suite-pro') . '</a>'
        ) . '</li>';
        echo '<li>' . sprintf(
            /* translators: %s: link to Payments */
            esc_html__('Connect a payment method in %s.', 'forex-affiliate-suite-pro'),
            '<a href="' . esc_url(admin_url('admin.php?page=fasp_payments')) . '">' . esc_html__('Payments', 'forex-affiliate-suite-pro') . '</a>'
        ) . '</li>';
        echo '<li>' . sprintf(
            /* translators: %1$s: link to Gating, %2$s: link to Geo Gating */
            esc_html__('Configure %1$s and %2$s.', 'forex-affiliate-suite-pro'),
            '<a href="' . esc_url(admin_url('admin.php?page=fasp_platform_gating')) . '">' . esc_html__('Gating', 'forex-affiliate-suite-pro') . '</a>',
            '<a href="' . esc_url(admin_url('admin.php?page=fasp_geo_gating')) . '">' . esc_html__('Geo Gating', 'forex-affiliate-suite-pro') . '</a>'
        ) . '</li>';
        echo '<li>' . sprintf(
            /* translators: %s: link to diagnostics */
            esc_html__('Send a %s.', 'forex-affiliate-suite-pro'),
            '<a href="' . esc_url(admin_url('admin.php?page=fasp_tools_diag')) . '">' . esc_html__('test webhook', 'forex-affiliate-suite-pro') . '</a>'
        ) . '</li>';
        echo '</ol></div></div>';
    }
}
