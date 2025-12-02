<?php if (!defined('ABSPATH')) exit;
add_action('init', function(){ add_rewrite_endpoint('forex-dashboard', EP_ROOT|EP_PAGES); });
add_action('woocommerce_account_forex-dashboard_endpoint', function(){
  $u=wp_get_current_user();
  echo '<h2>Forex Affiliate Dashboard</h2><p>Welcome, '.esc_html($u->display_name ?: $u->user_login).'.</p>';
  $plats=function_exists('fasp_get_platforms')?fasp_get_platforms():[];
  echo '<h3>Your Platform Verifications</h3><table class="shop_table"><thead><tr><th>Platform</th><th>Status</th><th>Action</th></tr></thead><tbody>';
  foreach ($plats as $p){ $k=sanitize_key($p['key']); $name=esc_html($p['name']); $ok=function_exists('fasp_is_user_verified_for_platform')?fasp_is_user_verified_for_platform(get_current_user_id(),$k):false; $act='';
    if ($k==='deriv'){ $auth=function_exists('fasp_deriv_authorize_url')?fasp_deriv_authorize_url():''; if ($auth) $act='<a class="button" href="'.esc_url($auth).'">Verify with Deriv</a>'; }
    echo '<tr><td>'.$name.' <code>'.$k.'</code></td><td>'.($ok?'✅ Verified':'❌ Not verified').'</td><td>'.$act.'</td></tr>'; }
  echo '</tbody></table>';
});
