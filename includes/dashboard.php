<?php if (!defined('ABSPATH')) exit;

if ( ! is_admin() && ! ( defined('DOING_AJAX') && DOING_AJAX ) ) {
    // Allow the file to continue if it is intended to run on frontend, otherwise run minimal logic.
    // We will still render the neutral title via the template below.
}

add_action('init', function(){ add_rewrite_endpoint('forex-dashboard', EP_ROOT|EP_PAGES); });
add_action('woocommerce_account_forex-dashboard_endpoint', function(){
  $u=wp_get_current_user();

  $page_title_front = __( 'Forex Trading Dashboard', 'fasp' );
  $page_title_admin = __( 'Forex Affiliate Dashboard', 'fasp' );

  // Determine where we are and select title
  if ( is_admin() ) {
    $page_title = $page_title_admin;
  } else {
    $page_title = $page_title_front;
  }

  echo '<h2>' . esc_html( $page_title ) . '</h2>';
  echo '<p>Welcome, '.esc_html($u->display_name ?: $u->user_login).'.</p>';
  $plats=function_exists('fasp_get_platforms')?fasp_get_platforms():[];
  echo '<h3>Your Platform Verifications</h3><table class="shop_table"><thead><tr><th>Platform</th><th>Status</th><th>Action</th></tr></thead><tbody>';
  foreach ($plats as $p){ $k=isset($p['key']) ? sanitize_key($p['key']) : ''; $name=isset($p['name']) ? esc_html($p['name']) : ''; $ok=function_exists('fasp_is_user_verified_for_platform')?fasp_is_user_verified_for_platform(get_current_user_id(),$k):false; $act='';
    if ($k==='deriv'){ $auth=function_exists('fasp_deriv_authorize_url')?fasp_deriv_authorize_url():''; if ($auth) $act='<a class="button" href="'.esc_url($auth).'">Verify with Deriv</a>'; }
    echo '<tr><td>'.$name.' <code>'.$k.'</code></td><td>'.($ok?'✅ Verified':'❌ Not verified').'</td><td>'.$act.'</td></tr>'; }
  echo '</tbody></table>';
});
