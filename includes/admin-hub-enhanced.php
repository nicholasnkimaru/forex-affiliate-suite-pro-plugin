<?php
/**
 * Enhanced Admin Hub - Dashboard with status cards, quick links, and checklist
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get hub status data
 *
 * @return array Status data for various integrations
 */
if (!function_exists('fasp_get_hub_status')) {
    function fasp_get_hub_status() {
        $status = array(
            'gating_enabled' => get_option('fasp_gating_require_login', false),
            'geo_enabled' => get_option('fasp_geo_enabled', false),
            'geo_rules_set' => !empty(get_option('fasp_geo_allow', array())) || !empty(get_option('fasp_geo_block', array())),
            'stripe_configured' => false,
            'mpesa_configured' => false,
            'webhook_healthy' => null,
            'platforms_count' => 0,
            'resources_count' => 0,
            'coaches_count' => 0,
        );
        
        // Check Stripe keys
        $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : array();
        if (!empty($payments)) {
            $stripe_pk = isset($payments['stripe_pk']) ? $payments['stripe_pk'] : '';
            $stripe_sk = isset($payments['stripe_sk']) ? $payments['stripe_sk'] : '';
            $status['stripe_configured'] = !empty($stripe_pk) && !empty($stripe_sk);
            
            // Check M-Pesa
            $mpesa_key = isset($payments['mpesa_consumer_key']) ? $payments['mpesa_consumer_key'] : '';
            $mpesa_secret = isset($payments['mpesa_consumer_secret']) ? $payments['mpesa_consumer_secret'] : '';
            $status['mpesa_configured'] = !empty($mpesa_key) && !empty($mpesa_secret);
        }
        
        // Check webhook health (last successful ping within 24h)
        $webhook_log = get_option('fasp_leads_webhook_log', array());
        if (!empty($webhook_log)) {
            $last_success = null;
            foreach (array_reverse($webhook_log) as $entry) {
                if (isset($entry['success']) && $entry['success']) {
                    $last_success = isset($entry['timestamp']) ? $entry['timestamp'] : null;
                    break;
                }
            }
            if ($last_success) {
                $status['webhook_healthy'] = (time() - $last_success) < DAY_IN_SECONDS;
            }
        }
        
        // Count platforms
        $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
        $status['platforms_count'] = is_array($platforms) ? count($platforms) : 0;
        
        // Count resources
        $resources_count = wp_count_posts('fasp_resource');
        $status['resources_count'] = isset($resources_count->publish) ? $resources_count->publish : 0;
        
        // Count coaches
        $coaches_count = wp_count_posts('fasp_coach_event');
        $status['coaches_count'] = isset($coaches_count->publish) ? $coaches_count->publish : 0;
        
        return $status;
    }
}

/**
 * Get setup checklist items
 *
 * @return array Checklist items with completion status
 */
if (!function_exists('fasp_get_setup_checklist')) {
    function fasp_get_setup_checklist() {
        $status = fasp_get_hub_status();
        
        return array(
            array(
                'id' => 'platforms',
                'label' => __('Add at least one trading platform', 'forex-affiliate-suite-pro'),
                'done' => $status['platforms_count'] > 0,
                'link' => admin_url('admin.php?page=fasp_platforms'),
            ),
            array(
                'id' => 'stripe',
                'label' => __('Configure Stripe payment keys', 'forex-affiliate-suite-pro'),
                'done' => $status['stripe_configured'],
                'link' => admin_url('admin.php?page=fasp_payments'),
            ),
            array(
                'id' => 'resources',
                'label' => __('Create trading resources', 'forex-affiliate-suite-pro'),
                'done' => $status['resources_count'] > 0,
                'link' => admin_url('edit.php?post_type=fasp_resource'),
            ),
            array(
                'id' => 'coaches',
                'label' => __('Add trading coaches', 'forex-affiliate-suite-pro'),
                'done' => $status['coaches_count'] > 0,
                'link' => admin_url('edit.php?post_type=fasp_coach_event'),
            ),
            array(
                'id' => 'gating',
                'label' => __('Configure access gating', 'forex-affiliate-suite-pro'),
                'done' => $status['gating_enabled'],
                'link' => admin_url('admin.php?page=fasp_gating_setup'),
            ),
        );
    }
}

