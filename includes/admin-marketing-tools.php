<?php
/**
 * Admin UI to inspect/flush/export the conversion queue
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_submenu_page('fasp_hub','Marketing Tools','Marketing Tools','manage_options','fasp_marketing_tools','fasp_admin_marketing_tools_page');
});

function fasp_admin_marketing_tools_page() {
    if (!current_user_can('manage_options')) return;
    $queue = get_option('fasp_conversion_queue', []);
    $count = is_array($queue) ? count($queue) : 0;

    echo '<div class="wrap"><h1>Marketing Tools</h1>';
    echo '<p>Conversion queue size: <strong>' . intval($count) . '</strong></p>';

    $flush_url = esc_url( add_query_arg(['action'=>'fasp_marketing_flush','_wpnonce'=>wp_create_nonce('fasp_marketing_flush')], admin_url('admin-post.php')) );
    $export_url = esc_url( add_query_arg(['action'=>'fasp_marketing_export','_wpnonce'=>wp_create_nonce('fasp_marketing_export')], admin_url('admin-post.php')) );

    echo '<p><a class="button button-secondary" href="'. $flush_url .'">Flush queue (attempt delivery)</a> ';
    echo '<a class="button" href="'. $export_url .'">Export queued events (JSON)</a></p>';

    if ($count>0) {
        echo '<h2>Sample queued event</h2><pre style="max-height:300px;overflow:auto;">' . esc_html( wp_json_encode( $queue[0], JSON_PRETTY_PRINT ) ) . '</pre>';
    }
    echo '</div>';
}

add_action('admin_post_fasp_marketing_export', function() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'fasp_marketing_export')) wp_die('Unauthorized');
    $queue = get_option('fasp_conversion_queue', []);
    header('Content-Type: application/json');
    echo wp_json_encode($queue, JSON_PRETTY_PRINT);
    exit;
});

add_action('admin_post_fasp_marketing_flush', function() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'fasp_marketing_flush')) wp_die('Unauthorized');
    // Trigger the queue processor (blocking)
    if (function_exists('fasp_process_conversion_queue')) {
        fasp_process_conversion_queue();
    }
    wp_redirect(admin_url('admin.php?page=fasp_marketing_tools&flushed=1'));
    exit;
});