<?php
/**
 * Gating Setup - Role-based access control and per-page gating overrides
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if a user is allowed to access content based on gating rules
 *
 * @param int      $user_id User ID to check
 * @param int|null $post_id Post ID to check (optional, uses current post if not provided)
 * @return bool Whether the user is allowed
 */
if (!function_exists('fasp_is_user_allowed_by_gating')) {
    function fasp_is_user_allowed_by_gating($user_id = null, $post_id = null) {
        // Get user
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        $user_id = absint($user_id);
        
        // Get post
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        $post_id = absint($post_id);
        
        // Check for per-page override first
        if ($post_id) {
            $override = get_post_meta($post_id, '_fasp_gating_override', true);
            if ($override === 'allow_all') {
                return true;
            }
            if ($override === 'block_all') {
                return false;
            }
            if ($override === 'require_login') {
                return $user_id > 0;
            }
        }
        
        // Get global gating settings
        $require_login = get_option('fasp_gating_require_login', false);
        $allowed_roles = get_option('fasp_gating_roles', array());
        
        // If no gating is configured, allow access
        if (!$require_login && empty($allowed_roles)) {
            return true;
        }
        
        // If login is required and user is not logged in
        if ($require_login && !$user_id) {
            return false;
        }
        
        // If no role restrictions, allow logged-in users
        if (empty($allowed_roles) || !is_array($allowed_roles)) {
            return $user_id > 0;
        }
        
        // Check if user has one of the allowed roles
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $user_roles = (array) $user->roles;
        $intersection = array_intersect($user_roles, $allowed_roles);
        
        return !empty($intersection);
    }
}

/**
 * Get the blocked message for gating
 *
 * @return string The blocked message HTML
 */
if (!function_exists('fasp_get_gating_blocked_message')) {
    function fasp_get_gating_blocked_message() {
        $message = get_option('fasp_gating_blocked_message', '');
        if (empty($message)) {
            $message = __('You do not have permission to access this content. Please log in or contact the site administrator.', 'forex-affiliate-suite-pro');
        }
        return wp_kses_post($message);
    }
}

/**
 * Get the redirect URL for blocked users
 *
 * @return string The redirect URL or empty string if none
 */
if (!function_exists('fasp_get_gating_redirect_url')) {
    function fasp_get_gating_redirect_url() {
        $url = get_option('fasp_gating_blocked_redirect', '');
        return $url ? esc_url($url) : '';
    }
}

/**
 * Register gating settings
 */
add_action('admin_init', 'fasp_register_gating_settings');
if (!function_exists('fasp_register_gating_settings')) {
    function fasp_register_gating_settings() {
        register_setting('fasp_gating_settings', 'fasp_gating_require_login', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        ));
        
        register_setting('fasp_gating_settings', 'fasp_gating_roles', array(
            'type' => 'array',
            'sanitize_callback' => 'fasp_sanitize_gating_roles',
            'default' => array(),
        ));
        
        register_setting('fasp_gating_settings', 'fasp_gating_blocked_message', array(
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => '',
        ));
        
        register_setting('fasp_gating_settings', 'fasp_gating_blocked_redirect', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ));
    }
}

/**
 * Sanitize gating roles array
 *
 * @param mixed $roles Roles input
 * @return array Sanitized roles array
 */
if (!function_exists('fasp_sanitize_gating_roles')) {
    function fasp_sanitize_gating_roles($roles) {
        if (!is_array($roles)) {
            return array();
        }
        return array_map('sanitize_key', $roles);
    }
}

/**
 * Add meta box for per-page gating override
 */
add_action('add_meta_boxes', 'fasp_add_gating_meta_box');
if (!function_exists('fasp_add_gating_meta_box')) {
    function fasp_add_gating_meta_box() {
        $post_types = array('post', 'page', 'fasp_resource', 'fasp_coach_event');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'fasp_gating_override',
                __('Access Gating', 'forex-affiliate-suite-pro'),
                'fasp_render_gating_meta_box',
                $post_type,
                'side',
                'default'
            );
        }
    }
}

