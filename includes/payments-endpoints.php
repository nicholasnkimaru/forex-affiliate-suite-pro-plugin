<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;

/**
 * Verify Stripe webhook signature manually using HMAC-SHA256.
 *
 * @param string $payload   Raw request body.
 * @param string $sig_header Stripe-Signature header value.
 * @param string $secret    Webhook secret key.
 * @param int    $tolerance Timestamp tolerance in seconds (default 300 = 5 minutes).
 * @return bool|WP_Error True if valid, WP_Error otherwise.
 */
function fasp_verify_stripe_signature($payload, $sig_header, $secret, $tolerance = 300) {
    if (empty($sig_header) || empty($secret)) {
        return new WP_Error('missing_signature', 'Missing signature or secret', array('status' => 401));
    }

    // Parse the signature header (format: t=TIMESTAMP,v1=SIGNATURE,v1=SIGNATURE2,...)
    $timestamp = null;
    $signatures = array();
    $elements = explode(',', $sig_header);
    
    foreach ($elements as $element) {
        $parts = explode('=', $element, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        if ($key === 't') {
            $timestamp = (int) $value;
        } elseif ($key === 'v1') {
            $signatures[] = $value;
        }
    }

    if ($timestamp === null) {
        return new WP_Error('invalid_header', 'Unable to extract timestamp from signature header', array('status' => 401));
    }

    if (empty($signatures)) {
        return new WP_Error('invalid_header', 'No valid signatures found in header', array('status' => 401));
    }

    // Check timestamp tolerance (prevent replay attacks)
    $current_time = time();
    if (abs($current_time - $timestamp) > $tolerance) {
        return new WP_Error('timestamp_expired', 'Timestamp outside tolerance window', array('status' => 401));
    }

    // Compute expected signature
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac('sha256', $signed_payload, $secret);

    // Compare signatures using timing-safe comparison
    $valid = false;
    foreach ($signatures as $sig) {
        if (hash_equals($expected_signature, $sig)) {
            $valid = true;
            break;
        }
    }

    if (!$valid) {
        return new WP_Error('invalid_signature', 'Signature verification failed', array('status' => 401));
    }

    return true;
}

/**
 * Check if a Stripe event has already been processed (idempotency).
 *
 * @param string $event_id Stripe event ID.
 * @return bool True if already processed, false otherwise.
 */
function fasp_is_stripe_event_processed($event_id) {
    $processed = get_option('fasp_stripe_processed_events', array());
    if (!is_array($processed)) {
        $processed = array();
    }
    return isset($processed[$event_id]);
}

/**
 * Mark a Stripe event as processed.
 *
 * @param string $event_id Stripe event ID.
 * @return void
 */
function fasp_mark_stripe_event_processed($event_id) {
    $processed = get_option('fasp_stripe_processed_events', array());
    if (!is_array($processed)) {
        $processed = array();
    }
    
    // Clean up old entries (older than 24 hours)
    $cutoff = time() - (24 * 60 * 60);
    foreach ($processed as $id => $timestamp) {
        if ($timestamp < $cutoff) {
            unset($processed[$id]);
        }
    }
    
    // Add new event
    $processed[$event_id] = time();
    
    // Limit to last 1000 events to prevent unbounded growth
    if (count($processed) > 1000) {
        asort($processed);
        $processed = array_slice($processed, -1000, 1000, true);
    }
    
    update_option('fasp_stripe_processed_events', $processed, false);
}

add_action('rest_api_init', function(){
  register_rest_route('fasp/v1','/stripe/webhook', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function($req){
      $payload = $req->get_body();
      $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) : '';
      
      // Get webhook secret from normalized payments config
      $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
      $secret = !empty($payments['stripe']['webhook_secret']) ? $payments['stripe']['webhook_secret'] : '';
      
      // Fallback to legacy option keys if not found
      if (empty($secret)) {
          $raw_opts = get_option('fasp_payments', array());
          $secret = $raw_opts['stripe_whsec'] ?? ($raw_opts['stripe_webhook_secret'] ?? '');
      }
      
      // Verify Stripe signature if secret is configured
      if (!empty($secret)) {
          $verification = fasp_verify_stripe_signature($payload, $sig_header, $secret);
          if (is_wp_error($verification)) {
              if (function_exists('fasp_log')) {
                  fasp_log(array(
                      'event' => 'stripe_webhook_signature_failed',
                      'error' => $verification->get_error_message(),
                      'time' => current_time('mysql'),
                  ));
              }
              return $verification;
          }
      } else {
          // Log warning that webhook secret is not configured
          if (function_exists('fasp_log')) {
              fasp_log(array(
                  'event' => 'stripe_webhook_no_secret',
                  'warning' => 'Webhook received without signature verification - no secret configured',
                  'time' => current_time('mysql'),
              ));
          }
      }
      
      $data = json_decode($payload, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
          return new WP_Error('bad_json', 'Invalid payload', array('status' => 400));
      }
      
      // Extract event ID for idempotency
      $event_id = isset($data['id']) ? sanitize_text_field($data['id']) : '';
      
      // Check idempotency - prevent duplicate processing
      if (!empty($event_id) && fasp_is_stripe_event_processed($event_id)) {
          if (function_exists('fasp_log')) {
              fasp_log(array(
                  'event' => 'stripe_webhook_duplicate',
                  'event_id' => $event_id,
                  'time' => current_time('mysql'),
              ));
          }
          return array('ok' => true, 'message' => 'Event already processed');
      }
      
      // Handle only known events
      $type = isset($data['type']) ? sanitize_text_field($data['type']) : '';
      $known_events = array('checkout.session.completed', 'payment_intent.succeeded');
      
      if (in_array($type, $known_events, true)) {
          // Extract minimal safe metadata
          $object = isset($data['data']['object']) && is_array($data['data']['object']) ? $data['data']['object'] : array();
          
          $payment_record = array(
              'type' => $type,
              'ts' => current_time('mysql'),
              'amount' => isset($object['amount_total']) ? intval($object['amount_total']) : (isset($object['amount']) ? intval($object['amount']) : null),
              'currency' => isset($object['currency']) ? sanitize_text_field($object['currency']) : null,
              'ref' => isset($object['id']) ? sanitize_text_field($object['id']) : null,
          );
          
          update_option('fasp_last_payment', $payment_record, false);
          
          // Also append to payments history (limited to last 100)
          $history = get_option('fasp_payments_history', array());
          if (!is_array($history)) {
              $history = array();
          }
          array_unshift($history, $payment_record);
          $history = array_slice($history, 0, 100);
          update_option('fasp_payments_history', $history, false);
          
          // Log successful processing
          if (function_exists('fasp_log')) {
              fasp_log(array(
                  'event' => 'stripe_webhook_processed',
                  'event_id' => $event_id,
                  'type' => $type,
                  'amount' => $payment_record['amount'],
                  'currency' => $payment_record['currency'],
                  'time' => current_time('mysql'),
              ));
          }
          
          // Mark event as processed for idempotency
          if (!empty($event_id)) {
              fasp_mark_stripe_event_processed($event_id);
          }
          
          // Fire action for extensibility
          do_action('fasp_stripe_webhook_processed', $type, $payment_record, $data);
      } else {
          // Log unhandled event type
          if (function_exists('fasp_log')) {
              fasp_log(array(
                  'event' => 'stripe_webhook_unhandled_type',
                  'type' => $type,
                  'event_id' => $event_id,
                  'time' => current_time('mysql'),
              ));
          }
      }
      
      return array('ok' => true);
    }
  ]);
});