<?php
if (!defined('ABSPATH')) {
    exit;
}

// Register the forex-affiliate endpoint
add_action('init', function () {
    add_rewrite_endpoint('forex-affiliate', EP_ROOT | EP_PAGES);
});

// Add forex-affiliate as query var
add_filter('query_vars', function ($v) {
    $v[] = 'forex-affiliate';
    return $v;
});

// Add menu item to WooCommerce My Account navigation
add_filter('woocommerce_account_menu_items', function ($items) {
    $new = array();
    foreach ($items as $k => $v) {
        $new[$k] = $v;
        if ($k === 'dashboard') {
            $new['forex-affiliate'] = __('Forex Trading', 'forex-affiliate-suite-pro');
        }
    }
    if (!isset($new['forex-affiliate'])) {
        $new['forex-affiliate'] = __('Forex Trading', 'forex-affiliate-suite-pro');
    }
    return $new;
});

// Render the Forex Trading dashboard in WooCommerce My Account
add_action('woocommerce_account_forex-affiliate_endpoint', 'fasp_wc_dashboard');

/**
 * Render the WooCommerce My Account Forex Trading dashboard
 */
function fasp_wc_dashboard() {
    $uid = get_current_user_id();
    $plats = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
    $progress_count = function_exists('fasp_get_progress_count') ? fasp_get_progress_count($uid) : 0;
    $total_steps = 5;
    $progress_percent = $total_steps > 0 ? round(($progress_count / $total_steps) * 100) : 0;
    ?>
    <div class="fasp-wrap">
        <div class="fasp-grid">
            <div class="fasp-card">
                <h2><?php esc_html_e('Welcome to Forex Trading', 'forex-affiliate-suite-pro'); ?></h2>
                <p class="fasp-muted"><?php esc_html_e('Your affiliate status and quick actions.', 'forex-affiliate-suite-pro'); ?></p>
                
                <!-- Progress Bar -->
                <div class="fasp-progress-section" style="margin: 15px 0;">
                    <p style="margin-bottom: 5px; font-weight: 500;">
                        <?php
                        /* translators: 1: completed steps, 2: total steps */
                        printf(esc_html__('Progress: %1$d of %2$d steps completed', 'forex-affiliate-suite-pro'), $progress_count, $total_steps);
                        ?>
                    </p>
                    <div style="background: #e5e7eb; border-radius: 10px; height: 10px; overflow: hidden;">
                        <div style="background: #10b981; height: 100%; width: <?php echo esc_attr($progress_percent); ?>%; transition: width 0.3s;"></div>
                    </div>
                </div>
                
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
                    <span class="fasp-pill"><?php
                        /* translators: %d: user ID */
                        printf(esc_html__('User #%d', 'forex-affiliate-suite-pro'), absint($uid));
                    ?></span>
                </div>
                
                <!-- Platform Cards -->
                <div style="margin-top:15px;">
                    <?php foreach ($plats as $slug => $pl) :
                        if ((isset($pl['show_in_dashboard']) && $pl['show_in_dashboard'] !== '1') || (isset($pl['enabled']) && $pl['enabled'] !== '1')) {
                            continue;
                        }
                        $safe_slug = sanitize_key($slug);
                        $ok = get_user_meta($uid, '_fasp_verified_' . $safe_slug, true) === '1';
                    ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:10px;background:#f8fafc;border-radius:8px;margin-bottom:8px;">
                            <?php if (!empty($pl['logo_url'])) : ?>
                                <img src="<?php echo esc_url($pl['logo_url']); ?>" alt="<?php echo esc_attr($pl['name'] ?? $safe_slug); ?>" style="height:32px;width:auto;border-radius:4px;">
                            <?php endif; ?>
                            <div style="flex:1;">
                                <strong><?php echo esc_html($pl['name'] ?? $safe_slug); ?></strong>
                                <?php if ($ok) : ?>
                                    <span class="fasp-pill" style="background:#dcfce7;border-color:#86efac;margin-left:8px;">✓ <?php esc_html_e('Verified', 'forex-affiliate-suite-pro'); ?></span>
                                <?php else : ?>
                                    <span class="fasp-pill" style="margin-left:8px;"><?php esc_html_e('Not verified', 'forex-affiliate-suite-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            <a class="fasp-button" href="<?php echo esc_url(home_url('/fasp-go/' . rawurlencode($safe_slug) . '?dest=signup')); ?>"><?php
                                /* translators: %s: platform name */
                                printf(esc_html__('Join %s', 'forex-affiliate-suite-pro'), esc_html($pl['name'] ?? $safe_slug));
                            ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Deriv Connect CTA -->
                <?php
                $deriv_verified = get_user_meta($uid, '_fasp_deriv_verified', true) === '1';
                if (!$deriv_verified) :
                    $deriv_app_id = function_exists('fasp_get_option') ? fasp_get_option('deriv_app_id', '') : get_option('fasp_deriv_app_id', '');
                    if ($deriv_app_id) :
                        $callback = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
                        $deriv_url = 'https://oauth.deriv.com/oauth2/authorize?app_id=' . rawurlencode($deriv_app_id) . '&scope=read&redirect_uri=' . rawurlencode($callback);
                ?>
                    <div style="margin-top:15px;padding:15px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;">
                        <p style="margin:0 0 10px 0;font-weight:500;"><?php esc_html_e('Connect your Deriv account to unlock all features', 'forex-affiliate-suite-pro'); ?></p>
                        <a class="button button-primary" href="<?php echo esc_url($deriv_url); ?>"><?php esc_html_e('Connect Deriv', 'forex-affiliate-suite-pro'); ?></a>
                    </div>
                <?php
                    endif;
                endif;
                ?>
            </div>
            
            <div class="fasp-card">
                <h2><?php esc_html_e('Your Progress', 'forex-affiliate-suite-pro'); ?></h2>
                <canvas id="faspChart" width="520" height="260"></canvas>
                <p class="fasp-muted"><?php esc_html_e('Clicks over time (placeholder chart).', 'forex-affiliate-suite-pro'); ?></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function(){
        var c = document.getElementById('faspChart');
        if (!c) return;
        new Chart(c, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: '<?php echo esc_js(__('Clicks', 'forex-affiliate-suite-pro')); ?>',
                    data: [0, 5, 9, 3, 12, 7, 10],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    })();
    </script>
    <?php
}

/**
 * Render the progress strip for a user
 *
 * @param int $uid User ID
 */
function fasp_progress_strip($uid) {
    $steps = array(
        array('key' => 'verified', 'label' => __('Verify Deriv', 'forex-affiliate-suite-pro')),
        array('key' => 'downloaded', 'label' => __('Download eBook', 'forex-affiliate-suite-pro')),
        array('key' => 'booked', 'label' => __('Book 15‑min coach', 'forex-affiliate-suite-pro')),
        array('key' => 'deposit', 'label' => __('First deposit', 'forex-affiliate-suite-pro')),
        array('key' => 'trade', 'label' => __('First trade', 'forex-affiliate-suite-pro'))
    );
    
    $ok = array(
        'verified' => (get_user_meta($uid, '_fasp_verified_deriv', true) === '1'),
        'downloaded' => (get_user_meta($uid, '_fasp_downloaded', true) === '1'),
        'booked' => (get_user_meta($uid, '_fasp_booked', true) === '1'),
        'deposit' => (get_user_meta($uid, '_fasp_deposit', true) === '1'),
        'trade' => (get_user_meta($uid, '_fasp_trade', true) === '1'),
    );
    
    echo '<div class="fasp-card" style="margin-top:12px;"><h3>' . esc_html__('Getting started', 'forex-affiliate-suite-pro') . '</h3><div style="display:flex;gap:8px;flex-wrap:wrap;">';
    
    foreach ($steps as $s) {
        $done = !empty($ok[$s['key']]);
        echo '<span class="fasp-pill" style="' . ($done ? 'background:#dcfce7;border-color:#86efac;' : '') . '">' . esc_html($s['label']) . ($done ? ' ✓' : '') . '</span>';
    }
    
    echo '</div></div>';
}
