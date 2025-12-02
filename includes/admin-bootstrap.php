<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('fasp_admin_assets')){
  function fasp_admin_assets($hook){
    if ( strpos($hook,'fasp')!==false || (isset($_GET['page']) && strpos(sanitize_text_field($_GET['page']),'fasp_')===0) ){
      wp_enqueue_style('fasp-admin-polish', plugins_url('assets/css/fasp-admin.css', dirname(__FILE__,1) . '/../'), [], 'r16p5');
    }
  }
  add_action('admin_enqueue_scripts','fasp_admin_assets');
}

if (!function_exists('fasp_classic_everywhere')){
  function fasp_classic_everywhere(){
    add_filter('use_block_editor_for_post_type','__return_false',100);
    add_filter('use_widgets_block_editor','__return_false',100);
  }
  add_action('init','fasp_classic_everywhere');
}
