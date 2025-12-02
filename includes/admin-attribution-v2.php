<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Attribution v2','Attribution v2','manage_options','fasp_attr_v2','fasp_attr_v2_page');
});
function fasp_attr_v2_page(){
  if (!current_user_can('manage_options')) return;
  if (isset($_GET['export_csv'])){
    fasp_attr_export_csv();
    return;
  }
  global $wpdb;
  $clicks = $wpdb->prefix . 'fasp_clicks';
  $rows = $wpdb->get_results($wpdb->prepare("SELECT user_id, url, created_at FROM {$clicks} WHERE created_at >= %s ORDER BY created_at DESC", gmdate('Y-m-d H:i:s', time()-30*86400)), ARRAY_A);
  $ver = $wpdb->get_results("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE '_fasp_verified_%' AND meta_value='1'", ARRAY_A);
  $verified = array_fill_keys(array_map('intval', wp_list_pluck($ver, 'user_id')), true);
  $daily = [];
  foreach($rows as $r){
    $d = substr($r['created_at'], 0, 10);
    $daily[$d] = $daily[$d] ?? ['clicks'=>0,'verified'=>0];
    $daily[$d]['clicks']++;
    if (!empty($verified[intval($r['user_id'])])) $daily[$d]['verified']++;
  }
  ?>
  <div class="wrap fasp-admin">
    <h1>Attribution v2</h1>
    <div class="fasp-wrap fasp-card">
      <p class="fasp-muted">Trend of clicks vs verifications (last 30 days). <a href="<?php echo esc_url(admin_url('admin.php?page=fasp_attr_v2&export_csv=1')); ?>" class="button">Export Verified (30d) CSV</a></p>
      <table class="widefat fasp-table">
        <thead><tr><th>Date</th><th>Clicks</th><th>Verified</th><th>Conv. %</th></tr></thead>
        <tbody>
        <?php foreach($daily as $d=>$m): $c=intval($m['clicks']); $v=intval($m['verified']); $conv=$c? round($v*100.0/$c,1):0; ?>
          <tr><td><?php echo esc_html($d); ?></td><td><?php echo $c; ?></td><td><?php echo $v; ?></td><td><?php echo $conv; ?>%</td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php
}
function fasp_attr_export_csv(){
  if (!current_user_can('manage_options')) return;
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="fasp-verified-30d.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, ['user_id','utm_source','utm_campaign','utm_medium','last_url']);
  global $wpdb;
  $clicks = $wpdb->prefix . 'fasp_clicks';
  $rows = $wpdb->get_results($wpdb->prepare("SELECT user_id, url, created_at FROM {$clicks} WHERE created_at >= %s ORDER BY created_at DESC", gmdate('Y-m-d H:i:s', time()-30*86400)), ARRAY_A);
  $ver = $wpdb->get_results("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE '_fasp_verified_%' AND meta_value='1'", ARRAY_A);
  $verified = array_fill_keys(array_map('intval', wp_list_pluck($ver, 'user_id')), true);
  $seen = [];
  foreach($rows as $r){
    $uid=intval($r['user_id']); if (empty($verified[$uid])) continue;
    if (isset($seen[$uid])) continue;
    $parts = wp_parse_url($r['url']); $q = []; if (!empty($parts['query'])) parse_str($parts['query'],$q);
    $src = sanitize_text_field($q['utm_source'] ?? '');
    $cmp = sanitize_text_field($q['utm_campaign'] ?? '');
    $med = sanitize_text_field($q['utm_medium'] ?? '');
    fputcsv($out, [$uid,$src,$cmp,$med,$r['url']]);
    $seen[$uid] = true;
  }
  fclose($out);
  exit;
}
