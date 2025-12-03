<?php
if (!defined('ABSPATH')) exit;

/**
 * FASP User Dashboard Helpers
 * Provides helper functions for user-facing dashboard data and Deriv connect button.
 */

/**
 * Get user dashboard data for the current user.
 *
 * @param int|null $user_id User ID, defaults to current user.
 * @return array Dashboard data including platforms, resources, coaches, progress, and stats.
 */
if (!function_exists('fasp_get_user_dashboard_data')) {
    function fasp_get_user_dashboard_data($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $data = array(
            'user_id'       => $user_id,
            'is_logged_in'  => $user_id > 0,
            'is_admin'      => user_can($user_id, 'manage_options'),
            'platforms'     => array(),
            'resources'     => array(),
            'coaches'       => array(),
            'progress'      => array(),
            'stats'         => array(),
            'gating_info'   => array(),
            'deriv_status'  => array(
                'verified'   => false,
                'connect_url' => '',
            ),
        );

        if (!$data['is_logged_in']) {
            return $data;
        }

        // Get user display info
        $user = get_userdata($user_id);
        $data['display_name'] = $user ? ($user->display_name ?: $user->user_login) : '';

        // Get platforms
        $platforms_raw = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
        $platforms = function_exists('fasp_filter_platforms_for_user') 
            ? fasp_filter_platforms_for_user($platforms_raw) 
            : $platforms_raw;
        $data['platforms'] = is_array($platforms) ? $platforms : array();

        // Get Deriv status
        $deriv_verified = get_user_meta($user_id, '_fasp_deriv_verified', true) === '1';
        $data['deriv_status']['verified'] = $deriv_verified;

        // Build Deriv connect URL if not verified
        if (!$deriv_verified) {
            $deriv_app_id = function_exists('fasp_get_option') 
                ? fasp_get_option('deriv_app_id', '') 
                : get_option('fasp_deriv_app_id', '');
            if ($deriv_app_id) {
                $callback = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
                $data['deriv_status']['connect_url'] = 'https://oauth.deriv.com/oauth2/authorize?app_id=' 
                    . rawurlencode($deriv_app_id) 
                    . '&scope=read&redirect_uri=' 
                    . rawurlencode($callback);
            }
        }

        // Get progress steps
        $data['progress'] = array(
            array(
                'key'   => 'verified',
                'label' => 'Verify Platform Account',
                'done'  => $deriv_verified,
            ),
            array(
                'key'   => 'downloaded',
                'label' => 'Download Resource',
                'done'  => get_user_meta($user_id, '_fasp_downloaded', true) === '1',
            ),
            array(
                'key'   => 'booked',
                'label' => 'Book Coach Session',
                'done'  => get_user_meta($user_id, '_fasp_booked', true) === '1',
            ),
            array(
                'key'   => 'deposit',
                'label' => 'First Deposit',
                'done'  => get_user_meta($user_id, '_fasp_deposit', true) === '1',
            ),
            array(
                'key'   => 'trade',
                'label' => 'First Trade',
                'done'  => get_user_meta($user_id, '_fasp_trade', true) === '1',
            ),
        );

        // Get coaches
        $coaches = get_posts(array(
            'post_type'   => 'fasp_coach_event',
            'numberposts' => 6,
            'post_status' => 'publish',
        ));
        foreach ($coaches as $c) {
            $name = get_post_meta($c->ID, '_fasp_coach_name', true) ?: get_the_title($c);
            $role = get_post_meta($c->ID, '_fasp_coach_role', true);
            $intro = get_post_meta($c->ID, '_fasp_coach_intro', true);
            $live = get_post_meta($c->ID, '_fasp_coach_live', true);
            $aff = get_post_meta($c->ID, '_fasp_coach_affiliate', true);
            $pid = intval(get_post_meta($c->ID, '_fasp_coach_photo_id', true));
            $img = $pid ? wp_get_attachment_image_url($pid, 'medium') : get_the_post_thumbnail_url($c, 'medium');

            $data['coaches'][] = array(
                'id'        => $c->ID,
                'name'      => $name,
                'role'      => $role,
                'intro'     => $intro,
                'image'     => $img,
                'live_url'  => $live,
                'aff_url'   => $aff,
                'permalink' => get_permalink($c),
            );
        }

        // Get resources
        $resources = get_posts(array(
            'post_type'   => 'fasp_resource',
            'numberposts' => 8,
            'post_status' => 'publish',
        ));
        foreach ($resources as $r) {
            $type = get_post_meta($r->ID, '_fasp_type', true) ?: 'n/a';
            $mon = get_post_meta($r->ID, '_fasp_monetization', true) ?: 'free';
            $reqd = get_post_meta($r->ID, '_fasp_require_deriv', true) ? true : false;
            $cid = intval(get_post_meta($r->ID, '_fasp_cover_id', true));
            $img = $cid ? wp_get_attachment_image_url($cid, 'medium') : get_the_post_thumbnail_url($r, 'medium');
            $intro = get_the_excerpt($r) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $r)), 22, '…');

            $data['resources'][] = array(
                'id'           => $r->ID,
                'title'        => get_the_title($r),
                'type'         => $type,
                'monetization' => $mon,
                'image'        => $img,
                'intro'        => $intro,
                'requires_deriv' => $reqd,
                'permalink'    => get_permalink($r),
            );
        }

        // Get gating info
        $gating_opts = get_option('fasp_platform_gating', array());
        $data['gating_info'] = array(
            'require_login'    => !empty($gating_opts['require_login']),
            'allowed_roles'    => isset($gating_opts['roles']) ? $gating_opts['roles'] : '',
            'is_user_allowed'  => function_exists('fasp_is_user_allowed_by_gating') 
                ? fasp_is_user_allowed_by_gating($user_id, 0) 
                : true,
        );

        // Get stats (if admin or has access)
        if ($data['is_admin']) {
            global $wpdb;
            $table = $wpdb->prefix . 'fasp_clicks';
            $d30 = gmdate('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
            $now = gmdate('Y-m-d') . ' 23:59:59';
            
            // Check if table exists before querying
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));
            
            if ($table_exists) {
                $q = "SELECT action, COUNT(*) c FROM `$table` WHERE created_at BETWEEN %s AND %s GROUP BY action";
                $rows = $wpdb->get_results($wpdb->prepare($q, $d30, $now), ARRAY_A);
                $map = array('click' => 0, 'lead' => 0, 'paid' => 0);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (isset($row['action'], $row['c'])) {
                            $map[$row['action']] = intval($row['c']);
                        }
                    }
                }
                $data['stats'] = array(
                    'clicks_30d' => $map['click'],
                    'leads_30d'  => $map['lead'],
                    'paid_30d'   => $map['paid'],
                    'cr_click_lead' => $map['click'] ? round($map['lead'] / $map['click'] * 100, 2) : 0,
                    'cr_lead_paid'  => $map['lead'] ? round($map['paid'] / $map['lead'] * 100, 2) : 0,
                );
            }
        }

        return $data;
    }
}