/**
 * Export all settings for backup
 *
 * @return array All plugin settings
 */
if (!function_exists('fasp_export_settings')) {
    function fasp_export_settings() {
        $options_to_export = array(
            'fasp_platforms',
            'fasp_gating_require_login',
            'fasp_gating_roles',
            'fasp_gating_blocked_message',
            'fasp_gating_blocked_redirect',
            'fasp_geo_enabled',
            'fasp_geo_allow',
            'fasp_geo_block',
            'fasp_geo_regions',
            'fasp_geo_unknown_blocked',
            'fasp_utm_presets',
            'fasp_affiliate_id',
        );
        
        $export = array(
            'plugin' => 'forex-affiliate-suite-pro',
            'version' => defined('FASP_VERSION') ? FASP_VERSION : '1.0.0',
            'exported_at' => gmdate('c'),
            'settings' => array(),
        );
        
        foreach ($options_to_export as $option) {
            $export['settings'][$option] = get_option($option, null);
        }
        
        return $export;
    }
}

/**
 * Render enhanced hub home page
 */
if (!function_exists('fasp_render_enhanced_hub')) {
    function fasp_render_enhanced_hub() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        $status = fasp_get_hub_status();
        $checklist = fasp_get_setup_checklist();
        $completed = count(array_filter($checklist, function ($item) {
            return $item['done'];
        }));
        $total = count($checklist);
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        // Handle settings export
        if (isset($_GET['fasp_export']) && $_GET['fasp_export'] === '1' && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'fasp_export_settings')) {
            $export = fasp_export_settings();
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="fasp-settings-' . gmdate('Y-m-d') . '.json"');
            echo wp_json_encode($export, JSON_PRETTY_PRINT);
            exit;
        }
        
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('Forex Trading Hub', 'forex-affiliate-suite-pro'); ?></h1>
            
            <!-- Quick Links -->
            <div class="fasp-quick-links" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_platforms')); ?>" class="button"><?php esc_html_e('Platforms', 'forex-affiliate-suite-pro'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_payments')); ?>" class="button"><?php esc_html_e('Payments', 'forex-affiliate-suite-pro'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_gating_setup')); ?>" class="button"><?php esc_html_e('Gating', 'forex-affiliate-suite-pro'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_geo_gating')); ?>" class="button"><?php esc_html_e('Geo Gating', 'forex-affiliate-suite-pro'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_utm_builder')); ?>" class="button"><?php esc_html_e('UTM Builder', 'forex-affiliate-suite-pro'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_diagnostics')); ?>" class="button"><?php esc_html_e('Diagnostics', 'forex-affiliate-suite-pro'); ?></a>
            </div>
            
            <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
                <!-- Status Cards -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('System Status', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <div class="fasp-status-grid" style="display:grid;gap:12px;">
                        <!-- Gating Status -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('Access Gating', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['gating_enabled']) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('ON', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#9ca3af;">— <?php esc_html_e('OFF', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Geo Status -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('Geo Gating', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['geo_enabled']) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('ON', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#9ca3af;">— <?php esc_html_e('OFF', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Geo Rules -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('Geo Rules Set', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['geo_rules_set']) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('Yes', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#9ca3af;">— <?php esc_html_e('No', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Stripe Status -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('Stripe Keys', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['stripe_configured']) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('Present', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#dc2626;font-weight:500;">✗ <?php esc_html_e('Missing', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- M-Pesa Status -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('M-Pesa Keys', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['mpesa_configured']) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('Present', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#9ca3af;">— <?php esc_html_e('Not set', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Webhook Health -->
                        <div class="status-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;">
                            <span><?php esc_html_e('Webhook Health', 'forex-affiliate-suite-pro'); ?></span>
                            <?php if ($status['webhook_healthy'] === true) : ?>
                                <span style="color:#166534;font-weight:500;">✓ <?php esc_html_e('Healthy', 'forex-affiliate-suite-pro'); ?></span>
                            <?php elseif ($status['webhook_healthy'] === false) : ?>
                                <span style="color:#dc2626;font-weight:500;">✗ <?php esc_html_e('Stale', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#9ca3af;">— <?php esc_html_e('No data', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Content Stats -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Content Stats', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <div class="fasp-stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;text-align:center;">
                        <div class="stat-item" style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#3b82f6;"><?php echo esc_html($status['platforms_count']); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Platforms', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                        <div class="stat-item" style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#10b981;"><?php echo esc_html($status['resources_count']); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Resources', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                        <div class="stat-item" style="padding:15px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:28px;font-weight:bold;color:#8b5cf6;"><?php echo esc_html($status['coaches_count']); ?></div>
                            <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Coaches', 'forex-affiliate-suite-pro'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Setup Checklist -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Setup Checklist', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <div style="background:#e5e7eb;border-radius:10px;height:8px;overflow:hidden;margin-bottom:10px;">
                        <div style="background:linear-gradient(90deg,#10b981,#3b82f6);height:100%;width:<?php echo esc_attr($progress); ?>%;transition:width 0.3s;"></div>
                    </div>
                    <p style="color:#6b7280;font-size:13px;margin-bottom:15px;">
                        <?php
                        /* translators: 1: completed steps, 2: total steps */
                        printf(esc_html__('%1$d of %2$d steps completed', 'forex-affiliate-suite-pro'), $completed, $total);
                        ?>
                    </p>
                    
                    <ul style="list-style:none;padding:0;margin:0;">
                        <?php foreach ($checklist as $item) : ?>
                            <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                <?php if ($item['done']) : ?>
                                    <span style="color:#166534;font-size:16px;">✓</span>
                                <?php else : ?>
                                    <span style="color:#9ca3af;font-size:16px;">○</span>
                                <?php endif; ?>
                                <span style="flex:1;<?php echo $item['done'] ? 'color:#6b7280;' : ''; ?>"><?php echo esc_html($item['label']); ?></span>
                                <?php if (!$item['done']) : ?>
                                    <a href="<?php echo esc_url($item['link']); ?>" class="button button-small"><?php esc_html_e('Set up', 'forex-affiliate-suite-pro'); ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Quick Actions -->
                <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Quick Actions', 'forex-affiliate-suite-pro'); ?></h2>
                    
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=fasp_resource')); ?>" class="button button-primary" style="text-align:center;">
                            <?php esc_html_e('+ Add Resource', 'forex-affiliate-suite-pro'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=fasp_coach_event')); ?>" class="button" style="text-align:center;">
                            <?php esc_html_e('+ Add Coach', 'forex-affiliate-suite-pro'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_utm_builder')); ?>" class="button" style="text-align:center;">
                            <?php esc_html_e('Create UTM Link', 'forex-affiliate-suite-pro'); ?>
                        </a>
                        
                        <hr style="margin:10px 0;border:0;border-top:1px solid #e5e7eb;">
                        
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=fasp_hub&fasp_export=1'), 'fasp_export_settings')); ?>" class="button" style="text-align:center;">
                            <?php esc_html_e('📥 Export Settings', 'forex-affiliate-suite-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Replace default hub home with enhanced version
 */
add_action('admin_init', 'fasp_replace_hub_home');
if (!function_exists('fasp_replace_hub_home')) {
    function fasp_replace_hub_home() {
        // Remove old hub home callback and add new one
        remove_action('admin_page_fasp_hub', 'fasp_hub_home');
    }
}

/**
 * Add enhanced hub page
 */
add_action('admin_menu', 'fasp_add_enhanced_hub_menu', 100);
if (!function_exists('fasp_add_enhanced_hub_menu')) {
    function fasp_add_enhanced_hub_menu() {
        // Update the hub home page callback
        global $submenu;
        
        if (isset($submenu['fasp_hub'])) {
            foreach ($submenu['fasp_hub'] as $key => $item) {
                if (isset($item[2]) && $item[2] === 'fasp_hub') {
                    // This updates the callback but may not work in all cases
                    break;
                }
            }
        }
    }
}
