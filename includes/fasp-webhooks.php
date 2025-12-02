<?php
if (!defined('ABSPATH')) exit;

/**
 * Webhook + M-Pesa endpoints — r14.2
 * Basic scaffolding; site owners can extend with real API calls.
 */

add_action('init', function(){
    if (!isset($_GET['fasp_webhook'])) return;
    $which = sanitize_text_field($_GET['fasp_webhook']);
    if ($which === 'mpesa') {
        status_header(200);
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'message'=>'M-Pesa callback received (stub).']);
        exit;
    }
    if ($which === 'primary') {
        status_header(200);
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'message'=>'Primary webhook reached.']);
        exit;
    }
});
