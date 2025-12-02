<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/helpers-core.php';
add_action('admin_enqueue_scripts', function($hook){
  if (strpos($hook,'fasp_')!==false || (isset($_GET['page']) && strpos(sanitize_text_field($_GET['page']),'fasp_')===0)){
    wp_enqueue_style('fasp-admin-polish', plugins_url('../assets/css/fasp-admin.css', __FILE__), array(), 'v3plus');
  }
});
add_action('admin_menu', function(){
  if (!current_user_can('manage_options')) return;
  $parent = fasp_parent_slug();
  global $menu; $has = false;
  foreach($menu as $m){ if (!empty($m[2]) && $m[2]===$parent){ $has=true; break; } }
  if (!$has){
    add_menu_page('Forex Affiliate','Forex Affiliate','manage_options',$parent,function(){
      echo '<div class="wrap fasp-admin"><h1>Forex Affiliate</h1><div class="fasp-wrap"><p class="fasp-muted">Hub</p></div></div>';
    },'dashicons-chart-line',25);
  }
  add_submenu_page($parent,'Getting Started','Getting Started','manage_options','fasp_getting_started', function(){
    echo '<div class="wrap fasp-admin"><h1>Getting Started</h1><div class="fasp-card"><ol class="fasp-muted" style="line-height:1.8;"><li>Set up Platforms.</li><li>Create a Landing.</li><li>Add Coaches/Resources.</li><li>Test payments & webhooks.</li></ol></div></div>';
  }, 1);
  add_submenu_page($parent,'User Dashboard','User Dashboard','manage_options','fasp_user_dash','fasp_render_user_dash',2);
  add_submenu_page($parent,'Reports / Analytics','Reports / Analytics','manage_options','fasp_reports','fasp_render_reports',3);
  add_submenu_page($parent,'Promo Landings','Promo Landings','manage_options','fasp_landings','fasp_render_landings',10);
  add_submenu_page($parent,'Resources','Resources','manage_options','edit.php?post_type=fasp_resource',null,11);
  add_submenu_page($parent,'Coaches','Coaches','manage_options','edit.php?post_type=fasp_coach',null,12);
  add_submenu_page($parent,'Creatives Lab','Creatives Lab','manage_options','fasp_creatives_lab','fasp_render_creatives_lab',13);
  add_submenu_page($parent,'Platform Setup','Platform Setup','manage_options','fasp_platforms','fasp_render_platforms',14);
  add_submenu_page($parent,'Platform Visibility','Platform Visibility','manage_options','fasp_visibility','fasp_render_visibility',15);
  add_submenu_page($parent,'Platform Gating','Platform Gating','manage_options','fasp_platform_gating','fasp_render_platform_gating',16);
  add_submenu_page($parent,'Geo Gating','Geo Gating','manage_options','fasp_geo_gating','fasp_render_geo_gating',17);
// removed duplicate Payments submenu (unified)
add_submenu_page($parent,'Email & Leads','Email & Leads','manage_options','fasp_leads','fasp_render_leads',40);
  add_submenu_page($parent,'Tools – UTM Builder','Tools – UTM Builder','manage_options','fasp_tools_utm','fasp_render_tools_utm',50);
  add_submenu_page($parent,'Tools – Export CSV','Tools – Export CSV','manage_options','fasp_tools_export','fasp_render_tools_export',51);
  add_submenu_page($parent,'Tools – Diagnostics','Tools – Diagnostics','manage_options','fasp_tools_diag','fasp_render_tools_diag',52);
  add_submenu_page($parent,'Settings Backup','Settings Backup','manage_options','fasp_tools_backup','fasp_render_tools_backup',53);
  add_submenu_page($parent,'Settings','Settings','manage_options','fasp_settings','fasp_render_settings',90);
}, 30);
