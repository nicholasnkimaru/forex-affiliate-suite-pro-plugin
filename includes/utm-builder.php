<?php
/**
 * UTM Builder - Marketing UTM link generator
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get saved UTM presets
 *
 * @return array Array of preset configurations
 */
if (!function_exists('fasp_get_utm_presets')) {
    function fasp_get_utm_presets() {
        $presets = get_option('fasp_utm_presets', array());
        return is_array($presets) ? $presets : array();
    }
}

/**
 * Save UTM presets
 *
 * @param array $presets Array of preset configurations
 * @return bool Whether the save was successful
 */
if (!function_exists('fasp_save_utm_presets')) {
    function fasp_save_utm_presets($presets) {
        if (!is_array($presets)) {
            return false;
        }
        return update_option('fasp_utm_presets', $presets);
    }
}

/**
 * Build a UTM URL from components
 *
 * @param string $base_url Base URL
 * @param array  $params   UTM parameters
 * @param bool   $append_affiliate Whether to append affiliate tracking
 * @return string Complete URL with UTM parameters
 */
if (!function_exists('fasp_build_utm_url')) {
    function fasp_build_utm_url($base_url, $params, $append_affiliate = false) {
        if (empty($base_url)) {
            return '';
        }
        
        // Validate and sanitize base URL
        $base_url = esc_url_raw($base_url);
        
        // Sanitize UTM parameters
        $utm_keys = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term');
        $clean_params = array();
        
        foreach ($utm_keys as $key) {
            if (!empty($params[$key])) {
                $value = sanitize_text_field($params[$key]);
                // Only allow alphanumeric, hyphens, underscores
                if (preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
                    $clean_params[$key] = $value;
                }
            }
        }
        
        // Append affiliate ID if enabled
        if ($append_affiliate) {
            $affiliate_id = get_option('fasp_affiliate_id', '');
            if (!empty($affiliate_id)) {
                $clean_params['ref'] = sanitize_key($affiliate_id);
            }
        }
        
        if (empty($clean_params)) {
            return $base_url;
        }
        
        return add_query_arg($clean_params, $base_url);
    }
}

/**
 * Render the UTM Builder admin page
 */
