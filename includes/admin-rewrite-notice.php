<?php
/**
 * Admin notice for flushing rewrite rules after update
 */

if (!defined('ABSPATH')) exit;

// Add admin notice to flush rewrite rules
add_action('admin_notices', function() {
  // Only show to admins
  if (!current_user_can('manage_options')) {
    return;
  }
  
  // Check if we need to show the notice
  $dismissed = get_option('fasp_rewrite_flush_dismissed_r148', false);
  
  if ($dismissed) {
    return;
  }
  
  // Check if user clicked flush
  if (isset($_GET['fasp_flush_rewrites']) && $_GET['fasp_flush_rewrites'] === '1') {
    check_admin_referer('fasp_flush_rewrites');
    flush_rewrite_rules();
    update_option('fasp_rewrite_flush_dismissed_r148', true);
    echo '<div class="notice notice-success is-dismissible"><p>';
    echo esc_html__('Rewrite rules flushed successfully! Dashboard endpoints are now active.', 'fasp');
    echo '</p></div>';
    return;
  }
  
  // Check if user dismissed
  if (isset($_GET['fasp_dismiss_flush']) && $_GET['fasp_dismiss_flush'] === '1') {
    check_admin_referer('fasp_dismiss_flush');
    update_option('fasp_rewrite_flush_dismissed_r148', true);
    return;
  }
  
  $flush_url = wp_nonce_url(add_query_arg('fasp_flush_rewrites', '1'), 'fasp_flush_rewrites');
  $dismiss_url = wp_nonce_url(add_query_arg('fasp_dismiss_flush', '1'), 'fasp_dismiss_flush');
  
  echo '<div class="notice notice-info is-dismissible">';
  echo '<p><strong>' . esc_html__('FASP Dashboard Update', 'fasp') . '</strong></p>';
  echo '<p>' . esc_html__('New dashboard endpoints have been added (Affiliate Tools, Referrals, Platforms, Resources, Coaches). Please flush rewrite rules to activate them.', 'fasp') . '</p>';
  echo '<p>';
  echo '<a href="' . esc_url($flush_url) . '" class="button button-primary">' . esc_html__('Flush Rewrite Rules', 'fasp') . '</a> ';
  echo '<a href="' . esc_url($dismiss_url) . '" class="button">' . esc_html__('Dismiss', 'fasp') . '</a>';
  echo '</p>';
  echo '</div>';
});
