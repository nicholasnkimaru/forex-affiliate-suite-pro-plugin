<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simple leads webhook logger + send/test helpers
 */

if ( ! function_exists( 'fasp_log_webhook_attempt' ) ) {
    function fasp_log_webhook_attempt( $endpoint, $payload, $http_code, $response_body ) {
        $log = get_option( 'fasp_leads_webhook_log', array() );
        $entry = array(
            'time' => time(),
            'endpoint' => $endpoint,
            'status' => intval( $http_code ),
            'payload' => is_scalar( $payload ) ? $payload : wp_json_encode( $payload ),
            'response' => is_scalar( $response_body ) ? $response_body : wp_json_encode( $response_body ),
        );
        array_unshift( $log, $entry );
        $log = array_slice( $log, 0, 20 );
        update_option( 'fasp_leads_webhook_log', $log );
    }
}

if ( ! function_exists( 'fasp_send_lead_webhook' ) ) {
    function fasp_send_lead_webhook( $endpoint, $payload ) {
        $args = array( 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $payload ), 'timeout' => 20 );
        $resp = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $resp ) ) {
            fasp_log_webhook_attempt( $endpoint, $payload, 0, $resp->get_error_message() );
            return false;
        }
        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        fasp_log_webhook_attempt( $endpoint, $payload, $code, $body );
        return $code >= 200 && $code < 300;
    }
}
