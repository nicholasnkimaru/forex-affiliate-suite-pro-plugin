<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;

// Save our extra payments fields (separate submit)
add_action('admin_init', function(){
  if (!current_user_can('manage_options')) return;
  if (isset($_POST['fasp_pay_ext_save']) && check_admin_referer('fasp_pay_ext','fasp_pay_ext_nonce')){
    $o = get_option('fasp_payments',[]);
    $o['cards_enabled']    = empty($_POST['cards_enabled']) ? '0':'1';
    $o['stripe_pk']        = sanitize_text_field($_POST['stripe_pk'] ?? '');
    $o['stripe_sk']        = sanitize_text_field($_POST['stripe_sk'] ?? '');
    $o['stripe_whsec']     = sanitize_text_field($_POST['stripe_whsec'] ?? '');
    $o['cards_return_url'] = esc_url_raw($_POST['cards_return_url'] ?? home_url('/?fasp_cards_return=1'));
    $o['cards_cancel_url'] = esc_url_raw($_POST['cards_cancel_url'] ?? home_url('/?fasp_cards_cancel=1'));
    $o['paypal_return_url']= esc_url_raw($_POST['paypal_return_url'] ?? home_url('/?fasp_paypal_return=1'));
    $o['paypal_cancel_url']= esc_url_raw($_POST['paypal_cancel_url'] ?? home_url('/?fasp_paypal_cancel=1'));
    $o['mpesa_callback_url']= esc_url_raw($_POST['mpesa_callback_url'] ?? home_url('/?fasp_mpesa_callback=1'));
    update_option('fasp_payments', array_replace_recursive((array) get_option('fasp_payments', array()), (array) $o));
    add_action('admin_notices', function(){ echo '<div class="notice notice-success is-dismissible"><p>Payment settings saved.</p></div>'; });
  }
});

// Inject UI onto the existing Payments page (non-invasive)
add_action('admin_footer', function(){
  if (!current_user_can('manage_options')) return;
  $screen = get_current_screen();
  if (!$screen) return;
  if (isset($_GET['page']) && $_GET['page']==='fasp_payments'){
    $o = get_option('fasp_payments',[]);
    ?>
    
    </div>
    <?php
  }
});

// Front routes to handle returns/webhooks (stubs to hook into)
add_action('init', function(){
  if (isset($_GET['fasp_cards_return'])) do_action('fasp/cards_return');
  if (isset($_GET['fasp_cards_cancel'])) do_action('fasp/cards_cancel');
  if (isset($_GET['fasp_stripe_webhook'])) do_action('fasp/stripe_webhook');
  if (isset($_GET['fasp_paypal_return'])) do_action('fasp/paypal_return');
  if (isset($_GET['fasp_paypal_cancel'])) do_action('fasp/paypal_cancel');
  if (isset($_GET['fasp_mpesa_callback'])) do_action('fasp/mpesa_callback');
});
