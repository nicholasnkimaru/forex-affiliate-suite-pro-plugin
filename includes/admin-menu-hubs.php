<?php if (!defined('ABSPATH')) exit;

add_action('admin_menu', function(){
  $slug='fasp_settings_top';

  // Hide duplicate CPT submenus
  remove_submenu_page($slug,$slug);
  remove_submenu_page($slug,'edit.php?post_type=fasp_resource');
  remove_submenu_page($slug,'edit.php?post_type=fasp_coach_event');
  remove_submenu_page($slug,'edit.php?post_type=fasp_landing');

  add_submenu_page($slug,'Overview','Overview','manage_options','fasp_overview','fasp_render_overview');
  add_submenu_page($slug,'Forex Resources','Forex Resources','edit_posts','fasp_resources_hub','fasp_render_resources_hub');
  add_submenu_page($slug,'Forex Coaches','Forex Coaches','edit_posts','fasp_coaches_hub','fasp_render_coaches_hub');
  add_submenu_page($slug,'Promo Landings','Promo Landings','edit_posts','edit.php?post_type=fasp_landing');
  add_submenu_page($slug,'Platform Visibility','Platform Visibility','manage_options','fasp_visibility','fasp_render_visibility');
  add_submenu_page($slug,'Platform Setup','Platform Setup','manage_options','fasp_platform_setup','fasp_render_platform_setup');
  add_submenu_page($slug,'Payments & Gateways','Payments & Gateways','manage_options','fasp_payments_setup','fasp_admin_payments_screen_setup');
  add_submenu_page($slug,'Creative Helper','Creative Helper','edit_posts','fasp_creative_helper','fasp_render_creative_helper_page');
  add_submenu_page($slug,'Email & Leads','Email & Leads','manage_options','fasp_email','fasp_render_email_integration');
  add_submenu_page($slug,'Getting Started','Getting Started','manage_options','fasp_onboarding','fasp_render_onboarding');
  add_submenu_page($slug,'Compliance','Compliance','manage_options','fasp_compliance','fasp_render_compliance');
  add_submenu_page($slug,'Analytics','Analytics','manage_options','fasp_analytics','fasp_render_analytics');
  add_submenu_page($slug,'User Verification','User Verification','manage_options','fasp_user_verify','fasp_render_user_verify');
}, 99);

function fasp_tabnav($tabs,$active){
  echo '<h2 class="nav-tab-wrapper">';
  foreach($tabs as $k=>$lab){
    $class = ($k===$active)?' nav-tab nav-tab-active':' nav-tab';
    echo '<a class="'.$class.'" href="?page='.esc_attr($_GET['page']).'&tab='.$k.'">'.esc_html($lab).'</a>';
  }
  echo '</h2>';
}

function fasp_render_overview(){
  echo '<div class="wrap"><h1>Forex Affiliate — Overview</h1>';
  echo '<p>Start with <a href="'.admin_url('admin.php?page=fasp_onboarding').'">Getting Started</a>. Configure platforms in <a href="'.admin_url('admin.php?page=fasp_platform_setup').'">Platform Setup</a>, then create <a href="'.admin_url('edit.php?post_type=fasp_resource').'">Resources</a> and <a href="'.admin_url('edit.php?post_type=fasp_landing').'">Promo Landings</a>. Use <a href="'.admin_url('admin.php?page=fasp_creative_helper').'">Creative Helper</a> for assets and <a href="'.admin_url('admin.php?page=fasp_email').'">Email & Leads</a> for capture.</p>';
  echo '</div>';
}

function fasp_render_resources_hub(){
  $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
  $tabs = ['list'=>'All Resources'];
  echo '<div class="wrap"><h1>Forex Resources</h1>';
  fasp_tabnav($tabs,$tab);
  if ($tab==='list'){
    echo '<p><a class="button button-primary" href="'.admin_url('post-new.php?post_type=fasp_resource').'">Add Resource</a> ';
    echo '<a class="button" href="'.admin_url('edit.php?post_type=fasp_resource').'">Open Resources List</a></p>';
  }
  echo '</div>';
}

function fasp_render_coaches_hub(){
  $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
  $tabs = ['list'=>'All Coaches'];
  echo '<div class="wrap"><h1>Forex Coaches</h1>';
  fasp_tabnav($tabs,$tab);
  if ($tab==='list'){
    echo '<p><a class="button button-primary" href="'.admin_url('post-new.php?post_type=fasp_coach_event').'">Add Coach</a> ';
    echo '<a class="button" href="'.admin_url('edit.php?post_type=fasp_coach_event').'">Open Coaches List</a></p>';
  }
  echo '</div>';
}
