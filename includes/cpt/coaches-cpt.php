<?php
if (!defined('ABSPATH')) exit;

/**
 * Coaches Custom Post Type
 */
function fasp_register_coaches_cpt() {
    $labels = array(
        'name' => __('Coaches', 'fasp'),
        'singular_name' => __('Coach', 'fasp'),
        'menu_name' => __('Coaches', 'fasp'),
        'add_new_item' => __('Add New Coach', 'fasp'),
        'edit_item' => __('Edit Coach', 'fasp'),
    );

    $args = array(
        'label' => __('Coaches', 'fasp'),
        'labels' => $labels,
        'description' => 'Trading coaches and mentors',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'fasp_hub',
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'coach'),
    );

    register_post_type('fasp_coach', $args);
    add_action('add_meta_boxes', 'fasp_coach_add_meta_boxes');
}

add_action('init', 'fasp_register_coaches_cpt');

/**
 * Add coach meta boxes
 */
function fasp_coach_add_meta_boxes() {
    add_meta_box(
        'fasp_coach_details',
        'Coach Information',
        'fasp_coach_details_meta_box',
        'fasp_coach',
        'normal',
        'high'
    );
}

/**
 * Coach meta box
 */
function fasp_coach_details_meta_box($post) {
    $expertise = get_post_meta($post->ID, '_fasp_coach_expertise', true);
    $facebook = get_post_meta($post->ID, '_fasp_coach_facebook', true);
    $twitter = get_post_meta($post->ID, '_fasp_coach_twitter', true);
    $instagram = get_post_meta($post->ID, '_fasp_coach_instagram', true);
    $linkedin = get_post_meta($post->ID, '_fasp_coach_linkedin', true);
    $telegram = get_post_meta($post->ID, '_fasp_coach_telegram', true);
    $calendly = get_post_meta($post->ID, '_fasp_coach_calendly', true);
    $email = get_post_meta($post->ID, '_fasp_coach_email', true);
    $phone = get_post_meta($post->ID, '_fasp_coach_phone', true);
    $priority = get_post_meta($post->ID, '_fasp_coach_priority', true) ?? 100;

    wp_nonce_field('fasp_coach_nonce');

    ?>
    <table class="form-table">
        <tr>
            <th><label for="coach-expertise">Expertise Tags</label></th>
            <td>
                <input type="text" name="fasp_coach_expertise" id="coach-expertise" value="<?php echo esc_attr($expertise); ?>" class="large-text" placeholder="scalping, psychology, risk-management">
            </td>
        </tr>
        <tr><td colspan="2"><strong>Social Media</strong></td></tr>
        <tr>
            <th><label for="coach-facebook">Facebook</label></th>
            <td><input type="url" name="fasp_coach_facebook" id="coach-facebook" value="<?php echo esc_url($facebook); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-twitter">Twitter/X</label></th>
            <td><input type="url" name="fasp_coach_twitter" id="coach-twitter" value="<?php echo esc_url($twitter); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-instagram">Instagram</label></th>
            <td><input type="url" name="fasp_coach_instagram" id="coach-instagram" value="<?php echo esc_url($instagram); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-linkedin">LinkedIn</label></th>
            <td><input type="url" name="fasp_coach_linkedin" id="coach-linkedin" value="<?php echo esc_url($linkedin); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-telegram">Telegram</label></th>
            <td><input type="url" name="fasp_coach_telegram" id="coach-telegram" value="<?php echo esc_url($telegram); ?>" class="large-text"></td>
        </tr>
        <tr><td colspan="2"><strong>Contact</strong></td></tr>
        <tr>
            <th><label for="coach-calendly">Calendly URL</label></th>
            <td><input type="url" name="fasp_coach_calendly" id="coach-calendly" value="<?php echo esc_url($calendly); ?>" class="large-text" placeholder="https://calendly.com/yourname"></td>
        </tr>
        <tr>
            <th><label for="coach-email">Email (logged-in users only)</label></th>
            <td><input type="email" name="fasp_coach_email" id="coach-email" value="<?php echo esc_attr($email); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-phone">Phone (logged-in users only)</label></th>
            <td><input type="tel" name="fasp_coach_phone" id="coach-phone" value="<?php echo esc_attr($phone); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th><label for="coach-priority">Display Priority (1-100)</label></th>
            <td><input type="number" name="fasp_coach_priority" id="coach-priority" value="<?php echo intval($priority); ?>" min="1" max="100"></td>
        </tr>
    </table>
    <?php
}

/**
 * Save coach meta
 */
add_action('save_post_fasp_coach', function($post_id) {
    if (!isset($_POST['fasp_coach_nonce']) || !wp_verify_nonce($_POST['fasp_coach_nonce'])) {
        return;
    }

    update_post_meta($post_id, '_fasp_coach_expertise', sanitize_text_field($_POST['fasp_coach_expertise'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_facebook', esc_url_raw($_POST['fasp_coach_facebook'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_twitter', esc_url_raw($_POST['fasp_coach_twitter'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_instagram', esc_url_raw($_POST['fasp_coach_instagram'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_linkedin', esc_url_raw($_POST['fasp_coach_linkedin'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_telegram', esc_url_raw($_POST['fasp_coach_telegram'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_calendly', esc_url_raw($_POST['fasp_coach_calendly'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_email', sanitize_email($_POST['fasp_coach_email'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_phone', sanitize_text_field($_POST['fasp_coach_phone'] ?? ''));
    update_post_meta($post_id, '_fasp_coach_priority', intval($_POST['fasp_coach_priority'] ?? 100));
});