/**
 * Render the gating meta box
 *
 * @param WP_Post $post The post object
 */
if (!function_exists('fasp_render_gating_meta_box')) {
    function fasp_render_gating_meta_box($post) {
        wp_nonce_field('fasp_gating_meta_box', 'fasp_gating_meta_nonce');
        
        $override = get_post_meta($post->ID, '_fasp_gating_override', true);
        $options = array(
            '' => __('Use Global Settings', 'forex-affiliate-suite-pro'),
            'allow_all' => __('Allow All (Public)', 'forex-affiliate-suite-pro'),
            'require_login' => __('Require Login', 'forex-affiliate-suite-pro'),
            'block_all' => __('Block All (Administrators Only)', 'forex-affiliate-suite-pro'),
        );
        
        echo '<p><label for="fasp_gating_override">' . esc_html__('Access Control:', 'forex-affiliate-suite-pro') . '</label></p>';
        echo '<select id="fasp_gating_override" name="fasp_gating_override" style="width:100%">';
        foreach ($options as $value => $label) {
            $selected = ($override === $value) ? ' selected' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Override the global gating settings for this content.', 'forex-affiliate-suite-pro') . '</p>';
    }
}

/**
 * Save gating meta box data
 */
add_action('save_post', 'fasp_save_gating_meta_box');
if (!function_exists('fasp_save_gating_meta_box')) {
    function fasp_save_gating_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['fasp_gating_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_gating_meta_nonce'])), 'fasp_gating_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save the override value
        if (isset($_POST['fasp_gating_override'])) {
            $value = sanitize_key(wp_unslash($_POST['fasp_gating_override']));
            $allowed = array('', 'allow_all', 'require_login', 'block_all');
            if (in_array($value, $allowed, true)) {
                update_post_meta($post_id, '_fasp_gating_override', $value);
            }
        }
    }
}

/**
 * Render the gating settings admin page
 */
