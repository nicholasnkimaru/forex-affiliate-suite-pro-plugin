<?php
/**
 * FASP – Events Queue
 * Queues events and uses fasp_send_meta_capi() from events-senders.php
 */
if (!defined('ABSPATH')) exit;

/**
 * Queue an event (stored in an option; simple & safe)
 */
if (!function_exists('fasp_queue_event')) {
    function fasp_queue_event($key, $payload = array()) {
        $key = sanitize_key($key);
        $queue = get_option('fasp_events_queue', array());
        if (!is_array($queue)) $queue = array();
        $queue[] = array(
            'k' => $key,
            'p' => $payload,
            't' => time(),
        );
        update_option('fasp_events_queue', $queue, false);
    }
}

/**
 * Process queue: sends Meta events by delegating to fasp_send_meta_capi()
 */
if (!function_exists('fasp_process_queue')) {
    function fasp_process_queue($limit = 20) {
        $queue = get_option('fasp_events_queue', array());
        if (empty($queue) || !is_array($queue)) return 0;

        // Ensure sender exists
        if (!function_exists('fasp_send_meta_capi')) {
            // try to include sender file if not loaded yet
            $file = plugin_dir_path(__FILE__) . 'events-senders.php';
            if (file_exists($file)) require_once $file;
        }

        $sent = 0;
        $left = array();
        foreach ($queue as $item) {
            if ($sent >= $limit) { $left[] = $item; continue; }
            $k = isset($item['k']) ? $item['k'] : '';
            $p = isset($item['p']) && is_array($item['p']) ? $item['p'] : array();

            if ($k === 'meta_complete_registration' && function_exists('fasp_send_meta_capi')) {
                fasp_send_meta_capi($p);
                $sent++;
            } else {
                // unknown event: keep it
                $left[] = $item;
            }
        }
        update_option('fasp_events_queue', $left, false);
        return $sent;
    }
}

/**
 * Example hook: when user is verified, queue CompleteRegistration
 * (Keep this lightweight; your verification flow should call fasp_queue_event)
 */
add_action('fasp/user_verified', function ($user_id, $platform) {
    $user_id = intval($user_id);
    $email   = get_userdata($user_id) ? get_userdata($user_id)->user_email : '';
    $email_h = $email ? hash('sha256', strtolower(trim($email))) : null;

    $payload = array(
        'event_name'  => 'CompleteRegistration',
        'event_time'  => time(),
        'email_hash'  => $email_h,
        'source_url'  => home_url('/'), // adjust if you want last referrer
    );
    fasp_queue_event('meta_complete_registration', $payload);
}, 10, 2);

/**
 * Cron-ish processing: process small batches on shutdown
 */
add_action('shutdown', function () {
    // Don’t overdo it on admin screens
    if (is_admin()) return;
    fasp_process_queue(5);
});
