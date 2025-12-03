<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Checkout Handlers — r14.4
 * Stripe Checkout, Flutterwave Payments, Paystack Initialize, M-Pesa STK Push
 * Security: AJAX nonce enforcement, IP rate limiting, input validation, error masking
 */

// Allowed currencies whitelist
define('FASP_ALLOWED_CURRENCIES', array(
    'USD', 'EUR', 'GBP', 'KES', 'NGN', 'GHS', 'ZAR', 'UGX', 'TZS', 'RWF',
    'AED', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'JPY', 'NZD', 'SGD', 'HKD'
));

// Amount bounds
define('FASP_MIN_AMOUNT', 0.50);
define('FASP_MAX_AMOUNT', 1000000);

// Rate limit settings
define('FASP_CHECKOUT_RATE_LIMIT', 5);
define('FASP_CHECKOUT_RATE_PERIOD', 60);

if (!function_exists('fasp_json')) {
    function fasp_json($arr, $code=200){
        status_header($code);
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode($arr);
        exit;
    }
}

/**
 * Get client IP address, sanitized.
 *
 * @return string Sanitized IP address.
 */
function fasp_get_client_ip(){
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can be a comma-separated list, take the first one
        $forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $ips = explode(',', $forwarded);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }
    // Validate as IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'unknown';
    }
    return $ip;
}

/**
 * Validate and sanitize amount and currency from POST.
 *
 * @return array [amount, currency]
 */
function fasp_get_amount_currency(){
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? strtoupper(sanitize_text_field($_POST['currency'])) : 'USD';

    // Validate amount bounds
    if ($amount < FASP_MIN_AMOUNT) {
        fasp_json(array('ok' => false, 'error' => 'Amount too small. Minimum is ' . FASP_MIN_AMOUNT), 400);
    }
    if ($amount > FASP_MAX_AMOUNT) {
        fasp_json(array('ok' => false, 'error' => 'Amount exceeds maximum allowed'), 400);
    }

    // Validate currency whitelist
    if (!in_array($currency, FASP_ALLOWED_CURRENCIES, true)) {
        fasp_json(array('ok' => false, 'error' => 'Unsupported currency'), 400);
    }

    return array($amount, $currency);
}

/**
 * Check rate limit and AJAX nonce for checkout requests.
 *
 * @return void
 */
function fasp_checkout_security_check(){
    // Rate limiting based on IP
    $client_ip = fasp_get_client_ip();
    $rate_key = 'checkout_' . $client_ip;

    if (function_exists('fasp_is_request_rate_limited') && fasp_is_request_rate_limited($rate_key, FASP_CHECKOUT_RATE_LIMIT, FASP_CHECKOUT_RATE_PERIOD)) {
        fasp_json(array('ok' => false, 'error' => 'Too many requests. Please wait and try again.'), 429);
    }

    // Increment request count
    if (function_exists('fasp_increment_request_count')) {
        fasp_increment_request_count($rate_key, FASP_CHECKOUT_RATE_PERIOD);
    }

    // AJAX nonce enforcement: require for unauthenticated users
    if (!is_user_logged_in()) {
        // For nopriv, require nonce
        if (!isset($_POST['fasp_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_ajax_nonce'])), 'fasp_ajax')) {
            fasp_json(array('ok' => false, 'error' => 'Security check failed. Please refresh the page.'), 403);
        }
    }
    // Logged-in users: allow without nonce for backward compatibility
}

