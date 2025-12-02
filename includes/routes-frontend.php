<?php
if (!defined('ABSPATH')) exit;
add_action('init', function(){
  add_rewrite_rule('^fasp-go/([a-z0-9\-]+)/?','index.php?fasp_go=1&fasp_slug=$matches[1]','top');
  add_rewrite_tag('%fasp_go%','([0-1])'); add_rewrite_tag('%fasp_slug%','([a-z0-9\-]+)');
});
add_action('template_redirect', function(){
  if (get_query_var('fasp_go')){
    $slug = sanitize_title(get_query_var('fasp_slug'));
    $url = home_url('/');
    $platforms = get_option('fasp_platforms', array('deriv'=>array('affiliate_url'=>home_url('/'))));
    if ($slug && isset($platforms[$slug])){
      $p = $platforms[$slug];
      $url = !empty($p['affiliate_url']) ? $p['affiliate_url'] : (!empty($p['signup_url']) ? $p['signup_url'] : $url);
      if (function_exists('fasp_log_click')) fasp_log_click($slug,'click',$url);
    }
    wp_safe_redirect($url); exit;
  }
});
add_action('init', function(){ add_rewrite_tag('%fasp_webhook%','([^&]+)'); });
add_action('template_redirect', function(){
  if (!isset($_GET['fasp_webhook'])) return;
  $provider = sanitize_key($_GET['fasp_webhook']);
  $raw = file_get_contents('php://input');
  do_action('fasp_webhook_'.$provider, $raw, $_REQUEST, function_exists('getallheaders') ? getallheaders() : array());
  status_header(200); echo 'OK'; exit;
});
add_shortcode('fasp_checkout_buttons', function($atts){
  // Use canonical accessor if available
  $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
  $raw = get_option('fasp_payments', array('env'=>'sandbox'));
  $env = esc_html($payments['mpesa']['env'] ?? ($raw['env'] ?? 'sandbox'));
  ob_start(); ?>
  <div class="fasp-wrap">
    <h3>Complete Payment</h3>
    <p class="fasp-muted">Environment: <strong><?php echo $env; ?></strong></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if (!empty($payments['paypal']['email']) || !empty($payments['paypal']['client'])): ?>
        <a class="fasp-button" href="<?php echo esc_url( add_query_arg('fasp_checkout','paypal', home_url('/')) ); ?>">Pay with PayPal</a>
      <?php endif; ?>
      <?php if (!empty($payments['stripe']['pk'])): ?>
        <a class="fasp-button" href="<?php echo esc_url( add_query_arg('fasp_checkout','stripe', home_url('/')) ); ?>">Pay with Card (Stripe)</a>
      <?php endif; ?>
      <?php if (!empty($payments['flutterwave']['public'])): ?>
        <a class="fasp-button" href="<?php echo esc_url( add_query_arg('fasp_checkout','flutter', home_url('/')) ); ?>">Pay with Flutterwave</a>
      <?php endif; ?>
      <?php if (!empty($payments['paystack']['public'])): ?>
        <a class="fasp-button" href="<?php echo esc_url( add_query_arg('fasp_checkout','paystack', home_url('/')) ); ?>">Pay with Paystack</a>
      <?php endif; ?>
      <?php if (!empty($payments['mpesa']['till']) || !empty($payments['mpesa']['paybill'])): ?>
        <a class="fasp-button" href="<?php echo esc_url( add_query_arg('fasp_checkout','mpesa', home_url('/')) ); ?>">Pay with M-Pesa</a>
      <?php endif; ?>
    </div>
  </div>
  <?php return ob_get_clean();
});
add_action('template_redirect', function(){
  if (empty($_GET['fasp_checkout'])) return;
  $method = sanitize_key(wp_unslash($_GET['fasp_checkout']));
  $ok_url = home_url('/?fasp_payment=ok&via='.$method);
  wp_safe_redirect($ok_url); exit;
});
