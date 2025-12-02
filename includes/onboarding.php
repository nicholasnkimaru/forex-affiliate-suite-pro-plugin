<?php if (!defined('ABSPATH')) exit;
function fasp_render_onboarding(){
  if(!current_user_can('manage_options')) wp_die('Unauthorized');
  $woo_ok=class_exists('WooCommerce');
  $pixels_ok=get_option('fasp_fb_pixel_id')||get_option('fasp_gtag_id');
  $geo_ok=get_option('fasp_geo_enabled');
  echo '<div class="wrap"><h1>Getting Started</h1><ol>';
  echo '<li>'.($woo_ok?'✅':'❌').' Configure WooCommerce gateways (e.g., M-Pesa) under Woo settings.</li>';
  echo '<li>'.($pixels_ok?'✅':'❌').' Set FB/GA IDs in Resources → Ads & Tracking.</li>';
  echo '<li>'.($geo_ok?'✅':'❌').' Configure Geo Rules if you need gateway-by-country.</li>';
  echo '<li>Set Platform Setup methods (OAuth/Webhook/Manual) per platform.</li>';
  echo '<li>Create your first Resource with gallery, A/B CTA and gating.</li>';
  echo '</ol></div>';
}
