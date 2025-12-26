<?php
/**
 * My Account: Trading Dashboard
 *
 * Neutral, professional dashboard for users. Affiliate-specific UI is shown
 * only to affiliates/admins (wrapped in $is_affiliate checks).
 */

if (!defined('ABSPATH')) exit;
$current_user = wp_get_current_user();

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
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__( 'Trading Dashboard', 'fasp' ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf( esc_html__( 'Hello %s — welcome. Use the links below to open accounts, learn about platforms and book coaching sessions.', 'fasp' ), esc_html( $current_user->display_name ?: $current_user->user_login ) ); ?></p>
  </header>

  <?php
  // Get user statistics
  global $wpdb;
  $click_table = $wpdb->prefix . 'fasp_clicks';
  $total_clicks = 0;
  $total_conversions = 0;
  $recent_activity_count = 0;
  
  if ($wpdb->get_var("SHOW TABLES LIKE '$click_table'") == $click_table) {
    $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $click_table WHERE user_id = %d",
      $current_user->ID
    ));
    
    $total_conversions = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $click_table WHERE user_id = %d AND action = 'conversion'",
      $current_user->ID
    ));
    
    $recent_activity_count = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $click_table WHERE user_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
      $current_user->ID
    ));
  }
  
  // Get connected platforms count
  $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];
  $connected_platforms = 0;
  foreach ($platforms as $platform_key => $platform) {
    if (function_exists('fasp_is_user_verified_for_platform') && 
        fasp_is_user_verified_for_platform($current_user->ID, $platform_key)) {
      $connected_platforms++;
    }
  }
  
  // Calculate earnings (for affiliates)
  $total_earnings = $total_conversions * 10; // Example: $10 per conversion
  $pending_payout = $total_earnings * 0.5;
  ?>

  <div class="fasp-dashboard fasp-grid">

    <?php if ( $is_affiliate ): ?>
      <!-- Affiliate Statistics -->
      <div class="fasp-card fasp-card--half">
        <h3><?php esc_html_e('Total Clicks', 'fasp'); ?></h3>
        <p style="font-size: 2em; font-weight: 700; margin: 8px 0; color: #06b6d4;">
          <?php echo number_format($total_clicks); ?>
        </p>
        <p class="fasp-muted" style="font-size: 13px;">
          <?php echo sprintf(esc_html__('%d in the last 7 days', 'fasp'), $recent_activity_count); ?>
        </p>
      </div>

      <div class="fasp-card fasp-card--half">
        <h3><?php esc_html_e('Conversions', 'fasp'); ?></h3>
        <p style="font-size: 2em; font-weight: 700; margin: 8px 0; color: #10b981;">
          <?php echo number_format($total_conversions); ?>
        </p>
        <p class="fasp-muted" style="font-size: 13px;">
          <?php echo $total_clicks > 0 ? number_format(($total_conversions / $total_clicks) * 100, 1) . '% conversion rate' : 'No data yet'; ?>
        </p>
      </div>

      <!-- Earnings Summary -->
      <div class="fasp-card fasp-card--wide">
        <h2><?php esc_html_e('Earnings Summary', 'fasp'); ?></h2>
        <div style="display: flex; gap: 32px; margin-top: 12px; flex-wrap: wrap;">
          <div>
            <p class="fasp-muted" style="margin: 0 0 4px 0; font-size: 13px;">
              <?php esc_html_e('Total Earnings', 'fasp'); ?>
            </p>
            <p style="font-size: 1.8em; font-weight: 700; margin: 0; color: #f59e0b;">
              $<?php echo number_format($total_earnings, 2); ?>
            </p>
          </div>
          <div>
            <p class="fasp-muted" style="margin: 0 0 4px 0; font-size: 13px;">
              <?php esc_html_e('Pending Payout', 'fasp'); ?>
            </p>
            <p style="font-size: 1.8em; font-weight: 700; margin: 0; color: #f59e0b;">
              $<?php echo number_format($pending_payout, 2); ?>
            </p>
          </div>
          <div>
            <p class="fasp-muted" style="margin: 0 0 4px 0; font-size: 13px;">
              <?php esc_html_e('Connected Platforms', 'fasp'); ?>
            </p>
            <p style="font-size: 1.8em; font-weight: 700; margin: 0; color: #8b5cf6;">
              <?php echo $connected_platforms . ' / ' . count($platforms); ?>
            </p>
          </div>
        </div>
        <p style="margin-top: 16px;">
          <a class="button button-primary" href="<?php echo esc_url( $account_url('referrals') ); ?>">
            <?php esc_html_e('View Detailed Reports', 'fasp'); ?>
          </a>
          <a class="button" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>">
            <?php esc_html_e('Get Referral Links', 'fasp'); ?>
          </a>
        </p>
      </div>
    <?php else: ?>
      <!-- Regular User Welcome -->
      <div class="fasp-card fasp-card--wide">
        <h2><?php esc_html_e('Get Started', 'fasp'); ?></h2>
        <p class="fasp-muted">Open a broker account and follow the onboarding checklist to access resources.</p>
        <p style="margin-top: 16px;">
          <span style="font-size: 2em; font-weight: 700; color: #06b6d4;">
            <?php echo $connected_platforms; ?>
          </span>
          <span class="fasp-muted"> / <?php echo count($platforms); ?> <?php esc_html_e('platforms connected', 'fasp'); ?></span>
        </p>
        <p style="margin-top: 16px;">
          <a class="button button-primary" href="<?php echo esc_url( $account_url('platforms') ); ?>">
            <?php esc_html_e('Connect Platforms', 'fasp'); ?>
          </a>
        </p>
      </div>
    <?php endif; ?>

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
      <div class="fasp-card fasp-card--half">
        <h3><?php esc_html_e('Referrals & Commissions', 'fasp'); ?></h3>
        <p class="fasp-muted">Track affiliate referral clicks and commission stats (admin/affiliate only).</p>
        <p><a href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('View Referrals', 'fasp'); ?></a></p>
      </div>
    <?php endif; ?>

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

