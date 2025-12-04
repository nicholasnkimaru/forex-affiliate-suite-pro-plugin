<?php
if (!defined('ABSPATH')) exit;

/**
 * Lightweight loader to ensure My Account menu entries exist and do not
 * produce duplicates. This harmonizes with includes/woocommerce-dashboard.php
 * which provides the endpoint callbacks. This file avoids re-adding items
 * if another loader already added them.
 *
 * Save as: includes/user-dashboard-loader.php (overwrite current).
 */

// Ensure endpoints exist (defensive)
add_action('init', function() {
  if ( ! did_action( 'fasp_user_endpoints_registered' ) ) {
    add_rewrite_endpoint( 'forex-affiliate', EP_PAGES );
    add_rewrite_endpoint( 'forex-dashboard', EP_PAGES );
    do_action( 'fasp_user_endpoints_registered' );
  }
});

// Ensure menu items exist but avoid duplicates.
// If another filter already adds these, this is a no-op.
add_filter('woocommerce_account_menu_items', function( $items ) {
  // If items are already present, bail out early
  if ( isset( $items['forex-affiliate'] ) && isset( $items['forex-dashboard'] ) ) {
    return $items;
  }

  $new = array();
  foreach ( $items as $key => $label ) {
    $new[ $key ] = $label;

    if ( 'dashboard' === $key ) {
      // Insert our items immediately after the dashboard entry (if missing)
      if ( ! isset( $new['forex-affiliate'] ) ) {
        $new['forex-affiliate'] = __( 'Forex Affiliate', 'fasp' );
      }
      if ( ! isset( $new['forex-dashboard'] ) ) {
        $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
      }
    }
  }

  // As a fallback (if we didn't find a 'dashboard' entry), ensure existence
  if ( ! isset( $new['forex-affiliate'] ) ) {
    $new['forex-affiliate'] = __( 'Forex Affiliate', 'fasp' );
  }
  if ( ! isset( $new['forex-dashboard'] ) ) {
    $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
  }

  return $new;
}, 20 );
?>
