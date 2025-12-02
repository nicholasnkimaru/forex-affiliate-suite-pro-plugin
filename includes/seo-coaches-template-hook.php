<?php if (!defined('ABSPATH')) exit;
add_filter('single_template', function($template){
  if (is_singular('fasp_coach_event')){
    $t=FASP_PATH.'templates/single-fasp_coach_event.php'; if (file_exists($t)) return $t;
  }
  return $template;
});
