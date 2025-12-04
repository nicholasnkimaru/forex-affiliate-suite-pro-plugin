<?php
/**
 * Frontend: WooCommerce My Account – Forex Affiliate / Trading Dashboard
 *
 * This template is intentionally compact and responsive. Cards use the
 * fasp-dashboard / fasp-card classes defined in assets/css/fasp-dashboard.css.
 *
 * Save this file to templates/dashboard.php (already present). After any rewrite
 * changes, flush permalinks (WP Admin → Settings → Permalinks → Save).
 */

if (!defined('ABSPATH')) exit;
$current_user = wp_get_current_user();
$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  // Fallback: try my-account base
  return esc_url( home_url( '/my-account/' ) . $endpoint . '/' );
};
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__( 'Forex Trading Dashboard', 'fasp' ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf(esc_html__('Hello %s — welcome to your trading dashboard. Use the links below to access platforms, resources, and coaches.', 'fasp'), esc_html( $current_user->display_name ?: $current_user->user_login )); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Get Started', 'fasp'); ?></h2>
      <p class="fasp-muted">Follow the checklist: Open an account with a supported broker, verify your account, and access beginner resources.</p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Open a Broker Account', 'fasp'); ?></a>
        <a class="button" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('View Dashboard Overview', 'fasp'); ?></a>
      </p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Platforms', 'fasp'); ?></h3>
      <p class="fasp-muted">See available trading platforms and open your affiliate link.</p>
      <p><a href="<?php echo esc_url( $account_url('platforms') ); ?>"><?php esc_html_e('Open Platform List', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Resources', 'fasp'); ?></h3>
      <p class="fasp-muted">Guides, eBooks and landing pages — gated until you complete the steps.</p>
      <p><a href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Browse Resources', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Coaches', 'fasp'); ?></h3>
      <p class="fasp-muted">Find and book coaching sessions.</p>
      <p><a href="<?php echo esc_url( $account_url('coaches') ); ?>"><?php esc_html_e('Meet Coaches', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('My Referrals', 'fasp'); ?></h3>
      <p class="fasp-muted">Track affiliate clicks and commissions (if enabled).</p>
      <p><a href="<?php echo esc_url( $account_url('referrals') ); ?>"><?php esc_html_e('View Referrals', 'fasp'); ?></a></p>
    </div>

    <div class="fasp-card fasp-card--wide">
      <h3><?php esc_html_e('Quick Actions', 'fasp'); ?></h3>
      <div class="fasp-grid-mini">
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-affiliate') ); ?>"><?php esc_html_e('Open Affiliate Link', 'fasp'); ?></a>
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('View Performance', 'fasp'); ?></a>
        <a class="fasp-qa" href="<?php echo esc_url( admin_url('admin.php?page=fasp_platform_gating') ); ?>"><?php esc_html_e('Gating Settings (admin)', 'fasp'); ?></a>
      </div>
    </div>

  </div> <!-- .fasp-dashboard -->
</div>

<style>
/* Small inline safety styles so the dashboard doesn't render huge before CSS loads */
.fasp-dashboard { display:flex; flex-wrap:wrap; gap:16px; }
.fasp-card { padding:12px; border-radius:8px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.fasp-card--half { flex: 1 1 calc(50% - 16px); max-width: calc(50% - 16px); }
.fasp-card--wide { flex: 1 1 100%; max-width:100%; }
@media (max-width:900px) {
  .fasp-card--half { flex:1 1 100%; max-width:100%; }
}
.fasp-dashboard-header h1 { margin:0 0 8px 0; font-size:1.6rem; }
.fasp-muted { color:#6b6b6b; }
.fasp-grid-mini { display:flex; gap:8px; flex-wrap:wrap; }
.fasp-qa { display:inline-block; padding:6px 10px; background:#f4f4f4; border-radius:6px; text-decoration:none; color:#111; }
</style>
?>
