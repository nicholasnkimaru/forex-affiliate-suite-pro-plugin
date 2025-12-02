<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Checkout Handlers — r14.3
 * Stripe Checkout, Flutterwave Payments, Paystack Initialize, M-Pesa STK Push
 */

function fasp_json($arr, $code=200){
    status_header($code);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($arr);
    exit;
}

function fasp_get_amount_currency(){
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'USD';
    if ($amount <= 0) fasp_json(['ok'=>false,'error'=>'Invalid amount'], 400);
    return [$amount, strtoupper($currency)];
}

add_action('wp_ajax_nopriv_fasp_create_checkout', 'fasp_create_checkout');
add_action('wp_ajax_fasp_create_checkout', 'fasp_create_checkout');
function fasp_create_checkout(){
    $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    $title = get_bloginfo('name') . ' Order';
    $success = esc_url_raw(isset($_POST['success_url']) ? $_POST['success_url'] : home_url('/?fasp=success'));
    $cancel  = esc_url_raw(isset($_POST['cancel_url'])  ? $_POST['cancel_url']  : home_url('/?fasp=cancel'));
    $opt = get_option('fasp_payments', []);

    if ($method === 'stripe'){
        $sk = $opt['stripe']['sk'] ?? '';
        if (!$sk){ fasp_json(['ok'=>false,'error'=>'Stripe secret missing'], 400); }
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
        if (is_wp_error($resp)) fasp_json(['ok'=>false,'error'=>$resp->get_error_message()], 500);
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['url'])){
            fasp_json(['ok'=>true,'redirect'=>$json['url']]);
        }
        fasp_json(['ok'=>false,'error'=>'Stripe error', 'details'=>$json], 502);
    }

    if ($method === 'flutterwave'){
        $sk = $opt['flutterwave']['sk'] ?? '';
        if (!$sk){ fasp_json(['ok'=>false,'error'=>'Flutterwave secret missing'], 400); }
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
        if (is_wp_error($resp)) fasp_json(['ok'=>false,'error'=>$resp->get_error_message()], 500);
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && isset($json['data']['link'])){
            fasp_json(['ok'=>true,'redirect'=>$json['data']['link']]);
        }
        fasp_json(['ok'=>false,'error'=>'Flutterwave error','details'=>$json], 502);
    }

    if ($method === 'paystack'){
        $sk = $opt['paystack']['sk'] ?? '';
        if (!$sk){ fasp_json(['ok'=>false,'error'=>'Paystack secret missing'], 400); }
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
        if (is_wp_error($resp)) fasp_json(['ok'=>false,'error'=>$resp->get_error_message()], 500);
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && !empty($json['data']['authorization_url'])){
            fasp_json(['ok'=>true,'redirect'=>$json['data']['authorization_url']]);
        }
        fasp_json(['ok'=>false,'error'=>'Paystack error','details'=>$json], 502);
    }

    fasp_json(['ok'=>false,'error'=>'Unsupported method'], 400);
}

/** M-Pesa STK Push */
add_action('wp_ajax_nopriv_fasp_mpesa_push', 'fasp_mpesa_push');
add_action('wp_ajax_fasp_mpesa_push', 'fasp_mpesa_push');
function fasp_mpesa_push(){
    $opt = get_option('fasp_payments', []);
    $mpesa = $opt['mpesa'] ?? [];
    $env = ($mpesa['env'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
    $base = $env === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    list($amount, $currency) = fasp_get_amount_currency();
    if (strtoupper($currency) !== 'KES'){
        // STK push is KES-only
        fasp_json(['ok'=>false,'error'=>'M-Pesa only supports KES'], 400);
    }
    // Normalize phone to 2547XXXXXXXX
    $phone = preg_replace('/\D+/', '', $phone);
    if (strpos($phone, '0') === 0) $phone = '254' . substr($phone,1);
    if (strpos($phone, '254') !== 0) $phone = '254' . ltrim($phone, '0');

    $shortcode = $mpesa['shortcode'] ?? ($mpesa['till'] ?? '');
    $passkey   = $mpesa['passkey'] ?? '';
    $account   = $mpesa['account'] ?? 'FASP';
    $consumer_key = $mpesa['consumer_key'] ?? '';
    $consumer_secret = $mpesa['consumer_secret'] ?? '';
    $mode = $mpesa['mode'] ?? 'till'; // till|paybill|both

    if (!$consumer_key || !$consumer_secret || !$shortcode || !$passkey){
        fasp_json(['ok'=>false,'error'=>'M-Pesa credentials missing'], 400);
    }

    // Access token
    $resp = wp_remote_get($base.'/oauth/v1/generate?grant_type=client_credentials', [
        'headers' => ['Authorization' => 'Basic '. base64_encode($consumer_key.':'.$consumer_secret) ],
        'timeout' => 20,
    ]);
    if (is_wp_error($resp)) fasp_json(['ok'=>false,'error'=>$resp->get_error_message()], 500);
    $tok = json_decode(wp_remote_retrieve_body($resp), true);
    $token = $tok['access_token'] ?? '';
    if (!$token) fasp_json(['ok'=>false,'error'=>'Failed to get token','details'=>$tok], 502);

    $timestamp = wp_date('YmdHis', time(), new DateTimeZone('Africa/Nairobi'));
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $endpoint = $base . '/mpesa/stkpush/v1/processrequest';
    $transactionType = 'CustomerPayBillOnline';
    if ($mode === 'till') $transactionType = 'CustomerBuyGoodsOnline';

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => esc_url_raw($mpesa['callback'] ?? home_url('/?fasp_webhook=mpesa')),
        'AccountReference' => substr($account,0,12),
        'TransactionDesc' => 'FASP Order'
    ];

    $stk = wp_remote_post($endpoint, [
        'headers' => ['Authorization' => 'Bearer '.$token, 'Content-Type'=>'application/json'],
        'body' => wp_json_encode($payload),
        'timeout' => 25,
    ]);
    if (is_wp_error($stk)) fasp_json(['ok'=>false,'error'=>$stk->get_error_message()], 500);
    $code = wp_remote_retrieve_response_code($stk);
    $json = json_decode(wp_remote_retrieve_body($stk), true);
    if ($code >= 200 && $code < 300){
        fasp_json(['ok'=>true,'response'=>$json]);
    }
    fasp_json(['ok'=>false,'error'=>'M-Pesa STK error','details'=>$json], 502);
}
