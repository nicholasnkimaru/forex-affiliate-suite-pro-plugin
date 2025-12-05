<?php
if (!defined('ABSPATH')) exit;

/**
 * user-dashboard-loader.php
 *
 * Keep frontend registration minimal: register only the user-facing
 * 'forex-dashboard' endpoint. Do NOT register or expose 'forex-affiliate'
 * on the frontend — that slug remains admin-only.
 */

// Ensure endpoints exist (defensive) — only register forex-dashboard for frontend
add_action('init', function() {
  if ( ! did_action( 'fasp_user_endpoints_registered' ) ) {
    add_rewrite_endpoint( 'forex-dashboard', EP_PAGES );
    do_action( 'fasp_user_endpoints_registered' );
  }
});

// Note: menu injection and affiliate endpoint handling is intentionally not done here.
// Admin-side menus and pages that use 'forex-affiliate' remain in the admin area.
?>