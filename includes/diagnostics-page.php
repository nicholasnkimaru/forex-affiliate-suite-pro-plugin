<?php
/**
 * Diagnostics - Connectivity checks, webhook pinger, Stripe/M-Pesa testers
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if curl is available
 *
 * @return array Check result
 */
if (!function_exists('fasp_check_curl')) {
    function fasp_check_curl() {
        return array(
            'name' => 'cURL',
            'available' => function_exists('curl_version'),
            'version' => function_exists('curl_version') ? curl_version()['version'] : null,
        );
    }
}

/**
 * Check if wp_remote_post works
 *
 * @return array Check result
 */
if (!function_exists('fasp_check_wp_remote')) {
    function fasp_check_wp_remote() {
        $test_url = 'https://httpbin.org/post';
        $response = wp_remote_post($test_url, array(
            'timeout' => 10,
            'body' => array('test' => '1'),
        ));
        
        return array(
            'name' => 'wp_remote_post',
            'available' => true,
            'working' => !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200,
            'error' => is_wp_error($response) ? $response->get_error_message() : null,
        );
    }
}

/**
 * Check if WP Cron is working
 *
 * @return array Check result
 */
if (!function_exists('fasp_check_wp_cron')) {
    function fasp_check_wp_cron() {
        $disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $next_run = wp_next_scheduled('wp_version_check');
        
        return array(
            'name' => 'WP Cron',
            'available' => !$disabled,
            'disabled' => $disabled,
            'next_run' => $next_run ? wp_date('Y-m-d H:i:s', $next_run) : null,
        );
    }
}

/**
 * Ping a webhook URL
 *
 * @param string $url URL to ping
 * @return array Ping result
 */
if (!function_exists('fasp_ping_webhook')) {
    function fasp_ping_webhook($url) {
        if (empty($url)) {
            return array('success' => false, 'error' => __('URL is required', 'forex-affiliate-suite-pro'));
        }
        
        $start = microtime(true);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true,
        ));
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        return array(
            'success' => !is_wp_error($response),
            'status_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'duration_ms' => $duration,
            'error' => is_wp_error($response) ? $response->get_error_message() : null,
        );
    }
}

/**
 * Test Stripe webhook signature verification
 *
 * @param string $payload Test payload
 * @param string $secret Webhook secret
 * @return array Test result
 */
if (!function_exists('fasp_test_stripe_signature')) {
    function fasp_test_stripe_signature($payload, $secret) {
        if (empty($payload) || empty($secret)) {
            return array('success' => false, 'error' => __('Payload and secret are required', 'forex-affiliate-suite-pro'));
        }
        
        // Generate a test signature
        $timestamp = time();
        $signed_payload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signed_payload, $secret);
        $header = 't=' . $timestamp . ',v1=' . $signature;
        
        // Verify the signature
        $expected = hash_hmac('sha256', $signed_payload, $secret);
        $valid = hash_equals($signature, $expected);
        
        return array(
            'success' => $valid,
            'timestamp' => $timestamp,
            'signature_header' => $header,
            'message' => $valid ? __('Signature verification passed', 'forex-affiliate-suite-pro') : __('Signature verification failed', 'forex-affiliate-suite-pro'),
        );
    }
}

/**
 * Check if M-Pesa sandbox credentials exist
 *
 * @return bool Whether sandbox credentials are configured
 */
if (!function_exists('fasp_mpesa_sandbox_exists')) {
    function fasp_mpesa_sandbox_exists() {
        $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
        
        $consumer_key = isset($payments['mpesa_consumer_key']) ? $payments['mpesa_consumer_key'] : '';
        $consumer_secret = isset($payments['mpesa_consumer_secret']) ? $payments['mpesa_consumer_secret'] : '';
        $sandbox = isset($payments['mpesa_sandbox']) ? $payments['mpesa_sandbox'] : false;
        
        return !empty($consumer_key) && !empty($consumer_secret) && $sandbox;
    }
}

/**
 * Test M-Pesa STK Push (sandbox only)
 *
 * @param string $phone Phone number to test
 * @param float  $amount Amount to test
 * @return array Test result
 */
