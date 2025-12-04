<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue Select2 (CDN) + plugin admin script for the Geo Gating admin page.
 *
 * Save as: includes/geo-gating-assets.php
 * Then ensure this file is required/loaded by your admin bootstrap (includes/admin-bootstrap.php)
 * or the main plugin file so it runs in the admin context.
 */

add_action('admin_enqueue_scripts', function($hook_suffix) {
  // Only enqueue on our Geo Gating admin page
  // The geo-gating page uses the page slug 'fasp_geo_gating' (query var 'page')
  if (empty($_GET['page']) || $_GET['page'] !== 'fasp_geo_gating') {
    return;
  }

  // Base URL to plugin root (assets path)
  $base = plugin_dir_url( dirname( __FILE__ ) );

  // Register Select2 from CDN (stable version)
  wp_register_style( 'fasp-select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13' );
  wp_register_script( 'fasp-select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true );

  // Enqueue Select2 + plugin admin styles + custom init script
  wp_enqueue_style( 'fasp-select2-css' );
  wp_enqueue_script( 'fasp-select2-js' );

  // Enqueue plugin admin CSS (if you want select2 to match admin styles)
  wp_enqueue_style( 'fasp-admin-css', $base . 'assets/css/fasp-admin.css', array(), '1.0.0' );

  // Custom admin script that initializes Select2 (depends on select2)
  wp_enqueue_script( 'fasp-geo-admin-select2', $base . 'assets/js/geo-admin-select2.js', array('jquery','fasp-select2-js'), '1.0.0', true );

  // Optional: localize strings if needed
  wp_localize_script( 'fasp-geo-admin-select2', 'FASP_GEO', array(
    'selectAllLabel' => __('Select all', 'fasp'),
    'clearAllLabel'  => __('Clear', 'fasp'),
  ) );
});
?>
