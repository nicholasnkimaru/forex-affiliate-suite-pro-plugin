<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_hub_home')) {
  function fasp_hub_home(){
    echo '<div class="wrap fasp-admin"><h1>Forex Affiliate</h1><div class="fasp-wrap fasp-card"><p class="fasp-muted">Central hub for FASP (platforms, payments, landings, creatives, analytics).</p></div></div>';
  }
}

if (!function_exists('fasp_register_admin_menus')) {
  add_action('admin_menu', 'fasp_register_admin_menus', 9);
  function fasp_register_admin_menus(){
    $cap = 'manage_fasp';
    $parent = 'fasp_hub';

    add_menu_page('Forex Affiliate','Forex Affiliate',$cap,$parent,'fasp_hub_home','dashicons-chart-line',25);

    add_submenu_page($parent,'Forex Resources','Forex Resources',$cap,'edit.php?post_type=fasp_resource');
    add_submenu_page($parent,'Forex Coaches','Forex Coaches',$cap,'edit.php?post_type=fasp_coach');

    add_submenu_page($parent,'Platform Setup','Platform Setup',$cap,'fasp_platforms','fasp_platforms_page');
    add_submenu_page($parent,'Payments & Gateways','Payments & Gateways',$cap,'fasp_payments','fasp_payments_page');
    add_submenu_page($parent,'Promo Landings','Promo Landings',$cap,'fasp_landings','fasp_landings_page');
    add_submenu_page($parent,'Creatives Lab','Creatives Lab',$cap,'fasp_creatives_lab','fasp_creatives_lab_page');
    add_submenu_page($parent,'Reports','Reports',$cap,'fasp_reports','fasp_reports_page');

    add_submenu_page($parent,'Attribution','Attribution',$cap,'fasp_attribution','fasp_attribution_page');
    add_submenu_page($parent,'Marketing & Analytics','Marketing & Analytics',$cap,'fasp_marketing','fasp_marketing_page');
    add_submenu_page($parent,'Settings','Settings',$cap,'fasp_settings','fasp_settings_page');
    add_submenu_page($parent,'Getting Started','Getting Started',$cap,'fasp_getting_started','fasp_getting_started_page');
  }
}




// --- KILL the old Payments & Gateways menu (fasp_payments_setup) ---
add_action('admin_menu', function () {
    // Remove if it was added as a child of the Forex Affiliate hub
    remove_submenu_page('fasp_hub', 'fasp_payments_setup');

    // Paranoia: also remove if it was added as a standalone page somewhere
    remove_menu_page('fasp_payments_setup');
}, 999);

// --- Redirect legacy slug to the new payments screen (or hub) ---
add_action('admin_init', function () {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'fasp_payments_setup') {
        // send them to the consolidated payments page inside the hub
        wp_safe_redirect(admin_url('admin.php?page=fasp_payments'), 301);
        exit;
    }
});






if (!function_exists('fasp_legacy_redirects')) {
  add_action('admin_init', 'fasp_legacy_redirects', 1);
  function fasp_legacy_redirects(){
    if (!current_user_can('manage_fasp')) return;
    $map = array(
      'fasp_platform_setup' => 'fasp_platforms',
      'fasp_platform'       => 'fasp_platforms',
      'fasp_payment'        => 'fasp_payments',
      'fasp_creatives'      => 'fasp_creatives_lab',
    );
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    if (isset($map[$page])) {
      wp_safe_redirect(admin_url('admin.php?page='.$map[$page]));
      exit;
    }
  }
}
