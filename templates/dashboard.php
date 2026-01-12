<?php
/**
 * My Account: Trading Dashboard (merged)
 *
 * Displays role-specific widgets: onboarding, demo/live comparison, affiliate stats.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

/* Helper to get account endpoint URL */
$account_url = function($endpoint) {
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url( home_url( '/my-account/' ) . $endpoint . '/' );
};

/* Determine affiliate access - prefer helper if available */
if (function_exists('fasp_is_affiliate')) {
  $is_affiliate = fasp_is_affiliate( $user_id );
} else {
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
}

/* Determine user experience level (novice|intermediate|experienced) */
if (function_exists('fasp_get_user_experience_level')) {
  $experience = fasp_get_user_experience_level( $user_id );
} else {
  // Fallback: simple heuristic
  $registered = strtotime( $current_user->user_registered ?: 'now' );
  $days = $registered ? ( ( time() - $registered ) / DAY_IN_SECONDS ) : 0;
  $plats = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
  $verified_count = 0;
  if ( function_exists('fasp_is_user_verified_for_platform') ) {
    foreach ( $plats as $p ) {
      if ( fasp_is_user_verified_for_platform( $user_id, sanitize_key($p['key']) ) ) {
        $verified_count++;
      }
    }
  }
  if ( $days < 30 && $verified_count === 0 ) {
    $experience = 'novice';
  } elseif ( $verified_count > 0 || $days >= 30 ) {
    $experience = 'experienced';
  } else {
    $experience = 'intermediate';
  }
}

/* Onboarding checklist - use helper if available */
$checklist = function_exists('fasp_get_onboarding_checklist') ? fasp_get_onboarding_checklist( $user_id ) : array(
  'complete_profile' => ! empty( $current_user->display_name ),
  'verify_email' => (bool) get_user_meta( $user_id, 'fasp_email_verified', true ),
  'connect_platform' => false,
  'complete_tutorial' => (bool) get_user_meta( $user_id, 'fasp_tutorial_completed', false ),
  'make_first_trade' => (bool) get_user_meta( $user_id, 'fasp_first_trade', false ),
);

/* Demo / Live accounts */
$demo_account = function_exists('fasp_get_user_demo_account') ? fasp_get_user_demo_account( $user_id ) : get_user_meta( $user_id, 'fasp_demo_account', true );
$live_account = function_exists('fasp_get_user_live_account') ? fasp_get_user_live_account( $user_id ) : get_user_meta( $user_id, 'fasp_live_account', true );

/* Referral stats for affiliates */
$referral_stats = $is_affiliate && function_exists('fasp_get_user_referral_stats') ? fasp_get_user_referral_stats( $user_id ) : array();

