<?php
if (!defined('ABSPATH')) { exit; }
add_action('init', function(){ add_rewrite_rule('^fasp-go/([a-z0-9\-]+)/?', 'index.php?fasp_go=1&fasp_slug=$matches[1]', 'top'); add_rewrite_tag('%fasp_go%','([0-1])'); add_rewrite_tag('%fasp_slug%','([a-z0-9\-]+)'); });
add_action('template_redirect', function(){
    if (get_query_var('fasp_go')){
        $slug = sanitize_title(get_query_var('fasp_slug')); $plats = function_exists('fasp_get_platforms')? fasp_get_platforms():[];
        if ($slug && isset($plats[$slug])){ $p=$plats[$slug]; $url = $p['affiliate_url'] ?: ($p['signup_url'] ?: home_url('/')); if (function_exists('fasp_log_click')) fasp_log_click($slug,'click',$url); wp_safe_redirect($url); exit; }
        wp_safe_redirect(home_url('/')); exit;
    }
});
add_action('rest_api_init', function(){
    register_rest_route('fasp/v1','/verify/(?P<slug>[a-z0-9\-]+)',['methods'=>'POST','permission_callback'=>function(){return is_user_logged_in();},'callback'=>function($req){
        $slug = sanitize_title($req['slug']); $plats = function_exists('fasp_get_platforms')? fasp_get_platforms():[];
        if (!$slug || !isset($plats[$slug])) return new WP_Error('invalid_platform','Invalid platform',['status'=>400]);
        update_user_meta(get_current_user_id(),'_fasp_verified_'.$slug,'1'); return ['ok'=>true,'slug'=>$slug];
    }]);
});