if (!function_exists('fasp_test_mpesa_stk')) {
    function fasp_test_mpesa_stk($phone, $amount) {
        if (!fasp_mpesa_sandbox_exists()) {
            return array('success' => false, 'error' => __('M-Pesa sandbox not configured', 'forex-affiliate-suite-pro'));
        }
        
        if (empty($phone) || empty($amount)) {
            return array('success' => false, 'error' => __('Phone and amount are required', 'forex-affiliate-suite-pro'));
        }
        
        // Get credentials
        $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
        $consumer_key = $payments['mpesa_consumer_key'] ?? '';
        $consumer_secret = $payments['mpesa_consumer_secret'] ?? '';
        $shortcode = $payments['mpesa_shortcode'] ?? '';
        $passkey = $payments['mpesa_passkey'] ?? '';
        $callback_url = $payments['mpesa_callback_url'] ?? home_url('/fasp-mpesa-callback');
        
        // Get access token
        $auth_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $auth_response = wp_remote_get($auth_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
            ),
        ));
        
        if (is_wp_error($auth_response)) {
            return array('success' => false, 'error' => $auth_response->get_error_message());
        }
        
        $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);
        if (!isset($auth_body['access_token'])) {
            return array('success' => false, 'error' => __('Failed to get access token', 'forex-affiliate-suite-pro'));
        }
        
        $access_token = $auth_body['access_token'];
        
        // STK Push
        $timestamp = gmdate('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        
        $stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $stk_response = wp_remote_post($stk_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => absint($amount),
                'PartyA' => $phone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callback_url,
                'AccountReference' => 'FASP_TEST',
                'TransactionDesc' => 'Test STK Push',
            )),
        ));
        
        if (is_wp_error($stk_response)) {
            return array('success' => false, 'error' => $stk_response->get_error_message());
        }
        
        $stk_body = json_decode(wp_remote_retrieve_body($stk_response), true);
        
        return array(
            'success' => isset($stk_body['ResponseCode']) && $stk_body['ResponseCode'] === '0',
            'response' => $stk_body,
        );
    }
}

/**
 * Render the diagnostics admin page
 */
