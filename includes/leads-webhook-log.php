<?php
/**
 * Leads Webhook Logging - Store and manage webhook attempts
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log a webhook attempt
 *
 * @param array $data Webhook data including URL, payload, response, success status
 * @return bool Whether the log was saved
 */
if (!function_exists('fasp_log_webhook_attempt')) {
    function fasp_log_webhook_attempt($data) {
        $log = get_option('fasp_leads_webhook_log', array());
        
        if (!is_array($log)) {
            $log = array();
        }
        
        // Add timestamp and ID
        $data['id'] = wp_generate_uuid4();
        $data['timestamp'] = time();
        
        // Add to log
        array_unshift($log, $data);
        
        // Keep only last 20 entries
        $log = array_slice($log, 0, 20);
        
        return update_option('fasp_leads_webhook_log', $log);
    }
}

/**
 * Get webhook log entries
 *
 * @param int $limit Maximum number of entries to return
 * @return array Array of log entries
 */
if (!function_exists('fasp_get_webhook_log')) {
    function fasp_get_webhook_log($limit = 20) {
        $log = get_option('fasp_leads_webhook_log', array());
        
        if (!is_array($log)) {
            return array();
        }
        
        return array_slice($log, 0, $limit);
    }
}

/**
 * Retry a webhook from the log
 *
 * @param string $entry_id The log entry ID to retry
 * @return array|WP_Error Result of the retry attempt
 */
if (!function_exists('fasp_retry_webhook')) {
    function fasp_retry_webhook($entry_id) {
        $log = fasp_get_webhook_log();
        
        $entry = null;
        foreach ($log as $item) {
            if (isset($item['id']) && $item['id'] === $entry_id) {
                $entry = $item;
                break;
            }
        }
        
        if (!$entry) {
            return new WP_Error('not_found', __('Log entry not found', 'forex-affiliate-suite-pro'));
        }
        
        if (empty($entry['url'])) {
            return new WP_Error('no_url', __('No URL in log entry', 'forex-affiliate-suite-pro'));
        }
        
        // Retry the webhook
        $response = wp_remote_post($entry['url'], array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => isset($entry['payload']) ? wp_json_encode($entry['payload']) : '',
        ));
        
        $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300;
        
        // Log the retry attempt
        fasp_log_webhook_attempt(array(
            'url' => $entry['url'],
            'payload' => $entry['payload'] ?? array(),
            'response_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'response_body' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
            'success' => $success,
            'is_retry' => true,
            'original_id' => $entry_id,
        ));
        
        if ($success) {
            return array('success' => true, 'message' => __('Webhook retried successfully', 'forex-affiliate-suite-pro'));
        }
        
        return new WP_Error('retry_failed', __('Webhook retry failed', 'forex-affiliate-suite-pro'));
    }
}

/**
 * Send a test webhook
 *
 * @param string $url Webhook URL to test
 * @param array  $payload Optional payload to send
 * @return array Result of the test
 */
if (!function_exists('fasp_send_test_webhook')) {
    function fasp_send_test_webhook($url, $payload = null) {
        if (empty($url)) {
            return array('success' => false, 'error' => __('URL is required', 'forex-affiliate-suite-pro'));
        }
        
        if ($payload === null) {
            $payload = array(
                'event' => 'test',
                'timestamp' => time(),
                'source' => 'forex-affiliate-suite-pro',
                'data' => array(
                    'message' => 'This is a test webhook from Forex Affiliate Suite Pro',
                    'site_url' => home_url(),
                ),
            );
        }
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
        ));
        
        $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300;
        
        // Log the test
        fasp_log_webhook_attempt(array(
            'url' => $url,
            'payload' => $payload,
            'response_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'response_body' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
            'success' => $success,
            'is_test' => true,
        ));
        
        return array(
            'success' => $success,
            'response_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'response_body' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
        );
    }
}

/**
 * Render the webhook logging admin page
 */
