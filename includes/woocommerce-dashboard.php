<?php
if (!defined('ABSPATH')) exit;

/**
 * Frontend WooCommerce My Account integration — minimal and neutral.
 *
 * - Register neutral endpoints: forex-dashboard, platforms, resources, coaches
 * - Do NOT register or expose the 'forex-affiliate' endpoint here.
 * - Inject only neutral menu items into My Account.
 * - Route endpoints to appropriate template loaders.
 */

// Register frontend endpoints
add_action('init', function() {
  add_rewrite_endpoint('forex-dashboard', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('platforms', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('resources', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('coaches', EP_ROOT | EP_PAGES);
});

// Add to query vars
add_filter('query_vars', function($vars) {
  $vars[] = 'forex-dashboard';
  $vars[] = 'platforms';
  $vars[] = 'resources';
  $vars[] = 'coaches';
  return $vars;
});

// Inject neutral menu items into My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
  $new = array();

  foreach ($items as $key => $label) {
    $new[$key] = $label;

    // After the default dashboard item, insert trading-related links
    if ($key === 'dashboard') {
      $new['forex-dashboard'] = __('Forex Trading', 'fasp');
      $new['platforms'] = __('Platforms', 'fasp');
      $new['resources'] = __('Resources', 'fasp');
      $new['coaches'] = __('Coaches', 'fasp');
    }
  }

  // Ensure endpoints exist (fallback)
  if (!isset($new['forex-dashboard'])) {
    $new['forex-dashboard'] = __('Forex Trading', 'fasp');
  }
  if (!isset($new['platforms'])) {
    $new['platforms'] = __('Platforms', 'fasp');
  }
  if (!isset($new['resources'])) {
    $new['resources'] = __('Resources', 'fasp');
  }
  if (!isset($new['coaches'])) {
    $new['coaches'] = __('Coaches', 'fasp');
  }

  // Ensure forex-affiliate is not present on frontend menus
  if (isset($new['forex-affiliate'])) {
    unset($new['forex-affiliate']);
  }

  return $new;
}, 20);

// Route the neutral endpoints to template loaders
add_action('woocommerce_account_forex-dashboard_endpoint', 'fasp_wc_dashboard');
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

function fasp_wc_platforms() {
  $tpl = dirname(__DIR__) . '/templates/platforms.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  // Fallback: display platforms inline
  echo '<div class="woocommerce-MyAccount-content fasp-dashboard-wrap">';
  echo '<header class="fasp-dashboard-header">';
  echo '<h1>' . esc_html__('Trading Platforms', 'fasp') . '</h1>';
  echo '<p class="fasp-muted">' . esc_html__('Available trading platforms and setup instructions.', 'fasp') . '</p>';
  echo '</header>';
  
  $platforms = get_option('fasp_platforms', array());
  if (!empty($platforms)) {
    echo '<div class="fasp-dashboard">';
    foreach ($platforms as $slug => $platform) {
      if (isset($platform['visible_in_dashboard']) && !$platform['visible_in_dashboard']) {
        continue;
      }
      echo '<div class="fasp-card fasp-card--half">';
      echo '<h3>' . esc_html($platform['name'] ?? $slug) . '</h3>';
      echo '<p class="fasp-muted">' . esc_html($platform['excerpt'] ?? '') . '</p>';
      if (!empty($platform['affiliate_url'])) {
        echo '<p><a class="button button-primary" href="' . esc_url($platform['affiliate_url']) . '" target="_blank" rel="noopener">' . esc_html__('Open Account', 'fasp') . '</a></p>';
      }
      echo '</div>';
    }
    echo '</div>';
  } else {
    echo '<p>' . esc_html__('No platforms configured yet.', 'fasp') . '</p>';
  }
  echo '</div>';
}

function fasp_wc_resources() {
  $tpl = dirname(__DIR__) . '/templates/resources.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  // Fallback: display resources inline
  echo '<div class="woocommerce-MyAccount-content fasp-dashboard-wrap">';
  echo '<header class="fasp-dashboard-header">';
  echo '<h1>' . esc_html__('Resources', 'fasp') . '</h1>';
  echo '<p class="fasp-muted">' . esc_html__('Guides, onboarding materials and FAQ.', 'fasp') . '</p>';
  echo '</header>';
  
  $resources = get_posts(array(
    'post_type' => 'fasp_resource',
    'posts_per_page' => 12,
    'post_status' => 'publish',
  ));
  
  if (!empty($resources)) {
    echo '<div class="fasp-dashboard">';
    foreach ($resources as $resource) {
      echo '<div class="fasp-card fasp-card--half">';
      echo '<h3><a href="' . esc_url(get_permalink($resource->ID)) . '">' . esc_html($resource->post_title) . '</a></h3>';
      if ($resource->post_excerpt) {
        echo '<p class="fasp-muted">' . esc_html($resource->post_excerpt) . '</p>';
      }
      echo '<p><a href="' . esc_url(get_permalink($resource->ID)) . '">' . esc_html__('Read More', 'fasp') . '</a></p>';
      echo '</div>';
    }
    echo '</div>';
  } else {
    echo '<p>' . esc_html__('No resources available yet.', 'fasp') . '</p>';
  }
  echo '</div>';
}

function fasp_wc_coaches() {
  $tpl = dirname(__DIR__) . '/templates/coaches.php';
  if (file_exists($tpl)) {
    include $tpl;
    return;
  }

  // Fallback: display coaches inline
  echo '<div class="woocommerce-MyAccount-content fasp-dashboard-wrap">';
  echo '<header class="fasp-dashboard-header">';
  echo '<h1>' . esc_html__('Coaches', 'fasp') . '</h1>';
  echo '<p class="fasp-muted">' . esc_html__('Book sessions with our coaches to get started faster.', 'fasp') . '</p>';
  echo '</header>';
  
  $coaches = get_posts(array(
    'post_type' => 'fasp_coach',
    'posts_per_page' => 12,
    'post_status' => 'publish',
  ));
  
  if (!empty($coaches)) {
    echo '<div class="fasp-dashboard">';
    foreach ($coaches as $coach) {
      echo '<div class="fasp-card fasp-card--half">';
      if (has_post_thumbnail($coach->ID)) {
        echo '<p>' . get_the_post_thumbnail($coach->ID, 'thumbnail') . '</p>';
      }
      echo '<h3><a href="' . esc_url(get_permalink($coach->ID)) . '">' . esc_html($coach->post_title) . '</a></h3>';
      if ($coach->post_excerpt) {
        echo '<p class="fasp-muted">' . esc_html($coach->post_excerpt) . '</p>';
      }
      echo '<p><a href="' . esc_url(get_permalink($coach->ID)) . '">' . esc_html__('View Profile', 'fasp') . '</a></p>';
      echo '</div>';
    }
    echo '</div>';
  } else {
    echo '<p>' . esc_html__('No coaches available yet.', 'fasp') . '</p>';
  }
  echo '</div>';
}
?>