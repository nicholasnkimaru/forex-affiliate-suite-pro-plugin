<?php
if (!defined('ABSPATH')) exit;
if (!defined('FASP_CLICK_TABLE')){
  global $wpdb; define('FASP_CLICK_TABLE',$wpdb->prefix.'fasp_clicks');
}
if (!function_exists('fasp_activate')){
  function fasp_activate(){
    global $wpdb; $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS ".FASP_CLICK_TABLE."(
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NULL,
      platform VARCHAR(32) NOT NULL,
      action VARCHAR(32) NOT NULL,
      url TEXT NULL,
      ip VARCHAR(64) NULL,
      user_agent TEXT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY(id),
      KEY platform(platform),
      KEY action(action),
      KEY user_id(user_id),
      KEY created_at(created_at)
    ) $charset;";
    require_once ABSPATH.'wp-admin/includes/upgrade.php'; dbDelta($sql);
  }
}
if (!function_exists('fasp_log_click')){
  function fasp_log_click($platform,$action,$url=''){
    global $wpdb; $wpdb->insert(FASP_CLICK_TABLE,array(
      'user_id'=>get_current_user_id(),
      'platform'=>sanitize_text_field($platform),
      'action'=>sanitize_text_field($action),
      'url'=>$url,
      'ip'=>$_SERVER['REMOTE_ADDR'] ?? '',
      'user_agent'=>$_SERVER['HTTP_USER_AGENT'] ?? '',
      'created_at'=>current_time('mysql')
    ));
  }
}
