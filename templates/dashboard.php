<?php
/**
 * My Account: Trading Dashboard
 *
 * Enhanced dashboard with user segmentation, demo/live tracking,
 * progress tracking, and role-specific content.
 */

if (!defined('ABSPATH')) exit;
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url( home_url( '/my-account/' ) . $endpoint . '/' );
};

// Determine affiliate access (admin OR role OR usermeta)
$is_affiliate = false;
if ( $current_user && $current_user->ID ) {
  if ( current_user_can('manage_options') ) {
    $is_affiliate = true;
  } elseif ( in_array( 'affiliate', (array) $current_user->roles, true ) ) {
    $is_affiliate = true;
  } elseif ( get_user_meta( $current_user->ID, 'fasp_is_affiliate', true ) ) {
    $is_affiliate = true;
  }
}

// Get user segment and progress
$user_segment = function_exists('fasp_get_user_segment') ? fasp_get_user_segment($user_id) : 'regular';
$user_progress = function_exists('fasp_get_user_progress') ? fasp_get_user_progress($user_id) : 0;
$onboarding_checklist = function_exists('fasp_get_onboarding_checklist') ? fasp_get_onboarding_checklist($user_id) : array();

// Get demo and live trade stats
$demo_stats = function_exists('fasp_get_demo_trade_stats') ? fasp_get_demo_trade_stats($user_id) : array();
$live_stats = function_exists('fasp_get_live_trade_stats') ? fasp_get_live_trade_stats($user_id) : array();
$show_demo_cta = function_exists('fasp_should_show_demo_to_live_cta') ? fasp_should_show_demo_to_live_cta($user_id) : false;
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__( 'Trading Dashboard', 'fasp' ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf( esc_html__( 'Hello %s — welcome. Use the links below to open accounts, learn about platforms and book coaching sessions.', 'fasp' ), esc_html( $current_user->display_name ?: $current_user->user_login ) ); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <?php if ($user_segment === 'novice' && !empty($onboarding_checklist)): ?>
      <!-- Onboarding Progress for Novices -->
      <div class="fasp-card fasp-card--wide fasp-onboarding-card">
        <h2><?php esc_html_e('Welcome! Let\'s Get You Started', 'fasp'); ?></h2>
        <div class="fasp-progress-container">
          <div class="fasp-progress-bar">
            <div class="fasp-progress-fill" style="width: <?php echo intval($user_progress); ?>%"></div>
          </div>
          <span class="fasp-progress-text"><?php echo intval($user_progress); ?>% <?php esc_html_e('Complete', 'fasp'); ?></span>
        </div>
        <ul class="fasp-checklist">
          <?php foreach ($onboarding_checklist as $item): ?>
            <li class="<?php echo $item['completed'] ? 'completed' : ''; ?>">
              <span class="fasp-check-icon"><?php echo $item['completed'] ? '✅' : '⬜'; ?></span>
              <?php echo esc_html($item['label']); ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($user_progress >= 80): ?>
          <div class="fasp-motivational-nudge">
            <p><?php esc_html_e('🎉 You\'re almost there! Complete the remaining steps to unlock all features.', 'fasp'); ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($demo_stats) || !empty($live_stats)): ?>
      <!-- Demo vs Live Trade Comparison Widget -->
      <div class="fasp-card fasp-card--wide fasp-trade-comparison">
        <h2><?php esc_html_e('Trading Performance', 'fasp'); ?></h2>
        <div class="fasp-trade-stats-grid">
          <div class="fasp-trade-stat-box fasp-demo">
            <h3><?php esc_html_e('Demo Account', 'fasp'); ?></h3>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Total Trades:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo intval($demo_stats['total_trades'] ?? 0); ?></span>
            </div>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Win Rate:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo intval($demo_stats['win_rate'] ?? 0); ?>%</span>
            </div>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Total Profit:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo esc_html(number_format($demo_stats['total_profit'] ?? 0, 2)); ?></span>
            </div>
          </div>
          <div class="fasp-trade-stat-box fasp-live">
            <h3><?php esc_html_e('Live Account', 'fasp'); ?></h3>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Total Trades:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo intval($live_stats['total_trades'] ?? 0); ?></span>
            </div>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Win Rate:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo intval($live_stats['win_rate'] ?? 0); ?>%</span>
            </div>
            <div class="fasp-stat-row">
              <span class="fasp-stat-label"><?php esc_html_e('Total Profit:', 'fasp'); ?></span>
              <span class="fasp-stat-value"><?php echo esc_html(number_format($live_stats['total_profit'] ?? 0, 2)); ?></span>
            </div>
          </div>
        </div>
        <?php if ($show_demo_cta): ?>
          <div class="fasp-demo-to-live-cta">
            <p class="fasp-cta-message">
              <strong><?php esc_html_e('Great job on your demo trades!', 'fasp'); ?></strong><br>
              <?php esc_html_e('You\'re ready to transition to live trading. Open a live account to start trading with real money.', 'fasp'); ?>
            </p>
            <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>">
              <?php esc_html_e('🚀 Switch to Live Trading', 'fasp'); ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Get Started', 'fasp'); ?></h2>
      <p class="fasp-muted">Open a broker account and follow the onboarding checklist to access resources.</p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Open / Connect Account', 'fasp'); ?></a>
        <?php if ( $is_affiliate ): // only affiliates see affiliate-specific CTA ?>
          <a class="button" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Affiliate Tools', 'fasp'); ?></a>
        <?php endif; ?>
      </p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Platforms', 'fasp'); ?></h3>
      <p class="fasp-muted">Available trading platforms and setup instructions.</p>
      <p><a href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('View Platforms', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Resources', 'fasp'); ?></h3>
      <p class="fasp-muted">Guides, onboarding materials and FAQ.</p>
      <p><a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Browse Resources', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Coaching', 'fasp'); ?></h3>
      <p class="fasp-muted">Book sessions with our coaches to get started faster.</p>
      <p><a href="<?php echo esc_url( $account_url('coaches') ); ?>"><?php esc_html_e('Meet Coaches', 'fasp'); ?></a></p>
    </div>

    <?php if ( $is_affiliate ): ?>
      <!-- Affiliate-Specific Dashboard Section -->
      <div class="fasp-card fasp-card--wide fasp-affiliate-section">
        <h2><?php esc_html_e('Affiliate Dashboard', 'fasp'); ?></h2>
        <div class="fasp-affiliate-grid">
          <div class="fasp-affiliate-stat">
            <h4><?php esc_html_e('Total Referrals', 'fasp'); ?></h4>
            <p class="fasp-stat-large"><?php echo intval(get_user_meta($user_id, 'fasp_total_referrals', true)); ?></p>
          </div>
          <div class="fasp-affiliate-stat">
            <h4><?php esc_html_e('Total Earnings', 'fasp'); ?></h4>
            <p class="fasp-stat-large">$<?php echo number_format(floatval(get_user_meta($user_id, 'fasp_total_earnings', true)), 2); ?></p>
          </div>
          <div class="fasp-affiliate-stat">
            <h4><?php esc_html_e('This Month', 'fasp'); ?></h4>
            <p class="fasp-stat-large">$<?php echo number_format(floatval(get_user_meta($user_id, 'fasp_month_earnings', true)), 2); ?></p>
          </div>
        </div>
        <p class="fasp-affiliate-actions">
          <a class="button" href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('View Details', 'fasp'); ?></a>
          <a class="button button-primary" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Get Affiliate Links', 'fasp'); ?></a>
        </p>
      </div>
    <?php endif; ?>

    <!-- Educational Resources Sidebar -->
    <div class="fasp-card fasp-card--wide fasp-education-panel">
      <h3><?php esc_html_e('Educational Resources', 'fasp'); ?></h3>
      <div class="fasp-education-grid">
        <div class="fasp-education-item">
          <h4>📚 <?php esc_html_e('Tutorials', 'fasp'); ?></h4>
          <p class="fasp-muted"><?php esc_html_e('Step-by-step guides for beginners', 'fasp'); ?></p>
          <a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Browse Tutorials', 'fasp'); ?></a>
        </div>
        <div class="fasp-education-item">
          <h4>❓ <?php esc_html_e('FAQ', 'fasp'); ?></h4>
          <p class="fasp-muted"><?php esc_html_e('Common questions answered', 'fasp'); ?></p>
          <a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('View FAQs', 'fasp'); ?></a>
        </div>
        <div class="fasp-education-item">
          <h4>📰 <?php esc_html_e('Market News', 'fasp'); ?></h4>
          <p class="fasp-muted"><?php esc_html_e('Stay updated with latest trends', 'fasp'); ?></p>
          <a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Read News', 'fasp'); ?></a>
        </div>
      </div>
    </div>

    <div class="fasp-card fasp-card--wide">
      <h3><?php esc_html_e('Quick Actions', 'fasp'); ?></h3>
      <div class="fasp-grid-mini">
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('View Performance', 'fasp'); ?></a>
        <?php if ( $is_affiliate ): ?>
          <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Affiliate Tools', 'fasp'); ?></a>
          <a class="fasp-qa" href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('My Referrals', 'fasp'); ?></a>
        <?php endif; ?>

        <?php if ( current_user_can('manage_options') ): // admin-only ?>
          <a class="fasp-qa" href="<?php echo esc_url( admin_url('admin.php?page=fasp_platform_gating') ); ?>"><?php esc_html_e('Gating Settings (admin)', 'fasp'); ?></a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>