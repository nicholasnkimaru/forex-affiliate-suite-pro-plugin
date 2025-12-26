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

// Register all dashboard endpoints (frontend)
add_action('init', function() {
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('forex-affiliate', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('referrals', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('platforms', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('resources', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('coaches', EP_ROOT | EP_PAGES);
});

// Add to query vars
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-dashboard';
  $vars[] = 'forex-affiliate';
  $vars[] = 'referrals';
  $vars[] = 'platforms';
  $vars[] = 'resources';
  $vars[] = 'coaches';
  return $vars;
});

// Inject menu items into My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
  $new = array();
  $current_user = wp_get_current_user();
  
  // Determine if user is an affiliate
  $is_affiliate = false;
  if ($current_user && $current_user->ID) {
    if (current_user_can('manage_options')) {
      $is_affiliate = true;
    } elseif (in_array('affiliate', (array) $current_user->roles, true)) {
      $is_affiliate = true;
    } elseif (get_user_meta($current_user->ID, 'fasp_is_affiliate', true)) {
      $is_affiliate = true;
    }
  }

  foreach ($items as $key => $label) {
    $new[$key] = $label;

    // After the default dashboard item, insert menu items
    if ($key === 'dashboard') {
      $new['forex-dashboard'] = __('Forex Trading', 'fasp');
      if ($is_affiliate) {
        $new['forex-affiliate'] = __('Affiliate Tools', 'fasp');
        $new['referrals'] = __('Referrals', 'fasp');
      }
    }
  }

  return $new;
}, 20);

// Route endpoints to template loaders
add_action('woocommerce_account_forex-dashboard_endpoint', 'fasp_wc_dashboard');
add_action('woocommerce_account_forex-affiliate_endpoint', 'fasp_wc_affiliate');
add_action('woocommerce_account_referrals_endpoint', 'fasp_wc_referrals');
add_action('woocommerce_account_platforms_endpoint', 'fasp_wc_platforms');
add_action('woocommerce_account_resources_endpoint', 'fasp_wc_resources');
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

function fasp_wc_affiliate() {
  fasp_load_template('template-forex-affiliate.php', 'Affiliate Tools');
}

function fasp_wc_referrals() {
  fasp_load_template('template-referrals.php', 'Referrals');
}

function fasp_wc_platforms() {
  fasp_load_template('template-platforms.php', 'Platforms');
}

function fasp_wc_resources() {
  fasp_load_template('template-resources.php', 'Resources');
}

function fasp_wc_coaches() {
  fasp_load_template('template-coaches.php', 'Coaches');
}

function fasp_load_template($filename, $title) {
  $tpl = dirname(__DIR__) . '/templates/' . $filename;
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html(sprintf(__('%s', 'fasp'), $title)) . '</h2>';
  echo '<p>' . esc_html(sprintf(__('%s page not available. Contact the site administrator.', 'fasp'), $title)) . '</p>';
  echo '</div>';
}
?>