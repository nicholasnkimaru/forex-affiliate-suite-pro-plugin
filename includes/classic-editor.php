<?php
if (!defined('ABSPATH')) exit;
// Classic editor globally
add_filter('use_block_editor_for_post', '__return_false', 100);
add_filter('use_block_editor_for_post_type', '__return_false', 100);
add_filter('gutenberg_use_widgets_block_editor', '__return_false', 100);
add_filter('use_widgets_block_editor', '__return_false', 100);
add_filter('wp_use_inline_editor', '__return_false', 100);
