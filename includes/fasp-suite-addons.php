<?php if (!defined('ABSPATH')) exit; if(!defined('FASP_SUITE_ADDONS')) define('FASP_SUITE_ADDONS', true);
/**
 * FASP Suite Addons — Platform Setup + Payments (M-Pesa STK w/ auth, PayPal REST + Smart Buttons, Stripe Checkout verification)
 * + Creative + Users Verification
 */

function fasp_log($m){ if (class_exists('WC_Logger')){ $l=new WC_Logger(); $l->add('fasp', is_string($m)?$m:wp_json_encode($m)); } }

/* ---------------- Platforms store ---------------- */
function fasp_platforms_get(){ $l=get_option('fasp_platforms', array()); return is_array($l)?$l:array(); }
function fasp_platforms_set($l){ if(!is_array($l))$l=array(); update_option('fasp_platforms',$l); }
if (!function_exists('fasp_get_platforms')){
  function fasp_get_platforms(){
    $l=fasp_platforms_get(); if (!empty($l)) return $l;
    return array(array('key'=>'deriv','name'=>'Deriv','link'=>'','enabled'=>1,'visible'=>1,'show_clicks'=>1));
  }
}

/* ---------------- Platform Setup page ---------------- */
add_action('admin_menu', function(){

}, 50);

