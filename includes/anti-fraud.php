<?php if (!defined('ABSPATH')) exit;
function fasp_is_suspicious_ua(){
  $ua=strtolower($_SERVER['HTTP_USER_AGENT']??'');
  foreach(['bot','spider','crawler','curl','wget','python-requests'] as $k){ if (strpos($ua,$k)!==false) return true; }
  return !$ua;
}
function fasp_rate_limit($key,$max=30,$win=60){
  $ip=$_SERVER['REMOTE_ADDR']??'0'; $k='fasp_rl_'.md5($key.'|'.$ip);
  $n=(int)get_transient($k); if($n>=$max) return false;
  set_transient($k,$n+1,$win); return true;
}
add_action('init', function(){
  if (isset($_GET['fasp_go'])){
    if (fasp_is_suspicious_ua() || !fasp_rate_limit('go',30,60)){ status_header(429); exit('Too Many Requests'); }
  }
});
