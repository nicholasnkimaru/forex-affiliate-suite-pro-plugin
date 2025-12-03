<?php
/**
 * Geo Gating - Country-based access control
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include countries data
if (file_exists(__DIR__ . '/../plugin/data/countries.php')) {
    require_once __DIR__ . '/../plugin/data/countries.php';
}

/**
 * Lookup country by IP address using MaxMind or fallback API
 *
 * @param string $ip IP address to lookup
 * @return string|null ISO 3166-1 alpha-2 country code or null if not found
 */
if (!function_exists('fasp_lookup_country_by_ip')) {
    function fasp_lookup_country_by_ip($ip) {
        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        
        // Try MaxMind database first
        $mmdb_path = FASP_PATH . 'plugin/data/GeoLite2-City.mmdb';
        if (file_exists($mmdb_path) && class_exists('MaxMind\\Db\\Reader')) {
            try {
                $reader = new \MaxMind\Db\Reader($mmdb_path);
                $record = $reader->get($ip);
                $reader->close();
                
                if (isset($record['country']['iso_code'])) {
                    return strtoupper($record['country']['iso_code']);
                }
            } catch (Exception $e) {
                // Fall through to fallback API
                if (function_exists('fasp_log')) {
                    fasp_log('MaxMind lookup failed: ' . $e->getMessage());
                }
            }
        }
        
        // Fallback to ip-api.com (free, no API key required)
        $response = wp_remote_get(
            'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=countryCode',
            array(
                'timeout' => 5,
                'sslverify' => false,
            )
        );
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['countryCode'])) {
            return strtoupper($data['countryCode']);
        }
        
        return null;
    }
}

/**
 * Get the visitor's country code
 *
 * @return string|null ISO 3166-1 alpha-2 country code
 */
if (!function_exists('fasp_get_visitor_country')) {
    function fasp_get_visitor_country() {
        // Check for cached value in session/transient
        $cache_key = 'fasp_geo_' . md5(fasp_get_client_ip());
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached === 'unknown' ? null : $cached;
        }
        
        $ip = fasp_get_client_ip();
        $country = fasp_lookup_country_by_ip($ip);
        
        // Cache for 1 hour
        set_transient($cache_key, $country ?: 'unknown', HOUR_IN_SECONDS);
        
        return $country;
    }
}

/**
 * Get client IP address
 *
 * @return string IP address
 */
if (!function_exists('fasp_get_client_ip')) {
    function fasp_get_client_ip() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
}

/**
 * Check if a country is allowed based on geo gating rules
 *
 * @param string $country_code ISO 3166-1 alpha-2 country code
 * @return bool Whether the country is allowed
 */
if (!function_exists('fasp_is_country_allowed')) {
    function fasp_is_country_allowed($country_code) {
        if (empty($country_code)) {
            // Handle unknown countries
            return !get_option('fasp_geo_unknown_blocked', false);
        }
        
        $country_code = strtoupper($country_code);
        
        // Get settings
        $allowlist = get_option('fasp_geo_allow', array());
        $blocklist = get_option('fasp_geo_block', array());
        $regions = get_option('fasp_geo_regions', array());
        
        // Expand regions to country codes
        $region_countries = array();
        if (!empty($regions) && function_exists('fasp_get_region_countries')) {
            foreach ($regions as $region) {
                $region_countries = array_merge($region_countries, fasp_get_region_countries($region));
            }
        }
        
        // Check blocklist first (explicit blocks take priority)
        if (!empty($blocklist) && in_array($country_code, $blocklist, true)) {
            return false;
        }
        
        // If allowlist is set, only those countries are allowed
        if (!empty($allowlist)) {
            return in_array($country_code, $allowlist, true);
        }
        
        // If regions are set, check if country is in allowed regions
        if (!empty($region_countries)) {
            return in_array($country_code, $region_countries, true);
        }
        
        // Default: allow if no restrictions set
        return true;
    }
}

/**
 * Check if visitor passes geo gating
 *
 * @return bool Whether visitor is allowed
 */
if (!function_exists('fasp_visitor_passes_geo_gating')) {
    function fasp_visitor_passes_geo_gating() {
        // Check if geo gating is enabled
        if (!get_option('fasp_geo_enabled', false)) {
            return true;
        }
        
        $country = fasp_get_visitor_country();
        return fasp_is_country_allowed($country);
    }
}

/**
 * Render the geo gating admin page
 */
