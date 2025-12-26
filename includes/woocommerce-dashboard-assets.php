<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue frontend assets for the dashboard endpoints.
 * NOTE: Only the user-facing 'forex-dashboard' endpoint is considered here.
 */

add_action('wp_enqueue_scripts', function() {
  if ( ! function_exists('is_account_page') || ! is_account_page() ) {
    return;
  }

  // Check if we're on any of our dashboard endpoints
  $endpoints = ['forex-dashboard', 'forex-affiliate', 'referrals', 'platforms', 'resources', 'coaches'];
  $on_endpoint = false;
  
  foreach ($endpoints as $endpoint) {
    if (get_query_var($endpoint, false) !== false) {
      $on_endpoint = true;
      break;
    }
  }

  if ($on_endpoint) {
    wp_enqueue_style(
      'fasp-dashboard-css',
      plugins_url('/assets/css/fasp-dashboard.css', dirname(__DIR__)),
      [],
      '1.1'
    );
    wp_enqueue_script(
      'fasp-dashboard-js',
      plugins_url('/assets/js/fasp-dashboard.js', dirname(__DIR__)),
      ['jquery'],
      '1.1',
      true
    );
  }
});
?>