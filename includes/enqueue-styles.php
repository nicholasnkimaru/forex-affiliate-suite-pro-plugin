<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue plugin frontend styles
 */
function fasp_enqueue_frontend_styles() {
    // plugin root URL (adjust if this file is moved)
    $css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fasp-dashboard.css';
    wp_enqueue_style( 'fasp-dashboard', $css_url, array(), '2025-12-05' );
}
add_action( 'wp_enqueue_scripts', 'fasp_enqueue_frontend_styles', 20 );