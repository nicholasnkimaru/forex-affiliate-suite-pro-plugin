<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Check if a user is verified for a specific platform.
 *
 * @param string $platform Platform slug.
 * @return bool True if user is verified.
 */
function fasp_user_is_verified($platform) {
    $uid = get_current_user_id();
    if (!$uid) {
        return false;
    }
    return get_user_meta($uid, '_fasp_verified_' . $platform, true) === '1';
}

/**
 * Check if a user is allowed by gating rules.
 *
 * This function checks role-based gating, login requirements, and per-page overrides.
 *
 * @param int $user_id User ID to check (0 for logged-out users).
 * @param int $post_id Post/page ID for per-page override check (0 for global check only).
 * @return bool True if user is allowed, false if blocked.
 */
if (!function_exists('fasp_is_user_allowed_by_gating')) {
    function fasp_is_user_allowed_by_gating($user_id = 0, $post_id = 0) {
        // Get gating options
        $gating_opts = get_option('fasp_platform_gating', array());
        
        // Default: if no gating is configured, allow access
        if (empty($gating_opts)) {
            return true;
        }
        
        $require_login = !empty($gating_opts['require_login']);
        $allowed_roles_raw = isset($gating_opts['roles']) ? $gating_opts['roles'] : '';
        $blocked_message = isset($gating_opts['blocked_message']) ? $gating_opts['blocked_message'] : '';
        $blocked_redirect = isset($gating_opts['blocked_redirect']) ? $gating_opts['blocked_redirect'] : '';
        
        // Get gating_roles option (new multi-select format)
        $gating_roles = get_option('fasp_gating_roles', array());
        if (!is_array($gating_roles)) {
            // Parse legacy CSV format
            $gating_roles = array_filter(array_map('trim', explode(',', $allowed_roles_raw)));
        }
        
        // Check per-page override if post_id is provided
        if ($post_id > 0) {
            $override = get_post_meta($post_id, '_fasp_gating_override', true);
            if ($override === 'allow') {
                return true;
            }
            if ($override === 'deny') {
                return false;
            }
            // 'inherit' or empty means use global rules
        }
        
        // Check login requirement
        if ($require_login && $user_id <= 0) {
            return false;
        }
        
        // If no role restrictions, allow all logged-in users (or all if login not required)
        if (empty($gating_roles)) {
            return true;
        }
        
        // If user is not logged in, deny if there are role restrictions
        if ($user_id <= 0) {
            return false;
        }
        
        // Get user roles
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) {
            return false;
        }
        
        // Check if user has any of the allowed roles
        foreach ($gating_roles as $allowed_role) {
            if (in_array($allowed_role, (array)$user->roles, true)) {
                return true;
            }
        }
        
        // User doesn't have any of the allowed roles
        return false;
    }
}

/**
 * Get the gating blocked message.
 *
 * @return string Blocked message text.
 */
if (!function_exists('fasp_get_gating_blocked_message')) {
    function fasp_get_gating_blocked_message() {
        $gating_opts = get_option('fasp_platform_gating', array());
        $message = isset($gating_opts['blocked_message']) ? $gating_opts['blocked_message'] : '';
        
        if (empty($message)) {
            $message = 'You do not have permission to access this content. Please log in or contact the site administrator.';
        }
        
        return $message;
    }
}

/**
 * Get the gating blocked redirect URL.
 *
 * @return string Redirect URL or empty string.
 */
if (!function_exists('fasp_get_gating_blocked_redirect')) {
    function fasp_get_gating_blocked_redirect() {
        $gating_opts = get_option('fasp_platform_gating', array());
        return isset($gating_opts['blocked_redirect']) ? $gating_opts['blocked_redirect'] : '';
    }
}

/**
 * Resource content gating filter.
 */
function fasp_resource_gate($content) {
    if (!is_singular('fasp_resource') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    
    $required = get_post_meta(get_the_ID(), '_fasp_required_platform', true);
    $showpill = get_post_meta(get_the_ID(), '_fasp_show_platform_pill', true);
    $download = get_post_meta(get_the_ID(), '_fasp_download_url', true);
    $pill = ($showpill && $required) 
        ? '<span class="fasp-pill" style="margin-left:8px;">Requires ' . esc_html(ucfirst($required)) . '</span>' 
        : '';
    
    if (!$required || fasp_user_is_verified($required)) {
        if ($download) {
            return $content . $pill . '<p><a class="fasp-button" href="' . esc_url($download) . '">Download</a></p>';
        }
        return $content . $pill;
    }
    
    $go = home_url('/fasp-go/' . $required . '?dest=signup');
    ob_start();
    ?>
    <div class="fasp-card fasp-hero">
        <h3>Unlock this resource</h3>
        <p class="fasp-muted">Join via our affiliate link, then click <em>Verify</em>. Once verified, the download will appear.</p>
        <p>
            <a class="fasp-button" href="<?php echo esc_url($go); ?>">Join <?php echo esc_html(ucfirst($required)); ?></a>
            <button class="fasp-button" onclick="faspQuickVerify('<?php echo esc_js($required); ?>')" style="margin-left:10px;">Verify</button>
        </p>
        <script>
        function faspQuickVerify(p){
            fetch('/wp-json/fasp/v1/verify/'+p,{method:'POST',headers:{'X-WP-Nonce': (window.wpApiSettings? wpApiSettings.nonce : '')}})
            .then(r=>r.json()).then(_=>location.reload());
        }
        </script>
    </div>
    <?php
    return $pill . ob_get_clean() . $content;
}
add_filter('the_content', 'fasp_resource_gate');
