<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('fasp_render_user_dash')){
  function fasp_render_user_dash(){
    if (!current_user_can('manage_options')) return;
    global $wpdb; $t = $wpdb->prefix.'fasp_clicks';
    $d30= date('Y-m-d', strtotime('-30 days')).' 00:00:00';
    $now= date('Y-m-d').' 23:59:59';
    $q = "SELECT action, COUNT(*) c FROM `$t` WHERE created_at BETWEEN %s AND %s GROUP BY action";
    $rows30 = $wpdb->get_results( fasp_prepare($q,$d30,$now), ARRAY_A );
    $map = array('click'=>0,'lead'=>0,'paid'=>0);
    foreach($rows30 as $r){ $map[$r['action']] = intval($r['c']); }
    $clicks=$map['click']; $leads=$map['lead']; $paid=$map['paid'];
    $cr1 = $clicks? round($leads/$clicks*100,2) : 0;
    $cr2 = $leads?  round($paid/$leads*100,2)  : 0;
    echo '<div class="wrap fasp-admin"><h1>User Dashboard</h1><div class="fasp-grid">';
    foreach([['Clicks (30d)',$clicks],['Leads (30d)',$leads],['Paid (30d)',$paid],['Click→Lead CR',$cr1."%"],['Lead→Paid CR',$cr2."%"]] as $c){
      echo '<div class="fasp-card"><h2>'.esc_html($c[0]).'</h2><p style="font-size:20px"><strong>'.esc_html($c[1]).'</strong></p></div>';
    }
    echo '</div><div class="fasp-card" style="margin-top:12px"><h2>Next Steps</h2><ol class="fasp-muted" style="line-height:1.8">';
    echo '<li>Create a tracking link in <a href="'.esc_url(admin_url('admin.php?page=fasp_tools_utm')).'">UTM Builder</a>.</li>';
    echo '<li>Connect a payment method in <a href="'.esc_url(admin_url('admin.php?page=fasp_payments')).'">Payments</a>.</li>';
    echo '<li>Configure <a href="'.esc_url(admin_url('admin.php?page=fasp_platform_gating')).'">Gating</a> and <a href="'.esc_url(admin_url('admin.php?page=fasp_geo_gating')).'">Geo Gating</a>.</li>';
    echo '<li>Send a <a href="'.esc_url(admin_url('admin.php?page=fasp_tools_diag')).'">test webhook</a>.</li>';
    echo '</ol></div></div>';
  }
}
