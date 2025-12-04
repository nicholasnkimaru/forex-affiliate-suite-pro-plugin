<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue dashboard styles and scripts only on the My Account pages
 * for the plugin's endpoints (forex-affiliate / forex-dashboard).
 *
 * Save as: includes/woocommerce-dashboard-assets.php
 * Then ensure this file is required/loaded by your admin/bootstrap loader
 * (for example in includes/admin-bootstrap.php or main plugin file).
 */

add_action('wp_enqueue_scripts', function() {
  // Only enqueue on frontend My Account pages
  if ( ! function_exists('is_account_page') || ! is_account_page() ) {
    return;
  }

  // Check if either endpoint is present in the query
  $qv = isset( $GLOBALS['wp_query']->query_vars ) ? $GLOBALS['wp_query']->query_vars : array();
  $has_endpoint = array_key_exists('forex-affiliate', $qv) || array_key_exists('forex-dashboard', $qv);

  if ( ! $has_endpoint ) {
    return;
  }

  // Base URL to plugin root
  $base = plugin_dir_url( dirname( __FILE__ ) );

  // Styles
  wp_enqueue_style( 'fasp-dashboard', $base . 'assets/css/fasp-dashboard.css', array(), '1.0.0' );
  wp_enqueue_style( 'fasp-admin', $base . 'assets/css/fasp-admin.css', array(), '1.0.0' );

  // Scripts (dashboard interactivity)
  wp_enqueue_script( 'fasp-dashboard', $base . 'assets/js/fasp-dashboard.js', array('jquery'), '1.0.0', true );

  // Localize small config if needed
  wp_localize_script( 'fasp-dashboard', 'FASP_DASHBOARD', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('fasp_dashboard_nonce'),
  ) );
});
?>
