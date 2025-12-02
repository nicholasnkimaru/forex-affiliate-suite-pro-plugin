<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Creatives Lab','Creatives Lab','manage_options','fasp_creatives_lab','fasp_creatives_lab_page');
});
function fasp_creatives_lab_page(){
  if (!current_user_can('manage_options')) return;
  $hook = sanitize_text_field($_POST['hook'] ?? '');
  $benefits = sanitize_textarea_field($_POST['benefits'] ?? '');
  $proofs = sanitize_textarea_field($_POST['proofs'] ?? '');
  $cta = sanitize_text_field($_POST['cta'] ?? 'Create your Deriv account');
  $variants = [];
  if (!empty($_POST['gen'])){
    $H = array_filter(array_map('trim', explode("\n", $hook)));
    $B = array_filter(array_map('trim', explode("\n", $benefits)));
    $P = array_filter(array_map('trim', explode("\n", $proofs)));
    foreach ($H as $h){ foreach ($B as $b){ foreach ($P as $p){
      $variants[] = ['primary'=>$h, 'body'=>$b.' — '.$p, 'cta'=>$cta];
    } } }
  }
  global $wpdb; $clicks = $wpdb->prefix.'fasp_clicks';
  $since = gmdate('Y-m-d H:i:s', time()-30*86400);
  $rows = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$clicks} WHERE created_at >= %s", $since), ARRAY_A);
  $camp = [];
  foreach($rows as $r){
    $q=[]; $p = wp_parse_url($r['url']);
    if (!empty($p['query'])) parse_str($p['query'],$q);
    $angle = sanitize_key($q['angle'] ?? ($q['utm_content'] ?? 'na'));
    $camp[$angle] = ($camp[$angle]??0)+1;
  }
  $ver = get_users([ 'fields'=>['ID'], 'meta_key'=>'_fasp_verified_at', 'meta_value'=>$since, 'meta_compare'=>'>=' ]);
  $ver_map = [];
  foreach($ver as $u){ $a = get_user_meta($u->ID, '_fasp_variant_angle', true); if(!$a) $a='na'; $ver_map[$a] = ($ver_map[$a]??0)+1; }
  ?>
  <div class="wrap fasp-admin">
    <h1>Creatives Lab</h1>
    <div class="fasp-wrap fasp-card">
      <h2>Generate copy variants</h2>
      <form method="post">
        <p><label><strong>Hooks</strong> (one per line)<br><textarea class="large-text code" rows="4" name="hook"><?php echo esc_textarea($hook); ?></textarea></label></p>
        <p><label><strong>Benefits</strong> (one per line)<br><textarea class="large-text code" rows="4" name="benefits"><?php echo esc_textarea($benefits); ?></textarea></label></p>
        <p><label><strong>Proofs</strong> (one per line)<br><textarea class="large-text code" rows="4" name="proofs"><?php echo esc_textarea($proofs); ?></textarea></label></p>
        <p><label><strong>CTA</strong><br><input class="regular-text" name="cta" value="<?php echo esc_attr($cta); ?>"></label></p>
        <p><button class="button button-primary" name="gen" value="1">Generate</button></p>
      </form>
      <?php if($variants): ?>
        <h3>Variants</h3>
        <table class="widefat fasp-table"><thead><tr><th>Primary Text</th><th>Body</th><th>CTA</th></tr></thead><tbody>
          <?php foreach($variants as $v): ?><tr>
            <td><?php echo esc_html($v['primary']); ?></td>
            <td><?php echo esc_html($v['body']); ?></td>
            <td><?php echo esc_html($v['cta']); ?></td>
          </tr><?php endforeach; ?>
        </tbody></table>
      <?php endif; ?>
    </div>
    <div class="fasp-wrap fasp-card">
      <h2>Angles scoreboard (last 30 days)</h2>
      <table class="widefat fasp-table"><thead><tr><th>Angle</th><th>Clicks</th><th>Verified</th><th>Conv %</th></tr></thead><tbody>
        <?php ksort($camp); foreach($camp as $a=>$c){ $v=intval($ver_map[$a]??0); $cv=$c>0? round($v*100/$c,1):0; echo '<tr><td>'.esc_html($a).'</td><td>'.intval($c).'</td><td>'.$v.'</td><td>'.$cv.'%</td></tr>'; } if(!$camp) echo '<tr><td colspan="4">No data.</td></tr>'; ?>
      </tbody></table>
    </div>
  </div>
  <?php
}