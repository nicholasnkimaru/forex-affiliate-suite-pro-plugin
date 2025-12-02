<?php
/**
 * FASP – Event Senders (Meta CAPI, etc.)
 * This file is the SINGLE source of truth for fasp_send_meta_capi().
 */
if (!defined('ABSPATH')) exit;

/**
 * Low-level HTTP post helper (no fatal if wp_remote_post missing)
 */
if (!function_exists('fasp_http_post_json')) {
    function fasp_http_post_json($url, $payload, $headers = array()) {
        if (!function_exists('wp_remote_post')) return array('error' => 'no_wp_remote_post');
        $args = array(
            'timeout' => 8,
            'headers' => array_merge(array('Content-Type' => 'application/json'), $headers),
            'body'    => wp_json_encode($payload),
        );
        $res = wp_remote_post($url, $args);
        if (is_wp_error($res)) return array('error' => $res->get_error_message());
        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        return array('code' => $code, 'body' => $body);
    }
}

/**
 * Send Meta (Facebook) CAPI CompleteRegistration (or custom event)
 * Guarded to prevent redeclare if this file is required twice.
 */
if (!function_exists('fasp_send_meta_capi')) {
    function fasp_send_meta_capi($args = array()) {
        // Consent soft-mode
        $ok = apply_filters('fasp/consent', true);
        if (!$ok) return array('skipped' => 'consent_denied');

        // Settings (pixel & token should be stored in options)
        $opts   = get_option('fasp_meta', array());
        $pixel  = isset($opts['pixel_id']) ? trim($opts['pixel_id']) : '';
        $token  = isset($opts['access_token']) ? trim($opts['access_token']) : '';
        if (!$pixel || !$token) return array('skipped' => 'missing_creds');

        // Inputs
        $event_name   = isset($args['event_name']) ? sanitize_text_field($args['event_name']) : 'CompleteRegistration';
        $event_time   = isset($args['event_time']) ? intval($args['event_time']) : time();
        $event_id     = !empty($args['event_id']) ? sanitize_text_field($args['event_id']) : wp_generate_uuid4();
        $email_hash   = !empty($args['email_hash']) ? sanitize_text_field($args['email_hash']) : null;
        $fbp          = !empty($args['fbp']) ? sanitize_text_field($args['fbp']) : (isset($_COOKIE['fasp_fbp']) ? sanitize_text_field($_COOKIE['fasp_fbp']) : null);
        $fbc          = !empty($args['fbc']) ? sanitize_text_field($args['fbc']) : (isset($_COOKIE['fasp_fbc']) ? sanitize_text_field($_COOKIE['fasp_fbc']) : null);
        $source_url   = !empty($args['source_url']) ? esc_url_raw($args['source_url']) : (is_front_page() ? home_url('/') : (is_singular() ? get_permalink() : home_url(add_query_arg(null, null))));
        $client_ip    = !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $user_agent   = !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        $user_data = array();
        if ($email_hash) $user_data['em'] = array($email_hash);
        if ($fbp)        $user_data['fbp'] = $fbp;
        if ($fbc)        $user_data['fbc'] = $fbc;
        if ($client_ip)  $user_data['client_ip_address'] = $client_ip;
        if ($user_agent) $user_data['client_user_agent'] = $user_agent;

        $payload = array(
            'data' => array(
                array(
                    'event_name'    => $event_name,
                    'event_time'    => $event_time,
                    'event_id'      => $event_id,
                    'action_source' => 'website',
                    'event_source_url' => $source_url,
                    'user_data'     => $user_data,
                )
            )
        );

        $endpoint = 'https://graph.facebook.com/v17.0/' . rawurlencode($pixel) . '/events?access_token=' . rawurlencode($token);
        $res = fasp_http_post_json($endpoint, $payload);
        do_action('fasp/debug', 'meta_capi_send', array('payload' => $payload, 'response' => $res));
        return $res;
    }
}
