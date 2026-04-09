<?php
if (!defined('ABSPATH')) exit;

/**
 * Return normalized payments configuration.
 *
 * Accepts both legacy flat keys (e.g. stripe_pk, stripe_sk, mpesa_passkey)
 * and nested shapes (stripe => [sk,...], mpesa => [...]) and returns a
 * canonical nested structure used by runtime code.
 *
 * @return array Normalized payments configuration with nested provider keys.
 */
if (!function_exists('fasp_get_payments')) {
    function fasp_get_payments() {
        $raw = get_option('fasp_payments', array());
        if (!is_array($raw)) {
            $raw = array();
        }

        // Build canonical nested structure
        $payments = array(
            'stripe' => array(
                'enable' => false,
                'pk'     => '',
                'sk'     => '',
                'whsec'  => '',
            ),
            'paypal' => array(
                'enable'  => false,
                'email'   => '',
                'client'  => '',
                'secret'  => '',
            ),
            'flutterwave' => array(
                'enable' => false,
                'pk'     => '',
                'sk'     => '',
            ),
            'paystack' => array(
                'enable' => false,
                'pk'     => '',
                'sk'     => '',
            ),
            'mpesa' => array(
                'enable'          => false,
                'mode'            => 'till',
                'till'            => '',
                'paybill'         => '',
                'shortcode'       => '',
                'account'         => '',
                'consumer_key'    => '',
                'consumer_secret' => '',
                'passkey'         => '',
                'initiator'       => '',
                'ipass'           => '',
                'cert'            => '',
                'env'             => 'sandbox',
                'callback'        => '',
            ),
            'crypto' => array(
                'enable' => false,
                'chain'  => 'trc20',
                'trc20'  => '',
                'erc20'  => '',
                'bep20'  => '',
            ),
            'bank' => array(
                'enable'       => false,
                'name'         => '',
                'account'      => '',
                'beneficiary'  => '',
                'iban'         => '',
                'swift'        => '',
                'instructions' => '',
            ),
            'webhooks' => array(
                'enable' => false,
                'secret' => '',
                'url'    => '',
            ),
        );

        // If nested structure already exists, merge it
        if (isset($raw['stripe']) && is_array($raw['stripe'])) {
            $payments['stripe'] = array_merge($payments['stripe'], $raw['stripe']);
        }
        if (isset($raw['paypal']) && is_array($raw['paypal'])) {
            $payments['paypal'] = array_merge($payments['paypal'], $raw['paypal']);
        }
        if (isset($raw['flutterwave']) && is_array($raw['flutterwave'])) {
            $payments['flutterwave'] = array_merge($payments['flutterwave'], $raw['flutterwave']);
        }
        if (isset($raw['paystack']) && is_array($raw['paystack'])) {
            $payments['paystack'] = array_merge($payments['paystack'], $raw['paystack']);
        }
        if (isset($raw['mpesa']) && is_array($raw['mpesa'])) {
            $payments['mpesa'] = array_merge($payments['mpesa'], $raw['mpesa']);
        }
        if (isset($raw['crypto']) && is_array($raw['crypto'])) {
            $payments['crypto'] = array_merge($payments['crypto'], $raw['crypto']);
        }
        if (isset($raw['bank']) && is_array($raw['bank'])) {
            $payments['bank'] = array_merge($payments['bank'], $raw['bank']);
        }
        if (isset($raw['webhooks']) && is_array($raw['webhooks'])) {
            $payments['webhooks'] = array_merge($payments['webhooks'], $raw['webhooks']);
        }

        // Map legacy flat keys to nested structure (for backward compatibility)
        // Stripe
        if (isset($raw['stripe_enable'])) {
            $payments['stripe']['enable'] = !empty($raw['stripe_enable']);
        }
        if (!empty($raw['stripe_pk'])) {
            $payments['stripe']['pk'] = $raw['stripe_pk'];
        }
        if (!empty($raw['stripe_sk'])) {
            $payments['stripe']['sk'] = $raw['stripe_sk'];
        }
        if (!empty($raw['stripe_whsec'])) {
            $payments['stripe']['whsec'] = $raw['stripe_whsec'];
        }
        if (!empty($raw['stripe_webhook_secret'])) {
            $payments['stripe']['whsec'] = $raw['stripe_webhook_secret'];
        }

        // PayPal
        if (isset($raw['paypal_enable'])) {
            $payments['paypal']['enable'] = !empty($raw['paypal_enable']);
        }
        if (!empty($raw['paypal_email'])) {
            $payments['paypal']['email'] = $raw['paypal_email'];
        }
        if (!empty($raw['paypal_client'])) {
            $payments['paypal']['client'] = $raw['paypal_client'];
        }
        if (!empty($raw['paypal_secret'])) {
            $payments['paypal']['secret'] = $raw['paypal_secret'];
        }

        // Flutterwave
        if (isset($raw['fw_enable'])) {
            $payments['flutterwave']['enable'] = !empty($raw['fw_enable']);
        }
        if (!empty($raw['fw_public'])) {
            $payments['flutterwave']['pk'] = $raw['fw_public'];
        }
        if (!empty($raw['fw_secret'])) {
            $payments['flutterwave']['sk'] = $raw['fw_secret'];
        }

        // Paystack
        if (isset($raw['ps_enable'])) {
            $payments['paystack']['enable'] = !empty($raw['ps_enable']);
        }
        if (!empty($raw['ps_public'])) {
            $payments['paystack']['pk'] = $raw['ps_public'];
        }
        if (!empty($raw['ps_secret'])) {
            $payments['paystack']['sk'] = $raw['ps_secret'];
        }

        // M-Pesa
        if (isset($raw['mpesa_enable'])) {
            $payments['mpesa']['enable'] = !empty($raw['mpesa_enable']);
        }
        if (!empty($raw['mpesa_mode'])) {
            $payments['mpesa']['mode'] = $raw['mpesa_mode'];
        }
        if (!empty($raw['mpesa_till'])) {
            $payments['mpesa']['till'] = $raw['mpesa_till'];
            if (empty($payments['mpesa']['shortcode'])) {
                $payments['mpesa']['shortcode'] = $raw['mpesa_till'];
            }
        }
        if (!empty($raw['mpesa_paybill'])) {
            $payments['mpesa']['paybill'] = $raw['mpesa_paybill'];
            if ($payments['mpesa']['mode'] === 'paybill' && empty($payments['mpesa']['shortcode'])) {
                $payments['mpesa']['shortcode'] = $raw['mpesa_paybill'];
            }
        }
        if (!empty($raw['mpesa_account'])) {
            $payments['mpesa']['account'] = $raw['mpesa_account'];
        }
        if (!empty($raw['mpesa_ck'])) {
            $payments['mpesa']['consumer_key'] = $raw['mpesa_ck'];
        }
        if (!empty($raw['mpesa_cs'])) {
            $payments['mpesa']['consumer_secret'] = $raw['mpesa_cs'];
        }
        if (!empty($raw['mpesa_passkey'])) {
            $payments['mpesa']['passkey'] = $raw['mpesa_passkey'];
        }
        if (!empty($raw['mpesa_initiator'])) {
            $payments['mpesa']['initiator'] = $raw['mpesa_initiator'];
        }
        if (!empty($raw['mpesa_ipass'])) {
            $payments['mpesa']['ipass'] = $raw['mpesa_ipass'];
        }
        if (!empty($raw['mpesa_cert'])) {
            $payments['mpesa']['cert'] = $raw['mpesa_cert'];
        }
        if (!empty($raw['mpesa_env'])) {
            $payments['mpesa']['env'] = $raw['mpesa_env'];
        }
        if (!empty($raw['mpesa_callback_url'])) {
            $payments['mpesa']['callback'] = $raw['mpesa_callback_url'];
        }

        // Crypto
        if (isset($raw['cr_enable'])) {
            $payments['crypto']['enable'] = !empty($raw['cr_enable']);
        }
        if (!empty($raw['cr_chain'])) {
            $payments['crypto']['chain'] = $raw['cr_chain'];
        }
        if (!empty($raw['cr_trc20'])) {
            $payments['crypto']['trc20'] = $raw['cr_trc20'];
        }
        if (!empty($raw['cr_erc20'])) {
            $payments['crypto']['erc20'] = $raw['cr_erc20'];
        }
        if (!empty($raw['cr_bep20'])) {
            $payments['crypto']['bep20'] = $raw['cr_bep20'];
        }

        // Bank
        if (isset($raw['bank_enable'])) {
            $payments['bank']['enable'] = !empty($raw['bank_enable']);
        }
        if (!empty($raw['bank_name'])) {
            $payments['bank']['name'] = $raw['bank_name'];
        }
        if (!empty($raw['bank_account'])) {
            $payments['bank']['account'] = $raw['bank_account'];
        }
        if (!empty($raw['bank_beneficiary'])) {
            $payments['bank']['beneficiary'] = $raw['bank_beneficiary'];
        }
        if (!empty($raw['bank_iban'])) {
            $payments['bank']['iban'] = $raw['bank_iban'];
        }
        if (!empty($raw['bank_swift'])) {
            $payments['bank']['swift'] = $raw['bank_swift'];
        }
        if (!empty($raw['bank_instructions'])) {
            $payments['bank']['instructions'] = $raw['bank_instructions'];
        }

        // Webhooks
        if (isset($raw['wh_enable'])) {
            $payments['webhooks']['enable'] = !empty($raw['wh_enable']);
        }
        if (!empty($raw['wh_secret'])) {
            $payments['webhooks']['secret'] = $raw['wh_secret'];
        }
        if (!empty($raw['wh_url'])) {
            $payments['webhooks']['url'] = $raw['wh_url'];
        }

        return $payments;
    }
}

/**
 * Log messages for FASP debugging.
 *
 * @param string $message The message to log.
 * @param string $level   Log level (info, warning, error).
 */
if (!function_exists('fasp_log')) {
    function fasp_log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf('[FASP][%s] %s', strtoupper($level), $message));
        }
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
