<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue plugin frontend styles only on WooCommerce My Account pages
 * This ensures dashboard styles are loaded when needed without bloating other pages.
 */
function fasp_enqueue_frontend_styles() {
    // Only enqueue on WooCommerce My Account pages
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return;
    }

    $css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fasp-dashboard.css';
    $js_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/fasp-dashboard.js';
    
    wp_enqueue_style( 'fasp-dashboard', $css_url, array(), '2025-12-05' );
    wp_enqueue_script( 'fasp-dashboard-js', $js_url, array( 'jquery' ), '2025-12-05', true );
}
add_action( 'wp_enqueue_scripts', 'fasp_enqueue_frontend_styles', 20 );