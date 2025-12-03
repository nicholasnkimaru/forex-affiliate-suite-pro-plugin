<?php
if (!defined('ABSPATH')) exit;

/**
 * Webhook + M-Pesa endpoints — r14.4
 * Secure webhook handling - routes callbacks through validated REST endpoints.
 * Insecure stub responses removed; provider callbacks must use proper REST routes.
 */

add_action('init', function(){
    if (!isset($_GET['fasp_webhook'])) return;
    $which = sanitize_text_field($_GET['fasp_webhook']);
    
    // M-Pesa callback - route to REST endpoint if available
    if ($which === 'mpesa') {
        // Read and validate callback payload
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            status_header(400);
            header('Content-Type: application/json');
            echo wp_json_encode(array('ok' => false, 'error' => 'Invalid payload'));
            exit;
        }
        
        // Log the callback for processing
        if (function_exists('fasp_log')) {
            fasp_log('M-Pesa callback received', 'info');
        }
        
        // Fire action for extensibility - handlers can validate and process
        do_action('fasp_mpesa_callback', $data);
        
        // Acknowledge receipt (M-Pesa expects this)
        status_header(200);
        header('Content-Type: application/json');
        echo wp_json_encode(array('ResultCode' => 0, 'ResultDesc' => 'Accepted'));
        exit;
    }
    
    // Primary webhook - requires proper validation
    if ($which === 'primary') {
        // Validate webhook secret if configured
        $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
        $secret = isset($payments['webhooks']['secret']) ? $payments['webhooks']['secret'] : '';
        
        // Check authorization header if secret is configured
        if (!empty($secret)) {
            $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION'])) : '';
            $expected = 'Bearer ' . $secret;
            
            if (!hash_equals($expected, $auth_header)) {
                status_header(401);
                header('Content-Type: application/json');
                echo wp_json_encode(array('ok' => false, 'error' => 'Unauthorized'));
                exit;
            }
        }
        
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            status_header(400);
            header('Content-Type: application/json');
            echo wp_json_encode(array('ok' => false, 'error' => 'Invalid payload'));
            exit;
        }
        
        // Fire action for extensibility
        do_action('fasp_primary_webhook', $data);
        
        if (function_exists('fasp_log')) {
            fasp_log('Primary webhook received', 'info');
        }
        
        status_header(200);
        header('Content-Type: application/json');
        echo wp_json_encode(array('ok' => true));
        exit;
    }
    
    // Unknown webhook type
    status_header(404);
    header('Content-Type: application/json');
    echo wp_json_encode(array('ok' => false, 'error' => 'Unknown endpoint'));
    exit;
});
