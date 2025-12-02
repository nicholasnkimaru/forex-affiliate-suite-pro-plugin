<?php
if (!defined('ABSPATH')) exit;
add_action('fasp_cron_winner','fasp_calc_variant_winners');
if (!wp_next_scheduled('fasp_cron_winner')){ wp_schedule_event(time()+180, 'twicedaily', 'fasp_cron_winner'); }

function fasp_calc_variant_winners(){
  $vars = get_option('fasp_variants', []); if (!$vars) return;
  global $wpdb; $clicks = $wpdb->prefix.'fasp_clicks';
  $since = gmdate('Y-m-d H:i:s', time()-7*86400);
  foreach ($vars as $k=>$ex){
    if (($ex['auto_winner'] ?? '0')!=='1') continue;
    $p = $ex['param'] ?? 'v'; $vals = $ex['values'] ?? [];
    if (!$vals) continue;
    // clicks by variant
    $clk = array_fill_keys($vals, 0);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$clicks} WHERE created_at >= %s", $since), ARRAY_A);
    foreach($rows as $r){ $parts = wp_parse_url($r['url']); $q=[]; if (!empty($parts['query'])) parse_str($parts['query'],$q); if (!empty($q[$p])){ $v=sanitize_key($q[$p]); if(isset($clk[$v])) $clk[$v]++; } }
    // verified by variant (from user_meta)
    $ver = array_fill_keys($vals, 0);
    $users = get_users([ 'fields'=>['ID'], 'meta_key'=>'_fasp_verified_at', 'meta_value'=>$since, 'meta_compare'=>'>=' ]);
    foreach($users as $u){ $vv = get_user_meta($u->ID, '_fasp_variant_'.$p, true); if ($vv && isset($ver[$vv])) $ver[$vv]++; }
    // compute best conv
    $best = null; $best_c = -1;
    foreach($vals as $v){
      $c = $clk[$v]; $vv = $ver[$v];
      $conv = $c>0 ? ($vv*100.0/$c) : 0;
      if ($conv > $best_c){ $best_c=$conv; $best=$v; }
    }
    if ($best!==null){
      // set winner weight 100, others 0
      $w = []; foreach($vals as $v){ $w[$v] = ($v===$best)? 100 : 0; }
      $vars[$k]['weights'] = $w;
    }
  }
  update_option('fasp_variants', $vars);
}