if (!function_exists('fasp_render_utm_builder_page')) {
    function fasp_render_utm_builder_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        $presets = fasp_get_utm_presets();
        
        // Handle preset save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_utm_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_utm_nonce'])), 'fasp_utm_save')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            // Save new preset
            if (isset($_POST['save_preset']) && isset($_POST['preset_name'])) {
                $preset_name = sanitize_text_field(wp_unslash($_POST['preset_name']));
                if (!empty($preset_name)) {
                    $new_preset = array(
                        'name' => $preset_name,
                        'base_url' => isset($_POST['base_url']) ? esc_url_raw(wp_unslash($_POST['base_url'])) : '',
                        'utm_source' => isset($_POST['utm_source']) ? sanitize_text_field(wp_unslash($_POST['utm_source'])) : '',
                        'utm_medium' => isset($_POST['utm_medium']) ? sanitize_text_field(wp_unslash($_POST['utm_medium'])) : '',
                        'utm_campaign' => isset($_POST['utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['utm_campaign'])) : '',
                        'utm_content' => isset($_POST['utm_content']) ? sanitize_text_field(wp_unslash($_POST['utm_content'])) : '',
                        'utm_term' => isset($_POST['utm_term']) ? sanitize_text_field(wp_unslash($_POST['utm_term'])) : '',
                        'append_affiliate' => isset($_POST['append_affiliate']) ? true : false,
                    );
                    $presets[] = $new_preset;
                    fasp_save_utm_presets($presets);
                    echo '<div class="updated"><p>' . esc_html__('Preset saved.', 'forex-affiliate-suite-pro') . '</p></div>';
                }
            }
            
            // Delete preset
            if (isset($_POST['delete_preset']) && isset($_POST['preset_index'])) {
                $index = absint($_POST['preset_index']);
                if (isset($presets[$index])) {
                    unset($presets[$index]);
                    $presets = array_values($presets); // Re-index
                    fasp_save_utm_presets($presets);
                    echo '<div class="updated"><p>' . esc_html__('Preset deleted.', 'forex-affiliate-suite-pro') . '</p></div>';
                }
            }
        }
        
        // Get current values from GET or defaults
        $base_url = isset($_GET['base_url']) ? esc_url(wp_unslash($_GET['base_url'])) : home_url('/');
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field(wp_unslash($_GET['utm_source'])) : '';
        $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field(wp_unslash($_GET['utm_medium'])) : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field(wp_unslash($_GET['utm_campaign'])) : '';
        $utm_content = isset($_GET['utm_content']) ? sanitize_text_field(wp_unslash($_GET['utm_content'])) : '';
        $utm_term = isset($_GET['utm_term']) ? sanitize_text_field(wp_unslash($_GET['utm_term'])) : '';
        $append_affiliate = isset($_GET['append_affiliate']) ? true : false;
        
        // Load preset if selected
        if (isset($_GET['load_preset']) && isset($presets[absint($_GET['load_preset'])])) {
            $p = $presets[absint($_GET['load_preset'])];
            $base_url = $p['base_url'];
            $utm_source = $p['utm_source'];
            $utm_medium = $p['utm_medium'];
            $utm_campaign = $p['utm_campaign'];
            $utm_content = $p['utm_content'] ?? '';
            $utm_term = $p['utm_term'] ?? '';
            $append_affiliate = $p['append_affiliate'] ?? false;
        }
        
        // Build the URL
        $generated_url = fasp_build_utm_url($base_url, array(
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'utm_content' => $utm_content,
            'utm_term' => $utm_term,
        ), $append_affiliate);
        
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('UTM Builder', 'forex-affiliate-suite-pro'); ?></h1>
            
            <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;">
                <!-- UTM Builder Form -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Build Your URL', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <form method="get" action="">
                        <input type="hidden" name="page" value="fasp_utm_builder">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="base_url"><?php esc_html_e('Base URL', 'forex-affiliate-suite-pro'); ?> *</label></th>
                                <td>
                                    <input type="url" id="base_url" name="base_url" value="<?php echo esc_attr($base_url); ?>" style="width:100%;" required>
                                    <p class="description"><?php esc_html_e('The destination URL (e.g., landing page)', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="utm_source"><?php esc_html_e('utm_source', 'forex-affiliate-suite-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="utm_source" name="utm_source" value="<?php echo esc_attr($utm_source); ?>" style="width:100%;" pattern="[a-zA-Z0-9_\-]+">
                                    <p class="description"><?php esc_html_e('e.g., google, facebook, newsletter', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="utm_medium"><?php esc_html_e('utm_medium', 'forex-affiliate-suite-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="utm_medium" name="utm_medium" value="<?php echo esc_attr($utm_medium); ?>" style="width:100%;" pattern="[a-zA-Z0-9_\-]+">
                                    <p class="description"><?php esc_html_e('e.g., cpc, email, social', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="utm_campaign"><?php esc_html_e('utm_campaign', 'forex-affiliate-suite-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="utm_campaign" name="utm_campaign" value="<?php echo esc_attr($utm_campaign); ?>" style="width:100%;" pattern="[a-zA-Z0-9_\-]+">
                                    <p class="description"><?php esc_html_e('e.g., spring_sale, brand_awareness', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="utm_content"><?php esc_html_e('utm_content', 'forex-affiliate-suite-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="utm_content" name="utm_content" value="<?php echo esc_attr($utm_content); ?>" style="width:100%;" pattern="[a-zA-Z0-9_\-]+">
                                    <p class="description"><?php esc_html_e('e.g., banner_a, text_link (optional)', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="utm_term"><?php esc_html_e('utm_term', 'forex-affiliate-suite-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="utm_term" name="utm_term" value="<?php echo esc_attr($utm_term); ?>" style="width:100%;" pattern="[a-zA-Z0-9_\-]+">
                                    <p class="description"><?php esc_html_e('e.g., forex_trading, keywords (optional)', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Append Affiliate ID', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="append_affiliate" value="1" <?php checked($append_affiliate); ?>>
                                        <?php esc_html_e('Add affiliate tracking parameter', 'forex-affiliate-suite-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Generate URL', 'forex-affiliate-suite-pro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Generated URL -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Generated URL', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <?php if ($generated_url) : ?>
                        <div style="background:#f8fafc;padding:15px;border-radius:8px;margin-bottom:15px;word-break:break-all;">
                            <code id="generated-url" style="font-size:13px;"><?php echo esc_html($generated_url); ?></code>
                        </div>
                        
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="button" onclick="copyToClipboard()" id="copy-btn">
                                <?php esc_html_e('📋 Copy URL', 'forex-affiliate-suite-pro'); ?>
                            </button>
                            <a class="button" href="<?php echo esc_url($generated_url); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e('🔗 Open URL', 'forex-affiliate-suite-pro'); ?>
                            </a>
                        </div>
                        
                        <!-- QR Code -->
                        <div style="margin-top:20px;">
                            <h3><?php esc_html_e('QR Code', 'forex-affiliate-suite-pro'); ?></h3>
                            <div id="qrcode" style="background:#fff;padding:10px;display:inline-block;border:1px solid #e5e7eb;border-radius:8px;"></div>
                            <p class="description"><?php esc_html_e('Scan with mobile device to open URL', 'forex-affiliate-suite-pro'); ?></p>
                        </div>
                        
                        <!-- Save as Preset -->
                        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e7eb;">
                            <h3><?php esc_html_e('Save as Preset', 'forex-affiliate-suite-pro'); ?></h3>
                            <form method="post" action="" style="display:flex;gap:10px;align-items:flex-end;">
                                <?php wp_nonce_field('fasp_utm_save', 'fasp_utm_nonce'); ?>
                                <input type="hidden" name="base_url" value="<?php echo esc_attr($base_url); ?>">
                                <input type="hidden" name="utm_source" value="<?php echo esc_attr($utm_source); ?>">
                                <input type="hidden" name="utm_medium" value="<?php echo esc_attr($utm_medium); ?>">
                                <input type="hidden" name="utm_campaign" value="<?php echo esc_attr($utm_campaign); ?>">
                                <input type="hidden" name="utm_content" value="<?php echo esc_attr($utm_content); ?>">
                                <input type="hidden" name="utm_term" value="<?php echo esc_attr($utm_term); ?>">
                                <input type="hidden" name="append_affiliate" value="<?php echo $append_affiliate ? '1' : ''; ?>">
                                
                                <div>
                                    <label for="preset_name" style="display:block;margin-bottom:4px;"><?php esc_html_e('Preset Name:', 'forex-affiliate-suite-pro'); ?></label>
                                    <input type="text" id="preset_name" name="preset_name" required style="width:200px;">
                                </div>
                                <button type="submit" name="save_preset" value="1" class="button"><?php esc_html_e('Save Preset', 'forex-affiliate-suite-pro'); ?></button>
                            </form>
                        </div>
                    <?php else : ?>
                        <p class="fasp-muted"><?php esc_html_e('Fill in the form to generate a UTM-tagged URL.', 'forex-affiliate-suite-pro'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Saved Presets -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('Saved Presets', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <?php if (empty($presets)) : ?>
                        <p class="fasp-muted"><?php esc_html_e('No presets saved yet.', 'forex-affiliate-suite-pro'); ?></p>
                    <?php else : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'forex-affiliate-suite-pro'); ?></th>
                                    <th><?php esc_html_e('Source', 'forex-affiliate-suite-pro'); ?></th>
                                    <th><?php esc_html_e('Medium', 'forex-affiliate-suite-pro'); ?></th>
                                    <th><?php esc_html_e('Campaign', 'forex-affiliate-suite-pro'); ?></th>
                                    <th><?php esc_html_e('Actions', 'forex-affiliate-suite-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($presets as $i => $preset) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($preset['name']); ?></strong></td>
                                        <td><?php echo esc_html($preset['utm_source'] ?: '—'); ?></td>
                                        <td><?php echo esc_html($preset['utm_medium'] ?: '—'); ?></td>
                                        <td><?php echo esc_html($preset['utm_campaign'] ?: '—'); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(add_query_arg('load_preset', $i, admin_url('admin.php?page=fasp_utm_builder'))); ?>" class="button button-small">
                                                <?php esc_html_e('Load', 'forex-affiliate-suite-pro'); ?>
                                            </a>
                                            <form method="post" action="" style="display:inline;">
                                                <?php wp_nonce_field('fasp_utm_save', 'fasp_utm_nonce'); ?>
                                                <input type="hidden" name="preset_index" value="<?php echo esc_attr($i); ?>">
                                                <button type="submit" name="delete_preset" value="1" class="button button-small" onclick="return confirm('<?php esc_attr_e('Delete this preset?', 'forex-affiliate-suite-pro'); ?>');">
                                                    <?php esc_html_e('Delete', 'forex-affiliate-suite-pro'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Tips -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2><?php esc_html_e('UTM Parameter Tips', 'forex-affiliate-suite-pro'); ?></h2>
                    <ul style="list-style:disc;padding-left:20px;line-height:1.8;">
                        <li><strong>utm_source:</strong> <?php esc_html_e('The referrer (e.g., google, facebook, twitter)', 'forex-affiliate-suite-pro'); ?></li>
                        <li><strong>utm_medium:</strong> <?php esc_html_e('Marketing medium (e.g., cpc, email, social, banner)', 'forex-affiliate-suite-pro'); ?></li>
                        <li><strong>utm_campaign:</strong> <?php esc_html_e('Product or campaign name (e.g., spring_promo)', 'forex-affiliate-suite-pro'); ?></li>
                        <li><strong>utm_content:</strong> <?php esc_html_e('A/B test or ad variation (e.g., banner_v1)', 'forex-affiliate-suite-pro'); ?></li>
                        <li><strong>utm_term:</strong> <?php esc_html_e('Paid keywords (e.g., forex_trading)', 'forex-affiliate-suite-pro'); ?></li>
                    </ul>
                    <p class="fasp-muted"><?php esc_html_e('Use lowercase, underscores instead of spaces, and be consistent.', 'forex-affiliate-suite-pro'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- QR Code Library -->
        <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
        <script>
        (function() {
            // Generate QR code
            var url = <?php echo wp_json_encode($generated_url); ?>;
            if (url && typeof qrcode !== 'undefined') {
                var qr = qrcode(0, 'M');
                qr.addData(url);
                qr.make();
                document.getElementById('qrcode').innerHTML = qr.createImgTag(5);
            }
        })();
        
        function copyToClipboard() {
            var url = document.getElementById('generated-url').textContent;
            navigator.clipboard.writeText(url).then(function() {
                var btn = document.getElementById('copy-btn');
                btn.textContent = '✓ <?php echo esc_js(__('Copied!', 'forex-affiliate-suite-pro')); ?>';
                setTimeout(function() {
                    btn.textContent = '📋 <?php echo esc_js(__('Copy URL', 'forex-affiliate-suite-pro')); ?>';
                }, 2000);
            });
        }
        </script>
        <?php
    }
}

/**
 * Add UTM Builder to admin menu
 */
add_action('admin_menu', 'fasp_add_utm_builder_menu', 52);
if (!function_exists('fasp_add_utm_builder_menu')) {
    function fasp_add_utm_builder_menu() {
        $parent = function_exists('fasp_parent_slug') ? fasp_parent_slug() : 'forex-affiliate';
        
        add_submenu_page(
            $parent,
            __('UTM Builder', 'forex-affiliate-suite-pro'),
            __('UTM Builder', 'forex-affiliate-suite-pro'),
            'manage_options',
            'fasp_utm_builder',
            'fasp_render_utm_builder_page'
        );
    }
}
