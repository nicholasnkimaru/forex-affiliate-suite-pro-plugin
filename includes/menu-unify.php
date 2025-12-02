<?php if (!defined('ABSPATH')) { exit; }
/**
 * Forces a full, ordered submenu under the Forex Affiliate top-level.
 * Relies on the existing top slug 'fasp_settings_top'.
 */
add_action('admin_menu', function(){
  $slug = 'fasp_settings_top';

  // Clean duplicate auto-added first item (same as top-level)
  remove_submenu_page($slug, $slug);

  // Ordered, comprehensive list
  add_submenu_page($slug,'Overview','Overview','manage_options','fasp_overview','fasp_render_overview');

  // Content types
  add_submenu_page($slug,'Forex Coaches','Forex Coaches','edit_posts','edit.php?post_type=fasp_coach_event');
  add_submenu_page($slug,'Forex Resources','Forex Resources','edit_posts','edit.php?post_type=fasp_resource');
  add_submenu_page($slug,'Promo Landings','Promo Landings','edit_posts','edit.php?post_type=fasp_landing');

  // Core pages
  add_submenu_page($slug,'Setup','Setup','manage_options','fasp_settings_top','fasp_render_settings_page');
  add_submenu_page($slug,'Platform Visibility','Platform Visibility','manage_options','fasp_visibility','fasp_render_visibility');
  add_submenu_page($slug,'Platform Settings','Platform Settings','manage_options','fasp_platform_settings','fasp_render_platform_settings');

  // Growth tools
  add_submenu_page($slug,'Ads & Tracking','Ads & Tracking','manage_options','fasp_ads_tracking','fasp_render_ads_tracking');
  add_submenu_page($slug,'Creative Helper','Creative Helper','manage_options','fasp_creative_helper','fasp_render_creative_helper');

  // Data
  add_submenu_page($slug,'Analytics','Analytics','manage_options','fasp_analytics','fasp_render_analytics');
  add_submenu_page($slug,'Tracking','Tracking','manage_options','fasp_tracking','fasp_render_tracking');

  // Integrations
  add_submenu_page($slug,'Platforms (Exness)','Platforms (Exness)','manage_options','fasp_platforms_exness','fasp_render_platforms_exness');
  add_submenu_page($slug,'Activations','Activations','manage_options','fasp_activations','fasp_render_activations');
  add_submenu_page($slug,'Passes','Passes','manage_options','fasp_passes','fasp_render_passes');

  // Optional links (open in new tab)
  add_submenu_page($slug,'Dashboard Preview','Dashboard Preview','manage_options','fasp_dashboard_preview', function(){
    echo '<div class="wrap"><h1>Dashboard Preview</h1><p><a class="button button-primary" href="'.esc_url(home_url('/dashboard')).'" target="_blank">Open front-end dashboard</a></p></div>';
  });
  add_submenu_page($slug,'Open My Account','Open My Account','manage_options','fasp_open_my_account', function(){
    echo '<div class="wrap"><h1>Open My Account</h1><p><a class="button button-primary" href="https://my.deriv.com/" target="_blank" rel="noopener">Open Deriv</a></p></div>';
  });
}, 60);
