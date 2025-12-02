<?php if (!defined('ABSPATH')) exit;
add_filter('single_template', function($template){
  if (is_singular('fasp_resource')){
    $t=FASP_PATH.'templates/single-fasp_resource.php'; if (file_exists($t)) return $t;
  }
  return $template;
});
