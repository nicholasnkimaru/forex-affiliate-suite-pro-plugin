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

  $qv = get_query_var( 'forex-dashboard', false ) !== false ? array( 'forex-dashboard' => true ) : array();

  // If user is on the forex-dashboard account endpoint, enqueue assets
  $has_endpoint = array_key_exists('forex-dashboard', $qv);

  if ( $has_endpoint ) {
    wp_enqueue_style(
      'fasp-dashboard-css',
      plugins_url('/assets/css/fasp-dashboard.css', dirname(__DIR__)),
      [],
      '1.0'
    );
    wp_enqueue_script(
      'fasp-dashboard-js',
      plugins_url('/assets/js/fasp-dashboard.js', dirname(__DIR__)),
      ['jquery'],
      '1.0',
      true
    );
  }
});
?>