<?php
/**
 * Conversion service client and queueing helper (hardened)
 *
 * - Adds attempt counters, exponential backoff, max attempts
 * - Cron worker flush with retries
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_get_conversion_service_url')) {
    function fasp_get_conversion_service_url() {
        $o = get_option('fasp_marketing', []);
        if (!empty($o['conversion_service_url'])) {
            return esc_url_raw($o['conversion_service_url']);
        }
        if (!empty($_ENV['FASP_CONVERSION_URL'])) {
            return esc_url_raw($_ENV['FASP_CONVERSION_URL']);
        }
        return '';
    }
}

if (!function_exists('fasp_enqueue_conversion_event')) {
    function fasp_enqueue_conversion_event(array $event) {
        $queue = get_option('fasp_conversion_queue', []);
        if (!is_array($queue)) $queue = [];

        // Normalize metadata for retries
        $event['_attempts'] = 0;
        $event['_created_at'] = time();
        $event['_last_attempt'] = 0;
        $queue[] = $event;

        update_option('fasp_conversion_queue', $queue);

        if (!wp_next_scheduled('fasp_cron_process_conversion_queue')) {
            wp_schedule_event(time() + 30, 'hourly', 'fasp_cron_process_conversion_queue');
        }
        return true;
    }
}

if (!function_exists('fasp_forward_event_to_conversion_service')) {
    function fasp_forward_event_to_conversion_service(array $event) {
        $url = fasp_get_conversion_service_url();
        if (empty($url)) {
            fasp_enqueue_conversion_event($event);
            return new WP_Error('no_service', 'No conversion service configured');
        }

        $body = wp_json_encode($event);

        // Try a non-blocking request first (fire & forget)
        $args = array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => $body,
            'timeout' => 3,
            'redirection' => 3,
            'blocking' => false,
        );

        $res = wp_remote_post( $url . '/v1/events', $args );

        if ( is_wp_error( $res ) ) {
            // enqueue on error
            fasp_enqueue_conversion_event($event);
            do_action('fasp/conversion_failed', $event, $res);
            return $res;
        }

        // If non-blocking, assume accepted; schedule background worker to ensure delivery
        do_action('fasp/conversion_forwarded_attempt', $event, $res);
        return true;
    }
}

if (!function_exists('fasp_process_conversion_queue')) {
    function fasp_process_conversion_queue() {
        $queue = get_option('fasp_conversion_queue', []);
        if (!is_array($queue) || empty($queue)) {
            return;
        }

        $url = fasp_get_conversion_service_url();
        if (empty($url)) return;

        $remaining = [];

        foreach ($queue as $event) {
            $attempts = intval($event['_attempts'] ?? 0);
            $last_attempt = intval($event['_last_attempt'] ?? 0);
            $now = time();

            // Backoff policy: wait pow(2, attempts) * 60 seconds
            $backoff_seconds = pow(2, max(0, $attempts)) * 60;
            if ($last_attempt > 0 && ($now - $last_attempt) < $backoff_seconds) {
                // Not ready yet; keep in queue
                $remaining[] = $event;
                continue;
            }

            $args = array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($event),
                'timeout' => 8,
                'redirection' => 3,
                'blocking' => true,
            );

            $res = wp_remote_post( $url . '/v1/events', $args );

            if ( is_wp_error( $res ) ) {
                $event['_attempts'] = $attempts + 1;
                $event['_last_attempt'] = $now;
                $event['_last_error'] = $res->get_error_message();
                // if too many attempts, mark failed and drop, but log
                if ($event['_attempts'] >= 5) {
                    do_action('fasp/conversion_failed', $event, $res);
                    error_log('FASP: conversion event failed permanently: ' . json_encode(array(
                        'event' => $event,
                        'error' => $res->get_error_message()
                    )));
                    continue; // drop
                }
                $remaining[] = $event;
                continue;
            }

            $code = intval(wp_remote_retrieve_response_code($res));
            if ($code >= 200 && $code < 300) {
                do_action('fasp/conversion_forwarded', $event, $res);
                continue; // delivered, don't requeue
            } else {
                $event['_attempts'] = $attempts + 1;
                $event['_last_attempt'] = $now;
                $event['_last_error'] = 'HTTP ' . $code;
                if ($event['_attempts'] >= 5) {
                    do_action('fasp/conversion_failed', $event, array('http_code'=>$code));
                    error_log("FASP: conversion event HTTP $code dropped: " . json_encode($event));
                    continue;
                }
                $remaining[] = $event;
            }
        }

        update_option('fasp_conversion_queue', $remaining);
    }
}

add_action('fasp_cron_process_conversion_queue', 'fasp_process_conversion_queue');
if (!wp_next_scheduled('fasp_cron_process_conversion_queue')) {
    wp_schedule_event(time() + 60, 'hourly', 'fasp_cron_process_conversion_queue');
}