/**
 * Render the Deriv connect button for the user.
 *
 * @param int|null $user_id User ID, defaults to current user.
 * @param array    $args    Optional. Button arguments (class, text).
 * @return string HTML for the connect button.
 */
if (!function_exists('fasp_render_deriv_connect_button')) {
    function fasp_render_deriv_connect_button($user_id = null, $args = array()) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        if ($user_id <= 0) {
            return '';
        }

        $defaults = array(
            'class'          => 'fasp-button',
            'text_connect'   => 'Connect Deriv',
            'text_verified'  => 'Deriv Connected ✓',
            'show_status'    => true,
        );
        $args = wp_parse_args($args, $defaults);

        $verified = get_user_meta($user_id, '_fasp_deriv_verified', true) === '1';

        if ($verified) {
            if ($args['show_status']) {
                return '<span class="fasp-badge ok">' . esc_html($args['text_verified']) . '</span>';
            }
            return '';
        }

        // Build connect URL
        $deriv_app_id = function_exists('fasp_get_option') 
            ? fasp_get_option('deriv_app_id', '') 
            : get_option('fasp_deriv_app_id', '');

        if (!$deriv_app_id) {
            return '<span class="fasp-badge muted">Deriv not configured</span>';
        }

        $callback = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
        $url = 'https://oauth.deriv.com/oauth2/authorize?app_id=' 
            . rawurlencode($deriv_app_id) 
            . '&scope=read&redirect_uri=' 
            . rawurlencode($callback);

        return '<a class="' . esc_attr($args['class']) . '" href="' . esc_url($url) . '">' 
            . esc_html($args['text_connect']) 
            . '</a>';
    }
}

