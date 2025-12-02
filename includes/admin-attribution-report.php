<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
    add_submenu_page('fasp_hub','Attribution (UTM)','Attribution (UTM)','manage_options','fasp_attribution','fasp_attribution_page');
});
function fasp_attribution_page(){
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $clicks = $wpdb->prefix . 'fasp_clicks';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, url, created_at FROM {$clicks} WHERE created_at >= %s ORDER BY created_at DESC LIMIT 5000",
        gmdate('Y-m-d H:i:s', time()-90*86400)
    ), ARRAY_A);
    $by_user = [];
    foreach($rows as $r){
        $uid = intval($r['user_id']);
        if (!isset($by_user[$uid])) $by_user[$uid] = [];
        $by_user[$uid][] = $r['url'];
    }
    $verified_users = [];
    $umeta_rows = $wpdb->get_results("SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE '_fasp_verified_%' AND meta_value='1'", ARRAY_A);
    foreach ($umeta_rows as $m){ $verified_users[intval($m['user_id'])] = true; }
    $agg = [];
    foreach($by_user as $uid=>$urls){
        $is_verified = !empty($verified_users[$uid]);
        $picked = null;
        foreach($urls as $u){ if (strpos($u,'utm_')!==false || strpos($u,'fbclid')!==false){ $picked=$u; break; } }
        if (!$picked) continue;
        $parts = wp_parse_url($picked); $q = [];
        if (!empty($parts['query'])){ parse_str($parts['query'], $q); }
        $src = sanitize_text_field($q['utm_source'] ?? 'unknown');
        $cmp = sanitize_text_field($q['utm_campaign'] ?? 'unknown');
        $med = sanitize_text_field($q['utm_medium'] ?? '');
        $key = strtolower($src.'|'.$cmp.'|'.$med);
        if (!isset($agg[$key])) $agg[$key] = ['source'=>$src,'campaign'=>$cmp,'medium'=>$med,'clicks'=>0,'verified'=>0];
        $agg[$key]['clicks'] += 1;
        if ($is_verified) $agg[$key]['verified'] += 1;
    }
    uasort($agg, function($a,$b){ return $b['verified'] <=> $a['verified']; });
    ?>
    <div class="wrap fasp-admin">
      <h1>Attribution (UTM)</h1>
      <div class="fasp-wrap fasp-card">
        <p class="fasp-muted">Clicks and Deriv verifications by UTM Source/Campaign (last 90 days).</p>
        <table class="widefat fasp-table">
          <thead><tr><th>Source</th><th>Campaign</th><th>Medium</th><th>Clicks</th><th>Verified</th><th>Conv. %</th></tr></thead>
          <tbody>
            <?php if($agg): foreach($agg as $row): $conv = $row['clicks'] ? round($row['verified']*100.0/$row['clicks'],1) : 0; ?>
              <tr>
                <td><?php echo esc_html($row['source']); ?></td>
                <td><?php echo esc_html($row['campaign']); ?></td>
                <td><?php echo esc_html($row['medium']); ?></td>
                <td><?php echo intval($row['clicks']); ?></td>
                <td><?php echo intval($row['verified']); ?></td>
                <td><?php echo $conv; ?>%</td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6">No data yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}
