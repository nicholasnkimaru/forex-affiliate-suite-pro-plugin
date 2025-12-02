<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin Menu Hub (Unified) — r14.2
 */
add_action('admin_menu', function() {
    $cap = 'manage_options';
    $parent_slug = 'fasp_hub';
    add_menu_page(
        __('Forex Affiliate', 'fasp'),
        __('Forex Affiliate', 'fasp'),
        $cap,
        $parent_slug,
        'fasp_admin_hub_screen',
        'dashicons-chart-line',
        56
    );

    $menus = [
        ['fasp_getting_started', __('Getting Started', 'fasp'), 'fasp_admin_getting_started_screen'],
        ['fasp_user_dashboard', __('User Dashboard', 'fasp'), 'fasp_admin_user_dashboard_screen'],
        ['fasp_payments', __('Payments', 'fasp'), 'fasp_admin_payments_screen'],
        ['fasp_tools', __('Tools', 'fasp'), 'fasp_admin_tools_screen'],
        ['fasp_backup', __('Settings Backup', 'fasp'), 'fasp_admin_backup_screen'],
    ];
    foreach ($menus as $m) {
        add_submenu_page($parent_slug, $m[1], $m[1], $cap, $m[0], $m[2]);
    }
}, 9);

function fasp_admin_hub_screen() {
    echo '<div class="wrap"><h1>Forex Affiliate — Overview</h1>';
    echo '<p>Welcome to the unified admin hub (r14.2). Use the left menu to configure payments, tools, and dashboards.</p>';
    echo '<p><a class="button button-primary" href="'.admin_url('admin.php?page=fasp_payments').'">Configure Payments</a> ';
    echo '<a class="button" href="'.admin_url('admin.php?page=fasp_tools').'">Open Tools</a></p>';
    echo '</div>';
}

function fasp_admin_getting_started_screen() {
    echo '<div class="wrap"><h1>Getting Started</h1><ol>';
    echo '<li>Set up your payment gateways under <strong>Payments</strong>.</li>';
    echo '<li>Create your onboarding page and insert shortcode <code>[fasp_onboarding]</code>.</li>';
    echo '<li>Add checkout buttons with <code>[fasp_checkout]</code>.</li>';
    echo '<li>Open <strong>User Dashboard</strong> to monitor conversions.</li>';
    echo '</ol></div>';
}

function fasp_admin_user_dashboard_screen() {
    echo '<div class="wrap"><h1>User Conversion Dashboard</h1>';
    echo '<div id="fasp-metrics"><p>Metrics will appear here. (Empty state on fresh sites.)</p></div>';
    echo '</div>';
}

function fasp_admin_tools_screen() {
    echo '<div class="wrap"><h1>Tools</h1>';
    echo '<h2>UTM Builder</h2>';
    echo '<p>Compose tracking links with UTM parameters.</p>';
    echo '<h2>Export CSV</h2><p>Export logs for the last 30 days.</p>';
    echo '</div>';
}

function fasp_admin_backup_screen() {
    if (!current_user_can('manage_options')) return;
    $opt = get_option('fasp_payments', []);
    $json = wp_json_encode($opt, JSON_PRETTY_PRINT);
    echo '<div class="wrap"><h1>Settings Backup</h1>';
    echo '<textarea rows="15" style="width:100%;">'.esc_textarea($json).'</textarea>';
    echo '</div>';
}
