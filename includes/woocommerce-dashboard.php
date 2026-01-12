<?php
if (!defined('ABSPATH')) exit;

/**
 * Frontend WooCommerce My Account integration — minimal and neutral.
 *
 * - Register neutral dashboard endpoints: forex-dashboard, platforms, resources, coaches
 * - Do NOT register or expose the 'forex-affiliate' endpoint here.
 * - Inject neutral menu items into My Account menu.
 * - Route endpoints to their respective template loaders.
 */

// Register forex-dashboard and related endpoints (frontend)
add_action('init', function() {
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('platforms', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('resources', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('coaches', EP_ROOT | EP_PAGES);
});

// Add to query vars (forex-dashboard and related endpoints)
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-dashboard';
  $vars[] = 'platforms';
  $vars[] = 'resources';
  $vars[] = 'coaches';
  return $vars;
});

// Inject neutral "Forex Trading" and sub-items into My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
  $new = array();

  foreach ($items as $key => $label) {
    $new[$key] = $label;

    // After the default dashboard item, insert the neutral trading dashboard link and sub-items
    if ($key === 'dashboard') {
      $new['forex-dashboard'] = __('Forex Trading', 'fasp');
      $new['platforms'] = __('Platforms', 'fasp');
      $new['resources'] = __('Resources', 'fasp');
      $new['coaches'] = __('Coaches', 'fasp');
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

// Route platforms endpoint
add_action('woocommerce_account_platforms_endpoint', 'fasp_wc_platforms');

// Route resources endpoint
add_action('woocommerce_account_resources_endpoint', 'fasp_wc_resources');

// Route coaches endpoint
add_action('woocommerce_account_coaches_endpoint', 'fasp_wc_coaches');

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

function fasp_wc_platforms() {
  $tpl = dirname(__DIR__) . '/templates/platforms.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Trading Platforms', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('Platforms page not available. Contact the site administrator.', 'fasp') . '</p>';
  echo '</div>';
}

function fasp_wc_resources() {
  // Track that user viewed resources
  if (is_user_logged_in()) {
    update_user_meta(get_current_user_id(), 'fasp_viewed_resources', time());
  }
  
  $tpl = dirname(__DIR__) . '/templates/resources.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Resources', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('Resources page not available. Contact the site administrator.', 'fasp') . '</p>';
  echo '</div>';
}

function fasp_wc_coaches() {
  // Track that user viewed coaches
  if (is_user_logged_in()) {
    update_user_meta(get_current_user_id(), 'fasp_viewed_coaches', time());
  }
  
  $tpl = dirname(__DIR__) . '/templates/coaches.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Coaches', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('Coaches page not available. Contact the site administrator.', 'fasp') . '</p>';
  echo '</div>';
}
?>