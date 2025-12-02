<?php
if (!defined('ABSPATH')) { exit; }

function fasp_shortcode_coach($atts){
    $a = shortcode_atts(['id'=>0], $atts, 'fasp_coach');
    $id = intval($a['id']); if (!$id) return '';
    $p = get_post($id); if (!$p || $p->post_type!=='fasp_coach') return '';
    setup_postdata($p);
    ob_start(); include __DIR__ . '/templates/partial-coach-card.php'; $out = ob_get_clean(); wp_reset_postdata(); return $out;
}
add_shortcode('fasp_coach','fasp_shortcode_coach');

function fasp_shortcode_coaches($atts){
    $a = shortcode_atts(['per_page'=>12], $atts, 'fasp_coaches');
    $q = new WP_Query(['post_type'=>'fasp_coach','post_status'=>'publish','posts_per_page'=>intval($a['per_page'])]);
    ob_start(); echo '<div class="fasp-profile-grid" style="max-width:1040px;margin:20px auto;padding:0 16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">';
    if ($q->have_posts()){ while($q->have_posts()){ $q->the_post(); include __DIR__ . '/templates/partial-coach-card.php'; } } else { echo '<p>No coaches yet.</p>'; }
    echo '</div>'; wp_reset_postdata(); return ob_get_clean();
}
add_shortcode('fasp_coaches','fasp_shortcode_coaches');
