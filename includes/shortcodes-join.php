<?php
if (!defined('ABSPATH')) { exit; }
function fasp_shortcode_join($atts){
    $a = shortcode_atts(['platform'=>'deriv'],$atts,'fasp_join');
    $p = strtolower($a['platform']); $url = home_url('/fasp-go/'.sanitize_title($p).'?dest=signup');
    return '<a class="fasp-button" href="'.esc_url($url).'">Join '.esc_html(ucfirst($p)).'</a>';
}
add_shortcode('fasp_join','fasp_shortcode_join');
