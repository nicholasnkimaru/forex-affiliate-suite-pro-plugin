<?php if (!defined('ABSPATH')) exit;
/**
 * Disable Gutenberg site-wide and strip block styles.
 * - Classic editor for all post types and widgets.
 * - Hides Site Editor submenu.
 * - Dequeues block CSS (front + admin).
 */
add_filter('use_block_editor_for_post', '__return_false', 10);
add_filter('use_block_editor_for_post_type', '__return_false', 10);
add_filter('gutenberg_can_edit_post', '__return_false', 10);
add_filter('gutenberg_can_edit_post_type', '__return_false', 10);
add_filter('gutenberg_use_widgets_block_editor', '__return_false');
add_filter('use_widgets_block_editor', '__return_false');

add_action('admin_menu', function(){
  // Hide Site Editor (block themes)
  remove_submenu_page('themes.php', 'site-editor.php');
}, 999);

function fasp_dequeue_block_styles(){
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');
  wp_dequeue_style('global-styles');
  wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'fasp_dequeue_block_styles', 100);
add_action('admin_enqueue_scripts', 'fasp_dequeue_block_styles', 100);

// Remove global duotone SVG filters if added
add_action('wp_enqueue_scripts', function(){ remove_action('wp_body_open','wp_global_styles_render_svg_filters'); }, 0);

add_action('after_setup_theme', function(){
  // Avoid block styles/widgets if theme asks for them
  remove_theme_support('widgets-block-editor');
  remove_theme_support('wp-block-styles');
}, 99);