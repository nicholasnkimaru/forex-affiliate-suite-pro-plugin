<?php
if (!defined('ABSPATH')) { exit; }
add_action('admin_menu', function(){ add_submenu_page('fasp_hub','Settings','Settings','manage_options','fasp_settings','fasp_settings_page'); });
add_action('admin_init', function(){
    register_setting('fasp_settings','fasp_settings',['type'=>'array','sanitize_callback'=>function($in){return is_array($in)? array_map('sanitize_text_field',$in):[];}]);
    add_settings_section('fasp_main','General','__return_false','fasp_settings');
    foreach(['deriv_app_id'=>'Deriv App ID','deriv_affiliate_link'=>'Deriv Affiliate Link','exness_affiliate_link'=>'Exness Affiliate Link'] as $k=>$label){
        add_settings_field($k,$label,function($a){$o=get_option('fasp_settings',[]);$k=$a['key'];$v=$o[$k]??'';printf('<input class="regular-text" name="fasp_settings[%s]" value="%s">',esc_attr($k),esc_attr($v));},'fasp_settings','fasp_main',['key'=>$k]);
    }
});
function fasp_settings_page(){ ?><div class="wrap fasp-admin"><h1>Settings</h1><div class="fasp-wrap fasp-card"><form method="post" action="options.php"><?php settings_fields('fasp_settings'); do_settings_sections('fasp_settings'); submit_button(); ?></form></div></div><?php }
