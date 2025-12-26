<?php
/**
 * Referrals Tracking Template
 *
 * Displays user referral statistics, clicks, conversions, and earnings.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Check if user is affiliate
$is_affiliate = false;
if ($current_user && $current_user->ID) {
  if (current_user_can('manage_options')) {
    $is_affiliate = true;
  } elseif (in_array('affiliate', (array) $current_user->roles, true)) {
    $is_affiliate = true;
  } elseif (get_user_meta($current_user->ID, 'fasp_is_affiliate', true)) {
    $is_affiliate = true;
  }
}

if (!$is_affiliate) {
  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Referrals', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('This page is only available to affiliates.', 'fasp') . '</p>';
  echo '</div>';
  return;
}

// Get tracking data from database
global $wpdb;
$click_table = $wpdb->prefix . 'fasp_clicks';

// Get user's referral code
$referral_code = get_user_meta($current_user->ID, 'fasp_referral_code', true);

// Get click statistics
$total_clicks = 0;
$total_conversions = 0;
$recent_clicks = [];

if ($wpdb->get_var("SHOW TABLES LIKE '$click_table'") == $click_table) {
  $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $click_table WHERE user_id = %d",
    $current_user->ID
  ));
  
  $recent_clicks = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $click_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
    $current_user->ID
  ), ARRAY_A);
  
  $total_conversions = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $click_table WHERE user_id = %d AND action = 'conversion'",
    $current_user->ID
  ));
}

// Calculate earnings (placeholder - would integrate with real payment system)
$total_earnings = $total_conversions * 10; // $10 per conversion example
$pending_payout = $total_earnings * 0.5;
$paid_out = $total_earnings * 0.5;

$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url(home_url('/my-account/') . $endpoint . '/');
};

?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Referral Statistics', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo sprintf(esc_html__('Track your referral performance and earnings, %s.', 'fasp'), esc_html($current_user->display_name ?: $current_user->user_login)); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <!-- Summary Stats -->
    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Total Clicks', 'fasp'); ?></h3>
      <p style="font-size: 2.5em; font-weight: 700; margin: 12px 0; color: #06b6d4;">
        <?php echo number_format($total_clicks); ?>
      </p>
      <p class="fasp-muted"><?php esc_html_e('All-time referral link clicks', 'fasp'); ?></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Conversions', 'fasp'); ?></h3>
      <p style="font-size: 2.5em; font-weight: 700; margin: 12px 0; color: #10b981;">
        <?php echo number_format($total_conversions); ?>
      </p>
      <p class="fasp-muted"><?php esc_html_e('Successful referral conversions', 'fasp'); ?></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Conversion Rate', 'fasp'); ?></h3>
      <p style="font-size: 2.5em; font-weight: 700; margin: 12px 0; color: #8b5cf6;">
        <?php echo $total_clicks > 0 ? number_format(($total_conversions / $total_clicks) * 100, 1) : '0'; ?>%
      </p>
      <p class="fasp-muted"><?php esc_html_e('Percentage of clicks that converted', 'fasp'); ?></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Total Earnings', 'fasp'); ?></h3>
      <p style="font-size: 2.5em; font-weight: 700; margin: 12px 0; color: #f59e0b;">
        $<?php echo number_format($total_earnings, 2); ?>
      </p>
      <p class="fasp-muted"><?php esc_html_e('All-time commission earnings', 'fasp'); ?></p>
    </div>

    <!-- Earnings Breakdown -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Earnings Summary', 'fasp'); ?></h2>
      <div style="display: flex; gap: 24px; margin-top: 16px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
          <div style="font-size: 14px; color: #666; margin-bottom: 4px;">
            <?php esc_html_e('Pending Payout', 'fasp'); ?>
          </div>
          <div style="font-size: 1.8em; font-weight: 600; color: #f59e0b;">
            $<?php echo number_format($pending_payout, 2); ?>
          </div>
        </div>
        <div style="flex: 1; min-width: 200px;">
          <div style="font-size: 14px; color: #666; margin-bottom: 4px;">
            <?php esc_html_e('Paid Out', 'fasp'); ?>
          </div>
          <div style="font-size: 1.8em; font-weight: 600; color: #10b981;">
            $<?php echo number_format($paid_out, 2); ?>
          </div>
        </div>
        <div style="flex: 1; min-width: 200px;">
          <div style="font-size: 14px; color: #666; margin-bottom: 4px;">
            <?php esc_html_e('Average Per Conversion', 'fasp'); ?>
          </div>
          <div style="font-size: 1.8em; font-weight: 600; color: #06b6d4;">
            $<?php echo $total_conversions > 0 ? number_format($total_earnings / $total_conversions, 2) : '0.00'; ?>
          </div>
        </div>
      </div>
      <p style="margin-top: 16px;">
        <a class="button button-primary" href="<?php echo esc_url($account_url('forex-affiliate')); ?>">
          <?php esc_html_e('Get Referral Links', 'fasp'); ?>
        </a>
      </p>
    </div>

    <!-- Recent Activity -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Recent Referral Activity', 'fasp'); ?></h2>
      
      <?php if (!empty($recent_clicks)): ?>
        <table class="shop_table" style="width: 100%; margin-top: 16px;">
          <thead>
            <tr>
              <th><?php esc_html_e('Date', 'fasp'); ?></th>
              <th><?php esc_html_e('Platform', 'fasp'); ?></th>
              <th><?php esc_html_e('Action', 'fasp'); ?></th>
              <th><?php esc_html_e('IP Address', 'fasp'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_clicks as $click): ?>
              <tr>
                <td><?php echo esc_html(date('M j, Y H:i', strtotime($click['created_at']))); ?></td>
                <td><?php echo esc_html($click['platform']); ?></td>
                <td>
                  <span style="padding: 3px 8px; border-radius: 3px; font-size: 12px; 
                               background: <?php echo $click['action'] === 'conversion' ? '#d1fae5' : '#dbeafe'; ?>;
                               color: <?php echo $click['action'] === 'conversion' ? '#065f46' : '#1e3a8a'; ?>;">
                    <?php echo esc_html(ucfirst($click['action'])); ?>
                  </span>
                </td>
                <td><?php echo esc_html($click['ip']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="margin-top: 16px; color: #666;">
          <?php esc_html_e('No referral activity yet. Start sharing your referral links!', 'fasp'); ?>
        </p>
      <?php endif; ?>
    </div>

  </div>
</div>
