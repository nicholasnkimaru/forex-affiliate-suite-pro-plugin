<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue plugin frontend styles
 * Loads dashboard CSS for all WooCommerce account pages, landing pages, and dashboard shortcodes
 */
function fasp_enqueue_frontend_styles() {
    $should_enqueue = false;
    
    // Load on WooCommerce account pages
    if ( function_exists('is_account_page') && is_account_page() ) {
        $should_enqueue = true;
    }
    
    // Load on landing pages
    if ( is_singular('fasp_landing') ) {
        $should_enqueue = true;
    }
    
    // Load when dashboard shortcode is present
    global $post;
    if ( $post && has_shortcode( $post->post_content, 'fasp_dashboard' ) ) {
        $should_enqueue = true;
    }
    
    if ( $should_enqueue ) {
        $css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fasp-dashboard.css';
        wp_enqueue_style( 'fasp-dashboard', $css_url, array(), '2025-12-27' );
    }
}
add_action( 'wp_enqueue_scripts', 'fasp_enqueue_frontend_styles', 10 );