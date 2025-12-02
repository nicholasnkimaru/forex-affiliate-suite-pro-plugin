<?php if (!defined('ABSPATH')) exit;
add_action('init', function(){
  foreach(['utm_source','utm_medium','utm_campaign','utm_term','utm_content','rid','ab'] as $p){
    if (isset($_GET[$p])){
      $val = sanitize_text_field(wp_unslash($_GET[$p]));
      setcookie('fasp_'.$p, $val, time()+60*60*24*30, COOKIEPATH?COOKIEPATH:'/', COOKIE_DOMAIN, is_ssl(), true);
      $_COOKIE['fasp_'.$p] = $val;
    }
  }
});
