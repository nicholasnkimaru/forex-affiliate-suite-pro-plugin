<?php
if (!defined('ABSPATH')) exit;

/**
 * Frontend WooCommerce My Account integration — minimal and neutral.
 *
 * - Register only the neutral 'forex-dashboard' endpoint for frontend.
 * - Do NOT register or expose the 'forex-affiliate' endpoint here.
 * - Inject only neutral 'Forex Trading' menu item into My Account.
 * - Route only 'forex-dashboard' to our dashboard template loader.
 */

// Register only forex-dashboard (frontend)
add_action('init', function() {
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
});

// Add to query vars (only forex-dashboard)
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-dashboard';
  return $vars;
});

// Inject only neutral "Forex Trading" into My Account menu; do NOT add "Forex Affiliate"
add_filter('woocommerce_account_menu_items', function($items) {
  $new = array();

  foreach ($items as $key => $label) {
    $new[$key] = $label;

    // After the default dashboard item, insert the neutral trading dashboard link
    if ($key === 'dashboard') {
      $new['forex-dashboard'] = __('Forex Trading', 'fasp');
      // Intentionally do NOT add forex-affiliate here.
    }
  }

  // Ensure forex-dashboard exists (fallback)
  if (!isset($new['forex-dashboard'])) {
    $new['forex-dashboard'] = __('Forex Trading', 'fasp');
  }

  // Ensure forex-affiliate is not present on frontend menus
  if (isset($new['forex-affiliate'])) {
    unset($new['forex-affiliate']);
  }

  return $new;
}, 20);

// Route the neutral endpoint to the template loader
add_action('woocommerce_account_forex-dashboard_endpoint', 'fasp_wc_dashboard');

function fasp_wc_dashboard() {
  $tpl = dirname(__DIR__) . '/templates/dashboard.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Trading Dashboard', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('Dashboard not available. Contact the site administrator.', 'fasp') . '</p>';
  echo '</div>';
}
?>