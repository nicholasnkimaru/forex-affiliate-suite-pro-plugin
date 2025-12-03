<?php
if (!defined('ABSPATH')) {
    exit;
}

// Register the forex-dashboard endpoint for WooCommerce My Account
add_action('init', function () {
    add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
});

// Add menu item to WooCommerce My Account
add_filter('woocommerce_account_menu_items', function ($items) {
    $new_items = array();
    foreach ($items as $k => $v) {
        $new_items[$k] = $v;
        if ($k === 'dashboard') {
            $new_items['forex-dashboard'] = __('Forex Trading', 'forex-affiliate-suite-pro');
        }
    }
    if (!isset($new_items['forex-dashboard'])) {
        $new_items['forex-dashboard'] = __('Forex Trading', 'forex-affiliate-suite-pro');
    }
    return $new_items;
});

// Render the forex dashboard content in WooCommerce My Account
add_action('woocommerce_account_forex-dashboard_endpoint', function () {
    $template_path = FASP_PATH . 'templates/dashboard.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback content
        $u = wp_get_current_user();
        echo '<h2>' . esc_html__('Forex Trading Dashboard', 'forex-affiliate-suite-pro') . '</h2>';
        echo '<p>' . sprintf(
            /* translators: %s: user display name */
            esc_html__('Welcome, %s.', 'forex-affiliate-suite-pro'),
            esc_html($u->display_name ?: $u->user_login)
        ) . '</p>';
        
        $plats = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
        echo '<h3>' . esc_html__('Your Platform Verifications', 'forex-affiliate-suite-pro') . '</h3>';
        echo '<table class="shop_table"><thead><tr><th>' . esc_html__('Platform', 'forex-affiliate-suite-pro') . '</th><th>' . esc_html__('Status', 'forex-affiliate-suite-pro') . '</th><th>' . esc_html__('Action', 'forex-affiliate-suite-pro') . '</th></tr></thead><tbody>';
        
        foreach ($plats as $slug => $p) {
            $k = sanitize_key($slug);
            $name = esc_html($p['name'] ?? $k);
            $ok = function_exists('fasp_is_user_verified_for_platform') ? fasp_is_user_verified_for_platform(get_current_user_id(), $k) : false;
            $act = '';
            
            if ($k === 'deriv') {
                $auth = function_exists('fasp_deriv_authorize_url') ? fasp_deriv_authorize_url() : '';
                if ($auth) {
                    $act = '<a class="button" href="' . esc_url($auth) . '">' . esc_html__('Verify with Deriv', 'forex-affiliate-suite-pro') . '</a>';
                }
            }
            
            echo '<tr><td>' . $name . ' <code>' . esc_html($k) . '</code></td>';
            echo '<td>' . ($ok ? '✅ ' . esc_html__('Verified', 'forex-affiliate-suite-pro') : '❌ ' . esc_html__('Not verified', 'forex-affiliate-suite-pro')) . '</td>';
            echo '<td>' . $act . '</td></tr>';
        }
        
        echo '</tbody></table>';
    }
});
