<?php if (!defined('ABSPATH')) exit; if (!is_admin()) return;

add_action('admin_menu', function(){
  add_menu_page('Forex Affiliate','Forex Affiliate','manage_options','fasp_settings_top','fasp_render_overview','dashicons-chart-line',58);
});

// Pretty placeholder in titlebox for our CPTs
add_filter('enter_title_here', function($text, $post){
  if (isset($post->post_type) && $post->post_type==='fasp_resource') return __('Resource title','forex-affiliate-suite');
  if (isset($post->post_type) && $post->post_type==='fasp_coach_event') return __('Coach name','forex-affiliate-suite');
  if (isset($post->post_type) && $post->post_type==='fasp_landing') return __('Landing page title','forex-affiliate-suite');
  return $text;
}, 10, 2);

// Minor admin CSS polish
add_action('admin_head', function(){
  $s = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$s) return;
  if (in_array($s->post_type, ['fasp_resource','fasp_coach_event','fasp_landing'])){
    echo '<style>#titlediv{margin:10px 0 12px}#titlediv #title{border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;height:auto;box-shadow:none;font-size:14px}</style>';
  }
});
