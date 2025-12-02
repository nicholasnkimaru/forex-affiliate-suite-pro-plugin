<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Checkout Handlers — r14.3 (hardened)
 * Stripe Checkout, Flutterwave Payments, Paystack Initialize, M-Pesa STK Push
 */

// Allowed currencies whitelist
define('FASP_ALLOWED_CURRENCIES', array(
    'USD', 'EUR', 'GBP', 'KES', 'NGN', 'GHS', 'ZAR', 'UGX', 'TZS', 
    'RWF', 'CAD', 'AUD', 'JPY', 'INR', 'CNY', 'BRL', 'MXN'
));

/**
 * Check rate limit for AJAX requests.
 *
 * @param string $action  Action name for rate limiting.
 * @param int    $limit   Max requests per window.
 * @param int    $window  Time window in seconds.
 * @return bool True if within limit, false if exceeded.
 */
function fasp_check_rate_limit($action, $limit = 10, $window = 60) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'fasp_rl_' . md5($action . '_' . $ip);
    
    $data = get_transient($key);
    if ($data === false) {
        $data = array('count' => 0, 'start' => time());
    }
    
    // Reset if window has passed
    if ((time() - $data['start']) > $window) {
        $data = array('count' => 0, 'start' => time());
    }
    
    $data['count']++;
    set_transient($key, $data, $window);
    
    return $data['count'] <= $limit;
}

/**
 * Verify nonce for AJAX requests. For unauthenticated users, nonce is required.
 *
 * @return bool|WP_Error True if valid or logged-in user, WP_Error if invalid for anonymous.
 */
function fasp_verify_ajax_nonce() {
    // Check if nonce is present and valid
    $nonce_valid = check_ajax_referer('fasp_ajax', 'fasp_ajax_nonce', false);
    
    if ($nonce_valid) {
        return true;
    }
    
    // For logged-in users, we're more lenient during transition period
    if (is_user_logged_in()) {
        return true;
    }
    
    // For anonymous users, nonce is required
    return new WP_Error('nonce_failed', 'Security check failed', array('status' => 403));
}

function fasp_json($arr, $code=200){
    status_header($code);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($arr);
    exit;
}

function fasp_get_amount_currency(){
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? strtoupper(sanitize_text_field($_POST['currency'])) : 'USD';
    
    // Validate amount bounds (reasonable limits)
    if ($amount <= 0) {
        fasp_json(array('ok' => false, 'error' => 'Invalid amount'), 400);
    }
    if ($amount > 1000000) {
        fasp_json(array('ok' => false, 'error' => 'Amount exceeds maximum'), 400);
    }
    
    // Validate currency against whitelist
    if (!in_array($currency, FASP_ALLOWED_CURRENCIES, true)) {
        fasp_json(array('ok' => false, 'error' => 'Unsupported currency'), 400);
    }
    
    return array($amount, $currency);
}