add_action('wp_ajax_nopriv_fasp_create_checkout', 'fasp_create_checkout');
add_action('wp_ajax_fasp_create_checkout', 'fasp_create_checkout');
function fasp_create_checkout(){
    // Security checks
    fasp_checkout_security_check();

    $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    $title = get_bloginfo('name') . ' Order';
    $success = esc_url_raw(isset($_POST['success_url']) ? $_POST['success_url'] : home_url('/?fasp=success'));
    $cancel  = esc_url_raw(isset($_POST['cancel_url'])  ? $_POST['cancel_url']  : home_url('/?fasp=cancel'));

    // Use normalized payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();

    if ($method === 'stripe'){
        $sk = isset($payments['stripe']['sk']) ? $payments['stripe']['sk'] : '';
        if (!$sk){
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
            if (function_exists('fasp_log')) {
                fasp_log('Stripe checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['url'])){
            fasp_json(array('ok' => true, 'redirect' => $json['url']));
        }
        // Log detailed error, return generic message
        if (function_exists('fasp_log')) {
            fasp_log('Stripe checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'flutterwave'){
        $sk = isset($payments['flutterwave']['sk']) ? $payments['flutterwave']['sk'] : '';
        if (!$sk){
            fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
        }
        $payload = array(
            'tx_ref' => 'fasp_' . wp_generate_uuid4(),
            'amount' => (string) $amount,
            'currency' => $currency,
            'redirect_url' => $success,
            'customer' => array(
                'email' => get_option('admin_email'),
                'name'  => wp_get_current_user()->display_name ?: 'Customer'
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
                fasp_log('Flutterwave checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['data']['link'])){
            fasp_json(array('ok' => true, 'redirect' => $json['data']['link']));
        }
        if (function_exists('fasp_log')) {
            fasp_log('Flutterwave checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'paystack'){
        $sk = isset($payments['paystack']['sk']) ? $payments['paystack']['sk'] : '';
        if (!$sk){
            fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
        }
        $email = sanitize_email(isset($_POST['email']) ? $_POST['email'] : get_option('admin_email'));
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
                fasp_log('Paystack checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && !empty($json['data']['authorization_url'])){
            fasp_json(array('ok' => true, 'redirect' => $json['data']['authorization_url']));
        }
        if (function_exists('fasp_log')) {
            fasp_log('Paystack checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    fasp_json(array('ok' => false, 'error' => 'Unsupported payment method'), 400);
}

/** M-Pesa STK Push */
add_action('wp_ajax_nopriv_fasp_mpesa_push', 'fasp_mpesa_push');
add_action('wp_ajax_fasp_mpesa_push', 'fasp_mpesa_push');
function fasp_mpesa_push(){
    // Security checks
    fasp_checkout_security_check();

    // Use normalized payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    $mpesa = isset($payments['mpesa']) ? $payments['mpesa'] : array();
    $env = (isset($mpesa['env']) && $mpesa['env'] === 'live') ? 'live' : 'sandbox';
    $base = $env === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    if (strtoupper($currency) !== 'KES'){
        fasp_json(array('ok' => false, 'error' => 'M-Pesa only supports KES'), 400);
    }
    // Normalize phone to 2547XXXXXXXX
    $phone = preg_replace('/\D+/', '', $phone);
    if (strpos($phone, '0') === 0) $phone = '254' . substr($phone, 1);
    if (strpos($phone, '254') !== 0) $phone = '254' . ltrim($phone, '0');

    $shortcode = isset($mpesa['shortcode']) && $mpesa['shortcode'] ? $mpesa['shortcode'] : (isset($mpesa['till']) ? $mpesa['till'] : '');
    $passkey   = isset($mpesa['passkey']) ? $mpesa['passkey'] : '';
    $account   = isset($mpesa['account']) ? $mpesa['account'] : 'FASP';
    $consumer_key = isset($mpesa['consumer_key']) ? $mpesa['consumer_key'] : '';
    $consumer_secret = isset($mpesa['consumer_secret']) ? $mpesa['consumer_secret'] : '';
    $mode = isset($mpesa['mode']) ? $mpesa['mode'] : 'till';

    if (!$consumer_key || !$consumer_secret || !$shortcode || !$passkey){
        fasp_json(array('ok' => false, 'error' => 'Payment method not configured'), 400);
    }

    // Access token
    $resp = wp_remote_get($base . '/oauth/v1/generate?grant_type=client_credentials', array(
        'headers' => array('Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)),
        'timeout' => 20,
    ));
    if (is_wp_error($resp)) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa token error: ' . $resp->get_error_message(), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 500);
    }
    $tok = json_decode(wp_remote_retrieve_body($resp), true);
    $token = isset($tok['access_token']) ? $tok['access_token'] : '';
    if (!$token) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa token retrieval failed: ' . wp_json_encode($tok), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 502);
    }

    $timestamp = wp_date('YmdHis', time(), new DateTimeZone('Africa/Nairobi'));
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $endpoint = $base . '/mpesa/stkpush/v1/processrequest';
    $transactionType = 'CustomerPayBillOnline';
    if ($mode === 'till') $transactionType = 'CustomerBuyGoodsOnline';

    $callback = isset($mpesa['callback']) && $mpesa['callback'] ? $mpesa['callback'] : home_url('/?fasp_webhook=mpesa');
    $payload = array(
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => esc_url_raw($callback),
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
            fasp_log('M-Pesa STK error: ' . $stk->get_error_message(), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service unavailable'), 500);
    }
    $code = wp_remote_retrieve_response_code($stk);
    $json = json_decode(wp_remote_retrieve_body($stk), true);
    if ($code >= 200 && $code < 300){
        // Return only non-sensitive response data
        $safe_response = array(
            'CheckoutRequestID' => isset($json['CheckoutRequestID']) ? $json['CheckoutRequestID'] : '',
            'ResponseCode' => isset($json['ResponseCode']) ? $json['ResponseCode'] : '',
            'CustomerMessage' => isset($json['CustomerMessage']) ? $json['CustomerMessage'] : '',
        );
        fasp_json(array('ok' => true, 'response' => $safe_response));
    }
    if (function_exists('fasp_log')) {
        fasp_log('M-Pesa STK push failed: ' . wp_json_encode($json), 'error');
    }
    fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
}

/**
 * Localize AJAX nonce for front-end checkout scripts.
 */
add_action('wp_enqueue_scripts', function(){
    // Register a localized script variable for nonce
    wp_localize_script('jquery', 'faspCheckout', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fasp_ajax'),
    ));
}, 20);
