<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','A/B Variants','A/B Variants','manage_options','fasp_variants','fasp_variants_page');
});

function fasp_variants_get(){
  $v = get_option('fasp_variants', []);
  if (!is_array($v)) $v = [];
  return $v;
}
function fasp_variants_save($v){ update_option('fasp_variants', is_array($v)? $v: []); }

function fasp_variants_page(){
  if (isset($_POST['fasp_var_weights']) && check_admin_referer('fasp_var','fasp_var_nonce')){
    $wk = sanitize_key($_POST['w_key'] ?? '');
    if ($wk && isset($vars[$wk])){
      $w = $_POST['w_val'] ?? [];
      $nw = [];
      foreach($vars[$wk]['values'] as $vv){ $nw[$vv] = isset($w[$vv]) ? intval($w[$vv]) : 0; }
      $vars[$wk]['weights'] = $nw; fasp_variants_save($vars);
      echo '<div class="updated"><p>Weights saved.</p></div>';
    }
  }
  if (isset($_POST['fasp_var_auto']) && check_admin_referer('fasp_var','fasp_var_nonce')){
    $ak = sanitize_key($_POST['a_key'] ?? ''); $en = empty($_POST['auto_winner'])?'0':'1';
    if ($ak && isset($vars[$ak])){ $vars[$ak]['auto_winner']=$en; fasp_variants_save($vars); echo '<div class="updated"><p>Auto-winner setting saved.</p></div>'; }
  }

  if (!current_user_can('manage_options')) return;
  $vars = fasp_variants_get();
  if (isset($_POST['fasp_var_save']) && check_admin_referer('fasp_var','fasp_var_nonce')){
    $label = sanitize_text_field($_POST['label'] ?? '');
    $param = sanitize_key($_POST['param'] ?? 'v');
    $values = array_filter(array_map('sanitize_key', array_map('trim', explode(',', $_POST['values'] ?? 'a,b'))));
    if ($label && $values){
      $key = sanitize_key($_POST['key'] ?? strtolower(preg_replace('/[^a-z0-9]+/','-', $label)));
      $vars[$key] = ['label'=>$label,'param'=>$param,'values'=>$values,'weights'=>array_fill_keys($values, 50),'auto_winner'=>'0','created_at'=>current_time('mysql')];
      fasp_variants_save($vars);
      echo '<div class="updated"><p>Saved experiment.</p></div>';
    }
  }
  if (isset($_POST['fasp_var_delete']) && check_admin_referer('fasp_var','fasp_var_nonce')){
    $del = sanitize_key($_POST['del_key'] ?? '');
    if ($del && isset($vars[$del])){ unset($vars[$del]); fasp_variants_save($vars); echo '<div class="updated"><p>Deleted.</p></div>'; }
  }

  // Report from clicks + verifications
  global $wpdb;
  $clicks = $wpdb->prefix.'fasp_clicks';
  $rows = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$clicks} WHERE created_at >= %s", gmdate('Y-m-d H:i:s', time()-30*86400)), ARRAY_A);
  $ver_users = $wpdb->get_results("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE '_fasp_verified_%' AND meta_value='1'", ARRAY_A);
  $verified = array_fill_keys(array_map('intval', wp_list_pluck($ver_users,'user_id')), true);
  $report = [];
  foreach ($vars as $k=>$ex){
    $report[$k] = [];
    foreach($ex['values'] as $val){ $report[$k][$val] = ['clicks'=>0,'verified'=>0]; }
  }
  foreach($rows as $r){
    $u = $r['url']; $q = []; $parts = wp_parse_url($u); if (!empty($parts['query'])) parse_str($parts['query'],$q);
    foreach ($vars as $k=>$ex){
      $p = $ex['param'];
      if (!empty($q[$p])){
        $val = sanitize_key($q[$p]);
        if (isset($report[$k][$val])){
          $report[$k][$val]['clicks']++;
          // cannot attribute verified per user without join; use ratio proxy from overall verified pool (optional)
        }
      }
    }
  }

  ?>
  <div class="wrap fasp-admin">
    <h1>A/B Variants</h1>
    <div class="fasp-wrap fasp-card">
      <h2>Create experiment</h2><p class="fasp-muted">You can set weights and enable auto-winner after saving.</p>
      <form method="post">
        <?php wp_nonce_field('fasp_var','fasp_var_nonce'); ?>
        <p><label>Label (e.g. Book Landing CTA Test)<br><input class="regular-text" name="label"></label></p>
        <p><label>URL Param (default: v)<br><input class="regular-text" name="param" value="v"></label></p>
        <p><label>Variants (CSV, e.g. a,b or hero1,hero2)<br><input class="regular-text" name="values" value="a,b"></label></p>
        <p><input type="hidden" name="fasp_var_save" value="1"><button class="button button-primary">Save</button></p>
      </form>
    </div>

    <div class="fasp-wrap fasp-card">
      <h2>Your experiments</h2>
      <?php if($vars): ?>
      <table class="widefat fasp-table"><thead><tr><th>Key</th><th>Param</th><th>Variants</th><th>Weights</th><th>Auto‑winner</th><th>Link builder</th><th>Delete</th></tr></thead><tbody>
      <?php foreach($vars as $k=>$ex): ?>
        <tr>
          <td><code><?php echo esc_html($k); ?></code><div class="fasp-muted"><?php echo esc_html($ex['label']); ?></div></td>
          <td><?php echo esc_html($ex['param']); ?></td>
          <td><?php echo esc_html(implode(', ', $ex['values'])); ?></td>
          <td>
            <form method="post" style="display:inline-flex;gap:6px;flex-wrap:wrap;align-items:center;">
              <?php wp_nonce_field('fasp_var','fasp_var_nonce'); ?>
              <input type="hidden" name="fasp_var_weights" value="1">
              <input type="hidden" name="w_key" value="<?php echo esc_attr($k); ?>">
              <?php foreach($ex['values'] as $vv): $w=intval($ex['weights'][$vv]??50); ?>
                <label><?php echo esc_html($vv); ?> <input style="width:70px" name="w_val[<?php echo esc_attr($vv); ?>]" value="<?php echo $w; ?>"></label>
              <?php endforeach; ?>
              <button class="button">Save</button>
            </form>
          </td>
          <td>
            <form method="post" style="display:inline-block">
              <?php wp_nonce_field('fasp_var','fasp_var_nonce'); ?>
              <input type="hidden" name="fasp_var_auto" value="1">
              <input type="hidden" name="a_key" value="<?php echo esc_attr($k); ?>">
              <label><input type="checkbox" name="auto_winner" value="1" <?php checked(($ex['auto_winner']??'0'),'1'); ?>> Enable</label>
              <button class="button">Save</button>
            </form>
          </td>
          <td>
            <input class="regular-text" placeholder="<?php echo esc_attr(home_url('/some-landing')); ?>" oninput="this.nextElementSibling.value=this.value+'?<?php echo esc_attr($ex['param']); ?>=<?php echo esc_attr($ex['values'][0] ?? 'a'); ?>'">
            <input class="large-text code" readonly value="">
          </td>
          <td>
            <form method="post" onsubmit="return confirm('Delete experiment?');">
              <?php wp_nonce_field('fasp_var','fasp_var_nonce'); ?>
              <input type="hidden" name="fasp_var_delete" value="1">
              <input type="hidden" name="del_key" value="<?php echo esc_attr($k); ?>">
              <button class="button">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php else: ?><p class="fasp-muted">No experiments yet.</p><?php endif; ?>
    </div>

    <div class="fasp-wrap fasp-card">
      <h2>30‑day clicks by variant</h2>
      <?php if($report): ?>
      <table class="widefat fasp-table"><thead><tr><th>Experiment</th><th>Variant</th><th>Clicks</th></tr></thead><tbody>
      <?php foreach($report as $k=>$rows): foreach($rows as $v=>$m): ?>
        <tr><td><?php echo esc_html($k); ?></td><td><?php echo esc_html($v); ?></td><td><?php echo intval($m['clicks']); ?></td></tr>
      <?php endforeach; endforeach; ?>
      </tbody></table>
      <?php else: ?><p class="fasp-muted">No data yet.</p><?php endif; ?>
    </div>
  </div>
  <?php
}