if (!function_exists('fasp_render_webhook_log_page')) {
    function fasp_render_webhook_log_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        // Handle retry
        if (isset($_POST['fasp_retry_webhook']) && isset($_POST['entry_id'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_webhook_nonce'] ?? '')), 'fasp_webhook_action')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $entry_id = sanitize_text_field(wp_unslash($_POST['entry_id']));
            $result = fasp_retry_webhook($entry_id);
            
            if (is_wp_error($result)) {
                echo '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="updated"><p>' . esc_html__('Webhook retried successfully.', 'forex-affiliate-suite-pro') . '</p></div>';
            }
        }
        
        // Handle test webhook
        if (isset($_POST['fasp_test_webhook']) && isset($_POST['test_url'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_webhook_nonce'] ?? '')), 'fasp_webhook_action')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $test_url = esc_url_raw(wp_unslash($_POST['test_url']));
            $result = fasp_send_test_webhook($test_url);
            
            if ($result['success']) {
                echo '<div class="updated"><p>' . esc_html__('Test webhook sent successfully.', 'forex-affiliate-suite-pro') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Test webhook failed.', 'forex-affiliate-suite-pro') . '</p></div>';
            }
        }
        
        // Handle clear log
        if (isset($_POST['fasp_clear_log'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_webhook_nonce'] ?? '')), 'fasp_webhook_action')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            update_option('fasp_leads_webhook_log', array());
            echo '<div class="updated"><p>' . esc_html__('Webhook log cleared.', 'forex-affiliate-suite-pro') . '</p></div>';
        }
        
        $log = fasp_get_webhook_log();
        
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('Webhook Logging', 'forex-affiliate-suite-pro'); ?></h1>
            
            <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;">
                <!-- Test Webhook -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Send Test Webhook', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('fasp_webhook_action', 'fasp_webhook_nonce'); ?>
                        
                        <p>
                            <label for="test_url"><?php esc_html_e('Webhook URL:', 'forex-affiliate-suite-pro'); ?></label>
                            <input type="url" id="test_url" name="test_url" style="width:100%;" placeholder="https://example.com/webhook" required>
                        </p>
                        
                        <p>
                            <button type="submit" name="fasp_test_webhook" value="1" class="button button-primary">
                                <?php esc_html_e('Send Test', 'forex-affiliate-suite-pro'); ?>
                            </button>
                        </p>
                    </form>
                    
                    <h3><?php esc_html_e('Payload Preview', 'forex-affiliate-suite-pro'); ?></h3>
                    <pre style="background:#f8fafc;padding:10px;border-radius:8px;font-size:12px;overflow:auto;max-height:200px;"><?php
                        echo esc_html(wp_json_encode(array(
                            'event' => 'test',
                            'timestamp' => time(),
                            'source' => 'forex-affiliate-suite-pro',
                            'data' => array(
                                'message' => 'This is a test webhook from Forex Affiliate Suite Pro',
                                'site_url' => home_url(),
                            ),
                        ), JSON_PRETTY_PRINT));
                    ?></pre>
                </div>
                
                <!-- Log Stats -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Log Statistics', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <?php
                    $total = count($log);
                    $successful = count(array_filter($log, function ($e) {
                        return !empty($e['success']);
                    }));
                    $failed = $total - $successful;
                    ?>
                    
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;text-align:center;">
                        <div style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#3b82f6;"><?php echo esc_html($total); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Total', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                        <div style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#10b981;"><?php echo esc_html($successful); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Successful', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                        <div style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#dc2626;"><?php echo esc_html($failed); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Failed', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                    </div>
                    
                    <form method="post" action="" style="margin-top:20px;">
                        <?php wp_nonce_field('fasp_webhook_action', 'fasp_webhook_nonce'); ?>
                        <button type="submit" name="fasp_clear_log" value="1" class="button" onclick="return confirm('<?php esc_attr_e('Clear all log entries?', 'forex-affiliate-suite-pro'); ?>');">
                            <?php esc_html_e('Clear Log', 'forex-affiliate-suite-pro'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Log Entries -->
            <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-top:20px;">
                <h2><?php esc_html_e('Recent Webhook Attempts', 'forex-affiliate-suite-pro'); ?></h2>
                
                <?php if (empty($log)) : ?>
                    <p class="fasp-muted"><?php esc_html_e('No webhook attempts logged yet.', 'forex-affiliate-suite-pro'); ?></p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Time', 'forex-affiliate-suite-pro'); ?></th>
                                <th><?php esc_html_e('URL', 'forex-affiliate-suite-pro'); ?></th>
                                <th><?php esc_html_e('Status', 'forex-affiliate-suite-pro'); ?></th>
                                <th><?php esc_html_e('Response', 'forex-affiliate-suite-pro'); ?></th>
                                <th><?php esc_html_e('Actions', 'forex-affiliate-suite-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($log as $entry) : ?>
                                <tr>
                                    <td>
                                        <?php
                                        $timestamp = isset($entry['timestamp']) ? $entry['timestamp'] : 0;
                                        echo esc_html(wp_date('Y-m-d H:i:s', $timestamp));
                                        ?>
                                        <?php if (!empty($entry['is_test'])) : ?>
                                            <br><span style="font-size:11px;color:#6b7280;"><?php esc_html_e('(Test)', 'forex-affiliate-suite-pro'); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($entry['is_retry'])) : ?>
                                            <br><span style="font-size:11px;color:#6b7280;"><?php esc_html_e('(Retry)', 'forex-affiliate-suite-pro'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <code style="font-size:11px;"><?php echo esc_html($entry['url'] ?? '—'); ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($entry['success'])) : ?>
                                            <span style="color:#166534;font-weight:500;">✓ <?php echo esc_html($entry['response_code'] ?? 200); ?></span>
                                        <?php else : ?>
                                            <span style="color:#dc2626;font-weight:500;">✗ <?php echo esc_html($entry['response_code'] ?? 0); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <code style="font-size:11px;"><?php echo esc_html(substr($entry['response_body'] ?? '', 0, 100)); ?></code>
                                    </td>
                                    <td>
                                        <?php if (empty($entry['success']) && !empty($entry['id'])) : ?>
                                            <form method="post" action="" style="display:inline;">
                                                <?php wp_nonce_field('fasp_webhook_action', 'fasp_webhook_nonce'); ?>
                                                <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry['id']); ?>">
                                                <button type="submit" name="fasp_retry_webhook" value="1" class="button button-small">
                                                    <?php esc_html_e('Retry', 'forex-affiliate-suite-pro'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="button button-small" onclick="togglePayload('<?php echo esc_attr($entry['id'] ?? ''); ?>')">
                                            <?php esc_html_e('Payload', 'forex-affiliate-suite-pro'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="payload-<?php echo esc_attr($entry['id'] ?? ''); ?>" style="display:none;">
                                    <td colspan="5">
                                        <pre style="background:#f8fafc;padding:10px;border-radius:8px;font-size:11px;overflow:auto;max-height:200px;margin:0;"><?php
                                            echo esc_html(wp_json_encode($entry['payload'] ?? array(), JSON_PRETTY_PRINT));
                                        ?></pre>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        function togglePayload(id) {
            var row = document.getElementById('payload-' + id);
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
            }
        }
        </script>
        <?php
    }
}

/**
 * Add webhook log to admin menu
 */
add_action('admin_menu', 'fasp_add_webhook_log_menu', 53);
if (!function_exists('fasp_add_webhook_log_menu')) {
    function fasp_add_webhook_log_menu() {
        $parent = function_exists('fasp_parent_slug') ? fasp_parent_slug() : 'forex-affiliate';
        
        add_submenu_page(
            $parent,
            __('Webhook Log', 'forex-affiliate-suite-pro'),
            __('Webhook Log', 'forex-affiliate-suite-pro'),
            'manage_options',
            'fasp_webhook_log',
            'fasp_render_webhook_log_page'
        );
    }
}
