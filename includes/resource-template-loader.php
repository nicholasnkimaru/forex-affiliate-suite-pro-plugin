<?php
if (!defined('ABSPATH')) exit;
add_filter('template_include', function($t){
  if (is_singular('fasp_resource')){
    $tpl = __DIR__.'/templates/single-fasp_resource.php';
    if (file_exists($tpl)) return $tpl;
  }
  return $t;
});