if (!function_exists('fasp_render_platform_setup_page')) { function fasp_render_platform_setup_page(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  if (isset($_POST['fasp_save_platforms']) && check_admin_referer('fasp_save_platforms','fasp_save_platforms_nonce')){
    $in = isset($_POST['plat'])?$_POST['plat']:array(); $out=array();
    if (is_array($in)){
      foreach ($in as $row){
        $key = sanitize_key($row['key'] ?? '');
        $name = sanitize_text_field($row['name'] ?? '');
        if (!$key || !$name) continue;
        $out[] = array(
          'key'=>$key,
          'name'=>$name,
          'link'=>esc_url_raw($row['link'] ?? ''),
          'enabled'=> !empty($row['enabled'])?1:0,
          'visible'=> !empty($row['visible'])?1:0,
          'show_clicks'=> !empty($row['show_clicks'])?1:0,
        );
      }
    }
    fasp_platforms_set($out);
    $s = get_option('fasp_settings', array()); if (!is_array($s)) $s=array();
    $s['deriv_app_id'] = sanitize_text_field($_POST['deriv_app_id'] ?? '');
    $s['deriv_client_secret'] = sanitize_text_field($_POST['deriv_client_secret'] ?? '');
    update_option('fasp_settings',$s);
    $g = get_option('fasp_platform_gating', array()); if (!is_array($g)) $g=array();
    if (!isset($g['deriv'])) $g['deriv']=array();
    $g['deriv']['webhook_url'] = esc_url_raw($_POST['deriv_webhook_url'] ?? '');
    $g['deriv']['webhook_auth'] = sanitize_text_field($_POST['deriv_webhook_auth'] ?? '');
    $g['deriv']['fail_open'] = !empty($_POST['deriv_fail_open'])?1:0;
    update_option('fasp_platform_gating',$g);
    echo '<div class="updated"><p>Settings saved.</p></div>';
  }

  if (isset($_POST['fasp_add_platform']) && check_admin_referer('fasp_add_platform','fasp_add_platform_nonce')){
    $k = sanitize_key($_POST['new_key'] ?? '');
    $n = sanitize_text_field($_POST['new_name'] ?? '');
    $lnk = esc_url_raw($_POST['new_link'] ?? '');
    if ($k && $n){
      $list = fasp_get_platforms();
      $list[] = array('key'=>$k,'name'=>$n,'link'=>$lnk,'enabled'=>1,'visible'=>1,'show_clicks'=>1);
      fasp_platforms_set($list);
      echo '<div class="updated"><p>Platform added.</p></div>';
    }
  }

  $plats = fasp_get_platforms();
  $s = get_option('fasp_settings', array());
  $g = get_option('fasp_platform_gating', array());
  $deriv = isset($g['deriv'])? $g['deriv'] : array();
  $cb = esc_html(add_query_arg('fasp_deriv_oauth','1', home_url('/')));

  echo '<div class="wrap"><h1>Platform Setup</h1>';
  echo '<form method="post">'; wp_nonce_field('fasp_save_platforms','fasp_save_platforms_nonce');
  echo '<input type="hidden" name="fasp_save_platforms" value="1">';

  echo '<h2>Platforms</h2>';
  echo '<table class="widefat striped"><thead><tr><th>Key</th><th>Name</th><th>Link</th><th>Enabled</th><th>Visible</th><th>Show Clicks</th></tr></thead><tbody>';
  foreach ($plats as $p){
    $key = esc_attr($p['key'] ?? '');
    $name = esc_attr($p['name'] ?? '');
    $lnk = esc_attr($p['link'] ?? '');
    $en = !empty($p['enabled']) ? 'checked' : '';
    $vi = !empty($p['visible']) ? 'checked' : '';
    $sc = !empty($p['show_clicks']) ? 'checked' : '';
    echo '<tr>';
    echo '<td><input type="text" name="plat[][key]" value="'.$key.'" /></td>';
    echo '<td><input type="text" name="plat[][name]" value="'.$name.'" /></td>';
    echo '<td><input type="url"  name="plat[][link]" class="regular-text" value="'.$lnk.'" /></td>';
    echo '<td style="text-align:center"><input type="checkbox" name="plat[][enabled]" value="1" '.$en.' /></td>';
    echo '<td style="text-align:center"><input type="checkbox" name="plat[][visible]" value="1" '.$vi.' /></td>';
    echo '<td style="text-align:center"><input type="checkbox" name="plat[][show_clicks]" value="1" '.$sc.' /></td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  submit_button('Save Settings');
  echo '</form>';

  echo '<h3>Add Platform</h3>';
  echo '<form method="post" style="margin-bottom:20px;">'; wp_nonce_field('fasp_add_platform','fasp_add_platform_nonce');
  echo '<input type="hidden" name="fasp_add_platform" value="1">';
  echo '<p><label>Key <input type="text" name="new_key" required></label> ';
  echo '<label>Name <input type="text" name="new_name" required></label> ';
  echo '<label>Link <input type="url" name="new_link" class="regular-text"></label> ';
  echo '<button class="button">Add Platform</button></p></form>';

  echo '<hr><h2>Deriv OAuth</h2>';
  echo '<table class="form-table"><tbody>';
  echo '<tr><th scope="row">App ID</th><td><input type="text" name="deriv_app_id" class="regular-text" value="'.esc_attr($s['deriv_app_id'] ?? '').'"></td></tr>';
  echo '<tr><th scope="row">Client Secret</th><td><input type="text" name="deriv_client_secret" class="regular-text" value="'.esc_attr($s['deriv_client_secret'] ?? '').'"></td></tr>';
  echo '<tr><th scope="row">Webhook URL</th><td><input type="url" name="deriv_webhook_url" class="regular-text" value="'.esc_attr($deriv['webhook_url'] ?? '').'"></td></tr>';
  echo '<tr><th scope="row">Webhook Auth</th><td><input type="text" name="deriv_webhook_auth" class="regular-text" value="'.esc_attr($deriv['webhook_auth'] ?? '').'"></td></tr>';
  echo '<tr><th scope="row">Fail open if webhook fails</th><td><label><input type="checkbox" name="deriv_fail_open" value="1" '.checked(!empty($deriv['fail_open']),1,false).'> Allow verification to succeed if webhook fails.</label></td></tr>';
  echo '<tr><th scope="row">Callback URL</th><td><code>'.$cb.'</code></td></tr>';
  echo '</tbody></table>';

  echo '<form method="post" style="margin-top:10px;">'; wp_nonce_field('fasp_save_platforms','fasp_save_platforms_nonce');
  echo '<input type="hidden" name="fasp_save_platforms" value="1">';
  submit_button('Save Settings');
  echo '</form>';

  echo '</div>'; }
} // end guard platform
/* ---------------- Payments & Gateways (M-Pesa, PayPal, Stripe) ---------------- */
add_action('admin_menu', function(){

}, 51);

function fasp_get_gateways_cfg(){ $g=get_option('fasp_gateways', array()); return is_array($g)?$g:array(); }
function fasp_save_gateways_cfg($g){ if(!is_array($g))$g=array(); update_option('fasp_gateways', $g); }

if (!function_exists('fasp_admin_payments_screen_setup')) { function fasp_admin_payments_screen_setup(){
  if(!current_user_can('manage_options')) wp_die('Unauthorized');
  $g=fasp_get_gateways_cfg();

  if (isset($_POST['fasp_pay_save']) && check_admin_referer('fasp_pay_save','fasp_pay_save_nonce')){
    // M-Pesa
    $mp = isset($g['mpesa']) && is_array($g['mpesa']) ? $g['mpesa'] : array();
    $mp['enabled'] = !empty($_POST['mpesa_enabled']) ? 1 : 0;
    $mp['env'] = (isset($_POST['mpesa_env']) && in_array($_POST['mpesa_env'], array('sandbox','live'), true)) ? $_POST['mpesa_env'] : 'sandbox';
    $mp['consumer_key'] = sanitize_text_field($_POST['mpesa_consumer_key']);
    $mp['consumer_secret'] = sanitize_text_field($_POST['mpesa_consumer_secret']);
    $mp['shortcode'] = sanitize_text_field($_POST['mpesa_shortcode']);
    $mp['passkey'] = sanitize_text_field($_POST['mpesa_passkey']);
    $mp['account_ref'] = sanitize_text_field($_POST['mpesa_account_ref']);
    $mp['callback_secret'] = sanitize_text_field($_POST['mpesa_callback_secret']);
    $g['mpesa']=$mp;

    // PayPal
    $pp = isset($g['paypal']) && is_array($g['paypal']) ? $g['paypal'] : array();
    $pp['enabled'] = !empty($_POST['pp_enabled']) ? 1 : 0;
    $pp['env'] = (isset($_POST['pp_env']) && in_array($_POST['pp_env'], array('sandbox','live'), true)) ? $_POST['pp_env'] : 'sandbox';
    $pp['client_id'] = sanitize_text_field($_POST['pp_client_id']);
    $pp['client_secret'] = sanitize_text_field($_POST['pp_client_secret']);
    $pp['brand'] = sanitize_text_field($_POST['pp_brand']);
    $pp['smart_buttons'] = !empty($_POST['pp_smart_buttons']) ? 1 : 0;
    $g['paypal']=$pp;

    // Stripe
    $st = isset($g['stripe']) && is_array($g['stripe']) ? $g['stripe'] : array();
    $st['enabled'] = !empty($_POST['st_enabled']) ? 1 : 0;
    $st['secret_key'] = sanitize_text_field($_POST['st_secret_key']);
    $st['publishable_key'] = sanitize_text_field($_POST['st_publishable_key']);
    $g['stripe']=$st;

    fasp_save_gateways_cfg($g);
    echo '<div class="updated"><p>Gateway settings saved.</p></div>';
  }

  $mp = isset($g['mpesa']) ? $g['mpesa'] : array();
  $pp = isset($g['paypal']) ? $g['paypal'] : array();
  $st = isset($g['stripe']) ? $g['stripe'] : array();
  $cb_mpesa = esc_html( home_url('/?fasp_mpesa_callback=1') );
  $cb_pp = esc_html( home_url('/?fasp_paypal_return=1') );
  $cb_pp_cancel = esc_html( home_url('/?fasp_paypal_cancel=1') );
  $cb_st_ok = esc_html( home_url('/?fasp_stripe_success=1') );
  $cb_st_cancel = esc_html( home_url('/?fasp_stripe_cancel=1') );

  echo '<div class="wrap"><h1>Payments & Gateways</h1>';

  echo '<h2>M-Pesa (Daraja STK Push)</h2>';
  echo '<form method="post">'; wp_nonce_field('fasp_pay_save','fasp_pay_save_nonce');
  echo '<input type="hidden" name="fasp_pay_save" value="1">';
  echo '<p><label><input type="checkbox" name="mpesa_enabled" value="1" '.checked(!empty($mp['enabled']),1,false).'> Enable M-Pesa Gateway</label></p>';
  echo '<p><label>Environment <select name="mpesa_env"><option value="sandbox" '.selected(($mp['env']??'sandbox'),'sandbox',false).'>Sandbox</option><option value="live" '.selected(($mp['env']??'sandbox'),'live',false).'>Live</option></select></label></p>';
  echo '<p><label>Consumer Key <input type="text" name="mpesa_consumer_key" value="'.esc_attr($mp['consumer_key'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Consumer Secret <input type="text" name="mpesa_consumer_secret" value="'.esc_attr($mp['consumer_secret'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Shortcode <input type="text" name="mpesa_shortcode" value="'.esc_attr($mp['shortcode'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Passkey <input type="text" name="mpesa_passkey" value="'.esc_attr($mp['passkey'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Account Reference <input type="text" name="mpesa_account_ref" value="'.esc_attr($mp['account_ref'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Callback Secret <input type="text" name="mpesa_callback_secret" value="'.esc_attr($mp['callback_secret'] ?? '').'" class="regular-text" placeholder="Optional shared secret for callback auth"></label></p>';
  echo '<p><strong>Callback URL:</strong> <code>'.$cb_mpesa.'</code></p>';

  echo '<hr><h2>PayPal</h2>';
  echo '<p><label><input type="checkbox" name="pp_enabled" value="1" '.checked(!empty($pp['enabled']),1,false).'> Enable PayPal</label></p>';
  echo '<p><label>Environment <select name="pp_env"><option value="sandbox" '.selected(($pp['env']??'sandbox'),'sandbox',false).'>Sandbox</option><option value="live" '.selected(($pp['env']??'sandbox'),'live',false).'>Live</option></select></label></p>';
  echo '<p><label>Client ID <input type="text" name="pp_client_id" value="'.esc_attr($pp['client_id'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Client Secret <input type="text" name="pp_client_secret" value="'.esc_attr($pp['client_secret'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Brand Name <input type="text" name="pp_brand" value="'.esc_attr($pp['brand'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label><input type="checkbox" name="pp_smart_buttons" value="1" '.checked(!empty($pp['smart_buttons']),1,false).'> Show PayPal Smart Buttons on order-pay page</label></p>';
  echo '<p><strong>Return URL:</strong> <code>'.$cb_pp.'</code> &nbsp; <strong>Cancel URL:</strong> <code>'.$cb_pp_cancel.'</code></p>';

  echo '<hr><h2>Card (Stripe Checkout)</h2>';
  echo '<p><label><input type="checkbox" name="st_enabled" value="1" '.checked(!empty($st['enabled']),1,false).'> Enable Stripe Card</label></p>';
  echo '<p><label>Secret Key <input type="text" name="st_secret_key" value="'.esc_attr($st['secret_key'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><label>Publishable Key <input type="text" name="st_publishable_key" value="'.esc_attr($st['publishable_key'] ?? '').'" class="regular-text"></label></p>';
  echo '<p><strong>Success URL:</strong> <code>'.$cb_st_ok.'</code> &nbsp; <strong>Cancel URL:</strong> <code>'.$cb_st_cancel.'</code></p>';

  submit_button('Save All Gateway Settings'); echo '</form></div>'; }
} // end guard
/* --------------- WooCommerce Gateways --------------- */
/* Helpers */
function fasp_wc_currency(){ return function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD'; }
function fasp_wc_amount_cents($amount){ return intval(round(floatval($amount)*100)); }

/* M-Pesa API */
function fasp_mpesa_api_base($env){ return $env==='live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke'; }
function fasp_mpesa_get_token($cfg){
  $base = fasp_mpesa_api_base($cfg['env'] ?? 'sandbox');
  $url = $base.'/oauth/v1/generate?grant_type=client_credentials';
  $auth = base64_encode(($cfg['consumer_key'] ?? '').':'.($cfg['consumer_secret'] ?? ''));
  $res = wp_remote_get($url, array('headers'=>array('Authorization'=>'Basic '.$auth), 'timeout'=>10));
  if (is_wp_error($res)) return '';
  $json = json_decode(wp_remote_retrieve_body($res), true);
  return isset($json['access_token']) ? $json['access_token'] : '';
}
function fasp_mpesa_stk_push($cfg, $amount, $phone, $order_id){
  $token = fasp_mpesa_get_token($cfg); if (!$token) return array('ok'=>0,'msg'=>'Token error');
  $base = fasp_mpesa_api_base($cfg['env'] ?? 'sandbox');
  $url = $base.'/mpesa/stkpush/v1/processrequest';
  $ts = current_time('YmdHis', true);
  $password = base64_encode(($cfg['shortcode'] ?? '').($cfg['passkey'] ?? '').$ts);
  $payload = array(
    'BusinessShortCode'=> $cfg['shortcode'] ?? '',
    'Password'=> $password,
    'Timestamp'=> $ts,
    'TransactionType'=> 'CustomerPayBillOnline',
    'Amount'=> intval($amount),
    'PartyA'=> $phone,
    'PartyB'=> $cfg['shortcode'] ?? '',
    'PhoneNumber'=> $phone,
    'CallBackURL'=> home_url('/?fasp_mpesa_callback=1'),
    'AccountReference'=> $cfg['account_ref'] ?? ('ORDER'.$order_id),
    'TransactionDesc'=> 'Order '.$order_id
  );
  $res = wp_remote_post($url, array(
    'headers'=>array('Authorization'=>'Bearer '.$token, 'Content-Type'=>'application/json'),
    'body'=>wp_json_encode($payload), 'timeout'=>15
  ));
  if (is_wp_error($res)){ return array('ok'=>0,'msg'=>$res->get_error_message()); }
  $json = json_decode(wp_remote_retrieve_body($res), true);
  if (!empty($json['ResponseCode']) && intval($json['ResponseCode'])===0){
    return array('ok'=>1,'CheckoutRequestID'=>$json['CheckoutRequestID'] ?? '', 'MerchantRequestID'=>$json['MerchantRequestID'] ?? '');
  }
  return array('ok'=>0,'msg'=> wp_remote_retrieve_body($res));
}
function fasp_mpesa_wc_gateway_init(){
  if (!class_exists('WC_Payment_Gateway')) return;
  class WC_Gateway_FASP_Mpesa extends WC_Payment_Gateway {
    public function __construct(){
      $this->id='fasp_mpesa'; $this->icon=''; $this->has_fields=true;
      $this->method_title='FASP M-Pesa';
      $this->method_description='M-Pesa STK Push via Daraja API';
      $this->supports=array('products');
      $this->init_form_fields(); $this->init_settings();
      add_action('woocommerce_update_options_payment_gateways_'.$this->id,array($this,'process_admin_options'));
    }
    public function init_form_fields(){
      $this->form_fields=array(
        'enabled'=>array('title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable FASP M-Pesa','default'=>'yes'),
        'title'=>array('title'=>'Title','type'=>'text','default'=>'M-Pesa'),
        'instructions'=>array('title'=>'Instructions','type'=>'textarea','default'=>'You will receive an M-Pesa prompt on your phone to authorize the payment.'),
      );
    }
    public function payment_fields(){
      echo wpautop( wp_kses_post( $this->get_option('instructions') ) );
      echo '<p><label>Phone (07XXXXXXXX) <input name="fasp_mpesa_phone" type="tel" required></label></p>';
    }
    public function validate_fields(){
      if (empty($_POST['fasp_mpesa_phone'])){ wc_add_notice('Phone number is required for M-Pesa.','error'); return false; }
      return true;
    }
    public function process_payment($order_id){
      $order = wc_get_order($order_id);
      $g = get_option('fasp_gateways', array());
      $cfg = isset($g['mpesa']) ? $g['mpesa'] : array();
      if (empty($cfg['enabled'])){ wc_add_notice('M-Pesa is unavailable.','error'); return; }
      if (get_woocommerce_currency()!=='KES'){ wc_add_notice('M-Pesa requires KES currency.','error'); return; }
      $amount = intval(round($order->get_total()));
      $phone_raw = sanitize_text_field($_POST['fasp_mpesa_phone']);
      $p = preg_replace('/\D+/','', $phone_raw);
      if (substr($p,0,1)=='0'){ $p='254'.substr($p,1); }
      elseif (substr($p,0,3)!='254'){ $p='254'.$p; }
      $res = fasp_mpesa_stk_push($cfg, $amount, $p, $order_id);
      if (!empty($res['ok'])){
        update_post_meta($order_id,'_fasp_mpesa_checkout_id', $res['CheckoutRequestID']);
        update_post_meta($order_id,'_fasp_mpesa_phone', $p);
        $order->update_status('on-hold','Awaiting M-Pesa confirmation');
        WC()->cart->empty_cart();
        return array('result'=>'success','redirect'=>$this->get_return_url($order));
      } else {
        wc_add_notice('M-Pesa error: '.esc_html($res['msg'] ?? 'Unknown'), 'error'); return;
      }
    }
  }
  add_filter('woocommerce_payment_gateways', function($methods){ $methods[]='WC_Gateway_FASP_Mpesa'; return $methods; });
}
add_action('plugins_loaded','fasp_mpesa_wc_gateway_init', 20);

/* M-Pesa callback w/ optional shared secret */
add_action('init', function(){
  if (!isset($_GET['fasp_mpesa_callback'])) return;
  $raw = file_get_contents('php://input');
  $cfg_all=get_option('fasp_gateways', array()); $cfg=$cfg_all['mpesa']??array();
  $secret = $cfg['callback_secret'] ?? '';
  if ($secret){
    $hdr = isset($_SERVER['HTTP_X_FASP_AUTH']) ? $_SERVER['HTTP_X_FASP_AUTH'] : '';
    if (!$hdr || !hash_equals($secret, $hdr)){ status_header(403); echo 'forbidden'; exit; }
  }
  fasp_log(array('mpesa_callback'=>$raw));
  $data = json_decode($raw, true);
  if (!is_array($data)){ status_header(400); echo 'bad json'; exit; }
  $stk = $data['Body']['stkCallback'] ?? array();
  $crid = $stk['CheckoutRequestID'] ?? '';
  $code = intval($stk['ResultCode'] ?? 1);
  $desc = $stk['ResultDesc'] ?? '';
  $items = array();
  if (!empty($stk['CallbackMetadata']['Item']) && is_array($stk['CallbackMetadata']['Item'])){
    foreach ($stk['CallbackMetadata']['Item'] as $it){ $items[$it['Name']] = $it['Value'] ?? ''; }
  }
  $q = new WP_Query(array('post_type'=>'shop_order','posts_per_page'=>1,'meta_key'=>'_fasp_mpesa_checkout_id','meta_value'=>$crid,'post_status'=>'any'));
  if (!$q->have_posts()){ status_header(200); echo 'no order'; exit; }
  $order_id = $q->posts[0]->ID; $order = wc_get_order($order_id);
  if ($code===0){
    $txn = isset($items['MpesaReceiptNumber']) ? $items['MpesaReceiptNumber'] : '';
    $order->payment_complete($txn);
    $order->add_order_note('M-Pesa paid. Receipt: '.$txn);
  } else {
    $order->update_status('failed','M-Pesa failed: '.$desc);
  }
  status_header(200); echo 'ok'; exit;
});

/* PayPal REST */
function fasp_paypal_api_base($env){ return $env==='live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'; }
function fasp_paypal_token($cfg){
  $base=fasp_paypal_api_base($cfg['env']??'sandbox');
  $res = wp_remote_post($base.'/v1/oauth2/token', array(
    'headers'=>array('Authorization'=>'Basic '.base64_encode(($cfg['client_id']??'').':'.($cfg['client_secret']??''))),
    'body'=>array('grant_type'=>'client_credentials'),
    'timeout'=>15
  ));
  if (is_wp_error($res)) return '';
  $j=json_decode(wp_remote_retrieve_body($res), true);
  return $j['access_token'] ?? '';
}
function fasp_paypal_create_order($cfg,$order){
  $token=fasp_paypal_token($cfg); if(!$token) return array('ok'=>0,'msg'=>'token failed');
  $base=fasp_paypal_api_base($cfg['env']??'sandbox');
  $return=add_query_arg(array('fasp_paypal_return'=>1,'order_id'=>$order->get_id(),'key'=>$order->get_order_key()), home_url('/'));
  $cancel=add_query_arg(array('fasp_paypal_cancel'=>1,'order_id'=>$order->get_id(),'key'=>$order->get_order_key()), home_url('/'));
  $payload=array(
    'intent'=>'CAPTURE',
    'purchase_units'=>array(array('amount'=>array('currency_code'=>get_woocommerce_currency(),'value'=>strval($order->get_total())))),
    'application_context'=>array('brand_name'=>$cfg['brand']??get_bloginfo('name'),'return_url'=>$return,'cancel_url'=>$cancel)
  );
  $res=wp_remote_post($base.'/v2/checkout/orders', array(
    'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'),
    'body'=>wp_json_encode($payload),'timeout'=>20
  ));
  if (is_wp_error($res)) return array('ok'=>0,'msg'=>$res->get_error_message());
  $j=json_decode(wp_remote_retrieve_body($res), true);
  $approve=''; if (!empty($j['links'])){ foreach($j['links'] as $ln){ if($ln['rel']==='approve') $approve=$ln['href']; } }
  if ($approve){ return array('ok'=>1,'id'=>$j['id'],'approve'=>$approve,'token'=>$token); }
  return array('ok'=>0,'msg'=>wp_remote_retrieve_body($res));
}
function fasp_paypal_capture_order($cfg,$pp_order_id){
  $token=fasp_paypal_token($cfg); if(!$token) return array('ok'=>0,'msg'=>'token failed');
  $base=fasp_paypal_api_base($cfg['env']??'sandbox');
  $res=wp_remote_post($base.'/v2/checkout/orders/'.$pp_order_id.'/capture', array(
    'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'),
    'timeout'=>20
  ));
  if (is_wp_error($res)) return array('ok'=>0,'msg'=>$res->get_error_message());
  $j=json_decode(wp_remote_retrieve_body($res), true);
  return array('ok'=>1,'data'=>$j);
}

/* Woo PayPal gateway */
add_action('plugins_loaded', function(){
  if (!class_exists('WC_Payment_Gateway')) return;
  class WC_Gateway_FASP_PayPal extends WC_Payment_Gateway {
    public function __construct(){
      $this->id='fasp_paypal'; $this->icon=''; $this->has_fields=false;
      $this->method_title='FASP PayPal';
      $this->method_description='Pay with PayPal (REST v2)';
      $this->supports=array('products');
      $this->init_form_fields(); $this->init_settings();
      add_action('woocommerce_update_options_payment_gateways_'.$this->id,array($this,'process_admin_options'));
    }
    public function init_form_fields(){
      $this->form_fields=array(
        'enabled'=>array('title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable FASP PayPal','default'=>'yes'),
        'title'=>array('title'=>'Title','type'=>'text','default'=>'PayPal'),
      );
    }
    public function process_payment($order_id){
      $order=wc_get_order($order_id);
      $g = get_option('fasp_gateways', array()); $cfg = isset($g['paypal'])?$g['paypal']:array();
      if (empty($cfg['enabled'])){ wc_add_notice('PayPal unavailable.','error'); return; }
      $res = fasp_paypal_create_order($cfg,$order);
      if (!empty($res['ok'])){
        update_post_meta($order_id,'_fasp_paypal_order_id',$res['id']);
        return array('result'=>'success','redirect'=>$res['approve']);
      }
      wc_add_notice('PayPal error: '.esc_html($res['msg'] ?? 'Unknown'),'error'); return;
    }
  }
  add_filter('woocommerce_payment_gateways', function($methods){ $methods[]='WC_Gateway_FASP_PayPal'; return $methods; });
}, 20);

/* PayPal return/cancel */
add_action('init', function(){
  if (isset($_GET['fasp_paypal_cancel'])){
    $oid=intval($_GET['order_id']??0); $key=sanitize_text_field($_GET['key']??'');
    $order=wc_get_order($oid); if ($order && $key===$order->get_order_key()){ $order->update_status('cancelled','PayPal cancelled by user.'); wp_safe_redirect($order->get_cancel_order_url()); exit; }
  }
  if (isset($_GET['fasp_paypal_return'])){
    $oid=intval($_GET['order_id']??0); $key=sanitize_text_field($_GET['key']??''); $order=wc_get_order($oid);
    if ($order && $key===$order->get_order_key()){
      $pp_id=get_post_meta($oid,'_fasp_paypal_order_id',true);
      $cfg = get_option('fasp_gateways', array()); $cfg=$cfg['paypal']??array();
      $cap=fasp_paypal_capture_order($cfg,$pp_id);
      if (!empty($cap['ok'])){ $order->payment_complete($pp_id); $order->add_order_note('PayPal paid.'); wp_safe_redirect($order->get_checkout_order_received_url()); exit; }
      $order->update_status('failed','PayPal capture failed.'); wp_safe_redirect($order->get_cancel_order_url()); exit;
    }
  }
});

/* PayPal Smart Buttons on order-pay */
add_action('woocommerce_before_pay_form', function($order){
  if (!is_a($order,'WC_Order')) return;
  $g=get_option('fasp_gateways', array()); $pp=$g['paypal']??array();
  if (empty($pp['smart_buttons']) || empty($pp['enabled'])) return;
  if ($order->get_payment_method()!=='fasp_paypal') return;
  $env = ($pp['env']??'sandbox')==='live' ? 'production' : 'sandbox';
  $cid = esc_js($pp['client_id'] ?? '');
  $amount = esc_js( (string) $order->get_total() );
  $currency = esc_js( get_woocommerce_currency() );
  $oid = $order->get_id();
  $ok = add_query_arg(array('fasp_paypal_return'=>1,'order_id'=>$oid,'key'=>$order->get_order_key()), home_url('/'));
  $cancel = add_query_arg(array('fasp_paypal_cancel'=>1,'order_id'=>$oid,'key'=>$order->get_order_key()), home_url('/'));
  echo '<div id="fasp-pp-buttons"></div>';
  echo '<script src="https://www.paypal.com/sdk/js?client-id='.$cid.'&currency='.$currency.'&intent=capture"></script>';
  echo '<script>
  paypal.Buttons({
    createOrder: function(data, actions){ return actions.order.create({purchase_units:[{amount:{currency_code:"'.$currency.'", value:"'.$amount.'"}}]}); },
    onApprove: function(data, actions){
      return actions.order.capture().then(function(details){
        window.location = "'.esc_url($ok).'";
      });
    },
    onCancel: function(){ window.location = "'.esc_url($cancel).'"; }
  }).render("#fasp-pp-buttons");
  </script>';
});

/* Stripe Checkout with verification */
function fasp_stripe_create_session($cfg,$order){
  $secret=$cfg['secret_key']??''; if(!$secret) return array('ok'=>0,'msg'=>'No Stripe secret key');
  $cur=get_woocommerce_currency(); $amount=intval(round(floatval($order->get_total())*100));
  $success=add_query_arg(array('fasp_stripe_success'=>1,'order_id'=>$order->get_id(),'key'=>$order->get_order_key(),'session_id'=>'{CHECKOUT_SESSION_ID}'), home_url('/'));
  $cancel=add_query_arg(array('fasp_stripe_cancel'=>1,'order_id'=>$order->get_id(),'key'=>$order->get_order_key()), home_url('/'));
  $body = array(
    'mode'=>'payment',
    'success_url'=>$success,
    'cancel_url'=>$cancel,
    'line_items[0][price_data][currency]'=>$cur,
    'line_items[0][price_data][product_data][name]'=>'Order '.$order->get_order_number(),
    'line_items[0][price_data][unit_amount]'=>$amount,
    'line_items[0][quantity]'=>1
  );
  $res = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
    'headers'=>array('Authorization'=>'Bearer '.$secret),
    'body'=>$body, 'timeout'=>20
  ));
  if (is_wp_error($res)) return array('ok'=>0,'msg'=>$res->get_error_message());
  $j=json_decode(wp_remote_retrieve_body($res), true);
  if (!empty($j['url'])) return array('ok'=>1,'id'=>$j['id'],'url'=>$j['url']);
  return array('ok'=>0,'msg'=>wp_remote_retrieve_body($res));
}
function fasp_stripe_get($cfg,$path){
  $secret=$cfg['secret_key']??''; if(!$secret) return array();
  $res = wp_remote_get('https://api.stripe.com/v1'.$path, array('headers'=>array('Authorization'=>'Bearer '.$secret),'timeout'=>20));
  if (is_wp_error($res)) return array();
  return json_decode(wp_remote_retrieve_body($res), true);
}
add_action('plugins_loaded', function(){
  if (!class_exists('WC_Payment_Gateway')) return;
  class WC_Gateway_FASP_Card extends WC_Payment_Gateway {
    public function __construct(){
      $this->id='fasp_card'; $this->icon=''; $this->has_fields=false;
      $this->method_title='FASP Card (Stripe Checkout)';
      $this->method_description='Visa/Mastercard via Stripe Checkout';
      $this->supports=array('products');
      $this->init_form_fields(); $this->init_settings();
      add_action('woocommerce_update_options_payment_gateways_'.$this->id,array($this,'process_admin_options'));
    }
    public function init_form_fields(){
      $this->form_fields=array(
        'enabled'=>array('title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable FASP Card (Stripe)','default'=>'yes'),
        'title'=>array('title'=>'Title','type'=>'text','default'=>'Card (Visa/Mastercard)'),
      );
    }
    public function process_payment($order_id){
      $order=wc_get_order($order_id);
      $g = get_option('fasp_gateways', array()); $cfg = isset($g['stripe'])?$g['stripe']:array();
      if (empty($cfg['enabled'])){ wc_add_notice('Card payments unavailable.','error'); return; }
      $res=fasp_stripe_create_session($cfg,$order);
      if (!empty($res['ok'])){
        update_post_meta($order_id,'_fasp_stripe_session_id',$res['id']);
        return array('result'=>'success','redirect'=>$res['url']);
      }
      wc_add_notice('Card gateway error: '.esc_html($res['msg'] ?? 'Unknown'),'error'); return;
    }
  }
  add_filter('woocommerce_payment_gateways', function($methods){ $methods[]='WC_Gateway_FASP_Card'; return $methods; });
}, 20);

/* Stripe success/cancel with verification */
add_action('init', function(){
  if (isset($_GET['fasp_stripe_cancel'])){
    $oid=intval($_GET['order_id']??0); $key=sanitize_text_field($_GET['key']??'');
    $order=wc_get_order($oid); if ($order && $key===$order->get_order_key()){ $order->update_status('cancelled','Stripe Checkout cancelled.'); wp_safe_redirect($order->get_cancel_order_url()); exit; }
  }
  if (isset($_GET['fasp_stripe_success'])){
    $oid=intval($_GET['order_id']??0); $key=sanitize_text_field($_GET['key']??''); $sid=sanitize_text_field($_GET['session_id']??'');
    $order=wc_get_order($oid);
    if ($order && $key===$order->get_order_key()){
      $g = get_option('fasp_gateways', array()); $cfg = $g['stripe']??array();
      $sess = $sid ? fasp_stripe_get($cfg, '/checkout/sessions/'. $sid) : array();
      if (!empty($sess['payment_status']) && $sess['payment_status']==='paid'){
        $pi_id = $sess['payment_intent'] ?? '';
        $order->payment_complete($pi_id);
        $order->add_order_note('Stripe Checkout paid. Session '. $sid);
        wp_safe_redirect($order->get_checkout_order_received_url()); exit;
      } else {
        $order->update_status('failed','Stripe verification failed.');
        wp_safe_redirect($order->get_cancel_order_url()); exit;
      }
    }
  }
});

/* ---------------- Creative Helper & Verification ---------------- */

function fasp_get_creatives(){ $c=get_option('fasp_creatives', array('brand'=>array(),'blocks'=>array())); return is_array($c)?$c:array('brand'=>array(),'blocks'=>array()); }
function fasp_save_creatives($c){ if(!is_array($c))$c=array('brand'=>array(),'blocks'=>array()); update_option('fasp_creatives',$c); }
function fasp_render_creative_setup(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $c=fasp_get_creatives();
  if (isset($_POST['fasp_cre_save']) && check_admin_referer('fasp_cre_save','fasp_cre_save_nonce')){
    $c['brand']['logo_url']=esc_url_raw($_POST['logo_url'] ?? '');
    $c['brand']['primary_color']=sanitize_text_field($_POST['primary_color'] ?? '');
    $c['blocks']['disclaimer']=wp_kses_post($_POST['disclaimer'] ?? '');
    $c['blocks']['cta_default']=sanitize_text_field($_POST['cta_default'] ?? '');
    fasp_save_creatives($c); echo '<div class="updated"><p>Creative settings saved.</p></div>';
  }
  echo '<div class="wrap"><h1>Creative Helper</h1><form method="post">'; wp_nonce_field('fasp_cre_save','fasp_cre_save_nonce');
  echo '<input type="hidden" name="fasp_cre_save" value="1">';
  echo '<h2>Brand</h2><p><label>Logo URL <input type="url" name="logo_url" class="regular-text" value="'.esc_attr($c['brand']['logo_url'] ?? '').'"></label></p>';
  echo '<p><label>Primary Color <input type="text" name="primary_color" class="regular-text" value="'.esc_attr($c['brand']['primary_color'] ?? '').'" placeholder="#0ea5e9"></label></p>';
  echo '<h2>Defaults</h2>';
  echo '<p><label>Disclaimer (HTML)<br><textarea class="large-text code" rows="4" name="disclaimer">'.esc_textarea($c['blocks']['disclaimer'] ?? '').'</textarea></label></p>';
  echo '<p><label>Default CTA Label <input type="text" name="cta_default" class="regular-text" value="'.esc_attr($c['blocks']['cta_default'] ?? '').'"></label></p>';
  submit_button('Save Creative Settings'); echo '</form></div>';
}

function fasp_get_uv_cfg(){ $v=get_option('fasp_verification_ui', array('show_dashboard'=>1,'allow_manual'=>1)); return is_array($v)?$v:array('show_dashboard'=>1,'allow_manual'=>1); }
function fasp_save_uv_cfg($v){ if(!is_array($v))$v=array('show_dashboard'=>1,'allow_manual'=>1); update_option('fasp_verification_ui',$v); }
function fasp_render_user_verif_setup(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $v=fasp_get_uv_cfg();
  if (isset($_POST['fasp_uv_save']) && check_admin_referer('fasp_uv_save','fasp_uv_save_nonce')){
    $v['show_dashboard']=!empty($_POST['show_dashboard'])?1:0;
    $v['allow_manual']=!empty($_POST['allow_manual'])?1:0;
    fasp_save_uv_cfg($v); echo '<div class="updated"><p>Settings saved.</p></div>';
  }
  echo '<div class="wrap"><h1>Users → Verification</h1><form method="post">'; wp_nonce_field('fasp_uv_save','fasp_uv_save_nonce');
  echo '<input type="hidden" name="fasp_uv_save" value="1">';
  echo '<p><label><input type="checkbox" name="show_dashboard" value="1" '.checked(!empty($v['show_dashboard']),1,false).'> Show Woo “My Account → forex-dashboard”</label></p>';
  echo '<p><label><input type="checkbox" name="allow_manual" value="1" '.checked(!empty($v['allow_manual']),1,false).'> Allow manual verification toggles in admin</label></p>';
  submit_button('Save Verification Settings'); echo '</form></div>';
}


