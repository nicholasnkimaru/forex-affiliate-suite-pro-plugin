<?php if (!defined('ABSPATH')) exit;
add_action('admin_head', function(){
  $s = function_exists('get_current_screen') ? get_current_screen() : null;
  if ($s && $s->post_type === 'fasp_resource'){ echo '<style>#titlediv{display:none}</style>'; }
});