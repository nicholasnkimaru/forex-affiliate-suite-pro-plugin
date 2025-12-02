<?php
if (!defined('ABSPATH')) exit;
add_action('init', function(){
  foreach (['fbp','fbc'] as $k){
    if (!empty($_GET[$k])){
      $val = sanitize_text_field($_GET[$k]);
      setcookie('fasp_'.$k, $val, time()+90*24*3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
      if (is_user_logged_in()){ update_user_meta(get_current_user_id(), '_fasp_'.$k, $val); }
    }
  }
  if (empty($_COOKIE['fasp_fbp']) && !empty($_COOKIE['_fbp'])){
    $val = sanitize_text_field($_COOKIE['_fbp']);
    setcookie('fasp_fbp', $val, time()+90*24*3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
  }
  if (empty($_COOKIE['fasp_fbc']) && !empty($_COOKIE['_fbc'])){
    $val = sanitize_text_field($_COOKIE['_fbc']);
    setcookie('fasp_fbc', $val, time()+90*24*3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
  }
});