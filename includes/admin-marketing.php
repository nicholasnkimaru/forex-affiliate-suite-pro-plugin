<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Marketing & Analytics','Marketing & Analytics','manage_options','fasp_marketing','fasp_marketing_page');
});
function fasp_marketing_settings(){
  $opt = get_option('fasp_marketing', []);
  return wp_parse_args($opt, [
    'alert_threshold'=> '5',
    'alert_window_hours'=>'24',
    'google_ads_enable'=>'0',
    'tiktok_enable'=>'0','tiktok_pixel_id'=>'','tiktok_access_token'=>'',
    'google_ads_conv_id'=>'',
    'tiktok_pixel_id'=>'' ,
    'enable_server_events'=>'1',
    'facebook_pixel_id'=>'',
    'facebook_access_token'=>'',
    'facebook_test_code'=>'',
    'ga4_measurement_id'=>'',
    'ga4_api_secret'=>'',
    'require_consent'=>'0',
    'slack_webhook'=>''
  ]);
}
function fasp_marketing_page(){
  if (!current_user_can('manage_options')) return;
  $o = fasp_marketing_settings();
  if (isset($_POST['fasp_mkt_save']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    $o['enable_server_events'] = empty($_POST['enable_server_events'])?'0':'1';
    $o['facebook_pixel_id'] = sanitize_text_field($_POST['facebook_pixel_id']??'');
    $o['facebook_access_token'] = sanitize_text_field($_POST['facebook_access_token']??'');
    $o['facebook_test_code'] = sanitize_text_field($_POST['facebook_test_code']??'');
    $o['ga4_measurement_id'] = sanitize_text_field($_POST['ga4_measurement_id']??'');
    $o['ga4_api_secret'] = sanitize_text_field($_POST['ga4_api_secret']??'');
    $o['require_consent'] = empty($_POST['require_consent'])?'0':'1';
    $o['slack_webhook'] = esc_url_raw($_POST['slack_webhook']??'');
    $o['alert_threshold'] = sanitize_text_field($_POST['alert_threshold']??'5');
    $o['alert_window_hours'] = sanitize_text_field($_POST['alert_window_hours']??'24');
    $o['google_ads_enable'] = empty($_POST['google_ads_enable'])?'0':'1';
    $o['tiktok_enable'] = empty($_POST['tiktok_enable'])?'0':'1';
    $o['tiktok_pixel_id'] = sanitize_text_field($_POST['tiktok_pixel_id']??'');
    $o['tiktok_access_token'] = sanitize_text_field($_POST['tiktok_access_token']??'');
    $o['google_ads_conv_id'] = sanitize_text_field($_POST['google_ads_conv_id']??'');
    $o['tiktok_pixel_id'] = sanitize_text_field($_POST['tiktok_pixel_id']??'');
    update_option('fasp_marketing', $o);
    echo '<div class="updated"><p>Saved.</p></div>';
  }
  // Test events
  if (isset($_POST['fasp_mkt_test']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    $eid = 'fasp-test-' . wp_generate_password(8,false,false);
    do_action('fasp/event/lead', ['event_id'=>$eid, 'custom_data'=>['value'=>0]]);
    do_action('fasp/event/complete_registration', ['event_id'=>$eid, 'custom_data'=>[]]);
    echo '<div class="updated"><p>Test events queued (Lead & CompleteRegistration). Event ID: '.esc_html($eid).'</p></div>';
  }
  // Dead letters replay
  if (isset($_POST['fasp_mkt_replay']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    $dead = get_option('fasp_event_dead_letters', []);
    $sent=0; foreach($dead as $i=>$evt){ do_action('fasp/event/replay', $evt); $sent++; }
    echo '<div class="updated"><p>Replayed '.intval($sent).' failed events (they will drop out if successful).</p></div>';
  }
  $dead = get_option('fasp_event_dead_letters', []);
  $queue = get_option('fasp_event_queue', []);
  $last_ok = get_option('fasp_event_last_ok', '');
  ?>
  <div class="wrap fasp-admin">
    <h1>Marketing & Analytics</h1>
    <div class="fasp-wrap fasp-card">
      <form method="post"><?php wp_nonce_field('fasp_mkt','fasp_mkt_nonce'); ?>
        <h2>Server-side Events</h2>
        <p><label><input type="checkbox" name="enable_server_events" value="1" <?php checked($o['enable_server_events'],'1'); ?>> Enable server events (Meta CAPI + GA4 MP)</label></p>
        <p><label><input type="checkbox" name="require_consent" value="1" <?php checked($o['require_consent'],'1'); ?>> Require user consent before setting tracking cookies</label></p>
        <hr>
        <h2>Meta (Facebook)</h2>
        <p><label>Pixel ID<br><input class="regular-text" name="facebook_pixel_id" value="<?php echo esc_attr($o['facebook_pixel_id']); ?>"></label></p>
        <p><label>Access Token<br><input class="regular-text" name="facebook_access_token" value="<?php echo esc_attr($o['facebook_access_token']); ?>"></label></p>
        <p><label>Test Event Code (optional)<br><input class="regular-text" name="facebook_test_code" value="<?php echo esc_attr($o['facebook_test_code']); ?>"></label></p>
        <h2>Google Analytics 4</h2>
        <p><label>Measurement ID<br><input class="regular-text" name="ga4_measurement_id" value="<?php echo esc_attr($o['ga4_measurement_id']); ?>"></label></p>
        <p><label>API Secret<br><input class="regular-text" name="ga4_api_secret" value="<?php echo esc_attr($o['ga4_api_secret']); ?>"></label></p>
        <h2>Alerts</h2>
        <p><label>Slack Webhook URL<br><input class="large-text code" name="slack_webhook" value="<?php echo esc_attr($o['slack_webhook']); ?>"></label></p>
        <p><label>Alert if last <input style="width:60px" name="alert_window_hours" value="<?php echo esc_attr($o['alert_window_hours']); ?>"> hours conversion % &lt; <input style="width:60px" name="alert_threshold" value="<?php echo esc_attr($o['alert_threshold']); ?>">%</label></p>
        <h2>Google Ads & TikTok</h2>
        <p><label><input type="checkbox" name="google_ads_enable" value="1" <?php checked($o['google_ads_enable'],'1'); ?>> Enable Google Ads server conversions (placeholder)</label></p>
        <p><label>Google Ads Conversion ID<br><input class="regular-text" name="google_ads_conv_id" value="<?php echo esc_attr($o['google_ads_conv_id']); ?>"></label></p>
        <p><label><input type="checkbox" name="tiktok_enable" value="1" <?php checked($o['tiktok_enable'],'1'); ?>> Enable TikTok Events API (placeholder)</label></p>
        <p><label>TikTok Pixel ID<br><input class="regular-text" name="tiktok_pixel_id" value="<?php echo esc_attr($o['tiktok_pixel_id']); ?>"></label></p>
        <p><label>Incoming Webhook URL<br><input class="large-text code" name="slack_webhook" value="<?php echo esc_attr($o['slack_webhook']); ?>"></label></p>
        <p>
          <button class="button button-primary" name="fasp_mkt_save" value="1">Save settings</button>
          <button class="button" name="fasp_mkt_test" value="1">Send Test Events (Lead & CompleteRegistration)</button>
        </p>
      
    <h2>TikTok Events API</h2>
    <p><label><input type="checkbox" name="tiktok_enable" value="1" <?php checked(($o['tiktok_enable']??'0'),'1'); ?>> Enable TikTok Events</label></p>
    <p><label>Pixel Code<br><input class="regular-text" name="tiktok_pixel_id" value="<?php echo esc_attr($o['tiktok_pixel_id'] ?? ''); ?>"></label></p>
    <p><label>Access Token<br><input class="regular-text" name="tiktok_access_token" value="<?php echo esc_attr($o['tiktok_access_token'] ?? ''); ?>"></label></p>
    <p class="fasp-muted">Note: Google Ads is best fed by importing GA4 conversions. We already send GA4 events with click IDs (gclid/gbraid/wbraid) when present.</p>
    </form>
    </div>
    <div class="fasp-wrap fasp-card">
      <h2>Health & Queue</h2>
      <p class="fasp-muted">Queue depth: <?php echo is_array($queue)? count($queue):0; ?> | Dead letters: <?php echo is_array($dead)? count($dead):0; ?> | Last OK: <?php echo esc_html($last_ok); ?></p>
      <form method="post"><?php wp_nonce_field('fasp_mkt','fasp_mkt_nonce'); ?>
        <button class="button" name="fasp_mkt_replay" value="1" <?php disabled(empty($dead)); ?>>Replay failed events</button>
      </form>
      <?php if ($dead): ?>
        <table class="widefat fasp-table"><thead><tr><th>When</th><th>Provider</th><th>Event</th><th>Error</th></tr></thead><tbody>
        <?php foreach(array_slice(array_reverse($dead),0,50) as $e): ?>
           <tr><td><?php echo esc_html($e['ts'] ?? ''); ?></td><td><?php echo esc_html($e['provider'] ?? ''); ?></td><td><?php echo esc_html($e['name'] ?? ''); ?></td><td><?php echo esc_html($e['error'] ?? ''); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <?php
}
