<?php
if (!defined('ABSPATH')) exit;
add_action('fasp_cron_daily_alert','fasp_check_conversion_alert');
if (!wp_next_scheduled('fasp_cron_daily_alert')){
  wp_schedule_event(time()+120, 'hourly', 'fasp_cron_daily_alert');
}
function fasp_check_conversion_alert(){
  $o = get_option('fasp_marketing', []);
  if (empty($o['slack_webhook'])) return;
  $hours = intval($o['alert_window_hours'] ?? 24);
  $thr = floatval($o['alert_threshold'] ?? 5);
  global $wpdb;
  $clicks = $wpdb->prefix.'fasp_clicks';
  $since = gmdate('Y-m-d H:i:s', time()-$hours*3600);
  $c = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$clicks} WHERE created_at >= %s", $since)));
  $v = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE '_fasp_verified_%' AND meta_value='1' AND FROM_UNIXTIME(UNIX_TIMESTAMP(meta_value)) IS NULL")); // fallback
  // Prefer timestamp if stored
  $v2 = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_fasp_verified_at' AND meta_value >= %s", $since)));
  if ($v2>0) $v=$v2;
  $conv = $c>0 ? ($v*100.0/$c) : 0;
  if ($c>20 && $conv < $thr){
    $msg = ['text'=>sprintf("⚠️ Conversion dip: %.1f%% over last %dh (Clicks=%d, Verified=%d)", $conv, $hours, $c, $v)];
    wp_remote_post($o['slack_webhook'], ['timeout'=>5,'headers'=>['Content-Type'=>'application/json'],'body'=>wp_json_encode($msg)]);
  }
}
