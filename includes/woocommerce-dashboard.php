<?php
if (!defined('ABSPATH')) exit;

/**
 * Register WooCommerce My Account endpoints and add menu items.
 *
 * Rule:
 * - 'forex-dashboard' (neutral trading dashboard) is available to all users.
 * - 'forex-affiliate' (affiliate tools / links) is only injected into My Account
 *   for users who are affiliates or for admins.
 *
 * Affiliate determination:
 * - current_user_can('manage_options') => admin (always affiliate view)
 * - user has role 'affiliate'
 * - usermeta 'fasp_is_affiliate' is truthy
 */

// Helper: determine if a given WP_User (or current user) should see affiliate UI.
if (!function_exists('fasp_user_is_affiliate')) {
  function fasp_user_is_affiliate( $user = null ) {
    if ( is_null( $user ) ) {
      $user = wp_get_current_user();
    } elseif ( is_numeric( $user ) ) {
      $user = get_userdata( (int) $user );
    }

    if ( ! $user || ! $user->ID ) {
      return false;
    }

    // Admins should always see affiliate parts (site operators)
    if ( user_can( $user, 'manage_options' ) ) {
      return true;
    }

    // Role-based opt-in (optional)
    if ( in_array( 'affiliate', (array) $user->roles, true ) ) {
      return true;
    }

    // Per-user meta opt-in (recommended for fine-grained control)
    if ( get_user_meta( $user->ID, 'fasp_is_affiliate', true ) ) {
      return true;
    }

    return false;
  }
}

// Register endpoints (endpoints exist for everyone but menu items are conditional)
add_action('init', function() {
  add_rewrite_endpoint('forex-affiliate', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
});

// Add to query vars (endpoints kept regardless)
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-affiliate';
  $vars[] = 'forex-dashboard';
  return $vars;
});

// Inject items into WooCommerce My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
  $new = array();
  $is_affiliate = function_exists('fasp_user_is_affiliate') && fasp_user_is_affiliate();

  foreach ( $items as $key => $label ) {
    $new[ $key ] = $label;

    // Insert our neutral trading dashboard for all users directly after 'dashboard'
    if ( 'dashboard' === $key ) {
      // Add a neutral label for normal users
      $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );

      // Only add affiliate menu item if user is affiliate/admin
      if ( $is_affiliate ) {
        $new['forex-affiliate'] = __( 'Affiliate Tools', 'fasp' ); // admin/affiliate-only label
      }
    }
  }

  // Fallback: if there was no dashboard key, ensure forex-dashboard exists (neutral)
  if ( ! isset( $new['forex-dashboard'] ) ) {
    $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
  }

  // If the user is affiliate and entries were not created above (fallback), ensure they exist
  if ( $is_affiliate ) {
    if ( ! isset( $new['forex-affiliate'] ) ) {
      $new['forex-affiliate'] = __( 'Affiliate Tools', 'fasp' );
    }
  } else {
    // Ensure forex-affiliate is removed for non-affiliates
    if ( isset( $new['forex-affiliate'] ) ) {
      unset( $new['forex-affiliate'] );
    }
  }

  return $new;
}, 20);

// Route endpoints to the same template
add_action('woocommerce_account_forex-dashboard_endpoint', 'fasp_wc_dashboard');
add_action('woocommerce_account_forex-affiliate_endpoint', 'fasp_wc_dashboard');

function fasp_wc_dashboard() {
  $tpl = dirname(__DIR__) . '/templates/dashboard.php';
  if ( file_exists( $tpl ) ) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__( 'Forex Trading', 'fasp' ) . '</h2>';
  echo '<p>' . esc_html__( 'Dashboard not available. Contact the site administrator.', 'fasp' ) . '</p>';
  echo '</div>';
} 
?>