<?php
if (!defined('ABSPATH')) exit;

/**
 * Canonical payments accessor.
 *
 * Reads get_option('fasp_payments') and normalizes legacy flat keys
 * (stripe_pk, stripe_sk, stripe_whsec, mpesa_passkey, mpesa_ck, mpesa_cs, etc.)
 * and nested shapes into a consistent nested array structure.
 *
 * @since r14.9
 * @return array Normalized payment configuration with nested provider arrays.
 */
if (!function_exists('fasp_get_payments')) {
    function fasp_get_payments() {
        $raw = get_option('fasp_payments', array());
        if (!is_array($raw)) {
            $raw = array();
        }

        // Initialize normalized structure
        $normalized = array(
            'stripe' => array(
                'pk'             => '',
                'sk'             => '',
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
                'env'             => 'sandbox',
                'till'            => '',
                'paybill'         => '',
                'passkey'         => '',
                'consumer_key'    => '',
                'consumer_secret' => '',
                'initiator'       => '',
                'ipass'           => '',
                'cert'            => '',
                'callback'        => '',
            ),
            'paypal' => array(
                'email'  => '',
                'client' => '',
                'secret' => '',
            ),
            'webhooks' => array(
                'enabled' => 0,
                'secret'  => '',
                'url'     => '',
            ),
            'crypto' => array(
                'enabled' => 0,
                'chain'   => 'trc20',
                'trc20'   => '',
                'erc20'   => '',
                'bep20'   => '',
            ),
            'bank' => array(
                'enabled'      => 0,
                'name'         => '',
                'account'      => '',
                'beneficiary'  => '',
                'iban'         => '',
                'swift'        => '',
                'instructions' => '',
            ),
        );

        // Check if already nested (new format)
        if (isset($raw['stripe']) && is_array($raw['stripe'])) {
            // Merge nested stripe
            $normalized['stripe']['pk'] = $raw['stripe']['pk'] ?? '';
            $normalized['stripe']['sk'] = $raw['stripe']['sk'] ?? '';
            $normalized['stripe']['webhook_secret'] = $raw['stripe']['webhook_secret'] ?? '';
        }
        if (isset($raw['flutterwave']) && is_array($raw['flutterwave'])) {
            $normalized['flutterwave']['public'] = $raw['flutterwave']['public'] ?? '';
            $normalized['flutterwave']['secret'] = $raw['flutterwave']['secret'] ?? ($raw['flutterwave']['sk'] ?? '');
        }
        if (isset($raw['paystack']) && is_array($raw['paystack'])) {
            $normalized['paystack']['public'] = $raw['paystack']['public'] ?? '';
            $normalized['paystack']['secret'] = $raw['paystack']['secret'] ?? ($raw['paystack']['sk'] ?? '');
        }
        if (isset($raw['mpesa']) && is_array($raw['mpesa'])) {
            $normalized['mpesa']['env'] = $raw['mpesa']['env'] ?? 'sandbox';
            $normalized['mpesa']['till'] = $raw['mpesa']['till'] ?? '';
            $normalized['mpesa']['paybill'] = $raw['mpesa']['paybill'] ?? '';
            $normalized['mpesa']['passkey'] = $raw['mpesa']['passkey'] ?? '';
            $normalized['mpesa']['consumer_key'] = $raw['mpesa']['consumer_key'] ?? '';
            $normalized['mpesa']['consumer_secret'] = $raw['mpesa']['consumer_secret'] ?? '';
            $normalized['mpesa']['initiator'] = $raw['mpesa']['initiator'] ?? '';
            $normalized['mpesa']['ipass'] = $raw['mpesa']['ipass'] ?? '';
            $normalized['mpesa']['cert'] = $raw['mpesa']['cert'] ?? '';
            $normalized['mpesa']['callback'] = $raw['mpesa']['callback'] ?? '';
        }
        if (isset($raw['paypal']) && is_array($raw['paypal'])) {
            $normalized['paypal']['email'] = $raw['paypal']['email'] ?? '';
            $normalized['paypal']['client'] = $raw['paypal']['client'] ?? '';
            $normalized['paypal']['secret'] = $raw['paypal']['secret'] ?? '';
        }
        if (isset($raw['webhooks']) && is_array($raw['webhooks'])) {
            $normalized['webhooks']['enabled'] = !empty($raw['webhooks']['enabled']) ? 1 : 0;
            $normalized['webhooks']['secret'] = $raw['webhooks']['secret'] ?? '';
            $normalized['webhooks']['url'] = $raw['webhooks']['url'] ?? '';
        }
        if (isset($raw['crypto']) && is_array($raw['crypto'])) {
            $normalized['crypto']['enabled'] = !empty($raw['crypto']['enabled']) ? 1 : 0;
            $normalized['crypto']['chain'] = $raw['crypto']['chain'] ?? 'trc20';
            $normalized['crypto']['trc20'] = $raw['crypto']['trc20'] ?? '';
            $normalized['crypto']['erc20'] = $raw['crypto']['erc20'] ?? '';
            $normalized['crypto']['bep20'] = $raw['crypto']['bep20'] ?? '';
        }
        if (isset($raw['bank']) && is_array($raw['bank'])) {
            $normalized['bank']['enabled'] = !empty($raw['bank']['enabled']) ? 1 : 0;
            $normalized['bank']['name'] = $raw['bank']['name'] ?? '';
            $normalized['bank']['account'] = $raw['bank']['account'] ?? '';
            $normalized['bank']['beneficiary'] = $raw['bank']['beneficiary'] ?? '';
            $normalized['bank']['iban'] = $raw['bank']['iban'] ?? '';
            $normalized['bank']['swift'] = $raw['bank']['swift'] ?? '';
            $normalized['bank']['instructions'] = $raw['bank']['instructions'] ?? '';
        }

        // Handle legacy flat keys (older format)
        // Stripe legacy keys
        if (empty($normalized['stripe']['pk']) && isset($raw['stripe_pk'])) {
            $normalized['stripe']['pk'] = $raw['stripe_pk'];
        }
        if (empty($normalized['stripe']['sk']) && isset($raw['stripe_sk'])) {
            $normalized['stripe']['sk'] = $raw['stripe_sk'];
        }
        if (empty($normalized['stripe']['webhook_secret'])) {
            $normalized['stripe']['webhook_secret'] = $raw['stripe_whsec'] ?? ($raw['stripe_webhook_secret'] ?? '');
        }

        // Flutterwave legacy keys
        if (empty($normalized['flutterwave']['public']) && isset($raw['fw_public'])) {
            $normalized['flutterwave']['public'] = $raw['fw_public'];
        }
        if (empty($normalized['flutterwave']['secret']) && isset($raw['fw_secret'])) {
            $normalized['flutterwave']['secret'] = $raw['fw_secret'];
        }

        // Paystack legacy keys
        if (empty($normalized['paystack']['public']) && isset($raw['ps_public'])) {
            $normalized['paystack']['public'] = $raw['ps_public'];
        }
        if (empty($normalized['paystack']['secret']) && isset($raw['ps_secret'])) {
            $normalized['paystack']['secret'] = $raw['ps_secret'];
        }

        // M-Pesa legacy keys
        if (empty($normalized['mpesa']['env']) || $normalized['mpesa']['env'] === 'sandbox') {
            $normalized['mpesa']['env'] = $raw['mpesa_env'] ?? 'sandbox';
        }
        if (empty($normalized['mpesa']['till']) && isset($raw['mpesa_till'])) {
            $normalized['mpesa']['till'] = $raw['mpesa_till'];
        }
        if (empty($normalized['mpesa']['paybill']) && isset($raw['mpesa_paybill'])) {
            $normalized['mpesa']['paybill'] = $raw['mpesa_paybill'];
        }
        if (empty($normalized['mpesa']['passkey']) && isset($raw['mpesa_passkey'])) {
            $normalized['mpesa']['passkey'] = $raw['mpesa_passkey'];
        }
        if (empty($normalized['mpesa']['consumer_key'])) {
            $normalized['mpesa']['consumer_key'] = $raw['mpesa_ck'] ?? ($raw['mpesa_consumer_key'] ?? '');
        }
        if (empty($normalized['mpesa']['consumer_secret'])) {
            $normalized['mpesa']['consumer_secret'] = $raw['mpesa_cs'] ?? ($raw['mpesa_consumer_secret'] ?? '');
        }
        if (empty($normalized['mpesa']['initiator']) && isset($raw['mpesa_initiator'])) {
            $normalized['mpesa']['initiator'] = $raw['mpesa_initiator'];
        }
        if (empty($normalized['mpesa']['ipass']) && isset($raw['mpesa_ipass'])) {
            $normalized['mpesa']['ipass'] = $raw['mpesa_ipass'];
        }
        if (empty($normalized['mpesa']['cert']) && isset($raw['mpesa_cert'])) {
            $normalized['mpesa']['cert'] = $raw['mpesa_cert'];
        }
        if (empty($normalized['mpesa']['callback']) && isset($raw['mpesa_callback'])) {
            $normalized['mpesa']['callback'] = $raw['mpesa_callback'];
        }
        // Also check mpesa_callback_url legacy key
        if (empty($normalized['mpesa']['callback']) && isset($raw['mpesa_callback_url'])) {
            $normalized['mpesa']['callback'] = $raw['mpesa_callback_url'];
        }

        // PayPal legacy keys
        if (empty($normalized['paypal']['email']) && isset($raw['paypal_email'])) {
            $normalized['paypal']['email'] = $raw['paypal_email'];
        }
        if (empty($normalized['paypal']['client']) && isset($raw['paypal_client'])) {
            $normalized['paypal']['client'] = $raw['paypal_client'];
        }
        if (empty($normalized['paypal']['secret']) && isset($raw['paypal_secret'])) {
            $normalized['paypal']['secret'] = $raw['paypal_secret'];
        }

        // Webhooks legacy keys
        if (empty($normalized['webhooks']['enabled'])) {
            $normalized['webhooks']['enabled'] = !empty($raw['wh_enable']) ? 1 : 0;
        }
        if (empty($normalized['webhooks']['secret']) && isset($raw['wh_secret'])) {
            $normalized['webhooks']['secret'] = $raw['wh_secret'];
        }
        if (empty($normalized['webhooks']['url']) && isset($raw['wh_url'])) {
            $normalized['webhooks']['url'] = $raw['wh_url'];
        }

        // Crypto legacy keys
        if (empty($normalized['crypto']['enabled'])) {
            $normalized['crypto']['enabled'] = !empty($raw['cr_enable']) ? 1 : 0;
        }
        if (empty($normalized['crypto']['chain']) || $normalized['crypto']['chain'] === 'trc20') {
            $normalized['crypto']['chain'] = $raw['cr_chain'] ?? 'trc20';
        }
        if (empty($normalized['crypto']['trc20']) && isset($raw['cr_trc20'])) {
            $normalized['crypto']['trc20'] = $raw['cr_trc20'];
        }
        if (empty($normalized['crypto']['erc20']) && isset($raw['cr_erc20'])) {
            $normalized['crypto']['erc20'] = $raw['cr_erc20'];
        }
        if (empty($normalized['crypto']['bep20']) && isset($raw['cr_bep20'])) {
            $normalized['crypto']['bep20'] = $raw['cr_bep20'];
        }

        // Bank legacy keys
        if (empty($normalized['bank']['enabled'])) {
            $normalized['bank']['enabled'] = !empty($raw['bank_enable']) ? 1 : 0;
        }
        if (empty($normalized['bank']['name']) && isset($raw['bank_name'])) {
            $normalized['bank']['name'] = $raw['bank_name'];
        }
        if (empty($normalized['bank']['account']) && isset($raw['bank_account'])) {
            $normalized['bank']['account'] = $raw['bank_account'];
        }
        if (empty($normalized['bank']['beneficiary']) && isset($raw['bank_beneficiary'])) {
            $normalized['bank']['beneficiary'] = $raw['bank_beneficiary'];
        }
        if (empty($normalized['bank']['iban']) && isset($raw['bank_iban'])) {
            $normalized['bank']['iban'] = $raw['bank_iban'];
        }
        if (empty($normalized['bank']['swift']) && isset($raw['bank_swift'])) {
            $normalized['bank']['swift'] = $raw['bank_swift'];
        }
        if (empty($normalized['bank']['instructions']) && isset($raw['bank_instructions'])) {
            $normalized['bank']['instructions'] = $raw['bank_instructions'];
        }

        return $normalized;
    }
}

/**
 * Log a message to the FASP log.
 *
 * @since r14.9
 * @param string $message Log message.
 * @param string $level   Log level (info, warning, error).
 * @return void
 */
if (!function_exists('fasp_log')) {
    function fasp_log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[FASP ' . strtoupper($level) . '] ' . $message);
        }
    }
}

/**
 * Check if a request is rate-limited based on a key.
 *
 * @since r14.9
 * @param string $key  Unique identifier for the rate limit (e.g., IP address).
 * @param int    $max  Maximum number of requests allowed within TTL.
 * @param int    $ttl  Time window in seconds.
 * @return bool True if rate-limited, false otherwise.
 */
if (!function_exists('fasp_is_request_rate_limited')) {
    function fasp_is_request_rate_limited($key, $max = 5, $ttl = 60) {
        $transient_key = 'fasp_rl_' . md5($key);
        $count = (int) get_transient($transient_key);
        return $count >= $max;
    }
}

/**
 * Increment the request count for rate limiting.
 *
 * @since r14.9
 * @param string $key Unique identifier for the rate limit.
 * @param int    $ttl Time window in seconds.
 * @return int Current count after increment.
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
