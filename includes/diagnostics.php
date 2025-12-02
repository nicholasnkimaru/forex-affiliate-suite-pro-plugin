<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('fasp_render_tools_diag')){
  function fasp_render_tools_diag(){
    if (!current_user_can('manage_options')) return;
    $opt = get_option('fasp_payments', array());
    $webhook = esc_url_raw($opt['webhook_primary_url'] ?? '');
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fasp_diag_test'])){
      check_admin_referer('fasp_diag_test');
      if (!$webhook){
        echo '<div class="notice notice-error"><p>No primary webhook URL set in Payments → Webhooks.</p></div>';
      } else {
        $payload = array('event'=>'fasp.test','time'=>current_time('mysql'),'site'=>home_url(),'nonce'=>wp_create_nonce('fasp_webhook_sample'));
        $resp = wp_remote_post($webhook, array('timeout'=>20,'headers'=>array('Content-Type'=>'application/json'),'body'=>wp_json_encode($payload)));
        if (is_wp_error($resp)){
          echo '<div class="notice notice-error"><p>Webhook error: '.esc_html($resp->get_error_message()).'</p></div>';
        } else {
          echo '<div class="notice notice-success"><p>Webhook sent. HTTP '.intval(wp_remote_retrieve_response_code($resp)).'</p></div>';
          echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:8px;max-height:240px;overflow:auto">'.esc_html(wp_remote_retrieve_body($resp)).'</pre>';
        }
      }
    }
    echo '<div class="wrap fasp-admin"><h1>Diagnostics</h1><div class="fasp-card"><form method="post">'; wp_nonce_field('fasp_diag_test');
    echo '<p><label>Webhook URL<br><input type="url" class="regular-text" value="'.esc_attr($webhook).'" disabled></label></p>';
    echo '<p><button class="button button-primary" name="fasp_diag_test" value="1">Send Test Webhook</button></p>';
    echo '</form></div></div>';
  }
}
