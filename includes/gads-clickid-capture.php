<?php
if (!defined('ABSPATH')) exit;
// Google Ads click IDs capture (90 days)
add_action('init', function(){
  foreach (['gclid','gbraid','wbraid'] as $k){
    if (!empty($_GET[$k])){
      $val = sanitize_text_field($_GET[$k]);
      setcookie('fasp_'+$k, $val, time()+90*24*3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
      if (is_user_logged_in()){ update_user_meta(get_current_user_id(), '_fasp_'+$k, $val); }
    }
  }
});
