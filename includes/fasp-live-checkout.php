<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Checkout Handlers — r14.3
 * Stripe Checkout, Flutterwave Payments, Paystack Initialize, M-Pesa STK Push
 */

// Currency whitelist for validation
define('FASP_ALLOWED_CURRENCIES', array(
    'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'INR', 'BRL',
    'KES', 'ZAR', 'NGN', 'GHS', 'UGX', 'TZS', 'RWF', 'ETB', 'XOF', 'XAF',
    'AED', 'SAR', 'QAR', 'KWD', 'BHD', 'OMR', 'MYR', 'SGD', 'HKD', 'TWD',
    'THB', 'PHP', 'IDR', 'VND', 'PKR', 'BDT', 'LKR', 'NPR', 'MXN', 'COP',
    'CLP', 'PEN', 'ARS', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'SEK', 'NOK',
    'DKK', 'ISK', 'NZD', 'ZMW', 'MWK', 'BWP', 'NAD', 'MZN', 'AOA', 'EGP',
    'MAD', 'TND', 'DZD', 'LYD', 'JOD', 'ILS', 'TRY', 'RUB', 'UAH', 'KZT'
));

function fasp_json($arr, $code=200){
    status_header($code);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($arr);
    exit;
}

/**
 * Check rate limiting for AJAX requests.
 *
 * @param string $action The action being rate-limited.
 * @return bool True if rate-limited, false otherwise.
 */
function fasp_check_ajax_rate_limit($action = 'checkout') {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
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
 * Validate nonce for AJAX requests.
 * Allows authenticated users without nonce as fallback.
 *
 * @return bool True if valid, false otherwise.
 */
function fasp_verify_ajax_nonce() {
    // Check nonce if provided
    if (check_ajax_referer('fasp_ajax', 'fasp_ajax_nonce', false)) {
        return true;
    }

    // Fallback: allow authenticated users without nonce
    if (is_user_logged_in() && current_user_can('read')) {
        return true;
    }

    return false;
}

function fasp_get_amount_currency(){
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? strtoupper(sanitize_text_field($_POST['currency'])) : 'USD';

    // Validate amount bounds
    if ($amount <= 0) {
        fasp_json(array('ok' => false, 'error' => 'Invalid amount: must be greater than 0'), 400);
    }
    if ($amount > 100000) {
        fasp_json(array('ok' => false, 'error' => 'Invalid amount: exceeds maximum allowed'), 400);
    }

    // Validate currency whitelist
    if (!in_array($currency, FASP_ALLOWED_CURRENCIES, true)) {
        fasp_json(array('ok' => false, 'error' => 'Unsupported currency'), 400);
    }

    return array($amount, $currency);
}

add_action('wp_ajax_nopriv_fasp_create_checkout', 'fasp_create_checkout');
add_action('wp_ajax_fasp_create_checkout', 'fasp_create_checkout');
function fasp_create_checkout(){
    // Rate limiting
    if (fasp_check_ajax_rate_limit('checkout')) {
        fasp_json(array('ok' => false, 'error' => 'Rate limit exceeded. Please try again later.'), 429);
    }

    // Nonce verification
    if (!fasp_verify_ajax_nonce()) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }

    $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    $title = get_bloginfo('name') . ' Order';
    $success = esc_url_raw(isset($_POST['success_url']) ? $_POST['success_url'] : home_url('/?fasp=success'));
    $cancel  = esc_url_raw(isset($_POST['cancel_url'])  ? $_POST['cancel_url']  : home_url('/?fasp=cancel'));

    // Get normalized payments config
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();

    if ($method === 'stripe'){
        $sk = $payments['stripe']['sk'] ?? '';
        if (!$sk){ fasp_json(array('ok' => false, 'error' => 'Payment gateway not configured'), 400); }
        $body = [
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $cancel,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => $title,
            'line_items[0][price_data][unit_amount]' => (int) round($amount * 100),
            'line_items[0][quantity]' => 1,
        ];
        $resp = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
            'headers' => [ 'Authorization' => 'Bearer ' . $sk ],
            'body' => $body,
            'timeout' => 25,
        ]);
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Stripe checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);

        // Log full response for debugging (not exposed to client)
        if (function_exists('fasp_log')) {
            fasp_log('Stripe checkout response: HTTP ' . $code, 'info');
        }

        if ($code >= 200 && $code < 300 && isset($json['url'])){
            fasp_json(array('ok' => true, 'redirect' => $json['url']));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'flutterwave'){
        $sk = $payments['flutterwave']['secret'] ?? '';
        if (!$sk){ fasp_json(array('ok' => false, 'error' => 'Payment gateway not configured'), 400); }
        $payload = [
            'tx_ref' => 'fasp_'.wp_generate_uuid4(),
            'amount' => (string) $amount,
            'currency' => $currency,
            'redirect_url' => $success,
            'customer' => [
                'email' => get_option('admin_email'),
                'name'  => wp_get_current_user()->display_name ?: 'Customer'
            ],
            'customizations' => ['title' => $title]
        ];
        $resp = wp_remote_post('https://api.flutterwave.com/v3/payments', [
            'headers' => [ 'Authorization' => 'Bearer '.$sk, 'Content-Type'=>'application/json' ],
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ]);
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Flutterwave checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);

        if (function_exists('fasp_log')) {
            fasp_log('Flutterwave checkout response: HTTP ' . $code, 'info');
        }

        if ($code >= 200 && $code < 300 && isset($json['data']['link'])){
            fasp_json(array('ok' => true, 'redirect' => $json['data']['link']));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'paystack'){
        $sk = $payments['paystack']['secret'] ?? '';
        if (!$sk){ fasp_json(array('ok' => false, 'error' => 'Payment gateway not configured'), 400); }
        $email = sanitize_email($_POST['email'] ?? get_option('admin_email'));
        $payload = [
            'email' => $email,
            'amount' => intval(round($amount * 100)), // kobo/pesewas
            'currency' => $currency,
            'callback_url' => $success,
            'metadata' => ['custom_fields'=>[['display_name'=>'Site','variable_name'=>'site','value'=>home_url()]]]
        ];
        $resp = wp_remote_post('https://api.paystack.co/transaction/initialize', [
            'headers' => [ 'Authorization' => 'Bearer '.$sk, 'Content-Type'=>'application/json' ],
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ]);
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Paystack checkout error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);

        if (function_exists('fasp_log')) {
            fasp_log('Paystack checkout response: HTTP ' . $code, 'info');
        }

        if ($code >= 200 && $code < 300 && !empty($json['data']['authorization_url'])){
            fasp_json(array('ok' => true, 'redirect' => $json['data']['authorization_url']));
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    fasp_json(array('ok' => false, 'error' => 'Unsupported payment method'), 400);
}

