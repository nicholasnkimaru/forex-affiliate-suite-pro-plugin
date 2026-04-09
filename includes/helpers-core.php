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
 * Canonical payments accessor.
 * Reads get_option('fasp_payments') and normalizes legacy flat keys
 * and nested shapes into a consistent nested array structure.
 *
 * @return array Normalized payments configuration array.
 */
if (!function_exists('fasp_get_payments')) {
  function fasp_get_payments() {
    $raw = get_option('fasp_payments', array());
    if (!is_array($raw)) {
      $raw = array();
    }

    // Build normalized nested structure
    $normalized = array(
      'stripe' => array(
        'pk' => '',
        'sk' => '',
        'webhook_secret' => '',
      ),
      'flutterwave' => array(
        'public' => '',
        'secret' => '',
      ),
      'paystack' => array(
        'public' => '',
        'secret' => '',
      ),
      'mpesa' => array(
        'env' => 'sandbox',
        'till' => '',
        'paybill' => '',
        'passkey' => '',
        'consumer_key' => '',
        'consumer_secret' => '',
        'initiator' => '',
        'ipass' => '',
        'cert' => '',
        'callback' => '',
      ),
      'paypal' => array(
        'email' => '',
        'client' => '',
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

    // Handle nested shapes if already present
    if (isset($raw['stripe']) && is_array($raw['stripe'])) {
      $normalized['stripe'] = array_merge($normalized['stripe'], $raw['stripe']);
    }
    if (isset($raw['flutterwave']) && is_array($raw['flutterwave'])) {
      $normalized['flutterwave'] = array_merge($normalized['flutterwave'], $raw['flutterwave']);
    }
    if (isset($raw['paystack']) && is_array($raw['paystack'])) {
      $normalized['paystack'] = array_merge($normalized['paystack'], $raw['paystack']);
    }
    if (isset($raw['mpesa']) && is_array($raw['mpesa'])) {
      $normalized['mpesa'] = array_merge($normalized['mpesa'], $raw['mpesa']);
    }
    if (isset($raw['paypal']) && is_array($raw['paypal'])) {
      $normalized['paypal'] = array_merge($normalized['paypal'], $raw['paypal']);
    }
    if (isset($raw['webhooks']) && is_array($raw['webhooks'])) {
      $normalized['webhooks'] = array_merge($normalized['webhooks'], $raw['webhooks']);
    }
    if (isset($raw['crypto']) && is_array($raw['crypto'])) {
      $normalized['crypto'] = array_merge($normalized['crypto'], $raw['crypto']);
    }
    if (isset($raw['bank']) && is_array($raw['bank'])) {
      $normalized['bank'] = array_merge($normalized['bank'], $raw['bank']);
    }

    // Map legacy flat keys to nested structure

    // Stripe legacy keys
    if (!empty($raw['stripe_pk'])) {
      $normalized['stripe']['pk'] = $raw['stripe_pk'];
    }
    if (!empty($raw['stripe_sk'])) {
      $normalized['stripe']['sk'] = $raw['stripe_sk'];
    }
    if (!empty($raw['stripe_whsec'])) {
      $normalized['stripe']['webhook_secret'] = $raw['stripe_whsec'];
    }
    if (!empty($raw['stripe_webhook_secret'])) {
      $normalized['stripe']['webhook_secret'] = $raw['stripe_webhook_secret'];
    }

    // Flutterwave legacy keys
    if (!empty($raw['fw_public'])) {
      $normalized['flutterwave']['public'] = $raw['fw_public'];
    }
    if (!empty($raw['fw_secret'])) {
      $normalized['flutterwave']['secret'] = $raw['fw_secret'];
    }
    if (!empty($raw['flutter_pk'])) {
      $normalized['flutterwave']['public'] = $raw['flutter_pk'];
    }
    if (!empty($raw['flutter_sk'])) {
      $normalized['flutterwave']['secret'] = $raw['flutter_sk'];
    }

    // Paystack legacy keys
    if (!empty($raw['ps_public'])) {
      $normalized['paystack']['public'] = $raw['ps_public'];
    }
    if (!empty($raw['ps_secret'])) {
      $normalized['paystack']['secret'] = $raw['ps_secret'];
    }
    if (!empty($raw['paystack_pk'])) {
      $normalized['paystack']['public'] = $raw['paystack_pk'];
    }
    if (!empty($raw['paystack_sk'])) {
      $normalized['paystack']['secret'] = $raw['paystack_sk'];
    }

    // M-Pesa legacy keys
    if (!empty($raw['mpesa_env'])) {
      $normalized['mpesa']['env'] = $raw['mpesa_env'];
    }
    if (!empty($raw['mpesa_till'])) {
      $normalized['mpesa']['till'] = $raw['mpesa_till'];
    }
    if (!empty($raw['mpesa_paybill'])) {
      $normalized['mpesa']['paybill'] = $raw['mpesa_paybill'];
    }
    if (!empty($raw['mpesa_passkey'])) {
      $normalized['mpesa']['passkey'] = $raw['mpesa_passkey'];
    }
    if (!empty($raw['mpesa_ck'])) {
      $normalized['mpesa']['consumer_key'] = $raw['mpesa_ck'];
    }
    if (!empty($raw['mpesa_cs'])) {
      $normalized['mpesa']['consumer_secret'] = $raw['mpesa_cs'];
    }
    if (!empty($raw['mpesa_consumer_key'])) {
      $normalized['mpesa']['consumer_key'] = $raw['mpesa_consumer_key'];
    }
    if (!empty($raw['mpesa_consumer_secret'])) {
      $normalized['mpesa']['consumer_secret'] = $raw['mpesa_consumer_secret'];
    }
    if (!empty($raw['mpesa_initiator'])) {
      $normalized['mpesa']['initiator'] = $raw['mpesa_initiator'];
    }
    if (!empty($raw['mpesa_initiator_username'])) {
      $normalized['mpesa']['initiator'] = $raw['mpesa_initiator_username'];
    }
    if (!empty($raw['mpesa_ipass'])) {
      $normalized['mpesa']['ipass'] = $raw['mpesa_ipass'];
    }
    if (!empty($raw['mpesa_initiator_password'])) {
      $normalized['mpesa']['ipass'] = $raw['mpesa_initiator_password'];
    }
    if (!empty($raw['mpesa_cert'])) {
      $normalized['mpesa']['cert'] = $raw['mpesa_cert'];
    }
    if (!empty($raw['mpesa_cert_pem'])) {
      $normalized['mpesa']['cert'] = $raw['mpesa_cert_pem'];
    }
    if (!empty($raw['mpesa_callback_url'])) {
      $normalized['mpesa']['callback'] = $raw['mpesa_callback_url'];
    }
    if (!empty($raw['mpesa_callback'])) {
      $normalized['mpesa']['callback'] = $raw['mpesa_callback'];
    }
    // shortcode variant
    if (!empty($raw['mpesa_short_code'])) {
      $normalized['mpesa']['till'] = $raw['mpesa_short_code'];
    }
    // account reference
    if (!empty($raw['mpesa_account'])) {
      $normalized['mpesa']['account'] = $raw['mpesa_account'];
    }
    // mode (till/paybill/both)
    if (!empty($raw['mpesa_mode'])) {
      $normalized['mpesa']['mode'] = $raw['mpesa_mode'];
    }

    // PayPal legacy keys
    if (!empty($raw['paypal_email'])) {
      $normalized['paypal']['email'] = $raw['paypal_email'];
    }
    if (!empty($raw['paypal_client'])) {
      $normalized['paypal']['client'] = $raw['paypal_client'];
    }
    if (!empty($raw['paypal_client_id'])) {
      $normalized['paypal']['client'] = $raw['paypal_client_id'];
    }
    if (!empty($raw['paypal_secret'])) {
      $normalized['paypal']['secret'] = $raw['paypal_secret'];
    }

    // Webhooks legacy keys
    if (isset($raw['wh_enable'])) {
      $normalized['webhooks']['enabled'] = !empty($raw['wh_enable']);
    }
    if (isset($raw['webhooks_enabled'])) {
      $normalized['webhooks']['enabled'] = !empty($raw['webhooks_enabled']);
    }
    if (!empty($raw['wh_secret'])) {
      $normalized['webhooks']['secret'] = $raw['wh_secret'];
    }
    if (!empty($raw['webhook_secret'])) {
      $normalized['webhooks']['secret'] = $raw['webhook_secret'];
    }
    if (!empty($raw['wh_url'])) {
      $normalized['webhooks']['url'] = $raw['wh_url'];
    }
    if (!empty($raw['webhook_endpoint'])) {
      $normalized['webhooks']['url'] = $raw['webhook_endpoint'];
    }
    if (!empty($raw['webhook_primary_url'])) {
      $normalized['webhooks']['url'] = $raw['webhook_primary_url'];
    }

    // Crypto legacy keys
    if (isset($raw['cr_enable'])) {
      $normalized['crypto']['enabled'] = !empty($raw['cr_enable']);
    }
    if (isset($raw['crypto_enabled'])) {
      $normalized['crypto']['enabled'] = !empty($raw['crypto_enabled']);
    }
    if (!empty($raw['cr_chain'])) {
      $normalized['crypto']['chain'] = $raw['cr_chain'];
    }
    if (!empty($raw['crypto_pref'])) {
      $normalized['crypto']['chain'] = $raw['crypto_pref'];
    }
    if (!empty($raw['cr_trc20'])) {
      $normalized['crypto']['trc20'] = $raw['cr_trc20'];
    }
    if (!empty($raw['usdt_trc20'])) {
      $normalized['crypto']['trc20'] = $raw['usdt_trc20'];
    }
    if (!empty($raw['cr_erc20'])) {
      $normalized['crypto']['erc20'] = $raw['cr_erc20'];
    }
    if (!empty($raw['usdt_erc20'])) {
      $normalized['crypto']['erc20'] = $raw['usdt_erc20'];
    }
    if (!empty($raw['cr_bep20'])) {
      $normalized['crypto']['bep20'] = $raw['cr_bep20'];
    }
    if (!empty($raw['usdt_bep20'])) {
      $normalized['crypto']['bep20'] = $raw['usdt_bep20'];
    }
    // Single wallet fallback
    if (!empty($raw['crypto_wallet'])) {
      if (empty($normalized['crypto']['trc20'])) {
        $normalized['crypto']['trc20'] = $raw['crypto_wallet'];
      }
    }

    // Bank legacy keys
    if (isset($raw['bank_enable'])) {
      $normalized['bank']['enabled'] = !empty($raw['bank_enable']);
    }
    if (isset($raw['bank_enabled'])) {
      $normalized['bank']['enabled'] = !empty($raw['bank_enabled']);
    }
    if (!empty($raw['bank_name'])) {
      $normalized['bank']['name'] = $raw['bank_name'];
    }
    if (!empty($raw['bank_account'])) {
      $normalized['bank']['account'] = $raw['bank_account'];
    }
    if (!empty($raw['bank_beneficiary'])) {
      $normalized['bank']['beneficiary'] = $raw['bank_beneficiary'];
    }
    if (!empty($raw['bank_iban'])) {
      $normalized['bank']['iban'] = $raw['bank_iban'];
    }
    if (!empty($raw['bank_swift'])) {
      $normalized['bank']['swift'] = $raw['bank_swift'];
    }
    if (!empty($raw['bank_instructions'])) {
      $normalized['bank']['instructions'] = $raw['bank_instructions'];
    }
    if (!empty($raw['bank_details'])) {
      $normalized['bank']['instructions'] = $raw['bank_details'];
    }

    return $normalized;
  }
}

/**
 * Check if a request is rate-limited.
 *
 * @param string $key Unique key identifying the rate limit (e.g., IP + action).
 * @param int    $max_requests Maximum requests allowed within the TTL window.
 * @param int    $ttl Time-to-live in seconds for the rate limit window.
 * @return bool True if rate-limited, false otherwise.
 */
if (!function_exists('fasp_is_request_rate_limited')) {
  function fasp_is_request_rate_limited($key, $max_requests = 5, $ttl = 60) {
    $transient_key = 'fasp_rl_' . md5($key);
    $count = (int) get_transient($transient_key);
    return $count >= $max_requests;
  }
}

/**
 * Increment the request count for rate limiting.
 *
 * @param string $key Unique key identifying the rate limit (e.g., IP + action).
 * @param int    $ttl Time-to-live in seconds for the rate limit window.
 * @return int Current request count after increment.
 */
if (!function_exists('fasp_increment_request_count')) {
  function fasp_increment_request_count($key, $ttl = 60) {
    $transient_key = 'fasp_rl_' . md5($key);
    $count = (int) get_transient($transient_key);
    $count++;
    set_transient($transient_key, $count, $ttl);
    return $count;
  }
}

/**
 * Log a message via the FASP logger.
 * Uses WC_Logger if available, otherwise uses error_log.
 *
 * @param mixed $message Message to log (string or array).
 * @return void
 */
if (!function_exists('fasp_log')) {
  function fasp_log($message) {
    static $logger = null;
    
    $log_message = is_string($message) ? $message : wp_json_encode($message);
    
    if (class_exists('WC_Logger')) {
      if ($logger === null) {
        $logger = new WC_Logger();
      }
      $logger->add('fasp', $log_message);
    } else {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log('[FASP] ' . $log_message);
    }
  }
}
