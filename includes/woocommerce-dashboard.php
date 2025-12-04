<?php
if (!defined('ABSPATH')) exit;

/**
 * Register WooCommerce My Account endpoints for the plugin and route
 * both "forex-affiliate" and "forex-dashboard" to the same display handler.
 *
 * This avoids duplicate/removed link problems and ensures both menu items
 * render the same dashboard template.
 */

// Register endpoints
add_action('init', function() {
  add_rewrite_endpoint('forex-affiliate', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
});

// Add to query vars
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-affiliate';
  $vars[] = 'forex-dashboard';
  return $vars;
});

// Inject items into WooCommerce My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
  $new = [];
  foreach ($items as $key => $label) {
    $new[$key] = $label;
    // After the default Dashboard item, inject our plugin items
    if ($key === 'dashboard') {
      // Keep both labels (backwards compatible)
      $new['forex-affiliate'] = __('Forex Affiliate', 'fasp');
      $new['forex-dashboard'] = __('Forex Trading', 'fasp');
    }
  }

  // Ensure entries exist at least at the end if Dashboard wasn't present
  if (!isset($new['forex-affiliate'])) {
    $new['forex-affiliate'] = __('Forex Affiliate', 'fasp');
  }
  if (!isset($new['forex-dashboard'])) {
    $new['forex-dashboard'] = __('Forex Trading', 'fasp');
  }

  return $new;
});

// Route both endpoints to the same content callback
add_action('woocommerce_account_forex-affiliate_endpoint', 'fasp_wc_dashboard');
add_action('woocommerce_account_forex-dashboard_endpoint', 'fasp_wc_dashboard');

function fasp_wc_dashboard() {
  // Prefer template in plugin templates directory
  $tpl = dirname(__DIR__) . '/templates/dashboard.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  // Fallback: small friendly message (avoid blank page)
  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Forex Affiliate', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('Forex Affiliate dashboard is not available. Please check that the template exists at includes/../templates/dashboard.php', 'fasp') . '</p>';
  echo '</div>';
}
?>
