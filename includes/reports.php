<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('fasp_render_reports')){
  function fasp_render_reports(){
    if (!current_user_can('manage_options')) return;
    global $wpdb; $t = $wpdb->prefix.'fasp_clicks';
    $rows = $wpdb->get_results("SELECT platform, action, COUNT(*) total FROM `$t` GROUP BY platform, action ORDER BY total DESC LIMIT 100", ARRAY_A);
    echo '<div class="wrap fasp-admin"><h1>Reports</h1><div class="fasp-card"><table class="widefat"><thead><tr><th>Platform</th><th>Action</th><th>Total</th></tr></thead><tbody>';
    if ($rows){ foreach($rows as $r){ echo '<tr><td>'.esc_html($r['platform']).'</td><td>'.esc_html($r['action']).'</td><td>'.intval($r['total']).'</td></tr>'; } }
    else { echo '<tr><td colspan="3">No data yet.</td></tr>'; }
    echo '</tbody></table></div></div>';
  }
}
