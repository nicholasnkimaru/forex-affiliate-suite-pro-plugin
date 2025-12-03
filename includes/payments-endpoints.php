<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;

/**
 * Stripe Webhook Endpoint with Signature Verification
 * 
 * Implements:
 * - Stripe webhook signature verification using Stripe-Signature header
 * - Timestamp tolerance enforcement (default 5 minutes)
 * - Transient-based idempotency for event IDs (24h)
 * - Only processes known events (checkout.session.completed, payment_intent.succeeded)
 */

// Default webhook timestamp tolerance (5 minutes)
define('FASP_STRIPE_WEBHOOK_TOLERANCE', 300);

// Idempotency TTL (24 hours)
define('FASP_STRIPE_IDEMPOTENCY_TTL', 86400);

// Allowed event types
define('FASP_STRIPE_ALLOWED_EVENTS', array(
    'checkout.session.completed',
    'payment_intent.succeeded'
));

/**
 * Parse Stripe-Signature header into components.
 *
 * @param string $header The Stripe-Signature header value.
 * @return array Associative array with 't' (timestamp) and 'v1' (signature) keys.
 */
function fasp_parse_stripe_signature($header) {
    $result = array('t' => '', 'v1' => array());
    if (empty($header)) {
        return $result;
    }

    $pairs = explode(',', $header);
    foreach ($pairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        if ($key === 't') {
            $result['t'] = $value;
        } elseif ($key === 'v1') {
            $result['v1'][] = $value;
        }
    }
    return $result;
}

/**
 * Verify Stripe webhook signature.
 *
 * @param string $payload   Raw request body.
 * @param string $sig_header Stripe-Signature header value.
 * @param string $secret    Webhook signing secret.
 * @param int    $tolerance Max age in seconds for the timestamp (default 300).
 * @return bool|WP_Error True if valid, WP_Error on failure.
 */
function fasp_verify_stripe_signature($payload, $sig_header, $secret, $tolerance = FASP_STRIPE_WEBHOOK_TOLERANCE) {
    if (empty($sig_header)) {
        return new WP_Error('missing_signature', 'No Stripe-Signature header present', array('status' => 400));
    }

    if (empty($secret)) {
        if (function_exists('fasp_log')) {
            fasp_log('Stripe webhook secret not configured', 'warning');
        }
        // If no secret configured, skip verification (backward compatibility)
        return true;
    }

    $parsed = fasp_parse_stripe_signature($sig_header);
    
    if (empty($parsed['t'])) {
        return new WP_Error('invalid_signature', 'Missing timestamp in signature', array('status' => 400));
    }

    if (empty($parsed['v1'])) {
        return new WP_Error('invalid_signature', 'Missing v1 signature', array('status' => 400));
    }

    $timestamp = (int) $parsed['t'];
    $expected_signatures = $parsed['v1'];

    // Check timestamp tolerance
    $current_time = time();
    if (abs($current_time - $timestamp) > $tolerance) {
        if (function_exists('fasp_log')) {
            fasp_log('Stripe webhook timestamp outside tolerance: ' . $timestamp . ' vs ' . $current_time, 'warning');
        }
        return new WP_Error('timestamp_expired', 'Webhook timestamp outside tolerance window', array('status' => 400));
    }

    // Compute expected signature
    $signed_payload = $timestamp . '.' . $payload;
    $computed_signature = hash_hmac('sha256', $signed_payload, $secret);

    // Compare with provided signatures
    $valid = false;
    foreach ($expected_signatures as $sig) {
        if (hash_equals($computed_signature, $sig)) {
            $valid = true;
            break;
        }
    }

    if (!$valid) {
        if (function_exists('fasp_log')) {
            fasp_log('Stripe webhook signature verification failed', 'warning');
        }
        return new WP_Error('signature_mismatch', 'Invalid webhook signature', array('status' => 400));
    }

    return true;
}

/**
 * Check if an event has already been processed (idempotency).
 *
 * @param string $event_id Stripe event ID.
 * @return bool True if already processed, false otherwise.
 */
function fasp_stripe_event_processed($event_id) {
    $transient_key = 'fasp_stripe_evt_' . md5($event_id);
    return (bool) get_transient($transient_key);
}

/**
 * Mark an event as processed.
 *
 * @param string $event_id Stripe event ID.
 * @return void
 */
function fasp_stripe_mark_event_processed($event_id) {
    $transient_key = 'fasp_stripe_evt_' . md5($event_id);
    set_transient($transient_key, 1, FASP_STRIPE_IDEMPOTENCY_TTL);
}

add_action('rest_api_init', function(){
    register_rest_route('fasp/v1', '/stripe/webhook', array(
        'methods'  => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function($req){
            $payload = $req->get_body();
            $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) : '';

            // Get webhook secret from normalized payments
            $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
            $secret = isset($payments['stripe']['webhook_secret']) ? $payments['stripe']['webhook_secret'] : '';

            // Verify signature
            $verification = fasp_verify_stripe_signature($payload, $sig_header, $secret);
            if (is_wp_error($verification)) {
                return $verification;
            }

            // Parse JSON payload
            $data = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('bad_json', 'Invalid JSON payload', array('status' => 400));
            }

            // Get event type and ID
            $event_type = isset($data['type']) ? sanitize_text_field($data['type']) : '';
            $event_id = isset($data['id']) ? sanitize_text_field($data['id']) : '';

            // Only process allowed event types
            if (!in_array($event_type, FASP_STRIPE_ALLOWED_EVENTS, true)) {
                // Acknowledge but don't process unknown events
                return array('ok' => true, 'message' => 'Event type not processed');
            }

            // Check idempotency
            if ($event_id && fasp_stripe_event_processed($event_id)) {
                return array('ok' => true, 'message' => 'Event already processed');
            }

            // Process the event
            $object = isset($data['data']['object']) ? $data['data']['object'] : array();
            
            // Store payment record
            update_option('fasp_last_payment', array(
                'type' => $event_type,
                'ts' => current_time('mysql'),
                'amount' => isset($object['amount_total']) ? $object['amount_total'] : (isset($object['amount']) ? $object['amount'] : null),
                'currency' => isset($object['currency']) ? sanitize_text_field($object['currency']) : null,
                'ref' => isset($object['id']) ? sanitize_text_field($object['id']) : null,
            ));

            // Fire action for extensibility
            do_action('fasp_stripe_webhook_event', $event_type, $object, $data);

            // Mark event as processed
            if ($event_id) {
                fasp_stripe_mark_event_processed($event_id);
            }

            if (function_exists('fasp_log')) {
                fasp_log('Stripe webhook processed: ' . $event_type . ' (' . $event_id . ')', 'info');
            }

            return array('ok' => true);
        }
    ));
});