/**
 * Check if preview mode is active for admins viewing as regular users.
 *
 * @return bool True if in preview mode.
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
 * Get count of completed progress steps for a user.
 *
 * @param int|null $user_id User ID, defaults to current user.
 * @return array Array with 'completed' count and 'total' count.
 */
if (!function_exists('fasp_get_progress_count')) {
    function fasp_get_progress_count($user_id = null) {
        $data = fasp_get_user_dashboard_data($user_id);
        $completed = 0;
        $total = count($data['progress']);
        
        foreach ($data['progress'] as $step) {
            if (!empty($step['done'])) {
                $completed++;
            }
        }
        
        return array(
            'completed' => $completed,
            'total'     => $total,
            'percent'   => $total > 0 ? round(($completed / $total) * 100) : 0,
        );
    }
}

/**
 * Render the admin user dashboard screen (stats and next steps).
 */
if (!function_exists('fasp_render_user_dash')) {
    function fasp_render_user_dash() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $data = fasp_get_user_dashboard_data();
        $stats = isset($data['stats']) ? $data['stats'] : array();

        $clicks = isset($stats['clicks_30d']) ? $stats['clicks_30d'] : 0;
        $leads = isset($stats['leads_30d']) ? $stats['leads_30d'] : 0;
        $paid = isset($stats['paid_30d']) ? $stats['paid_30d'] : 0;
        $cr1 = isset($stats['cr_click_lead']) ? $stats['cr_click_lead'] : 0;
        $cr2 = isset($stats['cr_lead_paid']) ? $stats['cr_lead_paid'] : 0;

        echo '<div class="wrap fasp-admin"><h1>Forex Trading Dashboard</h1><div class="fasp-grid">';
        $metrics = array(
            array('Clicks (30d)', $clicks),
            array('Leads (30d)', $leads),
            array('Paid (30d)', $paid),
            array('Click→Lead CR', $cr1 . '%'),
            array('Lead→Paid CR', $cr2 . '%'),
        );
        foreach ($metrics as $c) {
            echo '<div class="fasp-card"><h2>' . esc_html($c[0]) . '</h2><p style="font-size:20px"><strong>' . esc_html($c[1]) . '</strong></p></div>';
        }
        echo '</div><div class="fasp-card" style="margin-top:12px"><h2>Next Steps</h2><ol class="fasp-muted" style="line-height:1.8">';
        echo '<li>Create a tracking link in <a href="' . esc_url(admin_url('admin.php?page=fasp_tools_utm')) . '">UTM Builder</a>.</li>';
        echo '<li>Connect a payment method in <a href="' . esc_url(admin_url('admin.php?page=fasp_payments')) . '">Payments</a>.</li>';
        echo '<li>Configure <a href="' . esc_url(admin_url('admin.php?page=fasp_platform_gating')) . '">Gating</a> and <a href="' . esc_url(admin_url('admin.php?page=fasp_geo_gating')) . '">Geo Gating</a>.</li>';
        echo '<li>Send a <a href="' . esc_url(admin_url('admin.php?page=fasp_tools_diag')) . '">test webhook</a>.</li>';
        echo '</ol></div></div>';
    }
}
