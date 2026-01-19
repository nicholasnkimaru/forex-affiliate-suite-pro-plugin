<?php
/**
 * FASP – Ads & Analytics helpers (safe PHP 7.4+ syntax, no trailing commas in calls)
 */
if (!defined('ABSPATH')) exit;

/**
 * Load conversion client if present (provides fasp_forward_event_to_conversion_service, queue, worker)
 * This is idempotent and safe if the file doesn't exist yet.
 */
if ( file_exists( __DIR__ . '/conversion-client.php' ) ) {
    require_once __DIR__ . '/conversion-client.php';
}

/**
 * Capture common ad params into cookies/user meta:
 * - gclid (Google Ads), fbclid, utm_* and a creative "angle"
 */
add_action('init', function () {
    // Only on frontend
    if (is_admin()) { return; }

    $pairs = array(
        'gclid'       => array('cookie' => 'fasp_gclid', 'sanitize' => 'sanitize_text_field'),
        'fbclid'      => array('cookie' => 'fasp_fbclid', 'sanitize' => 'sanitize_text_field'),
        'utm_source'  => array('cookie' => 'fasp_utm_source', 'sanitize' => 'sanitize_text_field'),
        'utm_medium'  => array('cookie' => 'fasp_utm_medium', 'sanitize' => 'sanitize_text_field'),
        'utm_campaign'=> array('cookie' => 'fasp_utm_campaign', 'sanitize' => 'sanitize_text_field'),
        'utm_content' => array('cookie' => 'fasp_utm_content', 'sanitize' => 'sanitize_text_field'),
        // Our creative angle flag
        'angle'       => array('cookie' => 'fasp_variant_angle', 'sanitize' => 'sanitize_key'),
    );

    foreach ($pairs as $q => $cfg) {
        if (isset($_GET[$q])) {
            $raw = $_GET[$q];
            $val = $cfg['sanitize'] === 'sanitize_key' ? sanitize_key($raw) : sanitize_text_field($raw);
            // 90 days
            setcookie($cfg['cookie'], $val, time() + 90 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
            if (is_user_logged_in()) {
                update_user_meta(get_current_user_id(), '_' . $cfg['cookie'], $val);
            }
        }
    }
});

/**
 * Lightweight event logger you can call elsewhere:
 * fasp_track_click('deriv','ad_click', 'https://example.com?utm_campaign=...');
 */
if (!function_exists('fasp_track_click')) {
    function fasp_track_click($platform, $action, $url) {
        if (!function_exists('fasp_log_click')) { return; }
        $platform = sanitize_text_field($platform);
        $action   = sanitize_text_field($action);
        $url      = esc_url_raw($url);
        fasp_log_click($platform, $action, $url);
    }
}

/**
 * Mark a user as "verified" for a platform, recording a timestamp and
 * carrying across gclid/angle for offline conversion/angle scoreboard.
 * Safe to call from your OAuth/verify flow.
 */
if (!function_exists('fasp_mark_verified')) {
    function fasp_mark_verified($user_id, $platform_slug) {
        $user_id = intval($user_id);
        $slug    = sanitize_title($platform_slug);
        if (!$user_id || !$slug) return;

        // Record verification flags and timestamp
        update_user_meta($user_id, '_fasp_verified_' . $slug, '1');
        $now = current_time('mysql', true); // UTC
        update_user_meta($user_id, '_fasp_verified_at', $now);

        // carry across cookies if present (gclid, angle, utm campaign)
        $carry = array(
            'fasp_gclid'         => '_fasp_gclid',
            'fasp_variant_angle' => '_fasp_variant_angle',
            'fasp_utm_campaign'  => '_fasp_utm_campaign',
        );
        foreach ($carry as $cookie => $meta) {
            if (!empty($_COOKIE[$cookie])) {
                update_user_meta($user_id, $meta, sanitize_text_field($_COOKIE[$cookie]));
            }
        }

        /**
         * Forward verification event to conversion ingestion service (non-blocking).
         * If conversion-client is available it will attempt fire-and-forget POST and
         * enqueue on failure for cron retries.
         */
        if ( function_exists( 'fasp_forward_event_to_conversion_service' ) ) {
            $gclid = get_user_meta( $user_id, '_fasp_gclid', true ) ?: ( !empty($_COOKIE['fasp_gclid']) ? sanitize_text_field($_COOKIE['fasp_gclid']) : '' );
            $utm_campaign = get_user_meta( $user_id, '_fasp_utm_campaign', true ) ?: ( !empty($_COOKIE['fasp_utm_campaign']) ? sanitize_text_field($_COOKIE['fasp_utm_campaign']) : '' );

            $event = array(
                'event_id' => wp_generate_uuid4(),
                'type' => 'verify',
                'platform' => $slug,
                'user_id' => intval( $user_id ),
                'gclid' => $gclid,
                'utm' => array(
                    'utm_campaign' => $utm_campaign,
                ),
                'payload' => array(
                    'verified_at' => $now,
                ),
                'received_at' => current_time('mysql', true),
            );

            // fire-and-forget or enqueue (conversion-client handles enqueueing)
            fasp_forward_event_to_conversion_service( $event );
        }
    }
}

/**
 * Helper: fetch fbp/fbc captured by meta-fbp-fbc-capture.php (if present).
 * Returns array('fbp' => '...', 'fbc' => '...')
 */
if (!function_exists('fasp_get_fbp_fbc')) {
    function fasp_get_fbp_fbc() {
        $out = array();
        if (!empty($_COOKIE['fasp_fbp'])) $out['fbp'] = sanitize_text_field($_COOKIE['fasp_fbp']);
        if (!empty($_COOKIE['fasp_fbc'])) $out['fbc'] = sanitize_text_field($_COOKIE['fasp_fbc']);
        return $out;
    }
}

/**
 * Example server event sender stub (Meta CAPI).
 * Wire your real token/pixel in settings and call this when a registration completes.
 */
if (!function_exists('fasp_send_complete_registration')) {
    function fasp_send_complete_registration($user_id) {
        // Consent soft-mode check (optional)
        $ok = apply_filters('fasp/consent', true);
        if (!$ok) return;

        $user_id = intval($user_id);
        $email   = get_userdata($user_id) ? get_userdata($user_id)->user_email : '';
        $email_h = $email ? hash('sha256', strtolower(trim($email))) : null;

        // Basic payload (extend as needed)
        $evt_id = wp_generate_uuid4();
        $udata  = array(
            'em' => $email_h ? array($email_h) : array(),
        );
        $fbpfbc = fasp_get_fbp_fbc();
        foreach ($fbpfbc as $k => $v) {
            $udata[$k] = $v;
        }

        $payload = array(
            'data' => array(
                array(
                    'event_name'  => 'CompleteRegistration',
                    'event_time'  => time(),
                    'action_source' => 'website',
                    'event_id'    => $evt_id,
                    'user_data'   => $udata,
                )
            )
        );

        /**
         * Send with wp_remote_post if you’ve stored:
         * - fasp_meta_pixel_id
         * - fasp_meta_access_token
         * Keeping this stubbed so it never fatals.
         *
         * NOTE: consider using fasp_forward_event_to_conversion_service() instead of direct sending
         * so events are centrally deduplicated and retried by the conversion ingestion service.
         */
        do_action('fasp/debug', 'capi_payload', $payload);
    }
}

/**
 * Optional: upon user_register, send a server event (you can remove if using your own flow).
 */
add_action('user_register', function ($uid) {
    // Only fire if this was a flow that implies a signup from ads; otherwise no-op.
    fasp_send_complete_registration($uid);
});