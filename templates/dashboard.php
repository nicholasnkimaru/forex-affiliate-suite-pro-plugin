<?php
/**
 * My Account: Trading Dashboard (frontend)
 *
 * This template NEVER uses the "Forex Affiliate" label or shows affiliate CTAs.
 * Affiliate operations remain in WordPress admin only.
 */

if (!defined('ABSPATH')) exit;
$current_user = wp_get_current_user();

$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url( home_url( '/my-account/' ) . $endpoint . '/' );
};

// Always use neutral title on frontend
$page_title = __( 'Trading Dashboard', 'fasp' );
?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html( $page_title ); ?></h1>
    <p class="fasp-muted"><?php echo sprintf( esc_html__( 'Hello %s — welcome. Use the links below to open accounts, learn about platforms and book coaching sessions.', 'fasp' ), esc_html( $current_user->display_name ?: $current_user->user_login ) ); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Get Started', 'fasp'); ?></h2>
      <p class="fasp-muted">Open a broker account and follow the onboarding checklist to access resources.</p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('Open / Connect Account', 'fasp'); ?></a>
        <!-- Intentionally no affiliate CTA on frontend -->
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

    <div class="fasp-card fasp-card--wide">
      <h3><?php esc_html_e('Quick Actions', 'fasp'); ?></h3>
      <div class="fasp-grid-mini">
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('forex-dashboard') ); ?>"><?php esc_html_e('View Performance', 'fasp'); ?></a>
        <a class="fasp-qa" href="<?php echo esc_url( $account_url('resources') ); ?>"><?php esc_html_e('Help & Resources', 'fasp'); ?></a>
      </div>
    </div>

  </div>
</div>

<style>
/* minimal inline styles */
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