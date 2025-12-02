<?php
if (!defined('ABSPATH')) { exit; }
add_filter('template_include', function($template){
    if (is_singular('fasp_coach')){
        $tpl = __DIR__ . '/templates/single-fasp_coach.php';
        if (file_exists($tpl)) return $tpl;
    }
    return $template;
});
