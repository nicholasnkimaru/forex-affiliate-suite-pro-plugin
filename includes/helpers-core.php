<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_parent_slug')){
  function fasp_parent_slug(){
    $candidates = array('forex-affiliate','fasp_hub','toplevel_page_forex-affiliate');
    global $submenu;
    foreach ($candidates as $slug){
      if (isset($submenu[$slug]) || $slug==='forex-affiliate') return $slug;
    }
    return 'forex-affiliate';
  }
}

if (!function_exists('fasp_prepare')){
  function fasp_prepare($query){
    $args = func_get_args();
    array_shift($args);
    global $wpdb;
    if (strpos($query, '%') === false){
      return $query;
    }
    return $wpdb->prepare($query, $args);
  }
}

if (!function_exists('fasp_db_column_exists')){
  function fasp_db_column_exists($table, $col){
    global $wpdb;
    $sql = fasp_prepare("SHOW COLUMNS FROM `$table` LIKE %s", $col);
    return (bool) $wpdb->get_var($sql);
  }
}

/**
 * Canonical accessor for payment settings.
 *
 * Reads get_option('fasp_payments') and normalizes legacy flat keys
 * and nested shapes into the canonical nested structure:
 * stripe, flutterwave, paystack, mpesa, paypal, webhooks, crypto, bank.
 *
 * @return array Normalized payment settings with nested provider keys.
 */
if (!function_exists('fasp_get_payments')){
  function fasp_get_payments(){
    $opt = get_option('fasp_payments', array());
    if (!is_array($opt)) {
      $opt = array();
    }

    // Canonical nested structure
    $payments = array(
      'stripe' => array(
        'enabled' => false,
        'pk' => '',
        'sk' => '',
        'webhook_secret' => '',
      ),
      'flutterwave' => array(
        'enabled' => false,
        'pk' => '',
        'sk' => '',
      ),
      'paystack' => array(
        'enabled' => false,
        'pk' => '',
        'sk' => '',
      ),
      'mpesa' => array(
        'enabled' => false,
        'mode' => 'till',
        'till' => '',
        'paybill' => '',
        'shortcode' => '',
        'account' => '',
        'consumer_key' => '',
        'consumer_secret' => '',
        'passkey' => '',
        'initiator' => '',
        'initiator_password' => '',
        'cert' => '',
        'env' => 'sandbox',
        'callback' => '',
      ),
      'paypal' => array(
        'enabled' => false,
        'email' => '',
        'client_id' => '',
        'secret' => '',
      ),
      'webhooks' => array(
        'enabled' => false,
        'secret' => '',
        'url' => '',
      ),
      'crypto' => array(
        'enabled' => false,
        'chain' => 'trc20',
        'trc20' => '',
        'erc20' => '',
        'bep20' => '',
      ),
      'bank' => array(
        'enabled' => false,
        'name' => '',
        'account' => '',
        'beneficiary' => '',
        'iban' => '',
        'swift' => '',
        'instructions' => '',
      ),
    );

    // Check if already nested structure
    if (isset($opt['stripe']) && is_array($opt['stripe'])) {
      // Already has nested stripe key - merge with defaults
      foreach ($payments as $provider => $defaults) {
        if (isset($opt[$provider]) && is_array($opt[$provider])) {
          $payments[$provider] = array_merge($defaults, $opt[$provider]);
        }
      }
      // Handle webhook_secret at stripe level
      if (isset($opt['stripe']['webhook_secret'])) {
        $payments['stripe']['webhook_secret'] = $opt['stripe']['webhook_secret'];
      }
    } else {
      // Legacy flat structure - normalize to nested

      // Stripe
      $payments['stripe']['enabled'] = !empty($opt['stripe_enable']);
      $payments['stripe']['pk'] = isset($opt['stripe_pk']) ? $opt['stripe_pk'] : '';
      $payments['stripe']['sk'] = isset($opt['stripe_sk']) ? $opt['stripe_sk'] : '';
      // webhook secret can be in multiple places
      $payments['stripe']['webhook_secret'] = isset($opt['stripe_whsec']) ? $opt['stripe_whsec'] :
        (isset($opt['stripe_webhook_secret']) ? $opt['stripe_webhook_secret'] :
        (isset($opt['wh_secret']) ? $opt['wh_secret'] : ''));

      // Flutterwave
      $payments['flutterwave']['enabled'] = !empty($opt['fw_enable']);
      $payments['flutterwave']['pk'] = isset($opt['fw_public']) ? $opt['fw_public'] : '';
      $payments['flutterwave']['sk'] = isset($opt['fw_secret']) ? $opt['fw_secret'] : '';

      // Paystack
      $payments['paystack']['enabled'] = !empty($opt['ps_enable']);
      $payments['paystack']['pk'] = isset($opt['ps_public']) ? $opt['ps_public'] : '';
      $payments['paystack']['sk'] = isset($opt['ps_secret']) ? $opt['ps_secret'] : '';

      // M-Pesa
      $payments['mpesa']['enabled'] = !empty($opt['mpesa_enable']);
      $payments['mpesa']['mode'] = isset($opt['mpesa_mode']) ? $opt['mpesa_mode'] : 'till';
      $payments['mpesa']['till'] = isset($opt['mpesa_till']) ? $opt['mpesa_till'] : '';
      $payments['mpesa']['paybill'] = isset($opt['mpesa_paybill']) ? $opt['mpesa_paybill'] : '';
      // shortcode fallback: till or paybill depending on mode
      $mode = $payments['mpesa']['mode'];
      $payments['mpesa']['shortcode'] = ($mode === 'paybill' && !empty($opt['mpesa_paybill'])) ?
        $opt['mpesa_paybill'] : (isset($opt['mpesa_till']) ? $opt['mpesa_till'] : '');
      $payments['mpesa']['account'] = isset($opt['mpesa_account']) ? $opt['mpesa_account'] : '';
      $payments['mpesa']['consumer_key'] = isset($opt['mpesa_ck']) ? $opt['mpesa_ck'] : '';
      $payments['mpesa']['consumer_secret'] = isset($opt['mpesa_cs']) ? $opt['mpesa_cs'] : '';
      $payments['mpesa']['passkey'] = isset($opt['mpesa_passkey']) ? $opt['mpesa_passkey'] : '';
      $payments['mpesa']['initiator'] = isset($opt['mpesa_initiator']) ? $opt['mpesa_initiator'] : '';
      $payments['mpesa']['initiator_password'] = isset($opt['mpesa_ipass']) ? $opt['mpesa_ipass'] : '';
      $payments['mpesa']['cert'] = isset($opt['mpesa_cert']) ? $opt['mpesa_cert'] : '';
      $payments['mpesa']['env'] = isset($opt['mpesa_env']) ? $opt['mpesa_env'] : 'sandbox';
      $payments['mpesa']['callback'] = isset($opt['mpesa_callback_url']) ? $opt['mpesa_callback_url'] : '';

      // PayPal
      $payments['paypal']['enabled'] = !empty($opt['paypal_enable']);
      $payments['paypal']['email'] = isset($opt['paypal_email']) ? $opt['paypal_email'] : '';
      $payments['paypal']['client_id'] = isset($opt['paypal_client']) ? $opt['paypal_client'] : '';
      $payments['paypal']['secret'] = isset($opt['paypal_secret']) ? $opt['paypal_secret'] : '';

      // Webhooks
      $payments['webhooks']['enabled'] = !empty($opt['wh_enable']);
      $payments['webhooks']['secret'] = isset($opt['wh_secret']) ? $opt['wh_secret'] : '';
      $payments['webhooks']['url'] = isset($opt['wh_url']) ? $opt['wh_url'] : '';

      // Crypto
      $payments['crypto']['enabled'] = !empty($opt['cr_enable']);
      $payments['crypto']['chain'] = isset($opt['cr_chain']) ? $opt['cr_chain'] : 'trc20';
      $payments['crypto']['trc20'] = isset($opt['cr_trc20']) ? $opt['cr_trc20'] : '';
      $payments['crypto']['erc20'] = isset($opt['cr_erc20']) ? $opt['cr_erc20'] : '';
      $payments['crypto']['bep20'] = isset($opt['cr_bep20']) ? $opt['cr_bep20'] : '';

      // Bank
      $payments['bank']['enabled'] = !empty($opt['bank_enable']);
      $payments['bank']['name'] = isset($opt['bank_name']) ? $opt['bank_name'] : '';
      $payments['bank']['account'] = isset($opt['bank_account']) ? $opt['bank_account'] : '';
      $payments['bank']['beneficiary'] = isset($opt['bank_beneficiary']) ? $opt['bank_beneficiary'] : '';
      $payments['bank']['iban'] = isset($opt['bank_iban']) ? $opt['bank_iban'] : '';
      $payments['bank']['swift'] = isset($opt['bank_swift']) ? $opt['bank_swift'] : '';
      $payments['bank']['instructions'] = isset($opt['bank_instructions']) ? $opt['bank_instructions'] : '';
    }

    return $payments;
  }
}