add_action('wp_ajax_nopriv_fasp_create_checkout', 'fasp_create_checkout');
add_action('wp_ajax_fasp_create_checkout', 'fasp_create_checkout');
function fasp_create_checkout(){
    // Rate limiting: 10 requests per minute per IP
    if (!fasp_check_rate_limit('create_checkout', 10, 60)) {
        fasp_json(array('ok' => false, 'error' => 'Too many requests. Please wait.'), 429);
    }
    
    // Nonce verification for unauthenticated users
    $nonce_check = fasp_verify_ajax_nonce();
    if (is_wp_error($nonce_check)) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }

    $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    $title = get_bloginfo('name') . ' Order';
    
    // Validate and sanitize URLs
    $success_raw = isset($_POST['success_url']) ? esc_url_raw($_POST['success_url']) : '';
    $cancel_raw = isset($_POST['cancel_url']) ? esc_url_raw($_POST['cancel_url']) : '';
    
    // Ensure URLs are from same domain for security
    $home = wp_parse_url(home_url(), PHP_URL_HOST);
    $success = $success_raw;
    $cancel = $cancel_raw;
    
    if (!empty($success_raw)) {
        $success_host = wp_parse_url($success_raw, PHP_URL_HOST);
        if ($success_host && $success_host !== $home) {
            $success = home_url('/?fasp=success');
        }
    } else {
        $success = home_url('/?fasp=success');
    }
    
    if (!empty($cancel_raw)) {
        $cancel_host = wp_parse_url($cancel_raw, PHP_URL_HOST);
        if ($cancel_host && $cancel_host !== $home) {
            $cancel = home_url('/?fasp=cancel');
        }
    } else {
        $cancel = home_url('/?fasp=cancel');
    }
    
    // Use canonical payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    
    // Fallback to raw option for backward compatibility
    $opt = get_option('fasp_payments', array());

    if ($method === 'stripe'){
        $sk = !empty($payments['stripe']['sk']) ? $payments['stripe']['sk'] : ($opt['stripe']['sk'] ?? ($opt['stripe_sk'] ?? ''));
        if (!$sk){ 
            fasp_json(array('ok' => false, 'error' => 'Payment configuration error'), 400); 
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
            'headers' => array( 'Authorization' => 'Bearer ' . $sk ),
            'body' => $body,
            'timeout' => 25,
        ));
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Stripe API error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['url'])){
            fasp_json(array('ok' => true, 'redirect' => esc_url_raw($json['url'])));
        }
        if (function_exists('fasp_log')) {
            fasp_log('Stripe checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'flutterwave'){
        $sk = !empty($payments['flutterwave']['sk']) ? $payments['flutterwave']['sk'] : ($opt['flutterwave']['sk'] ?? ($opt['fw_secret'] ?? ''));
        if (!$sk){ 
            fasp_json(array('ok' => false, 'error' => 'Payment configuration error'), 400); 
        }
        
        // Validate email if provided
        $email = get_option('admin_email');
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $provided_email = sanitize_email($_POST['email']);
            if (is_email($provided_email)) {
                $email = $provided_email;
            }
        }
        
        $payload = array(
            'tx_ref' => 'fasp_' . wp_generate_uuid4(),
            'amount' => (string) $amount,
            'currency' => $currency,
            'redirect_url' => $success,
            'customer' => array(
                'email' => $email,
                'name'  => wp_get_current_user()->display_name ?: 'Customer'
            ),
            'customizations' => array('title' => $title)
        );
        $resp = wp_remote_post('https://api.flutterwave.com/v3/payments', array(
            'headers' => array( 'Authorization' => 'Bearer ' . $sk, 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ));
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Flutterwave API error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['data']['link'])){
            fasp_json(array('ok' => true, 'redirect' => esc_url_raw($json['data']['link'])));
        }
        if (function_exists('fasp_log')) {
            fasp_log('Flutterwave checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    if ($method === 'paystack'){
        $sk = !empty($payments['paystack']['sk']) ? $payments['paystack']['sk'] : ($opt['paystack']['sk'] ?? ($opt['ps_secret'] ?? ''));
        if (!$sk){ 
            fasp_json(array('ok' => false, 'error' => 'Payment configuration error'), 400); 
        }
        
        // Validate email format
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (empty($email) || !is_email($email)) {
            $email = get_option('admin_email');
        }
        
        $payload = array(
            'email' => $email,
            'amount' => intval(round($amount * 100)), // kobo/pesewas
            'currency' => $currency,
            'callback_url' => $success,
            'metadata' => array('custom_fields' => array(array('display_name' => 'Site', 'variable_name' => 'site', 'value' => home_url())))
        );
        $resp = wp_remote_post('https://api.paystack.co/transaction/initialize', array(
            'headers' => array( 'Authorization' => 'Bearer ' . $sk, 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode($payload),
            'timeout' => 25,
        ));
        if (is_wp_error($resp)) {
            if (function_exists('fasp_log')) {
                fasp_log('Paystack API error: ' . $resp->get_error_message(), 'error');
            }
            fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && !empty($json['data']['authorization_url'])){
            fasp_json(array('ok' => true, 'redirect' => esc_url_raw($json['data']['authorization_url'])));
        }
        if (function_exists('fasp_log')) {
            fasp_log('Paystack checkout failed: ' . wp_json_encode($json), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
    }

    fasp_json(array('ok' => false, 'error' => 'Unsupported method'), 400);
}

/** M-Pesa STK Push */
add_action('wp_ajax_nopriv_fasp_mpesa_push', 'fasp_mpesa_push');
add_action('wp_ajax_fasp_mpesa_push', 'fasp_mpesa_push');
function fasp_mpesa_push(){
    // Rate limiting: 5 requests per minute per IP (M-Pesa is sensitive)
    if (!fasp_check_rate_limit('mpesa_push', 5, 60)) {
        fasp_json(array('ok' => false, 'error' => 'Too many requests. Please wait.'), 429);
    }
    
    // Nonce verification for unauthenticated users
    $nonce_check = fasp_verify_ajax_nonce();
    if (is_wp_error($nonce_check)) {
        fasp_json(array('ok' => false, 'error' => 'Security verification failed'), 403);
    }

    // Use canonical payments accessor
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
    $mpesa = $payments['mpesa'] ?? array();
    
    // Fallback to raw option for backward compatibility
    if (empty($mpesa['consumer_key'])) {
        $opt = get_option('fasp_payments', array());
        $mpesa = $opt['mpesa'] ?? array();
    }
    
    $env = ($mpesa['env'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
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
    
    // Validate phone number format
    if (!preg_match('/^254[17]\d{8}$/', $phone)) {
        fasp_json(array('ok' => false, 'error' => 'Invalid phone number format'), 400);
    }

    $shortcode = $mpesa['shortcode'] ?? ($mpesa['till'] ?? '');
    $passkey   = $mpesa['passkey'] ?? '';
    $account   = $mpesa['account'] ?? 'FASP';
    $consumer_key = $mpesa['consumer_key'] ?? '';
    $consumer_secret = $mpesa['consumer_secret'] ?? '';
    $mode = $mpesa['mode'] ?? 'till'; // till|paybill|both

    if (!$consumer_key || !$consumer_secret || !$shortcode || !$passkey){
        fasp_json(array('ok' => false, 'error' => 'Payment configuration error'), 400);
    }

    // Access token
    $resp = wp_remote_get($base . '/oauth/v1/generate?grant_type=client_credentials', array(
        'headers' => array('Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)),
        'timeout' => 20,
    ));
    if (is_wp_error($resp)) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa OAuth error: ' . $resp->get_error_message(), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    $tok = json_decode(wp_remote_retrieve_body($resp), true);
    $token = $tok['access_token'] ?? '';
    if (!$token) {
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa token error: ' . wp_json_encode($tok), 'error');
        }
        fasp_json(array('ok' => false, 'error' => 'Payment service authentication failed'), 502);
    }

    $timestamp = wp_date('YmdHis', time(), new DateTimeZone('Africa/Nairobi'));
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $endpoint = $base . '/mpesa/stkpush/v1/processrequest';
    $transactionType = 'CustomerPayBillOnline';
    if ($mode === 'till') $transactionType = 'CustomerBuyGoodsOnline';

    // Validate and sanitize callback URL
    $callback_url = !empty($mpesa['callback']) ? esc_url_raw($mpesa['callback']) : home_url('/?fasp_webhook=mpesa');

    $payload = array(
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $callback_url,
        'AccountReference' => substr(sanitize_text_field($account), 0, 12),
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
        fasp_json(array('ok' => false, 'error' => 'Payment service temporarily unavailable'), 500);
    }
    $code = wp_remote_retrieve_response_code($stk);
    $json = json_decode(wp_remote_retrieve_body($stk), true);
    if ($code >= 200 && $code < 300){
        // Return minimal response, don't expose internal details
        $response_code = isset($json['ResponseCode']) ? sanitize_text_field($json['ResponseCode']) : '';
        $checkout_id = isset($json['CheckoutRequestID']) ? sanitize_text_field($json['CheckoutRequestID']) : '';
        fasp_json(array(
            'ok' => true, 
            'message' => 'STK push sent. Check your phone.',
            'checkout_id' => $checkout_id
        ));
    }
    if (function_exists('fasp_log')) {
        fasp_log('M-Pesa STK failed: ' . wp_json_encode($json), 'error');
    }
    fasp_json(array('ok' => false, 'error' => 'Payment initialization failed'), 502);
}

/**
 * Enqueue AJAX nonce for frontend scripts.
 */
add_action('wp_enqueue_scripts', function(){
    // Register a global object with nonce for AJAX calls
    wp_localize_script('jquery', 'fasp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('fasp_ajax'),
    ));
}, 20);

/**
 * Also add nonce for logged-in users in admin
 */
add_action('admin_enqueue_scripts', function(){
    wp_localize_script('jquery', 'fasp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('fasp_ajax'),
    ));
}, 20);
