<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue plugin frontend styles
 * Loads dashboard CSS for all WooCommerce account pages with forex endpoints
 */
function fasp_enqueue_frontend_styles() {
    // Load on WooCommerce account pages or when dashboard shortcode is used
    if ( function_exists('is_account_page') && is_account_page() ) {
        $css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fasp-dashboard.css';
        wp_enqueue_style( 'fasp-dashboard', $css_url, array(), '2025-12-27' );
    }
}
add_action( 'wp_enqueue_scripts', 'fasp_enqueue_frontend_styles', 10 );