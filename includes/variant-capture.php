<?php
if (!defined('ABSPATH')) exit;
// Capture experiment variant params into cookies for 30 days and user_meta if logged in
add_action('init', function(){
  $vars = get_option('fasp_variants', []);
  if (!is_array($vars) || empty($_GET)) return;
  foreach ($vars as $k=>$ex){
    $p = $ex['param'] ?? 'v';
    if (!empty($_GET[$p])){
      $val = sanitize_key($_GET[$p]);
      if (!in_array($val, $ex['values'])) continue;
      $cookie = 'fasp_var_' . $p;
      $exp = time() + 30*24*60*60;
      setcookie($cookie, $val, $exp, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
      if (is_user_logged_in()){
        update_user_meta(get_current_user_id(), '_fasp_variant_'.$p, $val);
      }
    }
  }
});
