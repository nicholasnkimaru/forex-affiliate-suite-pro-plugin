<?php
if (!defined('ABSPATH')) { exit; }
function fasp_shortcode_resources($atts){
    $a = shortcode_atts(['per_page'=>12], $atts, 'fasp_resources');
    $q = new WP_Query(['post_type'=>'fasp_resource','post_status'=>'publish','posts_per_page'=>intval($a['per_page'])]);
    ob_start(); ?>
    <div class="fasp-grid fasp-resources-grid">
    <?php if($q->have_posts()): while($q->have_posts()): $q->the_post();
        $thumb = get_the_post_thumbnail_url(get_the_ID(),'medium_large'); $thumb = $thumb ? $thumb : 'https://placehold.co/640x360?text=Resource';
        $excerpt = wp_trim_words(get_the_excerpt(),24,'…'); ?>
        <article class="fasp-card">
            <a href="<?php the_permalink(); ?>" style="text-decoration:none;">
                <div style="aspect-ratio:16/9;background:#f3f4f6 url('<?php echo esc_url($thumb); ?>') center/cover no-repeat;border-radius:12px;border:1px solid #e5e7eb;"></div>
                <h3><?php the_title(); ?></h3>
                <p class="fasp-muted"><?php echo esc_html($excerpt); ?></p>
                <span class="fasp-pill">Learn more</span>
            </a>
        </article>
    <?php endwhile; wp_reset_postdata(); else: ?>
        <div class="fasp-card"><p>No resources yet.</p></div>
    <?php endif; ?>
    </div><?php
    return ob_get_clean();
}
add_shortcode('fasp_resources','fasp_shortcode_resources');
add_action('wp_enqueue_scripts', function(){
    if (is_singular() || is_archive() || is_singular('fasp_coach') || is_post_type_archive('fasp_coach')) { wp_enqueue_style('fasp-front', plugins_url('assets/css/fasp-admin.css', __FILE__), [], '0905r8'); }
});
