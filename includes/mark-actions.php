<?php
if (!defined('ABSPATH')) exit;

// Route: /fasp-mark/<action>?r=<redirect>
add_action('init', function(){
  add_rewrite_rule('^fasp-mark/([a-z0-9\-_]+)/?$', 'index.php?fasp_mark=1&fasp_mark_action=$matches[1]', 'top');
  add_rewrite_tag('%fasp_mark%','([0-1])');
  add_rewrite_tag('%fasp_mark_action%','([a-z0-9\-_]+)');
});
add_action('template_redirect', function(){
  if (get_query_var('fasp_mark')){
    $act = sanitize_key(get_query_var('fasp_mark_action'));
    $uid = get_current_user_id();
    if ($uid && in_array($act, ['downloaded','booked','deposit','trade'], true)){
      update_user_meta($uid, '_fasp_'.$act, '1');
      do_action('fasp/event/lead', ['event_id'=>'mark-'.$act,'custom_data'=>['step'=>$act]]);
    }
    $r = isset($_GET['r']) ? esc_url_raw($_GET['r']) : home_url('/');
    wp_safe_redirect($r); exit;
  }
});

// Shortcode: [fasp_mark_link action="downloaded" url="https://..."]
add_shortcode('fasp_mark_link', function($atts){
  $a = shortcode_atts(['action'=>'downloaded','url'=>'/'], $atts, 'fasp_mark_link');
  $url = home_url('/fasp-mark/'.sanitize_key($a['action']).'/?r='.rawurlencode($a['url']));
  return esc_url($url);
});

// REST: /wp-json/fasp/v1/mark  { action: deposit|trade }
add_action('rest_api_init', function(){
  register_rest_route('fasp/v1', '/mark', [
    'methods' => 'POST',
    'permission_callback' => function(){ return is_user_logged_in(); },
    'callback' => function($req){
      $action = sanitize_key($req->get_param('action'));
      if (!in_array($action, ['deposit','trade','downloaded','booked'], true)){
        return new WP_Error('invalid_action','Invalid action', ['status'=>400]);
      }
      update_user_meta(get_current_user_id(), '_fasp_'.$action, '1');
      do_action('fasp/event/lead', ['event_id'=>'mark-'.$action,'custom_data'=>['step'=>$action]]);
      return ['ok'=>true,'action'=>$action];
    }
  ]);
});