/** M-Pesa STK Push */
add_action('wp_ajax_nopriv_fasp_mpesa_push', 'fasp_mpesa_push');
add_action('wp_ajax_fasp_mpesa_push', 'fasp_mpesa_push');
function fasp_mpesa_push(){
    // Rate limiting
    if (fasp_check_ajax_rate_limit('mpesa')) {
        fasp_json(array('ok' => false, 'error' => 'Rate limit exceeded. Please try again later.'), 429);
    }

    // Nonce verification
    if (!fasp_verify_ajax_nonce()) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }

    // Get normalized payments config
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    $mpesa = $payments['mpesa'] ?? array();

    $env = ($mpesa['env'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
    $base = $env === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    if (strtoupper($currency) !== 'KES'){
        // STK push is KES-only
        fasp_json(array('ok' => false, 'error' => 'M-Pesa only supports KES currency'), 400);
    }
    // Normalize phone to 2547XXXXXXXX
    $phone = preg_replace('/\D+/', '', $phone);
    if (strpos($phone, '0') === 0) $phone = '254' . substr($phone,1);
    if (strpos($phone, '254') !== 0) $phone = '254' . ltrim($phone, '0');

    // Validate phone format
    if (!preg_match('/^254[0-9]{9}$/', $phone)) {
        fasp_json(array('ok' => false, 'error' => 'Invalid phone number format'), 400);
    }

    $shortcode = $mpesa['till'] ?? ($mpesa['paybill'] ?? '');
    $passkey   = $mpesa['passkey'] ?? '';
    $account   = 'FASP';
    $consumer_key = $mpesa['consumer_key'] ?? '';
    $consumer_secret = $mpesa['consumer_secret'] ?? '';

    // Determine mode from legacy flat keys if needed
    $opt = get_option('fasp_payments', array());
    $mode = $opt['mpesa_mode'] ?? 'till';

    if (!$consumer_key || !$consumer_secret || !$shortcode || !$passkey){
        fasp_json(array('ok' => false, 'error' => 'M-Pesa not properly configured'), 400);
    }

    // Access token
    $resp = wp_remote_get($base.'/oauth/v1/generate?grant_type=client_credentials', [
        'headers' => ['Authorization' => 'Basic '. base64_encode($consumer_key.':'.$consumer_secret) ],
        'timeout' => 20,
    ]);
    if (is_wp_error($resp)) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa token error: ' . $resp->get_error_message(), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    $tok = json_decode(wp_remote_retrieve_body($resp), true);
    $token = $tok['access_token'] ?? '';
    if (!$token) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa token response: ' . wp_json_encode($tok), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment authorization failed'), 502);
    }

    $timestamp = wp_date('YmdHis', time(), new DateTimeZone('Africa/Nairobi'));
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $endpoint = $base . '/mpesa/stkpush/v1/processrequest';
    $transactionType = 'CustomerPayBillOnline';
    if ($mode === 'till') $transactionType = 'CustomerBuyGoodsOnline';

    $callback_url = $mpesa['callback'] ?? '';
    if (empty($callback_url)) {
        $callback_url = home_url('/?fasp_webhook=mpesa');
    }

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => esc_url_raw($callback_url),
        'AccountReference' => substr($account,0,12),
        'TransactionDesc' => 'FASP Order'
    ];

    $stk = wp_remote_post($endpoint, [
        'headers' => ['Authorization' => 'Bearer '.$token, 'Content-Type'=>'application/json'],
        'body' => wp_json_encode($payload),
        'timeout' => 25,
    ]);
    if (is_wp_error($stk)) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa STK push error: ' . $stk->get_error_message(), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    $code = wp_remote_retrieve_response_code($stk);
    $json = json_decode(wp_remote_retrieve_body($stk), true);

    if (function_exists('fasp_log')) {
        fasp_log('M-Pesa STK response: HTTP ' . $code, 'info');
    }

    if ($code >= 200 && $code < 300){
        // Return safe response without raw provider details
        $safe_response = array(
            'CheckoutRequestID' => sanitize_text_field($json['CheckoutRequestID'] ?? ''),
            'ResponseCode' => sanitize_text_field($json['ResponseCode'] ?? ''),
            'ResponseDescription' => sanitize_text_field($json['ResponseDescription'] ?? ''),
        );
        fasp_json(array('ok' => true, 'response' => $safe_response));
    }
    fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
}