if (!function_exists('fasp_render_gating_settings_page')) {
    function fasp_render_gating_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'forex-affiliate-suite-pro'));
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_gating_settings_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fasp_gating_settings_nonce'])), 'fasp_gating_settings_save')) {
                wp_die(esc_html__('Security check failed', 'forex-affiliate-suite-pro'));
            }
            
            // Save settings
            $require_login = isset($_POST['fasp_gating_require_login']) ? true : false;
            update_option('fasp_gating_require_login', $require_login);
            
            $roles = isset($_POST['fasp_gating_roles']) && is_array($_POST['fasp_gating_roles']) 
                ? array_map('sanitize_key', wp_unslash($_POST['fasp_gating_roles']))
                : array();
            update_option('fasp_gating_roles', $roles);
            
            $message = isset($_POST['fasp_gating_blocked_message']) 
                ? wp_kses_post(wp_unslash($_POST['fasp_gating_blocked_message']))
                : '';
            update_option('fasp_gating_blocked_message', $message);
            
            $redirect = isset($_POST['fasp_gating_blocked_redirect']) 
                ? esc_url_raw(wp_unslash($_POST['fasp_gating_blocked_redirect']))
                : '';
            update_option('fasp_gating_blocked_redirect', $redirect);
            
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'forex-affiliate-suite-pro') . '</p></div>';
        }
        
        // Get current values
        $require_login = get_option('fasp_gating_require_login', false);
        $selected_roles = get_option('fasp_gating_roles', array());
        $blocked_message = get_option('fasp_gating_blocked_message', '');
        $blocked_redirect = get_option('fasp_gating_blocked_redirect', '');
        
        // Get all available roles
        $wp_roles = wp_roles();
        $all_roles = $wp_roles->get_names();
        
        // Preview mode handling
        $preview_user_id = isset($_GET['fasp_preview_as']) ? absint($_GET['fasp_preview_as']) : 0;
        $preview_post_id = isset($_GET['fasp_preview_post']) ? absint($_GET['fasp_preview_post']) : 0;
        ?>
        <div class="wrap fasp-admin">
            <h1><?php esc_html_e('Gating Setup', 'forex-affiliate-suite-pro'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('fasp_gating_settings_save', 'fasp_gating_settings_nonce'); ?>
                
                <div class="fasp-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;">
                    <!-- General Settings -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('General Settings', 'forex-affiliate-suite-pro'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Require Login', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fasp_gating_require_login" value="1" <?php checked($require_login); ?>>
                                        <?php esc_html_e('Require users to be logged in to access gated content', 'forex-affiliate-suite-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Allowed Roles', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <select name="fasp_gating_roles[]" multiple style="min-height:150px;width:100%;">
                                        <?php foreach ($all_roles as $role_key => $role_name) : ?>
                                            <option value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, $selected_roles, true) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($role_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select roles that can access gated content. Hold Ctrl/Cmd to select multiple. Leave empty to allow all logged-in users.', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Blocked User Settings -->
                    <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                        <h2><?php esc_html_e('Blocked User Settings', 'forex-affiliate-suite-pro'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Blocked Message', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <textarea name="fasp_gating_blocked_message" rows="4" style="width:100%;"><?php echo esc_textarea($blocked_message); ?></textarea>
                                    <p class="description"><?php esc_html_e('Message shown to users who are blocked from content. HTML allowed.', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Redirect URL', 'forex-affiliate-suite-pro'); ?></th>
                                <td>
                                    <input type="url" name="fasp_gating_blocked_redirect" value="<?php echo esc_attr($blocked_redirect); ?>" style="width:100%;">
                                    <p class="description"><?php esc_html_e('Optional URL to redirect blocked users to (e.g., login page or upgrade page).', 'forex-affiliate-suite-pro'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'forex-affiliate-suite-pro'); ?></button>
                </p>
            </form>
            
            <!-- Preview Tool -->
            <div class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-top:20px;">
                <h2><?php esc_html_e('Test Access As', 'forex-affiliate-suite-pro'); ?></h2>
                <p class="description"><?php esc_html_e('Test how gating rules apply to specific users and posts.', 'forex-affiliate-suite-pro'); ?></p>
                
                <form method="get" action="" style="display:flex;gap:15px;flex-wrap:wrap;align-items:flex-end;">
                    <input type="hidden" name="page" value="fasp_gating_setup">
                    
                    <div>
                        <label for="fasp_preview_as" style="display:block;margin-bottom:4px;"><?php esc_html_e('User ID:', 'forex-affiliate-suite-pro'); ?></label>
                        <input type="number" id="fasp_preview_as" name="fasp_preview_as" value="<?php echo esc_attr($preview_user_id); ?>" min="0" style="width:120px;">
                    </div>
                    
                    <div>
                        <label for="fasp_preview_post" style="display:block;margin-bottom:4px;"><?php esc_html_e('Post ID:', 'forex-affiliate-suite-pro'); ?></label>
                        <input type="number" id="fasp_preview_post" name="fasp_preview_post" value="<?php echo esc_attr($preview_post_id); ?>" min="0" style="width:120px;">
                    </div>
                    
                    <button type="submit" class="button"><?php esc_html_e('Test Access', 'forex-affiliate-suite-pro'); ?></button>
                </form>
                
                <?php if ($preview_user_id || $preview_post_id) : ?>
                    <div style="margin-top:15px;padding:15px;background:#f8fafc;border-radius:8px;">
                        <h3 style="margin-top:0;"><?php esc_html_e('Test Result', 'forex-affiliate-suite-pro'); ?></h3>
                        <?php
                        $is_allowed = fasp_is_user_allowed_by_gating($preview_user_id, $preview_post_id);
                        $user_info = $preview_user_id ? get_userdata($preview_user_id) : null;
                        $post_info = $preview_post_id ? get_post($preview_post_id) : null;
                        ?>
                        <p>
                            <strong><?php esc_html_e('User:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($user_info) : ?>
                                <?php echo esc_html($user_info->display_name); ?> (<?php echo esc_html(implode(', ', $user_info->roles)); ?>)
                            <?php elseif ($preview_user_id === 0) : ?>
                                <?php esc_html_e('Guest (not logged in)', 'forex-affiliate-suite-pro'); ?>
                            <?php else : ?>
                                <?php esc_html_e('User not found', 'forex-affiliate-suite-pro'); ?>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong><?php esc_html_e('Post:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($post_info) : ?>
                                <?php echo esc_html($post_info->post_title); ?> (<?php echo esc_html($post_info->post_type); ?>)
                            <?php elseif ($preview_post_id) : ?>
                                <?php esc_html_e('Post not found', 'forex-affiliate-suite-pro'); ?>
                            <?php else : ?>
                                <?php esc_html_e('No post specified (global rules)', 'forex-affiliate-suite-pro'); ?>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong><?php esc_html_e('Access:', 'forex-affiliate-suite-pro'); ?></strong>
                            <?php if ($is_allowed) : ?>
                                <span style="color:#166534;font-weight:bold;">✓ <?php esc_html_e('ALLOWED', 'forex-affiliate-suite-pro'); ?></span>
                            <?php else : ?>
                                <span style="color:#dc2626;font-weight:bold;">✗ <?php esc_html_e('BLOCKED', 'forex-affiliate-suite-pro'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Add gating setup to admin menu
 */
add_action('admin_menu', 'fasp_add_gating_setup_menu', 50);
if (!function_exists('fasp_add_gating_setup_menu')) {
    function fasp_add_gating_setup_menu() {
        $parent = function_exists('fasp_parent_slug') ? fasp_parent_slug() : 'forex-affiliate';
        
        add_submenu_page(
            $parent,
            __('Gating Setup', 'forex-affiliate-suite-pro'),
            __('Gating Setup', 'forex-affiliate-suite-pro'),
            'manage_options',
            'fasp_gating_setup',
            'fasp_render_gating_settings_page'
        );
    }
}

/**
 * Apply gating on content access
 */
add_action('template_redirect', 'fasp_apply_content_gating');
if (!function_exists('fasp_apply_content_gating')) {
    function fasp_apply_content_gating() {
        // Only apply to singular content
        if (!is_singular()) {
            return;
        }
        
        // Don't gate admin users viewing content
        if (current_user_can('manage_options')) {
            return;
        }
        
        $post_id = get_queried_object_id();
        
        // Check if user is allowed
        if (fasp_is_user_allowed_by_gating(get_current_user_id(), $post_id)) {
            return;
        }
        
        // User is blocked - redirect or show message
        $redirect = fasp_get_gating_redirect_url();
        if ($redirect) {
            wp_safe_redirect($redirect);
            exit;
        }
        
        // Show blocked message
        add_filter('the_content', 'fasp_show_gating_blocked_message', 1);
    }
}

/**
 * Show blocked message instead of content
 *
 * @param string $content The post content
 * @return string Modified content
 */
if (!function_exists('fasp_show_gating_blocked_message')) {
    function fasp_show_gating_blocked_message($content) {
        if (!in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $message = fasp_get_gating_blocked_message();
        
        return '<div class="fasp-gating-blocked" style="padding:30px;background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;text-align:center;">' .
            '<h3 style="margin-top:0;">' . esc_html__('Access Restricted', 'forex-affiliate-suite-pro') . '</h3>' .
            '<p>' . $message . '</p>' .
            (!is_user_logged_in() ? '<p><a class="button" href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Log In', 'forex-affiliate-suite-pro') . '</a></p>' : '') .
            '</div>';
    }
}
