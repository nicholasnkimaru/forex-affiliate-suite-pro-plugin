<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/helpers-core.php';
if (!function_exists('fasp_render_creatives_lab')){
  function fasp_render_creatives_lab(){
    if (!current_user_can('manage_options')) return;
    global $wpdb; $table = $wpdb->prefix.'fasp_clicks';
    $since = date('Y-m-d', strtotime('-30 days')).' 00:00:00';
    echo '<div class="wrap fasp-admin"><h1>Creatives Lab</h1>';
    if (fasp_db_column_exists($table,'angle')){
      $rows = $wpdb->get_results( fasp_prepare("SELECT angle, COUNT(*) clicks FROM `$table` WHERE created_at >= %s GROUP BY angle ORDER BY clicks DESC LIMIT 50", $since), ARRAY_A );
      if ($rows){
        echo '<div class="fasp-card"><h2>Top Angles</h2><table class="widefat"><thead><tr><th>Angle</th><th>Clicks</th></tr></thead><tbody>';
        foreach($rows as $r){ echo '<tr><td>'.esc_html($r['angle']).'</td><td>'.intval($r['clicks']).'</td></tr>'; }
        echo '</tbody></table></div>';
      } else { echo '<div class="fasp-card"><p>No angle data yet.</p></div>'; }
    } else {
      echo '<div class="fasp-card"><p><strong>Note:</strong> clicks table has no <code>angle</code> column; Angles report hidden.</p></div>';
    }
    echo '</div>';
  }
}