// Normalize demo/live arrays
if ( ! is_array( $demo_account ) ) $demo_account = is_scalar($demo_account) ? array() : array();
if ( ! is_array( $live_account ) ) $live_account = is_scalar($live_account) ? array() : array();
if ( ! is_array( $referral_stats ) ) $referral_stats = array();
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__( 'Trading Dashboard', 'fasp' ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf( esc_html__( 'Hello %s — welcome. Use the links below to open accounts, learn about platforms and book coaching sessions.', 'fasp' ), esc_html( $current_user->display_name ?: $current_user->user_login ) ); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Get Started', 'fasp'); ?></h2>
      <p class="fasp-muted"><?php esc_html_e('Open a broker account and follow the onboarding checklist to access resources.', 'fasp'); ?></p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Open / Connect Account', 'fasp'); ?></a>
        <?php if ( $is_affiliate ): // only affiliates see affiliate-specific CTA ?>
          <a class="button" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Affiliate Tools', 'fasp'); ?></a>
        <?php endif; ?>
      </p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Platforms', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Available trading platforms and setup instructions.', 'fasp'); ?></p>
      <p><a href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('View Platforms', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Resources', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Guides, onboarding materials and FAQ.', 'fasp'); ?></p>
      <p><a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Browse Resources', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Coaching', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Book a session with a coach to accelerate your progress.', 'fasp'); ?></p>
      <p><a href="<?php echo esc_url( $account_url('coaches') ); ?>"><?php esc_html_e('Browse Coaches', 'fasp'); ?></a></p>
    </div>

    <!-- Onboarding checklist for novices -->
    <?php if ( $experience === 'novice' ) : ?>
      <div class="fasp-card fasp-card--half">
        <h3><?php esc_html_e('Onboarding Checklist', 'fasp'); ?></h3>
        <ul class="fasp-checklist">
          <?php foreach ( $checklist as $key => $done ) : ?>
            <li class="<?php echo $done ? 'completed' : 'pending'; ?>">
              <span class="fasp-check-icon"><?php echo $done ? '✅' : '⬜'; ?></span>
              <?php
                $label_map = array(
                  'complete_profile' => __( 'Complete your profile', 'fasp' ),
                  'verify_email' => __( 'Verify your email address', 'fasp' ),
                  'connect_platform' => __( 'Connect a trading platform', 'fasp' ),
                  'complete_tutorial' => __( 'Complete the getting-started tutorial', 'fasp' ),
                  'make_first_trade' => __( 'Execute your first practice trade', 'fasp' ),
                );
              ?>
              <span><?php echo esc_html( $label_map[ $key ] ?? $key ); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <p><a class="button" href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('View Tutorials', 'fasp'); ?></a></p>
      </div>
    <?php endif; ?>

    <!-- Demo vs Live comparison -->
    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Demo vs Live Performance', 'fasp'); ?></h3>
      <div class="fasp-performance-grid">
        <div class="fasp-performance-card fasp-demo">
          <h4><?php esc_html_e('Demo Account', 'fasp'); ?></h4>
          <p class="fasp-stat-large"><?php echo isset($demo_account['balance']) ? esc_html( number_format_i18n( floatval($demo_account['balance']), 2 ) ) : esc_html__('N/A', 'fasp'); ?></p>
          <?php $dl = isset($demo_account['profit_loss']) ? floatval($demo_account['profit_loss']) : null; ?>
          <p class="<?php echo ($dl !== null && $dl >= 0) ? 'fasp-positive' : 'fasp-negative'; ?>">
            <?php echo ($dl !== null) ? esc_html( sprintf( '%+.2f%%', $dl ) ) : esc_html__('No data', 'fasp'); ?>
          </p>
        </div>

        <div class="fasp-performance-card fasp-live">
          <h4><?php esc_html_e('Live Account', 'fasp'); ?></h4>
          <p class="fasp-stat-large"><?php echo isset($live_account['balance']) ? esc_html( number_format_i18n( floatval($live_account['balance']), 2 ) ) : esc_html__('N/A', 'fasp'); ?></p>
          <?php $ll = isset($live_account['profit_loss']) ? floatval($live_account['profit_loss']) : null; ?>
          <p class="<?php echo ($ll !== null && $ll >= 0) ? 'fasp-positive' : 'fasp-negative'; ?>">
            <?php echo ($ll !== null) ? esc_html( sprintf( '%+.2f%%', $ll ) ) : esc_html__('No data', 'fasp'); ?>
          </p>
        </div>
      </div>

      <?php
        $should_cta = function_exists('fasp_should_show_demo_to_live_cta') ? fasp_should_show_demo_to_live_cta( $user_id ) : ( isset($demo_account['total_trades']) && empty($live_account) && intval($demo_account['total_trades']) >= 10 );
      ?>
      <?php if ( $should_cta ) : ?>
        <p><a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Upgrade to Live', 'fasp'); ?></a></p>
      <?php endif; ?>
    </div>

    <!-- Affiliate widget -->
    <?php if ( $is_affiliate ) : ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php esc_html_e('Affiliate Performance', 'fasp'); ?></h3>
        <div class="fasp-stats-grid">
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html( intval( $referral_stats['total_referrals'] ?? 0 ) ); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Total Referrals', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html( intval( $referral_stats['active_referrals'] ?? 0 ) ); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Active Referrals', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html( intval( $referral_stats['total_clicks'] ?? 0 ) ); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Clicks', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html( number_format_i18n( floatval( $referral_stats['conversion_rate'] ?? 0 ), 2 ) ); ?>%</div>
            <div class="fasp-stat-label"><?php esc_html_e('Conversion', 'fasp'); ?></div>
          </div>
          <div class="fasp-stat-box">
            <div class="fasp-stat-value"><?php echo esc_html( number_format_i18n( floatval( $referral_stats['total_commission'] ?? 0 ), 2 ) ); ?></div>
            <div class="fasp-stat-label"><?php esc_html_e('Total Commission', 'fasp'); ?></div>
          </div>
        </div>
        <p><a class="button" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Manage Referrals', 'fasp'); ?></a></p>
      </div>
    <?php endif; ?>

  </div>
</div>