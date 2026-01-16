<?php
/**
 * Conversion service client and queueing helper
 *
 * Sends events to an external conversion ingestion service (conversion.go or similar).
 * - Non-blocking attempt when possible
 * - On failure, enqueue to option 'fasp_conversion_queue' and schedule cron worker
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_get_conversion_service_url')) {
    function fasp_get_conversion_service_url() {
        // prefer explicit option, fallback to env var
        $o = get_option('fasp_marketing', []);
        if (!empty($o['conversion_service_url'])) {
            return esc_url_raw($o['conversion_service_url']);
        }
        // fallback to environment variable
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
        $queue[] = $event;
        update_option('fasp_conversion_queue', $queue);
        // schedule worker if not already
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
            // no service configured: enqueue for manual processing / export
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
            'blocking' => false, // try fire-and-forget
        );

        $res = wp_remote_post( $url . '/v1/events', $args );

        if ( is_wp_error( $res ) ) {
            // enqueue on error
            fasp_enqueue_conversion_event($event);
            return $res;
        }

        // If non-blocking, WP returns an array only for blocking; for non-blocking it returns an empty array or int.
        // We'll check response codes only when blocking; here we assume accepted unless WP_Error.
        return true;
    }
}

/**
 * Cron worker: attempt to forward queued events (blocking requests so we get responses)
 */
if (!function_exists('fasp_process_conversion_queue')) {
    function fasp_process_conversion_queue() {
        $queue = get_option('fasp_conversion_queue', []);
        if (!is_array($queue) || empty($queue)) {
            // nothing to do
            return;
        }

        $url = fasp_get_conversion_service_url();
        if (empty($url)) return;

        $remaining = [];
        foreach ($queue as $event) {
            $args = array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($event),
                'timeout' => 8,
                'redirection' => 3,
                'blocking' => true,
            );
            $res = wp_remote_post( $url . '/v1/events', $args );
            if ( is_wp_error( $res ) ) {
                // keep in queue for retry
                $event['_last_error'] = $res->get_error_message();
                $event['_last_attempt'] = time();
                $remaining[] = $event;
                continue;
            }
            $code = wp_remote_retrieve_response_code( $res );
            if ( $code >= 200 && $code < 300 ) {
                // success -> drop
                continue;
            } else {
                $event['_last_error'] = 'HTTP ' . $code;
                $event['_last_attempt'] = time();
                $remaining[] = $event;
                continue;
            }
        }

        // Store remaining (or empty array)
        update_option('fasp_conversion_queue', $remaining);
    }
}

// Hook the cron worker
add_action('fasp_cron_process_conversion_queue', 'fasp_process_conversion_queue');

// Ensure scheduled event exists (on plugin load)
if (!wp_next_scheduled('fasp_cron_process_conversion_queue')) {
    wp_schedule_event(time() + 60, 'hourly', 'fasp_cron_process_conversion_queue');
}