if (!function_exists('fasp_render_geo_gating_page')) {
    function fasp_render_geo_gating_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_geo_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_geo_nonce'])), 'fasp_geo_save')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            // Save settings
            $enabled = isset($_POST['fasp_geo_enabled']) ? true : false;
            update_option('fasp_geo_enabled', $enabled);
            
            $allowlist = isset($_POST['fasp_geo_allow']) && is_array($_POST['fasp_geo_allow'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['fasp_geo_allow']))
                : array();
            update_option('fasp_geo_allow', $allowlist);
            
            $blocklist = isset($_POST['fasp_geo_block']) && is_array($_POST['fasp_geo_block'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['fasp_geo_block']))
                : array();
            update_option('fasp_geo_block', $blocklist);
            
            $regions = isset($_POST['fasp_geo_regions']) && is_array($_POST['fasp_geo_regions'])
                ? array_map('sanitize_key', wp_unslash($_POST['fasp_geo_regions']))
                : array();
            update_option('fasp_geo_regions', $regions);
            
            $unknown_blocked = isset($_POST['fasp_geo_unknown_blocked']) ? true : false;
            update_option('fasp_geo_unknown_blocked', $unknown_blocked);
            
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'forex-affiliate-suite-pro') . '</p></div>';
        }
        
        // Handle MaxMind upload
        if (isset($_FILES['fasp_mmdb_upload']) && !empty($_FILES['fasp_mmdb_upload']['tmp_name'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_geo_nonce'] ?? '')), 'fasp_geo_save')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            // Validate file upload
            $file = $_FILES['fasp_mmdb_upload'];
            $filename = isset($file['name']) ? sanitize_file_name($file['name']) : '';
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Only allow .mmdb files
            if ($file_ext !== 'mmdb') {
                echo '<div class="error"><p>' . esc_html__('Invalid file type. Only .mmdb files are allowed.', 'forex-affiliate-suite-pro') . '</p></div>';
            } elseif (!isset($file['size']) || $file['size'] > 100 * 1024 * 1024) {
                // Max 100MB
                echo '<div class="error"><p>' . esc_html__('File too large. Maximum size is 100MB.', 'forex-affiliate-suite-pro') . '</p></div>';
            } elseif (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                echo '<div class="error"><p>' . esc_html__('Upload error occurred.', 'forex-affiliate-suite-pro') . '</p></div>';
            } else {
                $upload_dir = FASP_PATH . 'plugin/data/';
                if (!file_exists($upload_dir)) {
                    wp_mkdir_p($upload_dir);
                }
                
                // Use WordPress function to get clean tmp path - don't sanitize binary paths
                $tmp_name = $file['tmp_name'];
                $dest = $upload_dir . 'GeoLite2-City.mmdb';
                
                // Verify it's an uploaded file before moving
                if (is_uploaded_file($tmp_name) && move_uploaded_file($tmp_name, $dest)) {
                    echo '<div class="updated"><p>' . esc_html__('MaxMind database uploaded successfully.', 'forex-affiliate-suite-pro') . '</p></div>';
                } else {
                    echo '<div class="error"><p>' . esc_html__('Failed to upload MaxMind database.', 'forex-affiliate-suite-pro') . '</p></div>';
                }
            }
        }
        
        // Get current values
        $enabled = get_option('fasp_geo_enabled', false);
        $allowlist = get_option('fasp_geo_allow', array());
        $blocklist = get_option('fasp_geo_block', array());
        $selected_regions = get_option('fasp_geo_regions', array());
        $unknown_blocked = get_option('fasp_geo_unknown_blocked', false);
        
        // Get countries and regions
        $countries = function_exists('fasp_get_countries') ? fasp_get_countries() : array();
        $regions = function_exists('fasp_get_regions') ? fasp_get_regions() : array();
        
        // Check MaxMind status
        $mmdb_exists = file_exists(FASP_PATH . 'plugin/data/GeoLite2-City.mmdb');
        
        // Get visitor's country for testing
        $test_ip = isset($_GET['test_ip']) ? sanitize_text_field(wp_unslash($_GET['test_ip'])) : '';
        $test_result = null;
        if ($test_ip && filter_var($test_ip, FILTER_VALIDATE_IP)) {
            $test_result = fasp_lookup_country_by_ip($test_ip);
        }
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('Geo Gating', 'forex-affiliate-suite-pro'); ?></h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('fasp_geo_save', 'fasp_geo_nonce'); ?>
                
                <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;">
                    <!-- General Settings -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('Geo Gating Settings', 'forex-affiliate-suite-pro'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Geo Gating', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fasp_geo_enabled" value="1" <?php checked($enabled); ?>>
                                        <?php esc_html_e('Enable country-based access control', 'forex-affiliate-suite-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Block Unknown Countries', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fasp_geo_unknown_blocked" value="1" <?php checked($unknown_blocked); ?>>
                                        <?php esc_html_e('Block visitors whose country cannot be determined', 'forex-affiliate-suite-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- MaxMind Database -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('MaxMind GeoLite2 Database', 'forex-affiliate-suite-pro'); ?></h2>
                        
                        <p>
                            <strong><?php esc_html_e('Status:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($mmdb_exists) : ?>
                                <span style="color:#166534;">✓ <?php esc_html_e('Installed', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#dc2626;">✗ <?php esc_html_e('Not installed (using fallback API)', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </p>
                        
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: link to MaxMind */
                                esc_html__('Upload the GeoLite2-City.mmdb file from %s for faster, offline IP lookups.', 'forex-affiliate-suite-pro'),
                                '<a href="https://www.maxmind.com/en/geoip2-databases" target="_blank" rel="noopener">MaxMind</a>'
                            );
                            ?>
                        </p>
                        
                        <p>
                            <input type="file" name="fasp_mmdb_upload" accept=".mmdb">
                        </p>
                    </div>
                    
                    <!-- Region Selection -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('Allowed Regions', 'forex-affiliate-suite-pro'); ?></h2>
                        <p class="description"><?php esc_html_e('Select regions to allow. Leave empty to use individual country selection.', 'forex-affiliate-suite-pro'); ?></p>
                        
                        <select name="fasp_geo_regions[]" multiple style="min-height:200px;width:100%;">
                            <?php foreach ($regions as $key => $region) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $selected_regions, true) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($region['name']); ?> (<?php echo count($region['countries']); ?> <?php esc_html_e('countries', 'forex-affiliate-suite-pro'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Allowlist -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('Allowed Countries', 'forex-affiliate-suite-pro'); ?></h2>
                        <p class="description"><?php esc_html_e('Only these countries can access. Leave empty to allow all (except blocklist).', 'forex-affiliate-suite-pro'); ?></p>
                        
                        <select name="fasp_geo_allow[]" multiple style="min-height:200px;width:100%;">
                            <?php foreach ($countries as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php echo in_array($code, $allowlist, true) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Blocklist -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('Blocked Countries', 'forex-affiliate-suite-pro'); ?></h2>
                        <p class="description"><?php esc_html_e('These countries are always blocked (overrides allowlist).', 'forex-affiliate-suite-pro'); ?></p>
                        
                        <select name="fasp_geo_block[]" multiple style="min-height:200px;width:100%;">
                            <?php foreach ($countries as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php echo in_array($code, $blocklist, true) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'forex-affiliate-suite-pro'); ?></button>
                </p>
            </form>
            
            <!-- IP Test Tool -->
            <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-top:20px;">
                <h2><?php esc_html_e('Test IP Lookup', 'forex-affiliate-suite-pro'); ?></h2>
                
                <form method="get" action="" style="display:flex;gap:15px;align-items:flex-end;">
                    <input type="hidden" name="page" value="fasp_geo_gating">
                    
                    <div>
                        <label for="test_ip" style="display:block;margin-bottom:4px;"><?php esc_html_e('IP Address:', 'forex-affiliate-suite-pro'); ?></label>
                        <input type="text" id="test_ip" name="test_ip" value="<?php echo esc_attr($test_ip); ?>" placeholder="8.8.8.8" style="width:200px;">
                    </div>
                    
                    <button type="submit" class="button"><?php esc_html_e('Lookup', 'forex-affiliate-suite-pro'); ?></button>
                </form>
                
                <?php if ($test_ip) : ?>
                    <div style="margin-top:15px;padding:15px;background:#f8fafc;border-radius:8px;">
                        <p>
                            <strong><?php esc_html_e('IP:', 'forex-affiliate-suite-pro'); ?></strong> <?php echo esc_html($test_ip); ?><br>
                            <strong><?php esc_html_e('Country:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($test_result) : ?>
                                <?php
                                $country_name = isset($countries[$test_result]) ? $countries[$test_result] : $test_result;
                                echo esc_html($country_name) . ' (' . esc_html($test_result) . ')';
                                ?>
                            <?php else : ?>
                                <em><?php esc_html_e('Unknown', 'forex-affiliate-suite-pro'); ?></em>
                            <?php endif; ?>
                            <br>
                            <strong><?php esc_html_e('Access:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if (fasp_is_country_allowed($test_result)) : ?>
                                <span style="color:#166534;">✓ <?php esc_html_e('ALLOWED', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#dc2626;">✗ <?php esc_html_e('BLOCKED', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <p style="margin-top:15px;">
                    <strong><?php esc_html_e('Your IP:', 'forex-affiliate-suite-pro'); ?></strong>
                    <?php echo esc_html(fasp_get_client_ip()); ?>
                    (<?php
                    $visitor_country = fasp_get_visitor_country();
                    if ($visitor_country && isset($countries[$visitor_country])) {
                        echo esc_html($countries[$visitor_country]);
                    } else {
                        esc_html_e('Unknown', 'forex-affiliate-suite-pro');
                    }
                    ?>)
                </p>
            </div>
        </div>
        <?php
    }
}

/**
 * Add geo gating to admin menu
 */
add_action('admin_menu', 'fasp_add_geo_gating_menu', 51);
if (!function_exists('fasp_add_geo_gating_menu')) {
    function fasp_add_geo_gating_menu() {
        $parent = function_exists('fasp_parent_slug') ? fasp_parent_slug() : 'forex-affiliate';
        
        add_submenu_page(
            $parent,
            __('Geo Gating', 'forex-affiliate-suite-pro'),
            __('Geo Gating', 'forex-affiliate-suite-pro'),
            'manage_options',
            'fasp_geo_gating',
            'fasp_render_geo_gating_page'
        );
    }
}
