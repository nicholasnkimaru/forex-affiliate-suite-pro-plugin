<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
if (!defined('FASP_CLICK_TABLE')) {
    define('FASP_CLICK_TABLE', $wpdb->prefix . 'fasp_clicks');
}

/**
 * Create the clicks tracking table on activation.
 */
function fasp_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $table_name = FASP_CLICK_TABLE;
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NULL,
        platform VARCHAR(32) NOT NULL,
        action VARCHAR(32) NOT NULL,
        url TEXT NULL,
        ip VARCHAR(64) NULL,
        user_agent TEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY platform (platform),
        KEY action (action),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) {$charset};";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * Log a click event to the database.
 *
 * @param string $platform Platform identifier.
 * @param string $action   Action identifier.
 * @param string $url      URL associated with the click (optional).
 */
function fasp_log_click($platform, $action, $url = '') {
    global $wpdb;
    
    // Sanitize $_SERVER values before use
    $ip = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }
    
    $user_agent = '';
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
    }
    
    // Use $wpdb->insert with explicit format specification
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(
        FASP_CLICK_TABLE,
        array(
            'user_id'    => get_current_user_id(),
            'platform'   => sanitize_text_field($platform),
            'action'     => sanitize_text_field($action),
            'url'        => esc_url_raw($url),
            'ip'         => $ip,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );
}

add_action('admin_menu', function() {
    add_submenu_page(
        'fasp_hub',
        'Reports',
        'Reports',
        'manage_options',
        'fasp_reports',
        'fasp_reports_page'
    );
});

/**
 * Render the reports admin page.
 */
function fasp_reports_page() {
    global $wpdb;
    
    $table_name = FASP_CLICK_TABLE;
    
    // Use $wpdb->prepare for the query - table name is a constant so it's safe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT platform, action, COUNT(*) as total FROM {$table_name} GROUP BY platform, action ORDER BY total DESC LIMIT %d",
            100
        ),
        ARRAY_A
    );
    ?>
    <div class="wrap fasp-admin">
        <h1>Reports</h1>
        <div class="fasp-wrap fasp-card">
            <table class="widefat fasp-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Action</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows) : ?>
                        <?php foreach ($rows as $r) : ?>
                            <tr>
                                <td><?php echo esc_html($r['platform']); ?></td>
                                <td><?php echo esc_html($r['action']); ?></td>
                                <td><?php echo intval($r['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3">No data yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
