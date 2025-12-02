<?php
if (!defined('ABSPATH')) { exit; }
global $wpdb; 
if (!defined('FASP_CLICK_TABLE')) define('FASP_CLICK_TABLE', $wpdb->prefix . 'fasp_clicks');

/**
 * Activate tracking table.
 */
function fasp_activate() { 
    global $wpdb; 
    $charset = $wpdb->get_charset_collate(); 
    $sql = "CREATE TABLE IF NOT EXISTS " . FASP_CLICK_TABLE . " (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    platform VARCHAR(32) NOT NULL,
    action VARCHAR(32) NOT NULL,
    url TEXT NULL,
    ip VARCHAR(64) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id), KEY platform (platform), KEY action (action), KEY user_id (user_id), KEY created_at (created_at)
) $charset;"; 
    require_once ABSPATH . 'wp-admin/includes/upgrade.php'; 
    dbDelta($sql); 
}

/**
 * Log a click event with sanitized server values.
 *
 * @param string $platform Platform identifier.
 * @param string $action   Action type.
 * @param string $url      Optional URL.
 */
function fasp_log_click($platform, $action, $url = '') { 
    global $wpdb; 
    
    // Sanitize $_SERVER values before storing
    $ip = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        // Additional IP validation
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }
    }
    
    $user_agent = '';
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        // Limit length to prevent abuse
        $user_agent = substr($user_agent, 0, 500);
    }
    
    $wpdb->insert(
        FASP_CLICK_TABLE,
        array(
            'user_id'    => get_current_user_id(),
            'platform'   => sanitize_text_field($platform),
            'action'     => sanitize_text_field($action),
            'url'        => esc_url_raw($url),
            'ip'         => $ip,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    ); 
}

add_action('admin_menu', function() { 
    add_submenu_page('fasp_hub', 'Reports', 'Reports', 'manage_options', 'fasp_reports', 'fasp_reports_page'); 
});

function fasp_reports_page() { 
    global $wpdb; 
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results("SELECT platform, action, COUNT(*) as total FROM " . FASP_CLICK_TABLE . " GROUP BY platform, action ORDER BY total DESC LIMIT 100", ARRAY_A); 
    ?>
<div class="wrap fasp-admin"><h1>Reports</h1><div class="fasp-wrap fasp-card"><table class="widefat fasp-table"><thead><tr><th>Platform</th><th>Action</th><th>Total</th></tr></thead><tbody>
<?php if ($rows): foreach($rows as $r): ?><tr><td><?php echo esc_html($r['platform']); ?></td><td><?php echo esc_html($r['action']); ?></td><td><?php echo intval($r['total']); ?></td></tr><?php endforeach; else: ?><tr><td colspan="3">No data yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php 
}
