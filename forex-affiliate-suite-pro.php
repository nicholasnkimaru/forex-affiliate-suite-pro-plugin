<?php
if (!defined('FASP_VERSION')) define('FASP_VERSION', 'r14.8');
require_once __DIR__ . '/includes/menu-unifier.php';

/**
 * Plugin Name: Forex Affiliate Suite PRO
 * Description: Unified affiliate funnels: Resources, Coaches, Landings, Ads/Pixels, Payments, Geo, Platform Gating, Analytics, Dashboard, Compliance.
 * Version: r14.8
 * Author: Pashicom AppTech
 * License: GPLv2 or later
 */
if (!defined('ABSPATH')) exit;

define('FASP_VER','1.0.0');
define('FASP_PATH', plugin_dir_path(__FILE__));
define('FASP_URL',  plugin_dir_url(__FILE__));

add_action('wp_enqueue_scripts', function(){
  wp_register_style('fasp-front', FASP_URL.'assets/css/front.css', [], FASP_VER);
});

register_activation_hook(__FILE__, function(){
  add_rewrite_endpoint('forex-dashboard', EP_ROOT|EP_PAGES);
  if (function_exists('flush_rewrite_rules')) flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ if (function_exists('flush_rewrite_rules')) flush_rewrite_rules(); });

require_once FASP_PATH.'includes/helpers.php';
require_once FASP_PATH.'includes/admin.php';
require_once FASP_PATH.'includes/admin-menu-hubs.php';

require_once FASP_PATH.'includes/coaches-cpt.php';
require_once FASP_PATH.'includes/resources-cpt.php';
require_once FASP_PATH.'includes/resources-ads-gallery-gate.php';
require_once FASP_PATH.'includes/resources-cta-ab.php';
require_once FASP_PATH.'includes/resources-gating-platforms.php';
require_once FASP_PATH.'includes/resources-pixels-webhook.php';
require_once FASP_PATH.'includes/dev-diagnostics.php';


require_once FASP_PATH.'includes/resources-templates.php';
require_once FASP_PATH.'includes/woo.php';
//require_once FASP_PATH.'includes/payments.php';
require_once FASP_PATH.'includes/geo-rules.php';
require_once FASP_PATH.'includes/platforms-visibility.php';
require_once FASP_PATH.'includes/platforms-gating.php';

require_once FASP_PATH.'includes/ads-kses.php';
require_once FASP_PATH.'includes/ads-tracking.php';
require_once FASP_PATH.'includes/content-injector.php';

require_once FASP_PATH.'includes/tracking.php';
require_once FASP_PATH.'includes/analytics.php';
require_once FASP_PATH.'includes/creative-helper.php';
require_once FASP_PATH.'includes/attribution.php';
require_once FASP_PATH.'includes/anti-fraud.php';
require_once FASP_PATH.'includes/seo.php';
require_once FASP_PATH.'includes/email-integration.php';
require_once FASP_PATH.'includes/onboarding.php';

require_once FASP_PATH.'includes/cta-frontend.php';
require_once FASP_PATH.'includes/dashboard.php';
require_once FASP_PATH.'includes/users-verify.php';
require_once FASP_PATH.'includes/deriv-oauth.php';
// Auto-added PLUS features
require_once FASP_PATH.'includes/landings-cpt.php';
require_once FASP_PATH.'includes/compliance.php';
require_once FASP_PATH.'includes/disable-block-editor.php';


require_once FASP_PATH.'includes/seo-coaches.php';

require_once FASP_PATH.'includes/seo-coaches-template-hook.php';

require_once FASP_PATH.'includes/fasp-suite-addons.php';

// FASP Admin UI (light theme)
add_action('admin_enqueue_scripts', function($hook){
    if (strpos($hook,'fasp') !== false || (isset($_GET['post_type']) && strpos($_GET['post_type'],'fasp_')===0)) {
        wp_enqueue_style('fasp-admin-polish', plugins_url('assets/css/fasp-admin.css', __FILE__), [], '0905r8');
    }
});

require_once __DIR__ . '/includes/admin-menu-hub.php';
require_once __DIR__ . '/includes/admin-platforms.php';
require_once __DIR__ . '/includes/routes-platforms.php';
require_once __DIR__ . '/includes/cpt-resources.php';
require_once __DIR__ . '/includes/cpt-coaches.php';
require_once __DIR__ . '/includes/admin-settings.php';
require_once __DIR__ . '/includes/tracking.php';
require_once __DIR__ . '/includes/gating.php';
require_once __DIR__ . '/includes/woocommerce-dashboard.php';
require_once __DIR__ . '/includes/shortcodes-join.php';
require_once __DIR__ . '/includes/shortcodes-resources.php';
require_once __DIR__ . '/includes/deriv-oauth.php';
require_once __DIR__ . '/includes/admin-placeholders.php';
require_once __DIR__ . '/includes/coach-template-loader.php';

