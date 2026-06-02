<?php
if (!defined('ABSPATH')) exit;

/**
 * Register Resources Custom Post Type
 */
function fasp_register_resources_cpt() {
    $labels = array(
        'name' => __('Resources', 'fasp'),
        'singular_name' => __('Resource', 'fasp'),
        'menu_name' => __('Resources', 'fasp'),
        'add_new_item' => __('Add New Resource', 'fasp'),
        'edit_item' => __('Edit Resource', 'fasp'),
        'view_item' => __('View Resource', 'fasp'),
    );

    $args = array(
        'label' => __('Resources', 'fasp'),
        'labels' => $labels,
        'description' => 'Learning resources (ebooks, videos, tools, templates)',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'fasp_hub',
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'resource'),
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'edit_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
        ),
    );

    register_post_type('fasp_resource', $args);

    // Add meta boxes
    add_action('add_meta_boxes', 'fasp_resource_add_meta_boxes');
}

add_action('init', 'fasp_register_resources_cpt');

/**
 * Add meta boxes to resource editor
 */
function fasp_resource_add_meta_boxes() {
    add_meta_box(
        'fasp_resource_details',
        'Resource Details',
        'fasp_resource_details_meta_box',
        'fasp_resource',
        'normal',
        'high'
    );

    add_meta_box(
        'fasp_resource_gating',
        'Access Control & Gating',
        'fasp_resource_gating_meta_box',
        'fasp_resource',
        'normal',
        'high'
    );
}

/**
 * Resource details meta box
 */
function fasp_resource_details_meta_box($post) {
    $type = get_post_meta($post->ID, '_fasp_resource_type', true);
    $description = get_post_meta($post->ID, '_fasp_resource_description', true);
    $download_url = get_post_meta($post->ID, '_fasp_resource_download_url', true);
    $cta_primary = get_post_meta($post->ID, '_fasp_resource_cta_primary', true) ?? 'Download Now';
    $cta_secondary = get_post_meta($post->ID, '_fasp_resource_cta_secondary', true);

    wp_nonce_field('fasp_resource_nonce');

    ?>
    <table class="form-table">
        <tr>
            <th><label for="res-type">Resource Type</label></th>
            <td>
                <select name="fasp_resource_type" id="res-type" required>
                    <option value="">Select Type...</option>
                    <option value="ebook" <?php selected($type, 'ebook'); ?>>Ebook/PDF</option>
                    <option value="video" <?php selected($type, 'video'); ?>>Video Course</option>
                    <option value="tool" <?php selected($type, 'tool'); ?>>Tool/Calculator</option>
                    <option value="template" <?php selected($type, 'template'); ?>>Template</option>
                    <option value="course" <?php selected($type, 'course'); ?>>Full Course</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="res-desc">Short Description</label></th>
            <td>
                <textarea name="fasp_resource_description" id="res-desc" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="res-download">Download URL or External Link</label></th>
            <td>
                <input type="url" name="fasp_resource_download_url" id="res-download" value="<?php echo esc_url($download_url); ?>" class="large-text" placeholder="https://example.com/file.pdf">
                <p class="description">Link to PDF, video, or external tool</p>
            </td>
        </tr>
        <tr>
            <th><label for="res-cta1">Primary CTA Button Text</label></th>
            <td>
                <input type="text" name="fasp_resource_cta_primary" id="res-cta1" value="<?php echo esc_attr($cta_primary); ?>" class="large-text" placeholder="Download Now">
            </td>
        </tr>
        <tr>
            <th><label for="res-cta2">Secondary CTA Button Text (Optional)</label></th>
            <td>
                <input type="text" name="fasp_resource_cta_secondary" id="res-cta2" value="<?php echo esc_attr($cta_secondary); ?>" class="large-text" placeholder="Book Coaching">
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Resource gating meta box
 */
function fasp_resource_gating_meta_box($post) {
    $require_login = get_post_meta($post->ID, '_fasp_resource_require_login', true);
    $require_verified = get_post_meta($post->ID, '_fasp_resource_require_verified', true);
    $geo_allowlist = get_post_meta($post->ID, '_fasp_resource_geo_allowlist', true);
    $geo_blocklist = get_post_meta($post->ID, '_fasp_resource_geo_blocklist', true);

    ?>
    <table class="form-table">
        <tr>
            <th><label><input type="checkbox" name="fasp_resource_require_login" value="1" <?php checked($require_login); ?>> Require User to be Logged In</label></th>
        </tr>
        <tr>
            <th><label><input type="checkbox" name="fasp_resource_require_verified" value="1" <?php checked($require_verified); ?>> Require User to be Verified</label></th>
        </tr>
        <tr>
            <th><label for="res-geo-allow">Allowed Countries (ISO codes, comma-separated)</label></th>
            <td>
                <input type="text" name="fasp_resource_geo_allowlist" id="res-geo-allow" value="<?php echo esc_attr($geo_allowlist); ?>" class="large-text" placeholder="KE,US,GB,AE">
                <p class="description">Leave empty to allow all. Example: KE,US,GB</p>
            </td>
        </tr>
        <tr>
            <th><label for="res-geo-block">Blocked Countries (ISO codes, comma-separated)</label></th>
            <td>
                <input type="text" name="fasp_resource_geo_blocklist" id="res-geo-block" value="<?php echo esc_attr($geo_blocklist); ?>" class="large-text" placeholder="CN,RU">
                <p class="description">Countries to block from access</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save resource meta
 */
add_action('save_post_fasp_resource', function($post_id) {
    if (!isset($_POST['fasp_resource_nonce']) || !wp_verify_nonce($_POST['fasp_resource_nonce'])) {
        return;
    }

    update_post_meta($post_id, '_fasp_resource_type', sanitize_text_field($_POST['fasp_resource_type'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_description', sanitize_textarea_field($_POST['fasp_resource_description'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_download_url', esc_url_raw($_POST['fasp_resource_download_url'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_cta_primary', sanitize_text_field($_POST['fasp_resource_cta_primary'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_cta_secondary', sanitize_text_field($_POST['fasp_resource_cta_secondary'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_require_login', isset($_POST['fasp_resource_require_login']));
    update_post_meta($post_id, '_fasp_resource_require_verified', isset($_POST['fasp_resource_require_verified']));
    update_post_meta($post_id, '_fasp_resource_geo_allowlist', sanitize_text_field($_POST['fasp_resource_geo_allowlist'] ?? ''));
    update_post_meta($post_id, '_fasp_resource_geo_blocklist', sanitize_text_field($_POST['fasp_resource_geo_blocklist'] ?? ''));
});
