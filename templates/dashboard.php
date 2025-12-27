<?php
/**
 * My Account: Trading Dashboard
 *
 * Enhanced dashboard with role-based personalization and demo-to-live integration.
 * - Novice traders: Onboarding tutorials and getting started guide
 * - Experienced traders: Performance tracking and advanced resources
 * - Affiliates: Analytics, referral tracking, and commission metrics
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

// Determine user characteristics
$is_affiliate = function_exists('fasp_is_affiliate') ? fasp_is_affiliate($user_id) : false;
$experience_level = function_exists('fasp_get_user_experience_level') ? fasp_get_user_experience_level($user_id) : 'novice';
$onboarding_checklist = function_exists('fasp_get_onboarding_checklist') ? fasp_get_onboarding_checklist($user_id) : array();
$demo_account = function_exists('fasp_get_user_demo_account') ? fasp_get_user_demo_account($user_id) : false;
$live_account = function_exists('fasp_get_user_live_account') ? fasp_get_user_live_account($user_id) : false;
$referral_stats = $is_affiliate && function_exists('fasp_get_user_referral_stats') ? fasp_get_user_referral_stats($user_id) : false;
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__( 'Trading Dashboard', 'fasp' ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf( esc_html__( 'Hello %s — welcome to your personalized trading hub.', 'fasp' ), esc_html( $current_user->display_name ?: $current_user->user_login ) ); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <?php if ($experience_level === 'novice' && !empty($onboarding_checklist)): ?>
      <!-- Onboarding Card for New Traders -->
      <div class="fasp-card fasp-card--wide">
        <h2><?php esc_html_e('🎓 Get Started with Trading', 'fasp'); ?></h2>
        <p class="fasp-muted"><?php esc_html_e('Complete these steps to unlock your full trading potential:', 'fasp'); ?></p>
        <ul class="fasp-checklist">
          <?php foreach ($onboarding_checklist as $key => $completed): ?>
            <li class="<?php echo $completed ? 'fasp-checked' : 'fasp-unchecked'; ?>">
              <?php 
              $labels = array(
                'complete_profile' => __('Complete your profile', 'fasp'),
                'verify_email' => __('Verify your email address', 'fasp'),
                'connect_platform' => __('Connect your first trading platform', 'fasp'),
                'complete_tutorial' => __('Complete the trading tutorial', 'fasp'),
                'make_first_trade' => __('Execute your first trade', 'fasp'),
              );
              echo esc_html($labels[$key] ?? $key);
              ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p>
          <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Start Now', 'fasp'); ?></a>
        </p>
      </div>
    <?php endif; ?>

    <?php if ($demo_account || $live_account): ?>
      <!-- Demo-to-Live Performance Widget -->
      <div class="fasp-card fasp-card--wide">
        <h2><?php esc_html_e('📊 Trading Performance', 'fasp'); ?></h2>
        <div class="fasp-performance-grid">
          <?php if ($demo_account): ?>
            <div class="fasp-performance-card fasp-demo">
              <h3><?php esc_html_e('Demo Account', 'fasp'); ?></h3>
              <div class="fasp-stat-large">
                <?php echo esc_html(isset($demo_account['balance']) ? '$' . number_format($demo_account['balance'], 2) : 'N/A'); ?>
              </div>
              <p class="fasp-muted"><?php esc_html_e('Practice Balance', 'fasp'); ?></p>
              <?php if (isset($demo_account['profit_loss'])): ?>
                <div class="fasp-stat-small <?php echo $demo_account['profit_loss'] >= 0 ? 'fasp-positive' : 'fasp-negative'; ?>">
                  <?php echo $demo_account['profit_loss'] >= 0 ? '▲' : '▼'; ?>
                  <?php echo esc_html(number_format(abs($demo_account['profit_loss']), 2)); ?>%
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($live_account): ?>
            <div class="fasp-performance-card fasp-live">
              <h3><?php esc_html_e('Live Account', 'fasp'); ?></h3>
              <div class="fasp-stat-large">
                <?php echo esc_html(isset($live_account['balance']) ? '$' . number_format($live_account['balance'], 2) : 'N/A'); ?>
              </div>
              <p class="fasp-muted"><?php esc_html_e('Real Balance', 'fasp'); ?></p>
              <?php if (isset($live_account['profit_loss'])): ?>
                <div class="fasp-stat-small <?php echo $live_account['profit_loss'] >= 0 ? 'fasp-positive' : 'fasp-negative'; ?>">
                  <?php echo $live_account['profit_loss'] >= 0 ? '▲' : '▼'; ?>
                  <?php echo esc_html(number_format(abs($live_account['profit_loss']), 2)); ?>%
                </div>
              <?php endif; ?>
            </div>
          <?php elseif ($demo_account): ?>
            <div class="fasp-performance-card fasp-upgrade">
              <h3><?php esc_html_e('Ready to Go Live?', 'fasp'); ?></h3>
              <p class="fasp-muted"><?php esc_html_e('You have practiced enough. Open a live account to start real trading.', 'fasp'); ?></p>
              <p>
                <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Open Live Account', 'fasp'); ?></a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($is_affiliate && $referral_stats): ?>
      <!-- Affiliate Analytics Widget -->
      <div class="fasp-card fasp-card--wide">
        <h2><?php esc_html_e('💼 Affiliate Performance', 'fasp'); ?></h2>
        <p class="fasp-muted"><?php esc_html_e('Track your referrals and commission earnings:', 'fasp'); ?></p>
        <div class="fasp-stats-grid">
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html($referral_stats['total_referrals']); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Total Referrals', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html($referral_stats['active_referrals']); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Active', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html($referral_stats['total_clicks']); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Total Clicks', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html(number_format($referral_stats['conversion_rate'], 1)); ?>%</div>
            <div class="fasp-stat-label"><?php esc_html_e('Conversion Rate', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box fasp-stat-highlight">
            <div class="fasp-stat-value">$<?php echo esc_html(number_format($referral_stats['total_commission'], 2)); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Total Commission', 'fasp'); ?></div>
          </div>
        </div>
        <p>
          <a class="button button-primary" href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('View Detailed Analytics', 'fasp'); ?></a>
        </p>
      </div>
    <?php endif; ?>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Platforms', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Available trading platforms and setup instructions.', 'fasp'); ?></p>
      <p><a href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('View Platforms', 'fasp'); ?> →</a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Resources', 'fasp'); ?></h3>
      <p class="fasp-muted">
        <?php 
        if ($experience_level === 'novice') {
          esc_html_e('Beginner guides and tutorials to get you started.', 'fasp');
        } else {
          esc_html_e('Advanced strategies and market analysis.', 'fasp');
        }
        ?>
      </p>
      <p><a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Browse Resources', 'fasp'); ?> →</a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Coaching', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Book sessions with our coaches to accelerate your growth.', 'fasp'); ?></p>
      <p><a href="<?php echo esc_url( $account_url('coaches') ); ?>"><?php esc_html_e('Meet Coaches', 'fasp'); ?> →</a></p>
    </div>

    <?php if ($experience_level !== 'novice'): ?>
      <div class="fasp-card fasp-card--half">
        <h3><?php esc_html_e('Advanced Tools', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php esc_html_e('Pro trading tools and market insights for experienced traders.', 'fasp'); ?></p>
        <p><a href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('Access Tools', 'fasp'); ?> →</a></p>
      </div>
    <?php endif; ?>

    <div class="fasp-card fasp-card--wide">
      <h3><?php esc_html_e('Quick Actions', 'fasp'); ?></h3>
      <div class="fasp-grid-mini">
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('Dashboard', 'fasp'); ?></a>
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Platforms', 'fasp'); ?></a>
        <?php if ( $is_affiliate ): ?>
          <a class="fasp-qa" href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('My Referrals', 'fasp'); ?></a>
          <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Affiliate Tools', 'fasp'); ?></a>
        <?php endif; ?>
        <?php if ( current_user_can('manage_options') ): ?>
          <a class="fasp-qa" href="<?php echo esc_url( admin_url('admin.php?page=fasp_platform_gating') ); ?>"><?php esc_html_e('Admin Settings', 'fasp'); ?></a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>