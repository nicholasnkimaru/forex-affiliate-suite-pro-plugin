<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('fasp_register_cpt_coach')) {
function fasp_register_cpt_coach(){
    $labels = [
        'name' => 'Forex Coaches',
        'singular_name' => 'Forex Coach',
        'menu_name' => 'Forex Coaches'
    ];
    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => false,
        'supports' => ['title','editor','thumbnail','excerpt','revisions'],
        'has_archive' => true,
        'rewrite' => ['slug'=>'coaches'],
        'show_in_rest' => true
    ];
    register_post_type('fasp_coach', $args);
}
add_action('init', 'fasp_register_cpt_coach');

function fasp_coach_meta_boxes(){
    add_meta_box('fasp_coach_meta', 'Coach Details', 'fasp_coach_meta_cb', 'fasp_coach', 'normal','high');
}
add_action('add_meta_boxes', 'fasp_coach_meta_boxes');

function fasp_coach_meta_cb($post){
    $role = get_post_meta($post->ID, '_fasp_coach_role', true);
    $live = get_post_meta($post->ID, '_fasp_coach_live', true);
    $aff  = get_post_meta($post->ID, '_fasp_coach_affiliate', true);
    $video = get_post_meta($post->ID, '_fasp_coach_video', true);
    wp_nonce_field('fasp_coach_meta', 'fasp_coach_meta_nonce');
    ?>
    <p><label><strong>Role/Expertise</strong><br><input type="text" class="widefat" name="fasp_coach_role" value="<?php echo esc_attr($role); ?>"></label></p>
    <p><label><strong>Live coaching URL</strong> (can be your affiliate link)<br><input type="url" class="widefat" name="fasp_coach_live" value="<?php echo esc_attr($live); ?>"></label></p>
    <p><label><strong>Affiliate URL</strong><br><input type="url" class="widefat" name="fasp_coach_affiliate" value="<?php echo esc_attr($aff); ?>"></label></p>
    <p><label><strong>Intro video (YouTube/Vimeo)</strong><br><input type="url" class="widefat" name="fasp_coach_video" value="<?php echo esc_attr($video); ?>"></label></p>

    <hr>
    <p><label><strong>Tagline</strong><br><input type="text" class="widefat" name="fasp_coach_tagline" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_tagline',true)); ?>"></label></p>
    <p><label><strong>Timezone</strong> (e.g., Africa/Nairobi)<br><input type="text" class="widefat" name="fasp_coach_timezone" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_timezone',true)); ?>"></label></p>
    <p><label><strong>Languages</strong> (CSV)<br><input type="text" class="widefat" name="fasp_coach_languages" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_languages',true)); ?>"></label></p>
    <p><label><strong>Years of experience</strong><br><input type="number" min="0" class="widefat" name="fasp_coach_years" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_years',true)); ?>"></label></p>
    <p><label><strong>Rate (per hour)</strong><br><input type="text" class="widefat" name="fasp_coach_rate" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_rate',true)); ?>"></label></p>
    <p><label><strong>WhatsApp</strong> (full link)<br><input type="url" class="widefat" name="fasp_coach_whatsapp" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_whatsapp',true)); ?>"></label></p>
    <p><label><strong>Telegram</strong> (full link)<br><input type="url" class="widefat" name="fasp_coach_telegram" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_telegram',true)); ?>"></label></p>
    <p><label><strong>Twitter/X</strong> (full link)<br><input type="url" class="widefat" name="fasp_coach_twitter" value="<?php echo esc_attr(get_post_meta($post->ID,'_fasp_coach_twitter',true)); ?>"></label></p>
    <?php
}

function fasp_coach_meta_save($post_id){
    if (!isset($_POST['fasp_coach_meta_nonce']) || !wp_verify_nonce($_POST['fasp_coach_meta_nonce'], 'fasp_coach_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_fasp_coach_role', sanitize_text_field($_POST['fasp_coach_role'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_live', esc_url_raw($_POST['fasp_coach_live'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_affiliate', esc_url_raw($_POST['fasp_coach_affiliate'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_video', esc_url_raw($_POST['fasp_coach_video'] ?? ''));

    update_post_meta($post_id, '_fasp_coach_tagline', sanitize_text_field($_POST['fasp_coach_tagline'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_timezone', sanitize_text_field($_POST['fasp_coach_timezone'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_languages', sanitize_text_field($_POST['fasp_coach_languages'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_years', intval($_POST['fasp_coach_years'] ?? 0));
    update_post_meta($post_id, '_fasp_coach_rate', sanitize_text_field($_POST['fasp_coach_rate'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_whatsapp', esc_url_raw($_POST['fasp_coach_whatsapp'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_telegram', esc_url_raw($_POST['fasp_coach_telegram'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_twitter', esc_url_raw($_POST['fasp_coach_twitter'] ?? ''));

}
add_action('save_post_fasp_coach', 'fasp_coach_meta_save');

// Optional: show columns in list table for quick visibility
add_filter('manage_fasp_coach_posts_columns', function($cols){
    $cols['fasp_coach_role'] = 'Role';
    $cols['fasp_coach_live'] = 'Live URL';
    return $cols;
});
add_action('manage_fasp_coach_posts_custom_column', function($col, $post_id){
    if ($col==='fasp_coach_role'){ echo esc_html(get_post_meta($post_id,'_fasp_coach_role',true)); }
    if ($col==='fasp_coach_live'){ $u = esc_url(get_post_meta($post_id,'_fasp_coach_live',true)); if ($u) echo '<a href="'.$u.'" target="_blank">Open</a>'; }
}, 10, 2);
}
