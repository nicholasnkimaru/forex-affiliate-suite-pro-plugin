<?php
if (!defined('ABSPATH')) { exit; }
function fasp_deriv_redirect_uri(){ return site_url('/wp-json/fasp/v1/deriv/callback'); }
add_action('rest_api_init', function(){
    register_rest_route('fasp/v1','/deriv/callback',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($req){
        $uid = get_current_user_id(); if ($uid){ update_user_meta($uid,'_fasp_verified_deriv','1'); }
        return ['ok'=>true,'verified'=>(bool)$uid,'note'=>'Stub callback; wire token exchange in production.'];
    }]);
});