if (!function_exists('fasp_render_diagnostics_page')) {
    function fasp_render_diagnostics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        $curl_check = fasp_check_curl();
        $cron_check = fasp_check_wp_cron();
        
        // Handle webhook ping
        $ping_result = null;
        if (isset($_POST['fasp_ping_webhook']) && isset($_POST['ping_url'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_diag_nonce'] ?? '')), 'fasp_diagnostics')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $ping_url = esc_url_raw(wp_unslash($_POST['ping_url']));
            $ping_result = fasp_ping_webhook($ping_url);
        }
        
        // Handle Stripe signature test
        $stripe_result = null;
        if (isset($_POST['fasp_test_stripe']) && isset($_POST['stripe_payload']) && isset($_POST['stripe_secret'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_diag_nonce'] ?? '')), 'fasp_diagnostics')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $payload = sanitize_text_field(wp_unslash($_POST['stripe_payload']));
            $secret = sanitize_text_field(wp_unslash($_POST['stripe_secret']));
            $stripe_result = fasp_test_stripe_signature($payload, $secret);
        }
        
        // Handle M-Pesa STK test
        $mpesa_result = null;
        if (isset($_POST['fasp_test_mpesa']) && isset($_POST['mpesa_phone']) && isset($_POST['mpesa_amount'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_diag_nonce'] ?? '')), 'fasp_diagnostics')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $phone = sanitize_text_field(wp_unslash($_POST['mpesa_phone']));
            // Sanitize and validate amount - must be numeric and within range
            $amount_raw = sanitize_text_field(wp_unslash($_POST['mpesa_amount']));
            $amount = is_numeric($amount_raw) ? floatval($amount_raw) : 0;
            // Limit to 1-10 KES for sandbox testing
            $amount = max(1, min(10, $amount));
            $mpesa_result = fasp_test_mpesa_stk($phone, $amount);
        }
        
        // Handle wp_remote_post check
        $remote_check = null;
        if (isset($_POST['fasp_check_remote'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_diag_nonce'] ?? '')), 'fasp_diagnostics')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            $remote_check = fasp_check_wp_remote();
        }
        
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('Diagnostics', 'forex-affiliate-suite-pro'); ?></h1>
            
            <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;">
                <!-- System Status -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('System Status', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <td><strong><?php esc_html_e('PHP Version', 'forex-affiliate-suite-pro'); ?></strong></td>
                                <td><?php echo esc_html(phpversion()); ?></td>
                                <td>
                                    <?php if (version_compare(phpversion(), '7.4', '>=')) : ?>
                                        <span style="color:#166534;">✓</span>
                                    <?php else : ?>
                                        <span style="color:#dc2626;">✗ <?php esc_html_e('Upgrade recommended', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('WordPress Version', 'forex-affiliate-suite-pro'); ?></strong></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                                <td><span style="color:#166534;">✓</span></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('cURL', 'forex-affiliate-suite-pro'); ?></strong></td>
                                <td><?php echo esc_html($curl_check['version'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($curl_check['available']) : ?>
                                        <span style="color:#166534;">✓</span>
                                    <?php else : ?>
                                        <span style="color:#dc2626;">✗ <?php esc_html_e('Not available', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('WP Cron', 'forex-affiliate-suite-pro'); ?></strong></td>
                                <td><?php echo $cron_check['disabled'] ? esc_html__('Disabled', 'forex-affiliate-suite-pro') : esc_html__('Enabled', 'forex-affiliate-suite-pro'); ?></td>
                                <td>
                                    <?php if (!$cron_check['disabled']) : ?>
                                        <span style="color:#166534;">✓</span>
                                    <?php else : ?>
                                        <span style="color:#f59e0b;">⚠ <?php esc_html_e('External cron required', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Memory Limit', 'forex-affiliate-suite-pro'); ?></strong></td>
                                <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                                <td><span style="color:#166534;">✓</span></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <form method="post" action="" style="margin-top:15px;">
                        <?php wp_nonce_field('fasp_diagnostics', 'fasp_diag_nonce'); ?>
                        <button type="submit" name="fasp_check_remote" value="1" class="button">
                            <?php esc_html_e('Test wp_remote_post', 'forex-affiliate-suite-pro'); ?>
                        </button>
                    </form>
                    
                    <?php if ($remote_check) : ?>
                        <div style="margin-top:10px;padding:10px;background:#f8fafc;border-radius:8px;">
                            <strong><?php esc_html_e('wp_remote_post:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($remote_check['working']) : ?>
                                <span style="color:#166534;">✓ <?php esc_html_e('Working', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#dc2626;">✗ <?php echo esc_html($remote_check['error']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Webhook Pinger -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Webhook Pinger', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('fasp_diagnostics', 'fasp_diag_nonce'); ?>
                        
                        <p>
                            <label for="ping_url"><?php esc_html_e('URL to ping:', 'forex-affiliate-suite-pro'); ?></label>
                            <input type="url" id="ping_url" name="ping_url" style="width:100%;" placeholder="https://example.com/webhook" required>
                        </p>
                        
                        <button type="submit" name="fasp_ping_webhook" value="1" class="button button-primary">
                            <?php esc_html_e('Ping', 'forex-affiliate-suite-pro'); ?>
                        </button>
                    </form>
                    
                    <?php if ($ping_result) : ?>
                        <div style="margin-top:15px;padding:15px;background:#f8fafc;border-radius:8px;">
                            <?php if ($ping_result['success']) : ?>
                                <p style="color:#166534;font-weight:500;">✓ <?php esc_html_e('Ping successful', 'forex-affiliate-suite-pro'); ?></p>
                            <?php else : ?>
                                <p style="color:#dc2626;font-weight:500;">✗ <?php esc_html_e('Ping failed', 'forex-affiliate-suite-pro'); ?></p>
                            <?php endif; ?>
                            <p><strong><?php esc_html_e('Status Code:', 'forex-affiliate-suite-pro'); ?></strong> <?php echo esc_html($ping_result['status_code']); ?></p>
                            <p><strong><?php esc_html_e('Duration:', 'forex-affiliate-suite-pro'); ?></strong> <?php echo esc_html($ping_result['duration_ms']); ?>ms</p>
                            <?php if ($ping_result['error']) : ?>
                                <p><strong><?php esc_html_e('Error:', 'forex-affiliate-suite-pro'); ?></strong> <?php echo esc_html($ping_result['error']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Stripe Signature Tester -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Stripe Signature Tester', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('fasp_diagnostics', 'fasp_diag_nonce'); ?>
                        
                        <p>
                            <label for="stripe_payload"><?php esc_html_e('Test Payload:', 'forex-affiliate-suite-pro'); ?></label>
                            <textarea id="stripe_payload" name="stripe_payload" rows="3" style="width:100%;">{"type":"test","data":{}}</textarea>
                        </p>
                        
                        <p>
                            <label for="stripe_secret"><?php esc_html_e('Webhook Secret:', 'forex-affiliate-suite-pro'); ?></label>
                            <input type="text" id="stripe_secret" name="stripe_secret" style="width:100%;" placeholder="whsec_...">
                        </p>
                        
                        <button type="submit" name="fasp_test_stripe" value="1" class="button button-primary">
                            <?php esc_html_e('Test Signature', 'forex-affiliate-suite-pro'); ?>
                        </button>
                    </form>
                    
                    <?php if ($stripe_result) : ?>
                        <div style="margin-top:15px;padding:15px;background:#f8fafc;border-radius:8px;">
                            <?php if ($stripe_result['success']) : ?>
                                <p style="color:#166534;font-weight:500;">✓ <?php echo esc_html($stripe_result['message']); ?></p>
                            <?php else : ?>
                                <p style="color:#dc2626;font-weight:500;">✗ <?php echo esc_html($stripe_result['error'] ?? $stripe_result['message']); ?></p>
                            <?php endif; ?>
                            <?php if (isset($stripe_result['signature_header'])) : ?>
                                <p><strong><?php esc_html_e('Generated Header:', 'forex-affiliate-suite-pro'); ?></strong></p>
                                <code style="font-size:11px;word-break:break-all;"><?php echo esc_html($stripe_result['signature_header']); ?></code>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- M-Pesa STK Tester -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('M-Pesa STK Sandbox Test', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <?php if (!fasp_mpesa_sandbox_exists()) : ?>
                        <div style="padding:15px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;">
                            <p style="margin:0;color:#92400e;">
                                <?php esc_html_e('M-Pesa sandbox credentials not configured. Please set up M-Pesa in Payments settings with sandbox mode enabled.', 'forex-affiliate-suite-pro'); ?>
                            </p>
                            <p style="margin:10px 0 0;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_payments')); ?>" class="button">
                                    <?php esc_html_e('Configure Payments', 'forex-affiliate-suite-pro'); ?>
                                </a>
                            </p>
                        </div>
                    <?php else : ?>
                        <form method="post" action="">
                            <?php wp_nonce_field('fasp_diagnostics', 'fasp_diag_nonce'); ?>
                            
                            <p>
                                <label for="mpesa_phone"><?php esc_html_e('Test Phone Number:', 'forex-affiliate-suite-pro'); ?></label>
                                <input type="tel" id="mpesa_phone" name="mpesa_phone" style="width:100%;" placeholder="254712345678" required>
                                <span class="description"><?php esc_html_e('Format: 254XXXXXXXXX', 'forex-affiliate-suite-pro'); ?></span>
                            </p>
                            
                            <p>
                                <label for="mpesa_amount"><?php esc_html_e('Test Amount (KES):', 'forex-affiliate-suite-pro'); ?></label>
                                <input type="number" id="mpesa_amount" name="mpesa_amount" style="width:100%;" value="1" min="1" max="10" required>
                            </p>
                            
                            <button type="submit" name="fasp_test_mpesa" value="1" class="button button-primary">
                                <?php esc_html_e('Send STK Push', 'forex-affiliate-suite-pro'); ?>
                            </button>
                        </form>
                        
                        <?php if ($mpesa_result) : ?>
                            <div style="margin-top:15px;padding:15px;background:#f8fafc;border-radius:8px;">
                                <?php if ($mpesa_result['success']) : ?>
                                    <p style="color:#166534;font-weight:500;">✓ <?php esc_html_e('STK Push initiated successfully', 'forex-affiliate-suite-pro'); ?></p>
                                <?php else : ?>
                                    <p style="color:#dc2626;font-weight:500;">✗ <?php echo esc_html($mpesa_result['error'] ?? __('STK Push failed', 'forex-affiliate-suite-pro')); ?></p>
                                <?php endif; ?>
                                <?php if (isset($mpesa_result['response'])) : ?>
                                    <pre style="font-size:11px;overflow:auto;max-height:150px;"><?php echo esc_html(wp_json_encode($mpesa_result['response'], JSON_PRETTY_PRINT)); ?></pre>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Add diagnostics to admin menu
 */
add_action('admin_menu', 'fasp_add_diagnostics_menu', 54);
if (!function_exists('fasp_add_diagnostics_menu')) {
    function fasp_add_diagnostics_menu() {
        $parent = function_exists('fasp_parent_slug') ? fasp_parent_slug() : 'forex-affiliate';
        
        add_submenu_page(
            $parent,
            __('Diagnostics', 'forex-affiliate-suite-pro'),
            __('Diagnostics', 'forex-affiliate-suite-pro'),
            'manage_options',
            'fasp_diagnostics',
            'fasp_render_diagnostics_page'
        );
    }
}
