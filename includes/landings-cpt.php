<?php if (!defined('ABSPATH')) exit;
add_action('init', function(){
  register_post_type('fasp_landing', [
    'labels'=>['name'=>'Promo Landings','singular_name'=>'Landing','add_new_item'=>'Add Landing'],
    'public'=>true,'show_in_menu'=>'fasp_settings_top','menu_icon'=>'dashicons-megaphone',
    'supports'=>['title','editor','thumbnail','excerpt','revisions','custom-fields'],
    'hierarchical'=>true,'show_in_rest'=>true
  ]);
});
