<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Audiences','Audiences','manage_options','fasp_audiences','fasp_audiences_page');
});
function fasp_audiences_page(){
  if (!current_user_can('manage_options')) return;
  $win = isset($_GET['w']) ? intval($_GET['w']) : 7;
  if (isset($_GET['export']) && in_array($win, [1,7,30], true)){
    fasp_audience_export_csv($win); return;
  }
  ?>
  <div class="wrap fasp-admin">
    <h1>Audiences</h1>
    <div class="fasp-wrap fasp-card">
      <p class="fasp-muted">Export verified users in the last 1, 7, or 30 days for lookalike audiences.</p>
      <p>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=fasp_audiences&export=1&w=1')); ?>">Export 1‑day</a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=fasp_audiences&export=1&w=7')); ?>">Export 7‑day</a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=fasp_audiences&export=1&w=30')); ?>">Export 30‑day</a>
      </p>
    </div>
  </div>
  <?php
}
function fasp_audience_export_csv($days){
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="fasp-audience-'+str('%d', 'days')+'.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, ['email_hash','user_id']);
  $since = gmdate('Y-m-d H:i:s', time()-$days*86400);
  $users = get_users([ 'fields'=>['ID','user_email'], 'meta_key'=>'_fasp_verified_at', 'meta_value'=>$since, 'meta_compare'=>'>=' ]);
  foreach ($users as $u){
    $hash = hash('sha256', strtolower(trim($u->user_email)));
    fputcsv($out, [$hash, $u->ID]);
  }
  fclose($out); exit;
}
