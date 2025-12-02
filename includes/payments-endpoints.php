<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;

/**
 * Verify Stripe webhook signature manually (without stripe-php library).
 *
 * @param string $payload   Raw request body.
 * @param string $sig_header Stripe-Signature header value.
 * @param string $secret    Webhook secret from Stripe dashboard.
 * @param int    $tolerance Timestamp tolerance in seconds (default 300 = 5 minutes).
 * @return true|WP_Error True if valid, WP_Error otherwise.
 */
function fasp_verify_stripe_signature($payload, $sig_header, $secret, $tolerance = 300) {
    if (empty($sig_header) || empty($secret)) {
        return new WP_Error('missing_signature', 'Missing signature or secret', array('status' => 400));
    }

    // Parse signature header: t=timestamp,v1=signature,...
    $parts = explode(',', $sig_header);
    $timestamp = null;
    $signatures = array();
    
    foreach ($parts as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) !== 2) {
            continue;
        }
        $key = trim($kv[0]);
        $val = trim($kv[1]);
        
        if ($key === 't') {
            $timestamp = (int) $val;
        } elseif ($key === 'v1') {
            $signatures[] = $val;
        }
    }

    if ($timestamp === null) {
        return new WP_Error('invalid_signature', 'Missing timestamp in signature', array('status' => 400));
    }
    
    if (empty($signatures)) {
        return new WP_Error('invalid_signature', 'No valid signature found', array('status' => 400));
    }

    // Check timestamp tolerance to prevent replay attacks
    $now = time();
    if (abs($now - $timestamp) > $tolerance) {
        return new WP_Error('timestamp_expired', 'Webhook timestamp outside tolerance', array('status' => 400));
    }

    // Compute expected signature: HMAC-SHA256 of "timestamp.payload"
    $signed_payload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signed_payload, $secret);

    // Verify signature using timing-safe comparison
    $valid = false;
    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            $valid = true;
            break;
        }
    }

    if (!$valid) {
        return new WP_Error('signature_mismatch', 'Signature verification failed', array('status' => 400));
    }

    return true;
}

/**
 * Check if a Stripe event has already been processed (idempotency).
 *
 * @param string $event_id Stripe event ID.
 * @return bool True if already processed, false otherwise.
 */
function fasp_stripe_event_processed($event_id) {
    $transient_key = 'fasp_stripe_event_' . md5($event_id);
    return (bool) get_transient($transient_key);
}

/**
 * Mark a Stripe event as processed.
 *
 * @param string $event_id Stripe event ID.
 */
function fasp_stripe_mark_event_processed($event_id) {
    $transient_key = 'fasp_stripe_event_' . md5($event_id);
    // Store for 1 day to prevent duplicates
    set_transient($transient_key, 1, DAY_IN_SECONDS);
}

add_action('rest_api_init', function(){
  register_rest_route('fasp/v1','/stripe/webhook', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function($req){
      $payload = $req->get_body();
      $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) : '';
      
      // Get webhook secret from options (check both flat and nested keys)
      $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
      $secret = !empty($payments['stripe']['whsec']) ? $payments['stripe']['whsec'] : '';
      
      // Fallback to legacy flat key if nested not available
      if (empty($secret)) {
          $opts = get_option('fasp_payments', array());
          $secret = $opts['stripe_webhook_secret'] ?? ($opts['stripe_whsec'] ?? '');
      }

      // Verify signature if secret is configured
      if (!empty($secret)) {
          $verification = fasp_verify_stripe_signature($payload, $sig_header, $secret);
          if (is_wp_error($verification)) {
              if (function_exists('fasp_log')) {
                  fasp_log('Stripe webhook signature verification failed: ' . $verification->get_error_message(), 'error');
              }
              return $verification;
          }
      }

      // Parse JSON payload
      $data = json_decode($payload, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
          return new WP_Error('bad_json', 'Invalid JSON payload', array('status' => 400));
      }

      // Extract event ID for idempotency check
      $event_id = isset($data['id']) ? sanitize_text_field($data['id']) : '';
      if (empty($event_id)) {
          return new WP_Error('missing_event_id', 'Missing event ID', array('status' => 400));
      }

      // Check for duplicate event (idempotency)
      if (fasp_stripe_event_processed($event_id)) {
          // Already processed - return success to acknowledge receipt
          return array('ok' => true, 'message' => 'Event already processed');
      }

      // Handle supported events
      $type = sanitize_text_field($data['type'] ?? '');
      if (in_array($type, array('checkout.session.completed', 'payment_intent.succeeded'), true)) {
        $event_data = $data['data']['object'] ?? array();
        update_option('fasp_last_payment', array(
          'type'     => $type,
          'ts'       => current_time('mysql'),
          'amount'   => isset($event_data['amount_total']) ? $event_data['amount_total'] : (isset($event_data['amount']) ? $event_data['amount'] : null),
          'currency' => isset($event_data['currency']) ? sanitize_text_field($event_data['currency']) : null,
          'ref'      => isset($event_data['id']) ? sanitize_text_field($event_data['id']) : null,
        ));
        // Hook for extensions to handle payment events
        do_action('fasp/stripe/event', $type, $event_data, $data);
      }

      // Mark event as processed for idempotency
      fasp_stripe_mark_event_processed($event_id);

      return array('ok' => true);
    }
  ]);
});