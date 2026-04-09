<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Checkout Handlers — r14.3 (hardened)
 * Stripe Checkout, Flutterwave Payments, Paystack Initialize, M-Pesa STK Push
 * 
 * Security improvements:
 * - Nonce verification for AJAX requests
 * - IP-based rate limiting
 * - Amount/currency validation with bounds
 * - Sanitized error responses (no raw provider details to clients)
 */

// Currency whitelist for validation
define('FASP_ALLOWED_CURRENCIES', array(
    'USD', 'EUR', 'GBP', 'KES', 'NGN', 'GHS', 'ZAR', 'TZS', 'UGX', 'RWF',
    'CAD', 'AUD', 'NZD', 'CHF', 'JPY', 'CNY', 'INR', 'BRL', 'MXN', 'SGD',
    'HKD', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'ILS', 'AED', 'SAR'
));

// Amount limits
define('FASP_MIN_AMOUNT', 0.01);
define('FASP_MAX_AMOUNT', 100000);

/**
 * Output JSON response and exit.
 *
 * @param array $arr  Response array.
 * @param int   $code HTTP status code.
 */
function fasp_json($arr, $code = 200) {
    status_header($code);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($arr);
    exit;
}

/**
 * Check if request is rate-limited based on IP.
 *
 * @param string $action Action identifier.
 * @return bool True if rate-limited.
 */
function fasp_check_rate_limit($action) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = $action . '_' . $ip;
    
    if (function_exists('fasp_is_request_rate_limited') && fasp_is_request_rate_limited($key, 5, 60)) {
        return true;
    }
    
    if (function_exists('fasp_increment_request_count')) {
        fasp_increment_request_count($key, 60);
    }
    
    return false;
}

/**
 * Verify nonce for AJAX request with fallback for authenticated users.
 *
 * @param string $action Action name for nonce.
 * @return bool True if verified, false otherwise.
 */
function fasp_verify_ajax_request($action = 'fasp_ajax') {
    // Check nonce from POST data
    $nonce_valid = false;
    
    if (isset($_POST['fasp_ajax_nonce'])) {
        $nonce_valid = wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_ajax_nonce'])), $action);
    }
    
    // Fallback: allow authenticated users without nonce (for backwards compatibility)
    if (!$nonce_valid && is_user_logged_in() && current_user_can('read')) {
        return true;
    }
    
    return (bool) $nonce_valid;
}

/**
 * Validate and get amount and currency from request.
 *
 * @return array [amount, currency] or exits with error.
 */
function fasp_get_amount_currency() {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? strtoupper(sanitize_text_field($_POST['currency'])) : 'USD';
    
    // Validate amount bounds
    if ($amount < FASP_MIN_AMOUNT) {
        fasp_json(array('ok' => false, 'error' => 'Amount must be greater than ' . FASP_MIN_AMOUNT), 400);
    }
    
    if ($amount > FASP_MAX_AMOUNT) {
        fasp_json(array('ok' => false, 'error' => 'Amount exceeds maximum allowed (' . FASP_MAX_AMOUNT . ')'), 400);
    }
    
    // Validate currency against whitelist
    if (!in_array($currency, FASP_ALLOWED_CURRENCIES, true)) {
        fasp_json(array('ok' => false, 'error' => 'Unsupported currency'), 400);
    }
    
    return array($amount, $currency);
}

add_action('wp_ajax_nopriv_fasp_create_checkout', 'fasp_create_checkout');
add_action('wp_ajax_fasp_create_checkout', 'fasp_create_checkout');

/**
 * Handle checkout creation for various payment methods.
 */
