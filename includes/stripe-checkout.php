<?php
if (!defined('ABSPATH')) exit;

/**
 * Get Stripe API keys from normalized payments settings.
 *
 * @return array [publishable_key, secret_key]
 */
function fasp_stripe_keys(){
  $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
  $pk = isset($payments['stripe']['pk']) ? trim($payments['stripe']['pk']) : '';
  $sk = isset($payments['stripe']['sk']) ? trim($payments['stripe']['sk']) : '';
  return array($pk, $sk);
}

// Shortcode: [fasp_checkout amount="9.99" currency="USD" description="eBook" success="/thank-you"]
add_shortcode('fasp_checkout', function($atts){
  $a = shortcode_atts(array('amount'=>'0','currency'=>'USD','description'=>'','success'=>'/'), $atts, 'fasp_checkout');
  list($pk, $sk) = fasp_stripe_keys();
  if (!$pk || !$sk) return '<div class="fasp-muted">Stripe not configured.</div>';
  $amt = floatval($a['amount']);
  if ($amt <= 0) return '<div class="fasp-muted">Invalid amount.</div>';
  $nonce = wp_create_nonce('fasp_checkout_create');
  ob_start(); ?>
  <form method="post">
    <input type="hidden" name="fasp_checkout_create" value="1">
    <input type="hidden" name="amount" value="<?php echo esc_attr($amt); ?>">
    <input type="hidden" name="currency" value="<?php echo esc_attr($a['currency']); ?>">
    <input type="hidden" name="description" value="<?php echo esc_attr($a['description']); ?>">
    <input type="hidden" name="success" value="<?php echo esc_url($a['success']); ?>">
    <?php wp_nonce_field('fasp_checkout_create','fasp_checkout_nonce'); ?>
    <button class="button button-primary">Pay <?php echo esc_html($a['currency']); ?> <?php echo esc_html(number_format($amt,2)); ?></button>
  </form>
  <?php return ob_get_clean();
});

add_action('init', function(){
  if (!empty($_POST['fasp_checkout_create']) && isset($_POST['fasp_checkout_nonce']) && wp_verify_nonce($_POST['fasp_checkout_nonce'],'fasp_checkout_create')){
    list($pk, $sk) = fasp_stripe_keys();
    if (!$pk || !$sk) wp_die('Stripe not configured');
    $amt = floatval($_POST['amount']);
    $cur = sanitize_text_field($_POST['currency']);
    $desc = sanitize_text_field($_POST['description']);
    $succ = esc_url_raw(home_url(isset($_POST['success']) ? $_POST['success'] : '/'));
    $args = array(
      'timeout' => 10,
      'headers' => array('Authorization' => 'Bearer ' . $sk, 'Content-Type' => 'application/x-www-form-urlencoded'),
      'body' => http_build_query(array(
        'success_url' => add_query_arg(array('fasp_paid' => '1'), $succ),
        'cancel_url' => home_url('/'),
        'mode' => 'payment',
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => $cur,
        'line_items[0][price_data][product_data][name]' => $desc ? $desc : 'Purchase',
        'line_items[0][price_data][unit_amount]' => intval(round($amt * 100)),
        'line_items[0][quantity]' => 1
      ))
    );
    $resp = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', $args);
    if (is_wp_error($resp)) {
      if (function_exists('fasp_log')) {
        fasp_log('Stripe shortcode checkout error: ' . $resp->get_error_message(), 'error');
      }
      wp_die('Payment service error');
    }
    $js = json_decode(wp_remote_retrieve_body($resp), true);
    if (!empty($js['url'])) {
      wp_safe_redirect($js['url']);
      exit;
    }
    if (function_exists('fasp_log')) {
      fasp_log('Stripe shortcode checkout failed: ' . wp_json_encode($js), 'error');
    }
    wp_die('Payment initialization failed');
  }
  // On success, mark purchase + fire events
  if (!empty($_GET['fasp_paid'])){
    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
    $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
    do_action('fasp/event/purchase', array(
      'event_id' => 'purchase',
      'custom_data' => array(
        'currency' => $currency,
        'value' => $amount
      )
    ));
  }
});

/**
 * Localize AJAX nonce for front-end Stripe checkout scripts.
 */
add_action('wp_enqueue_scripts', function(){
  wp_localize_script('jquery', 'faspStripeCheckout', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('fasp_ajax'),
  ));
}, 20);
