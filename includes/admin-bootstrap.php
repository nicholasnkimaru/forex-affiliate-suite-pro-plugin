<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_admin_assets')){
  function fasp_admin_assets($hook){
    if ( strpos($hook,'fasp')!==false || (isset($_GET['page']) && strpos(sanitize_text_field($_GET['page']),'fasp_')===0) ){
      wp_enqueue_style('fasp-admin-polish', plugins_url('assets/css/fasp-admin.css', dirname(__FILE__,1) . '/../'), [], 'r16p5');
    }
  }
  add_action('admin_enqueue_scripts','fasp_admin_assets');
}

if (!function_exists('fasp_classic_everywhere')){
  function fasp_classic_everywhere(){
    add_filter('use_block_editor_for_post_type','__return_false',100);
    add_filter('use_widgets_block_editor','__return_false',100);
  }
  add_action('init','fasp_classic_everywhere');
}

/**
 * Load user dashboard and geo-gating modules.
 * Guards ensure we don't emit warnings or re-declare functions if these
 * files are already loaded by the main plugin bootstrap.
 */

if ( file_exists( __DIR__ . '/woocommerce-dashboard.php' ) && ! function_exists( 'fasp_wc_dashboard' ) ) {
    require_once __DIR__ . '/woocommerce-dashboard.php';
}

if ( file_exists( __DIR__ . '/woocommerce-dashboard-assets.php' ) ) {
    require_once __DIR__ . '/woocommerce-dashboard-assets.php';
}

if ( file_exists( __DIR__ . '/user-dashboard-loader.php' ) ) {
    require_once __DIR__ . '/user-dashboard-loader.php';
}

if ( file_exists( __DIR__ . '/geo-gating.php' ) && ! function_exists( 'fasp_geo_gating_page' ) ) {
    require_once __DIR__ . '/geo-gating.php';
}

if ( file_exists( __DIR__ . '/geo-gating-assets.php' ) ) {
    require_once __DIR__ . '/geo-gating-assets.php';
}