function fasp_create_checkout() {
    // Rate limiting check
    if (fasp_check_rate_limit('fasp_checkout')) {
        fasp_json(array('ok' => false, 'error' => 'Too many requests. Please wait and try again.'), 429);
    }
    
    // Nonce verification with fallback for authenticated users
    if (!fasp_verify_ajax_request('fasp_ajax')) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }
    
    $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    $title = get_bloginfo('name') . ' Order';
    $success = esc_url_raw(isset($_POST['success_url']) ? $_POST['success_url'] : home_url('/?fasp=success'));
    $cancel = esc_url_raw(isset($_POST['cancel_url']) ? $_POST['cancel_url'] : home_url('/?fasp=cancel'));
    
    // Use normalized payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    
    // Fallback to raw option for backwards compatibility
    if (empty($payments)) {
        $payments = get_option('fasp_payments', array());
    }

    if ($method === 'stripe') {
        $sk = isset($payments['stripe']['sk']) ? $payments['stripe']['sk'] : '';
        if (empty($sk) && isset($payments['stripe_sk'])) {
            $sk = $payments['stripe_sk'];
        }
        
        if (empty($sk)) {
            fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
        }
        
        $body = array(
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $cancel,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => $title,
            'line_items[0][price_data][unit_amount]' => (int) round($amount * 100),
            'line_items[0][quantity]' => 1,
        );
        
        $resp = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
            'headers' => array('Authorization' => 'Bearer ' . $sk),
            'body' => $body,
            'timeout' => 25,
        ));
        
        if (is_wp_error($resp)) {
            // Log full error details internally
            if (function_exists('fasp_log')) {
                fasp_log(array(
                    'event' => 'stripe_checkout_error',
                    'error' => $resp->get_error_message(),
                    'time' => current_time('mysql'),
                ));
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        
        if ($code >= 200 && $code < 300 && isset($json['url'])) {
            fasp_json(array('ok' => true, 'redirect' => $json['url']));
        }
        
        // Log full response internally, return generic error to client
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'stripe_checkout_failed',
                'response' => $json,
                'code' => $code,
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'flutterwave') {
        $sk = isset($payments['flutterwave']['secret']) ? $payments['flutterwave']['secret'] : '';
        if (empty($sk) && isset($payments['fw_secret'])) {
            $sk = $payments['fw_secret'];
        }
        
        if (empty($sk)) {
            fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
        }
        
        $payload = array(
            'tx_ref' => 'fasp_' . wp_generate_uuid4(),
            'amount' => (string) $amount,
            'currency' => $currency,
            'redirect_url' => $success,
            'customer' => array(
                'email' => get_option('admin_email'),
                'name' => wp_get_current_user()->display_name ?: 'Customer'
            ),
            'customizations' => array('title' => $title)
        );
        
        $resp = wp_remote_post('https://api.flutterwave.com/v3/payments', array(
            'headers' => array('Authorization' => 'Bearer ' . $sk, 'Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ));
        
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log(array(
                    'event' => 'flutterwave_checkout_error',
                    'error' => $resp->get_error_message(),
                    'time' => current_time('mysql'),
                ));
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        
        if ($code >= 200 && $code < 300 && isset($json['data']['link'])) {
            fasp_json(array('ok' => true, 'redirect' => $json['data']['link']));
        }
        
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'flutterwave_checkout_failed',
                'response' => $json,
                'code' => $code,
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'paystack') {
        $sk = isset($payments['paystack']['secret']) ? $payments['paystack']['secret'] : '';
        if (empty($sk) && isset($payments['ps_secret'])) {
            $sk = $payments['ps_secret'];
        }
        
        if (empty($sk)) {
            fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
        }
        
        $email = sanitize_email($_POST['email'] ?? get_option('admin_email'));
        $payload = array(
            'email' => $email,
            'amount' => intval(round($amount * 100)),
            'currency' => $currency,
            'callback_url' => $success,
            'metadata' => array('custom_fields' => array(array('display_name' => 'Site', 'variable_name' => 'site', 'value' => home_url())))
        );
        
        $resp = wp_remote_post('https://api.paystack.co/transaction/initialize', array(
            'headers' => array('Authorization' => 'Bearer ' . $sk, 'Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ));
        
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log(array(
                    'event' => 'paystack_checkout_error',
                    'error' => $resp->get_error_message(),
                    'time' => current_time('mysql'),
                ));
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        
        if ($code >= 200 && $code < 300 && !empty($json['data']['authorization_url'])) {
            fasp_json(array('ok' => true, 'redirect' => $json['data']['authorization_url']));
        }
        
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'paystack_checkout_failed',
                'response' => $json,
                'code' => $code,
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    fasp_json(array('ok' => false, 'error' => 'Unsupported payment method'), 400);
}

/** M-Pesa STK Push */
add_action('wp_ajax_nopriv_fasp_mpesa_push', 'fasp_mpesa_push');
add_action('wp_ajax_fasp_mpesa_push', 'fasp_mpesa_push');

/**
 * Handle M-Pesa STK Push request.
 */
function fasp_mpesa_push() {
    // Rate limiting check
    if (fasp_check_rate_limit('fasp_mpesa')) {
        fasp_json(array('ok' => false, 'error' => 'Too many requests. Please wait and try again.'), 429);
    }
    
    // Nonce verification with fallback for authenticated users
    if (!fasp_verify_ajax_request('fasp_ajax')) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }
    
    // Use normalized payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    
    // Fallback to raw option for backwards compatibility
    if (empty($payments)) {
        $payments = get_option('fasp_payments', array());
    }
    
    $mpesa = isset($payments['mpesa']) && is_array($payments['mpesa']) ? $payments['mpesa'] : array();
    $env = (isset($mpesa['env']) && $mpesa['env'] === 'live') ? 'live' : 'sandbox';
    $base = $env === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    
    if (strtoupper($currency) !== 'KES') {
        fasp_json(array('ok' => false, 'error' => 'M-Pesa only supports KES'), 400);
    }
    
    // Normalize phone to 2547XXXXXXXX
    $phone = preg_replace('/\D+/', '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '254' . substr($phone, 1);
    }
    if (strpos($phone, '254') !== 0) {
        $phone = '254' . ltrim($phone, '0');
    }

    $shortcode = isset($mpesa['till']) ? $mpesa['till'] : '';
    if (empty($shortcode) && isset($mpesa['shortcode'])) {
        $shortcode = $mpesa['shortcode'];
    }
    
    $passkey = isset($mpesa['passkey']) ? $mpesa['passkey'] : '';
    $account = isset($mpesa['account']) ? $mpesa['account'] : 'FASP';
    $consumer_key = isset($mpesa['consumer_key']) ? $mpesa['consumer_key'] : '';
    $consumer_secret = isset($mpesa['consumer_secret']) ? $mpesa['consumer_secret'] : '';
    $mode = isset($mpesa['mode']) ? $mpesa['mode'] : 'till';

    if (empty($consumer_key) || empty($consumer_secret) || empty($shortcode) || empty($passkey)) {
        fasp_json(array('ok' => false, 'error' => 'M-Pesa not configured'), 400);
    }

    // Access token
    $resp = wp_remote_get($base . '/oauth/v1/generate?grant_type=client_credentials', array(
        'headers' => array('Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)),
        'timeout' => 20,
    ));
    
    if (is_wp_error($resp)) {
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'mpesa_token_error',
                'error' => $resp->get_error_message(),
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    
    $tok = json_decode(wp_remote_retrieve_body($resp), true);
    $token = isset($tok['access_token']) ? $tok['access_token'] : '';
    
    if (empty($token)) {
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'mpesa_token_failed',
                'response' => $tok,
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Failed to authenticate with payment service'), 502);
    }

    $timestamp = wp_date('YmdHis', time(), new DateTimeZone('Africa/Nairobi'));
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $endpoint = $base . '/mpesa/stkpush/v1/processrequest';
    $transactionType = 'CustomerPayBillOnline';
    if ($mode === 'till') {
        $transactionType = 'CustomerBuyGoodsOnline';
    }

    $callback_url = isset($mpesa['callback']) ? $mpesa['callback'] : home_url('/?fasp_webhook=mpesa');
    
    $payload = array(
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => esc_url_raw($callback_url),
        'AccountReference' => substr($account, 0, 12),
        'TransactionDesc' => 'FASP Order'
    );

    $stk = wp_remote_post($endpoint, array(
        'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode($payload),
        'timeout' => 25,
    ));
    
    if (is_wp_error($stk)) {
        if (function_exists('fasp_log')) {
            fasp_log(array(
                'event' => 'mpesa_stk_error',
                'error' => $stk->get_error_message(),
                'time' => current_time('mysql'),
            ));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    
    $code = wp_remote_retrieve_response_code($stk);
    $json = json_decode(wp_remote_retrieve_body($stk), true);
    
    if ($code >= 200 && $code < 300) {
        // Return only safe response data
        $safe_response = array(
            'CheckoutRequestID' => isset($json['CheckoutRequestID']) ? sanitize_text_field($json['CheckoutRequestID']) : '',
            'ResponseDescription' => isset($json['ResponseDescription']) ? sanitize_text_field($json['ResponseDescription']) : '',
        );
        fasp_json(array('ok' => true, 'checkout_id' => $safe_response['CheckoutRequestID'], 'message' => $safe_response['ResponseDescription']));
    }
    
    if (function_exists('fasp_log')) {
        fasp_log(array(
            'event' => 'mpesa_stk_failed',
            'response' => $json,
            'code' => $code,
            'time' => current_time('mysql'),
        ));
    }
    fasp_json(array('ok' => false, 'error' => 'M-Pesa request failed'), 502);
}

/**
 * Enqueue nonce for frontend AJAX calls.
 */
add_action('wp_enqueue_scripts', function() {
    // Only add if on a page that might use checkout
    wp_localize_script('jquery', 'fasp_ajax', array(
        'nonce' => wp_create_nonce('fasp_ajax'),
        'url' => admin_url('admin-ajax.php'),
    ));
}, 100);
