<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Cohorts','Cohorts','manage_options','fasp_cohorts','fasp_cohorts_page');
});
function fasp_cohorts_page(){
  if (!current_user_can('manage_options')) return;
  global $wpdb;
  $clicks = $wpdb->prefix.'fasp_clicks';
  $since14 = gmdate('Y-m-d', time()-13*86400);
  $since30 = gmdate('Y-m-d', time()-29*86400);
  $clk14 = $wpdb->get_results($wpdb->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM {$clicks} WHERE created_at >= %s GROUP BY DATE(created_at)", $since14), ARRAY_A);
  $clk30 = $wpdb->get_results($wpdb->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM {$clicks} WHERE created_at >= %s GROUP BY DATE(created_at)", $since30), ARRAY_A);
  $verified = $wpdb->get_results($wpdb->prepare("SELECT meta_value v FROM {$wpdb->usermeta} WHERE meta_key='_fasp_verified_at' AND meta_value >= %s", $since30.' 00:00:00'), ARRAY_A);
  $mapClk14 = []; foreach($clk14 as $r){ $mapClk14[$r['d']] = intval($r['c']); }
  $mapClk30 = []; foreach($clk30 as $r){ $mapClk30[$r['d']] = intval($r['c']); }
  $mapVer14 = []; $mapVer30 = [];
  foreach($verified as $v){ $d = substr($v['v'],0,10); if ($d >= $since14){ $mapVer14[$d] = ($mapVer14[$d]??0)+1; } if ($d >= $since30){ $mapVer30[$d] = ($mapVer30[$d]??0)+1; } }
  // Campaign breakdown (utm_campaign from clicks)
  $rowsCamp = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$clicks} WHERE created_at >= %s", $since30.' 00:00:00'), ARRAY_A);
  $camp = [];
  foreach($rowsCamp as $r){ $q=[]; $parts = wp_parse_url($r['url']); if (!empty($parts['query'])) parse_str($parts['query'],$q); if (!empty($q['utm_campaign'])){ $k = sanitize_text_field($q['utm_campaign']); $camp[$k] = ($camp[$k]??0)+1; } }
  ?>
  <div class="wrap fasp-admin">
    <h1>Cohorts</h1>
    <div class="fasp-wrap fasp-card">
      <h2>Last 14 days (daily)</h2>
      <table class="widefat fasp-table"><thead><tr><th>Date</th><th>Clicks</th><th>Verified</th><th>Conv %</th></tr></thead><tbody>
      <?php for($i=13;$i>=0;$i--): $d=date('Y-m-d', time()-$i*86400); $c=intval($mapClk14[$d]??0); $v=intval($mapVer14[$d]??0); $cv=$c>0?round($v*100/$c,1):0; ?>
        <tr><td><?php echo esc_html($d); ?></td><td><?php echo $c; ?></td><td><?php echo $v; ?></td><td><?php echo $cv; ?>%</td></tr>
      <?php endfor; ?>
      </tbody></table>
    </div>
    <div class="fasp-wrap fasp-card">
      <h2>Campaigns (last 30 days by clicks)</h2>
      <table class="widefat fasp-table"><thead><tr><th>Campaign</th><th>Clicks</th></tr></thead><tbody>
        <?php arsort($camp); foreach($camp as $k=>$v): ?>
          <tr><td><?php echo esc_html($k); ?></td><td><?php echo intval($v); ?></td></tr>
        <?php endforeach; if(!$camp): ?><tr><td colspan="2">No data.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <?php
}
