<?php if (is_admin() && !defined('FASP_LEGACY_HTML_GUARD')) { /* prevent stray output in admin */ }
if (!defined('ABSPATH')) exit;
add_action('rest_api_init', function(){
  register_rest_route('fasp/v1','/stripe/webhook', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function($req){
      $payload = $req->get_body();
      $sig = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field($_SERVER['HTTP_STRIPE_SIGNATURE']) : '';
      $opts = get_option('fasp_payments', []);
      $secret = $opts['stripe_webhook_secret'] ?? '';
      // TODO: Verify signature (requires Stripe library). For now we accept and log minimal data safely.
      $data = json_decode($payload, true);
      if (json_last_error() !== JSON_ERROR_NONE) return new WP_Error('bad_json','Invalid payload', ['status'=>400]);
      // Handle basic events
      $type = sanitize_text_field($data['type'] ?? '');
      if (in_array($type, ['checkout.session.completed','payment_intent.succeeded'])) {
        update_option('fasp_last_payment', [
          'type'=>$type,
          'ts'=> current_time('mysql'),
          'amount'=> $data['data']['object']['amount_total'] ?? ($data['data']['object']['amount'] ?? null),
          'currency'=> $data['data']['object']['currency'] ?? null,
          'ref'=> $data['data']['object']['id'] ?? null,
        ]);
        // You can add: tag user, grant resource, etc.
      }
      return ['ok'=>true];
    }
  ]);
});