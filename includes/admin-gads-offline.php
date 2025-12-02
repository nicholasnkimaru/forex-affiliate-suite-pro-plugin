<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Google Ads Offline','Google Ads Offline','manage_options','fasp_gads_offline','fasp_gads_offline_page');
});
function fasp_gads_offline_page(){
  if (!current_user_can('manage_options')) return;
  $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : date('Y-m-d', time()-7*86400);
  $to   = isset($_GET['to'])   ? sanitize_text_field($_GET['to'])   : date('Y-m-d');
  if (!empty($_GET['export'])){ fasp_gads_export_csv($from,$to); return; } ?>
  <div class="wrap fasp-admin">
    <h1>Google Ads Offline Conversions</h1>
    <div class="fasp-wrap fasp-card">
      <form method="get">
        <input type="hidden" name="page" value="fasp_gads_offline">
        <p>From: <input type="date" name="from" value="<?php echo esc_attr($from); ?>"> &nbsp; To: <input type="date" name="to" value="<?php echo esc_attr($to); ?>"></p>
        <p><button class="button">Preview</button> <a class="button button-primary" href="<?php echo esc_url(add_query_arg(['export'=>1,'from'=>$from,'to'=>$to])); ?>">Export CSV</a></p>
      </form>
      <p class="fasp-muted">Columns: gclid, conversion_name, conversion_time (UTC), value, currency.</p>
    </div>
  </div>
<?php }
function fasp_gads_export_csv($from,$to){
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="gads-offline-'.str_replace('-','',$from).'-'.str_replace('-','',$to).'.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, ['gclid','conversion_name','conversion_time','value','currency']);
  $from .= ' 00:00:00'; $to .= ' 23:59:59';
  $users = get_users([ 'fields'=>['ID'], 'meta_key'=>'_fasp_verified_at', 'meta_value'=>$from, 'meta_compare'=>'>=' ]);
  foreach ($users as $u){
    $t = get_user_meta($u->ID, '_fasp_verified_at', true);
    if ($t < $from || $t > $to) continue;
    $gclid = get_user_meta($u->ID, '_fasp_gclid', true);
    if (!$gclid && !empty($_COOKIE['fasp_gclid'])) $gclid = sanitize_text_field($_COOKIE['fasp_gclid']);
    if (!$gclid) continue;
    fputcsv($out, [$gclid, 'CompleteRegistration', gmdate('Y-m-d H:i:s', strtotime($t)).' UTC', '0.00', 'USD']);
  }
  fclose($out); exit;
}