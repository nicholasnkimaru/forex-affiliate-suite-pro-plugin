<?php
if (!defined('ABSPATH')) { exit; }

/**
 * WooCommerce My Account - Forex Trading Dashboard Endpoint
 */
add_action('init', function() {
    add_rewrite_endpoint('forex-affiliate', EP_ROOT | EP_PAGES);
});

add_filter('query_vars', function($v) {
    $v[] = 'forex-affiliate';
    return $v;
});

add_filter('woocommerce_account_menu_items', function($items) {
    $menu_label = __('Forex Trading', 'fasp');
    $new = array();
    foreach ($items as $k => $v) {
        $new[$k] = $v;
        if ($k === 'dashboard') {
            $new['forex-affiliate'] = $menu_label;
        }
    }
    if (!isset($new['forex-affiliate'])) {
        $new['forex-affiliate'] = $menu_label;
    }
    return $new;
});

add_action('woocommerce_account_forex-affiliate_endpoint', 'fasp_wc_dashboard');

/**
 * Render the WooCommerce My Account Forex Trading Dashboard.
 */
function fasp_wc_dashboard() {
    $uid = get_current_user_id();
    $plats = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
    
    // Get dashboard data
    $dashboard_data = function_exists('fasp_get_user_dashboard_data') 
        ? fasp_get_user_dashboard_data($uid) 
        : array();
    
    // Deriv status
    $deriv_verified = get_user_meta($uid, '_fasp_deriv_verified', true) === '1';
    $deriv_app_id = function_exists('fasp_get_option') 
        ? fasp_get_option('deriv_app_id', '') 
        : get_option('fasp_deriv_app_id', '');
    $callback = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
    $deriv_url = $deriv_app_id 
        ? 'https://oauth.deriv.com/oauth2/authorize?app_id=' . rawurlencode($deriv_app_id) . '&scope=read&redirect_uri=' . rawurlencode($callback) 
        : '';
    
    // Progress
    $progress_count = function_exists('fasp_get_progress_count') 
        ? fasp_get_progress_count($uid) 
        : array('completed' => 0, 'total' => 5, 'percent' => 0);
    ?>
    <div class="fasp-wrap">
        <div class="fasp-grid">
            <div class="fasp-card">
                <h2>Welcome to Forex Trading</h2>
                <p class="fasp-muted">Your affiliate status and quick actions for forex trading.</p>
                
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
                    <span class="fasp-pill">User #<?php echo intval($uid); ?></span>
                    <?php if ($deriv_verified): ?>
                        <span class="fasp-pill" style="background:#dcfce7;border-color:#86efac;">Verified ✓</span>
                    <?php endif; ?>
                </div>
                
                <!-- Progress Strip -->
                <?php fasp_progress_strip($uid); ?>
                
                <!-- Platforms -->
                <div style="margin-top:16px;">
                    <h3>Trading Platforms</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach ($plats as $slug => $pl):
                            if (($pl['show_in_dashboard'] ?? '1') !== '1' || ($pl['enabled'] ?? '1') !== '1') continue;
                            $ok = get_user_meta($uid, '_fasp_verified_' . $slug, true) === '1';
                        ?>
                            <div style="display:flex;align-items:center;gap:8px;padding:8px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;">
                                <?php if (!empty($pl['logo_url'])): ?>
                                    <img src="<?php echo esc_url($pl['logo_url']); ?>" alt="<?php echo esc_attr($pl['name']); ?>" style="height:24px;width:auto;border-radius:4px;">
                                <?php endif; ?>
                                <span><?php echo esc_html($pl['name']); ?></span>
                                <span class="fasp-pill"><?php echo $ok ? '✓ Verified' : '— Not verified'; ?></span>
                                <a class="button" href="<?php echo esc_url(home_url('/fasp-go/' . $slug . '?dest=signup')); ?>">
                                    <?php echo $ok ? 'Open' : 'Join'; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Deriv Connect -->
                <?php if (!$deriv_verified && $deriv_url): ?>
                <div style="margin-top:16px;padding:16px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;">
                    <h4>Connect Your Deriv Account</h4>
                    <p class="fasp-muted">Link your Deriv account to unlock exclusive trading resources.</p>
                    <a class="fasp-button" href="<?php echo esc_url($deriv_url); ?>">Connect Deriv →</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="fasp-card">
                <h2>Your Trading Progress</h2>
                <div style="margin-bottom:16px;">
                    <div style="background:#e5e7eb;border-radius:999px;height:8px;overflow:hidden;">
                        <div style="background:linear-gradient(90deg,#22c55e,#16a34a);height:100%;width:<?php echo esc_attr($progress_count['percent']); ?>%;border-radius:999px;"></div>
                    </div>
                    <p class="fasp-muted" style="margin-top:8px;"><?php echo esc_html($progress_count['completed']); ?> of <?php echo esc_html($progress_count['total']); ?> steps completed</p>
                </div>
                <canvas id="faspChart" width="520" height="260"></canvas>
                <p class="fasp-muted">Trading activity over time.</p>
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
                    label: 'Activity',
                    data: [0, 5, 9, 3, 12, 7, 10],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    })();
    </script>
    <?php
}

/**
 * Render progress strip for user dashboard.
 *
 * @param int $uid User ID.
 */
function fasp_progress_strip($uid) {
    $steps = array(
        array('key' => 'verified', 'label' => 'Verify Account'),
        array('key' => 'downloaded', 'label' => 'Download Resource'),
        array('key' => 'booked', 'label' => 'Book Coach Session'),
        array('key' => 'deposit', 'label' => 'First Deposit'),
        array('key' => 'trade', 'label' => 'First Trade'),
    );
    
    $ok = array(
        'verified'   => (get_user_meta($uid, '_fasp_deriv_verified', true) === '1'),
        'downloaded' => (get_user_meta($uid, '_fasp_downloaded', true) === '1'),
        'booked'     => (get_user_meta($uid, '_fasp_booked', true) === '1'),
        'deposit'    => (get_user_meta($uid, '_fasp_deposit', true) === '1'),
        'trade'      => (get_user_meta($uid, '_fasp_trade', true) === '1'),
    );
    
    echo '<div class="fasp-card fasp-progress-strip"><h3>Getting Started with Forex Trading</h3><div class="fasp-progress-strip-items">';
    foreach ($steps as $s) {
        $done = !empty($ok[$s['key']]);
        $class = $done ? 'fasp-pill done' : 'fasp-pill';
        echo '<span class="' . esc_attr($class) . '">' . esc_html($s['label']) . ($done ? ' ✓' : '') . '</span>';
    }
    echo '</div></div>';
}