require_once __DIR__ . '/includes/shortcodes-coaches.php';

require_once __DIR__ . '/includes/ext/platforms-columns-inject.php';
require_once __DIR__ . '/includes/classic-editor.php';
require_once __DIR__ . '/includes/coaches-photo-panel.php';
require_once __DIR__ . '/includes/resources-gallery-landing.php';
require_once __DIR__ . '/includes/payments-extensions.php';
require_once __DIR__ . '/includes/ads-analytics.php';
require_once __DIR__ . '/includes/admin-marketing.php';
require_once __DIR__ . '/includes/admin-attribution-report.php';
require_once __DIR__ . '/includes/shortcode-consent.php';
require_once __DIR__ . '/includes/events-queue.php';
require_once __DIR__ . '/includes/admin-attribution-v2.php';
require_once __DIR__ . '/includes/events-senders.php';
require_once __DIR__ . '/includes/admin-variants.php';
require_once __DIR__ . '/includes/mark-actions.php';
require_once __DIR__ . '/includes/variant-capture.php';
require_once __DIR__ . '/includes/alerts-cron.php';
require_once __DIR__ . '/includes/admin-audiences.php';
require_once __DIR__ . '/includes/shortcode-variant-router.php';
require_once __DIR__ . '/includes/variants-winner-cron.php';
require_once __DIR__ . '/includes/gads-clickid-capture.php';
require_once __DIR__ . '/includes/events-sender-tiktok.php';
require_once __DIR__ . '/includes/admin-cohorts.php';
require_once __DIR__ . '/includes/stripe-checkout.php';

require_once __DIR__ . '/includes/meta-fbp-fbc-capture.php';
require_once __DIR__ . '/includes/resource-template-loader.php';
require_once __DIR__ . '/includes/admin-gads-offline.php';
require_once __DIR__ . '/includes/admin-creatives-lab.php';
require_once __DIR__ . '/includes/consent-soft.php';
// Payments admin loader — prefer unified file, fall back to legacy shim if present.
$__pay_files = [
    __DIR__ . '/includes/fasp-admin-payments.php',   // unified
    
];
foreach ($__pay_files as $__pf) {
    if (file_exists($__pf)) { require_once $__pf; break; }
}
unset($__pay_files, $__pf);
require_once __DIR__ . '/includes/payments-endpoints.php';

/** FASP classic editor globally */
add_filter('use_block_editor_for_post_type','__return_false',100);
add_filter('use_widgets_block_editor','__return_false',100);


// FASP Pro upgrades loader (safe if files missing)
foreach (['includes/admin-bootstrap.php','includes/admin-payments-pro.php','includes/admin-explainers.php'] as $__fasp_addin){
  $abs = __DIR__ . '/' . $__fasp_addin;
  if (file_exists($abs)) { require_once $abs; }
}


require_once __DIR__ . '/includes/admin-capability-guard.php';

// FASP v3plus bake loader
if (function_exists('add_action')) { require_once __DIR__.'/includes/helpers-core.php'; require_once __DIR__.'/includes/tracking-bake.php'; require_once __DIR__.'/includes/admin-menu-augment.php'; // Payments admin loader — prefer unified file, fall back to legacy shim if present.
$__pay_files = [
    __DIR__ . '/includes/fasp-admin-payments.php',   // unified
    
];
foreach ($__pay_files as $__pf) {
    if (file_exists($__pf)) { require_once $__pf; break; }
}
unset($__pay_files, $__pf); require_once __DIR__.'/includes/admin-creatives-lab-impl.php'; require_once __DIR__.'/includes/diagnostics.php'; require_once __DIR__.'/includes/routes-frontend.php'; require_once __DIR__.'/includes/reports.php'; require_once __DIR__.'/includes/tools.php'; require_once __DIR__.'/includes/user-dash.php'; }

require_once plugin_dir_path(__FILE__) . 'includes/fasp-admin-menu.php';

require_once plugin_dir_path(__FILE__) . 'includes/fasp-admin-payments.php';

require_once plugin_dir_path(__FILE__) . 'includes/fasp-shortcodes.php';

require_once plugin_dir_path(__FILE__) . 'includes/fasp-webhooks.php';

require_once plugin_dir_path(__FILE__) . 'includes/fasp-live-checkout.php';

require_once plugin_dir_path(__FILE__) . 'includes/fasp-daraja.php';
