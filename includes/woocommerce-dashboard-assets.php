<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue frontend JavaScript for the dashboard endpoints.
 * NOTE: CSS is now handled by includes/enqueue-styles.php
 */

add_action('wp_enqueue_scripts', function() {
  if ( ! function_exists('is_account_page') || ! is_account_page() ) {
    return;
  }

  $qv = get_query_var( 'forex-dashboard', false ) !== false ? array( 'forex-dashboard' => true ) : array();

  // If user is on the forex-dashboard account endpoint, enqueue JS
  $has_endpoint = array_key_exists('forex-dashboard', $qv);

  if ( $has_endpoint ) {
    // Check if JS file exists before enqueuing
    $js_path = plugin_dir_path( dirname(__FILE__) ) . 'assets/js/fasp-dashboard.js';
    if ( file_exists($js_path) ) {
      wp_enqueue_script(
        'fasp-dashboard-js',
        plugins_url('/assets/js/fasp-dashboard.js', dirname(__FILE__)),
        ['jquery'],
        '1.0',
        true
      );
    }
  }
});
?>