/**
 * Check if a request is rate-limited based on a key.
 *
 * Uses transients to track request counts within a time period.
 *
 * @param string $key    Unique identifier for the rate limit (e.g., IP address).
 * @param int    $limit  Maximum number of requests allowed (default: 5).
 * @param int    $period Time period in seconds (default: 60).
 * @return bool True if rate-limited (should block), false if allowed.
 */
if (!function_exists('fasp_is_request_rate_limited')){
  function fasp_is_request_rate_limited($key, $limit = 5, $period = 60){
    $transient_key = 'fasp_rl_' . md5($key);
    $count = (int) get_transient($transient_key);
    return $count >= $limit;
  }
}

/**
 * Increment the request count for rate limiting.
 *
 * @param string $key Unique identifier for the rate limit (e.g., IP address).
 * @param int    $ttl Time-to-live in seconds for the transient (default: 60).
 * @return int The new request count.
 */
if (!function_exists('fasp_increment_request_count')){
  function fasp_increment_request_count($key, $ttl = 60){
    $transient_key = 'fasp_rl_' . md5($key);
    $count = (int) get_transient($transient_key);
    $count++;
    set_transient($transient_key, $count, $ttl);
    return $count;
  }
}

/**
 * Log a message using WC_Logger if available, otherwise error_log.
 *
 * @param string $message The message to log.
 * @param string $level   Log level: 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'.
 * @return void
 */
if (!function_exists('fasp_log')){
  function fasp_log($message, $level = 'info'){
    if (class_exists('WC_Logger')) {
      $logger = wc_get_logger();
      $context = array('source' => 'forex-affiliate-suite-pro');
      $logger->log($level, $message, $context);
    } else {
      // Fallback to error_log
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log('[FASP ' . strtoupper($level) . '] ' . $message);
    }
  }
}
