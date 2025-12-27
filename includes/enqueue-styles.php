<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue plugin frontend styles
 * 
 * This is the primary location for dashboard CSS enqueueing.
 * Loads on all frontend pages to ensure consistent styling.
 */
function fasp_enqueue_frontend_styles() {
    // Enqueue dashboard CSS globally on frontend
    $css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fasp-dashboard.css';
    wp_enqueue_style( 'fasp-dashboard', $css_url, array(), '2025-12-27' );
    
    // Enqueue dashboard JS on WooCommerce account pages
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        $js_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/fasp-dashboard.js';
        wp_enqueue_script( 'fasp-dashboard-js', $js_url, array( 'jquery' ), '2025-12-27', true );
    }
}
add_action( 'wp_enqueue_scripts', 'fasp_enqueue_frontend_styles', 20 );