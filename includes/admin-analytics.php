<?php
/**
 * Admin page for viewing dashboard analytics and heatmaps
 */

if (!defined('ABSPATH')) exit;

// Add admin menu item
add_action('admin_menu', function() {
    add_submenu_page(
        'fasp_hub',
        __('Dashboard Analytics', 'fasp'),
        __('Analytics', 'fasp'),
        'manage_options',
        'fasp_analytics',
        'fasp_render_analytics_page'
    );
}, 30);

/**
 * Render analytics admin page
 */
function fasp_render_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'fasp'));
    }
    
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $analytics = function_exists('fasp_get_dashboard_analytics') ? fasp_get_dashboard_analytics($days) : array();
    $heatmap = function_exists('fasp_get_activity_heatmap') ? fasp_get_activity_heatmap() : array();
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Dashboard Analytics', 'fasp'); ?></h1>
        
        <div class="fasp-analytics-filters" style="margin: 20px 0;">
            <form method="get" action="">
                <input type="hidden" name="page" value="fasp_analytics">
                <label for="days"><?php esc_html_e('Time Period:', 'fasp'); ?></label>
                <select name="days" id="days" onchange="this.form.submit()">
                    <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
                    <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
                    <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
                </select>
            </form>
        </div>
        
        <div class="fasp-analytics-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="fasp-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3><?php esc_html_e('Unique Users', 'fasp'); ?></h3>
                <p style="font-size: 2em; font-weight: bold; margin: 0;">
                    <?php echo intval($analytics['unique_users'] ?? 0); ?>
                </p>
            </div>
            
            <div class="fasp-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3><?php esc_html_e('Total Activities', 'fasp'); ?></h3>
                <p style="font-size: 2em; font-weight: bold; margin: 0;">
                    <?php 
                    $total = 0;
                    if (!empty($analytics['popular_activities'])) {
                        foreach ($analytics['popular_activities'] as $activity) {
                            $total += intval($activity['count']);
                        }
                    }
                    echo $total;
                    ?>
                </p>
            </div>
        </div>
        
        <div class="fasp-popular-activities" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
            <h2><?php esc_html_e('Most Popular Activities', 'fasp'); ?></h2>
            <?php if (!empty($analytics['popular_activities'])): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Activity', 'fasp'); ?></th>
                            <th><?php esc_html_e('Count', 'fasp'); ?></th>
                            <th><?php esc_html_e('Percentage', 'fasp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['popular_activities'] as $activity): ?>
                            <?php 
                            $count = intval($activity['count']);
                            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($activity['activity']); ?></td>
                                <td><?php echo $count; ?></td>
                                <td>
                                    <div style="background: #ddd; height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div style="background: #0073aa; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <?php echo number_format($percentage, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php esc_html_e('No activity data available yet.', 'fasp'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="fasp-activity-heatmap" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
            <h2><?php esc_html_e('Activity Heatmap (Last 7 Days)', 'fasp'); ?></h2>
            <?php if (!empty($heatmap)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                    <?php 
                    $max_intensity = 1;
                    foreach ($heatmap as $item) {
                        if ($item['intensity'] > $max_intensity) {
                            $max_intensity = $item['intensity'];
                        }
                    }
                    
                    foreach ($heatmap as $item): 
                        $intensity = intval($item['intensity']);
                        $opacity = ($intensity / $max_intensity) * 0.9 + 0.1;
                        $color = "rgba(0, 115, 170, $opacity)";
                    ?>
                        <div style="background: <?php echo $color; ?>; padding: 15px; border-radius: 6px; text-align: center; color: <?php echo $opacity > 0.5 ? '#fff' : '#333'; ?>;">
                            <strong><?php echo esc_html($item['activity']); ?></strong><br>
                            <span style="font-size: 1.5em;"><?php echo $intensity; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php esc_html_e('No heatmap data available yet.', 'fasp'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($analytics['daily_activity'])): ?>
            <div class="fasp-daily-activity" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
                <h2><?php esc_html_e('Daily Activity', 'fasp'); ?></h2>
                <canvas id="dailyActivityChart" width="400" height="100"></canvas>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
            <script>
            (function() {
                var ctx = document.getElementById('dailyActivityChart');
                if (!ctx) return;
                
                var dates = <?php echo json_encode(array_column($analytics['daily_activity'], 'date')); ?>;
                var counts = <?php echo json_encode(array_map('intval', array_column($analytics['daily_activity'], 'count'))); ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Activities',
                            data: counts,
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            fill: true,
                            tension: 0.3
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
        <?php endif; ?>
    </div>
    <?php
}
