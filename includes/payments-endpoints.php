<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;

/**
 * Verify Stripe webhook signature manually using HMAC.
 *
 * @param string $payload Raw request body.
 * @param string $sig_header Stripe-Signature header value.
 * @param string $secret Webhook secret.
 * @param int $tolerance Timestamp tolerance in seconds (default 300 = 5 minutes).
 * @return array ['valid' => bool, 'event_id' => string|null, 'error' => string|null]
 */
function fasp_verify_stripe_signature($payload, $sig_header, $secret, $tolerance = 300) {
    if (empty($sig_header) || empty($secret)) {
        return array('valid' => false, 'event_id' => null, 'error' => 'Missing signature or secret');
    }

    // Parse the header: t=timestamp,v1=signature1,v1=signature2,...
    $parts = explode(',', $sig_header);
    $timestamp = null;
    $signatures = array();

    foreach ($parts as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) !== 2) continue;
        $key = trim($kv[0]);
        $value = trim($kv[1]);
        if ($key === 't') {
            $timestamp = (int) $value;
        } elseif ($key === 'v1') {
            $signatures[] = $value;
        }
    }

    if ($timestamp === null || empty($signatures)) {
        return array('valid' => false, 'event_id' => null, 'error' => 'Invalid signature header format');
    }

    // Check timestamp tolerance (5 minutes)
    $current_time = time();
    if (abs($current_time - $timestamp) > $tolerance) {
        return array('valid' => false, 'event_id' => null, 'error' => 'Timestamp outside tolerance window');
    }

    // Compute expected signature
    $signed_payload = $timestamp . '.' . $payload;
    $expected_sig = hash_hmac('sha256', $signed_payload, $secret);

    // Compare with provided signatures using timing-safe comparison
    $valid = false;
    foreach ($signatures as $sig) {
        if (hash_equals($expected_sig, $sig)) {
            $valid = true;
            break;
        }
    }

    if (!$valid) {
        return array('valid' => false, 'event_id' => null, 'error' => 'Signature verification failed');
    }

    // Extract event ID from payload for idempotency
    $data = json_decode($payload, true);
    $event_id = isset($data['id']) ? sanitize_text_field($data['id']) : null;

    return array('valid' => true, 'event_id' => $event_id, 'error' => null);
}

/**
 * Check if an event ID has already been processed (idempotency).
 *
 * @param string $event_id Stripe event ID.
 * @return bool True if already processed.
 */
function fasp_is_stripe_event_processed($event_id) {
    if (empty($event_id)) return false;
    $processed = get_option('fasp_stripe_processed_events', array());
    if (!is_array($processed)) $processed = array();
    return isset($processed[$event_id]);
}

/**
 * Mark a Stripe event as processed (idempotency).
 *
 * @param string $event_id Stripe event ID.
 * @return void
 */
function fasp_mark_stripe_event_processed($event_id) {
    if (empty($event_id)) return;

    $processed = get_option('fasp_stripe_processed_events', array());
    if (!is_array($processed)) $processed = array();

    // Add new event with timestamp
    $processed[$event_id] = time();

    // Clean up old events (older than 24 hours)
    $cutoff = time() - (24 * 60 * 60);
    $processed = array_filter($processed, function($ts) use ($cutoff) {
        return $ts > $cutoff;
    });

    update_option('fasp_stripe_processed_events', $processed);
}

add_action('rest_api_init', function(){
  register_rest_route('fasp/v1','/stripe/webhook', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function($req){
      $payload = $req->get_body();
      $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field($_SERVER['HTTP_STRIPE_SIGNATURE']) : '';

      // Get webhook secret from normalized payments config
      $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
      $secret = $payments['stripe']['webhook_secret'] ?? '';

      // Fallback to legacy options
      if (empty($secret)) {
          $opts = get_option('fasp_payments', array());
          $secret = $opts['stripe_whsec'] ?? ($opts['stripe_webhook_secret'] ?? '');
      }

      // Verify signature
      if (!empty($secret)) {
          $verification = fasp_verify_stripe_signature($payload, $sig_header, $secret);

          if (!$verification['valid']) {
              if (function_exists('fasp_log')) {
                  fasp_log('Stripe webhook signature verification failed: ' . $verification['error'], 'error');
              }
              return new WP_Error('invalid_signature', $verification['error'], array('status' => 401));
          }

          // Check idempotency
          if (!empty($verification['event_id']) && fasp_is_stripe_event_processed($verification['event_id'])) {
              if (function_exists('fasp_log')) {
                  fasp_log('Stripe webhook event already processed: ' . $verification['event_id'], 'info');
              }
              return array('ok' => true, 'message' => 'Event already processed');
          }
      } else {
          // Log warning that verification is skipped
          if (function_exists('fasp_log')) {
              fasp_log('Stripe webhook received without signature verification (no webhook secret configured)', 'warning');
          }
      }

      $data = json_decode($payload, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
          if (function_exists('fasp_log')) {
              fasp_log('Stripe webhook: Invalid JSON payload', 'error');
          }
          return new WP_Error('bad_json', 'Invalid payload', array('status' => 400));
      }

      // Handle known events only
      $type = sanitize_text_field($data['type'] ?? '');
      $event_id = sanitize_text_field($data['id'] ?? '');

      $known_events = array('checkout.session.completed', 'payment_intent.succeeded');

      if (in_array($type, $known_events, true)) {
          // Extract safe, minimal metadata
          $amount = isset($data['data']['object']['amount_total'])
              ? intval($data['data']['object']['amount_total'])
              : (isset($data['data']['object']['amount']) ? intval($data['data']['object']['amount']) : null);
          $currency = isset($data['data']['object']['currency'])
              ? sanitize_text_field($data['data']['object']['currency'])
              : null;
          $ref = isset($data['data']['object']['id'])
              ? sanitize_text_field($data['data']['object']['id'])
              : null;

          // Store last payment info
          $payment_record = array(
              'type'     => $type,
              'ts'       => current_time('mysql'),
              'amount'   => $amount,
              'currency' => $currency,
              'ref'      => $ref,
              'event_id' => $event_id,
          );
          update_option('fasp_last_payment', $payment_record);

          // Also log to payments array for history
          $payments_log = get_option('fasp_last_payments', array());
          if (!is_array($payments_log)) $payments_log = array();
          array_unshift($payments_log, $payment_record);
          // Keep only last 100 payments
          $payments_log = array_slice($payments_log, 0, 100);
          update_option('fasp_last_payments', $payments_log);

          // Mark as processed for idempotency
          fasp_mark_stripe_event_processed($event_id);

          if (function_exists('fasp_log')) {
              fasp_log('Stripe webhook processed: ' . $type . ' - Event ID: ' . $event_id, 'info');
          }

          // Trigger action for extensibility
          do_action('fasp/stripe/webhook/' . $type, $data['data']['object'] ?? array(), $event_id);
      } else {
          if (function_exists('fasp_log')) {
              fasp_log('Stripe webhook received unknown event type: ' . $type, 'info');
          }
      }

      return array('ok' => true);
    }
  ]);
});