<!-- <style>
/* intermediate grey strength between the subtle and strong versions */
:root {
  --fasp-bg: #ffffff;
  --fasp-card-bg: #f6f6f6;        /* intermediate grey */
  --fasp-card-bg-wide: #f5f5f5;   /* slightly different for wide cards */
  --fasp-card-border: rgba(0,0,0,0.09);
  --fasp-card-shadow: 0 1px 4px rgba(0,0,0,0.065);
  --fasp-gap: 16px;
}

.fasp-dashboard { display:flex; flex-wrap:wrap; gap:var(--fasp-gap); align-items:stretch; }

/* base card */
.fasp-card {
  padding:18px;
  border-radius:10px;
  background:var(--fasp-card-bg);
  border:1px solid var(--fasp-card-border);
  box-shadow:var(--fasp-card-shadow);
  transition:box-shadow .12s ease, transform .06s ease;
  color: inherit;
}

/* slightly different background for full-width cards */
.fasp-card--wide { background:var(--fasp-card-bg-wide); }

/* keep halves reasonably sized */
.fasp-card--half { flex: 1 1 calc(50% - var(--fasp-gap)); max-width: calc(50% - var(--fasp-gap)); }

/* wide takes full width */
.fasp-card--wide { flex: 1 1 100%; max-width:100%; }

/* subtle interaction affordance */
.fasp-card:hover { box-shadow: 0 8px 22px rgba(0,0,0,0.09); transform: translateY(-2px); }

/* Responsive */
@media (max-width:900px) {
  .fasp-card--half { flex:1 1 100%; max-width:100%; }
}

/* Header spacing and text */
.fasp-dashboard-header { margin-bottom: 12px; }
.fasp-dashboard-header h1 { margin:0 0 8px 0; font-size:1.6rem; }
.fasp-muted { color:#606060; }

/* Quick actions small button styling */
.fasp-grid-mini { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.fasp-qa {
  display:inline-block;
  padding:7px 13px;
  background:transparent;
  border-radius:6px;
  text-decoration:none;
  color:#111;
  border:1px solid rgba(0,0,0,0.06);
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(2px);
}

/* make the QA pills slightly more visible on top of the card background */
.fasp-card--wide .fasp-qa { background: rgba(255,255,255,0.8); border-color: rgba(0,0,0,0.06); }
</style> -->