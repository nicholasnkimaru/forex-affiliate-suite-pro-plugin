<?php
/**
 * Dashboard Analytics and Activity Tracking
 * 
 * Tracks user interactions and feature usage on the dashboard
 */

if (!defined('ABSPATH')) exit;

/**
 * Log dashboard activity
 * 
 * @param int $user_id User ID
 * @param string $activity Activity type (e.g., 'view_platforms', 'click_resource')
 * @param array $meta Additional metadata
 */
function fasp_log_dashboard_activity($user_id, $activity, $meta = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'fasp_analytics';
    
    // Check if table exists, if not create it
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity varchar(100) NOT NULL,
            meta longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY activity (activity),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    $wpdb->insert(
        $table,
        array(
            'user_id' => intval($user_id),
            'activity' => sanitize_text_field($activity),
            'meta' => maybe_serialize($meta),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s')
    );
}

/**
 * Get dashboard activity stats
 * 
 * @param int $days Number of days to look back (default 30)
 * @return array Activity statistics
 */
function fasp_get_dashboard_analytics($days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'fasp_analytics';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return array();
    }
    
    $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $stats = array();
    
    // Get total unique users
    $stats['unique_users'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM $table WHERE created_at >= %s",
        $date_from
    ));
    
    // Get most popular activities
    $stats['popular_activities'] = $wpdb->get_results($wpdb->prepare(
        "SELECT activity, COUNT(*) as count 
         FROM $table 
         WHERE created_at >= %s 
         GROUP BY activity 
         ORDER BY count DESC 
         LIMIT 10",
        $date_from
    ), ARRAY_A);
    
    // Get activity by day
    $stats['daily_activity'] = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as count 
         FROM $table 
         WHERE created_at >= %s 
         GROUP BY DATE(created_at) 
         ORDER BY date ASC",
        $date_from
    ), ARRAY_A);
    
    return $stats;
}

/**
 * Get heatmap data for admin dashboard
 * 
 * @return array Heatmap data showing click density
 */
function fasp_get_activity_heatmap() {
    global $wpdb;
    $table = $wpdb->prefix . 'fasp_analytics';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return array();
    }
    
    $date_from = date('Y-m-d H:i:s', strtotime('-7 days'));
    
    $heatmap = $wpdb->get_results($wpdb->prepare(
        "SELECT activity, COUNT(*) as intensity 
         FROM $table 
         WHERE created_at >= %s 
         GROUP BY activity",
        $date_from
    ), ARRAY_A);
    
    return $heatmap;
}

// AJAX handler for tracking dashboard clicks
add_action('wp_ajax_fasp_track_activity', function() {
    check_ajax_referer('fasp-track', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('Not logged in');
    }
    
    $activity = isset($_POST['activity']) ? sanitize_text_field($_POST['activity']) : '';
    $meta = isset($_POST['meta']) ? array_map('sanitize_text_field', $_POST['meta']) : array();
    
    if ($activity) {
        fasp_log_dashboard_activity($user_id, $activity, $meta);
        wp_send_json_success(array('tracked' => true));
    }
    
    wp_send_json_error('Invalid activity');
});

// Enqueue analytics tracking script
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in() && (is_page() || is_account_page())) {
        wp_enqueue_script(
            'fasp-analytics',
            FASP_URL . 'assets/js/fasp-analytics.js',
            array('jquery'),
            FASP_VER,
            true
        );
        
        wp_localize_script('fasp-analytics', 'faspAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fasp-track'),
            'userId' => get_current_user_id()
        ));
    }
});
