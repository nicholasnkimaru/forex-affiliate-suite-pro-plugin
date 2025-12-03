<?php
/**
 * Modern dashboard template (frontend).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'fasp_get_user_dashboard_data' ) ) {
    $helper = dirname( __DIR__ ) . '/includes/user-dash.php';
    if ( file_exists( $helper ) ) {
        require_once $helper;
    }
}

$current_user = wp_get_current_user();
$data = function_exists( 'fasp_get_user_dashboard_data' ) ? fasp_get_user_dashboard_data( get_current_user_id() ) : array();

$platforms = isset( $data['platforms'] ) ? $data['platforms'] : array();
$resources = isset( $data['resources'] ) ? $data['resources'] : array();
$coaches   = isset( $data['coaches'] ) ? $data['coaches'] : array();
$gating    = isset( $data['gating'] ) ? $data['gating'] : array();
$utm       = isset( $data['utm'] ) ? $data['utm'] : array();
?>
<div class="fasp-dash-wrap">
  <header class="fasp-dash-header">
    <div class="fasp-dash-title">
      <h1><?php echo esc_html__( 'Forex Trading Dashboard', 'fasp' ); ?></h1>
      <p class="fasp-dash-sub"><?php echo esc_html__( 'Welcome — your platforms, training and account links in one place.', 'fasp' ); ?></p>
    </div>
    <div class="fasp-dash-quick">
      <a class="fasp-cta-primary" href="<?php echo esc_url( home_url( '/promo-landing/' ) ); ?>"><?php echo esc_html__( 'Open Broker Account', 'fasp' ); ?></a>
      <a class="fasp-cta-secondary" href="<?php echo esc_url( home_url( '/forex-dashboard/resources/' ) ); ?>"><?php echo esc_html__( 'Access Resources', 'fasp' ); ?></a>
    </div>
  </header>

  <?php if ( ! empty( $gating['blocked'] ) ) : ?>
    <div class="fasp-block-notice" role="alert">
      <strong><?php echo esc_html__( 'Access blocked', 'fasp' ); ?></strong>
      <p><?php echo esc_html( $gating['message'] ); ?></p>
      <?php if ( ! empty( $gating['redirect'] ) ) : ?>
        <p><a class="fasp-cta-primary" href="<?php echo esc_url( $gating['redirect'] ); ?>"><?php echo esc_html__( 'Join now', 'fasp' ); ?></a></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="fasp-grid">
    <section class="fasp-card fasp-card--wide">
      <h2><?php echo esc_html__( 'Get started', 'fasp' ); ?></h2>
      <ol class="fasp-checklist">
        <li><?php echo esc_html__( 'Create a broker account via the Open Broker button.', 'fasp' ); ?></li>
        <li><?php echo esc_html__( 'Connect Deriv (optional) to verify identity.', 'fasp' ); ?></li>
        <li><?php echo esc_html__( 'Access starter resources and follow onboarding lessons.', 'fasp' ); ?></li>
      </ol>
    </section>

    <aside class="fasp-card fasp-card--actions" aria-labelledby="fasp-quick-actions">
      <h3 id="fasp-quick-actions"><?php echo esc_html__( 'Quick actions', 'fasp' ); ?></h3>
      <p>
        <?php
        if ( function_exists( 'fasp_render_deriv_connect_button' ) ) {
          echo fasp_render_deriv_connect_button();
        } else {
          echo '<a class="fasp-cta" href="' . esc_url( home_url( '/connect-deriv/' ) ) . '">' . esc_html__( 'Connect Deriv', 'fasp' ) . '</a>';
        }
        ?>
      </p>
      <p><a class="fasp-cta" href="<?php echo esc_url( home_url( '/profile/' ) ); ?>"><?php echo esc_html__( 'Complete profile', 'fasp' ); ?></a></p>

      <div style="margin-top:12px;">
        <strong><?php echo esc_html__( 'Coaches', 'fasp' ); ?></strong>
        <div style="display:flex;gap:8px;margin-top:8px;">
          <?php
          $shown = 0;
          foreach ( $coaches as $c ) {
            if ( $shown >= 3 ) break;
            $name = ! empty( $c->post_title ) ? $c->post_title : 'Coach';
            $initials = implode('', array_map(function($w){ return strtoupper(mb_substr($w,0,1)); }, array_filter(explode(' ', $name))));
            echo '<span class="fasp-avatar" data-initials="' . esc_attr( $initials ) . '" title="' . esc_attr( $name ) . '"></span>';
            $shown++;
          }
          if ( $shown === 0 ) {
            echo '<span class="fasp-avatar" data-initials="N/A">N/A</span>';
          }
          ?>
        </div>
      </div>
    </aside>

    <section class="fasp-card">
      <h3><?php echo esc_html__( 'Platforms', 'fasp' ); ?></h3>
      <div class="fasp-cards-row">
        <?php if ( empty( $platforms ) ) : ?>
          <p><?php echo esc_html__( 'No platforms available', 'fasp' ); ?></p>
        <?php else : foreach ( $platforms as $p ) : ?>
          <article class="fasp-platform" <?php if ( ! empty( $p['affiliate'] ) ) echo ' data-href="' . esc_attr( $p['affiliate'] ) . '"'; ?>>
            <h4><?php echo esc_html( $p['name'] ); ?></h4>
            <p class="fasp-excerpt"><?php echo esc_html( $p['excerpt'] ); ?></p>
            <?php if ( ! empty( $p['affiliate'] ) ) : ?>
              <p><a class="fasp-cta-small" href="<?php echo esc_url( $p['affiliate'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Open Account', 'fasp' ); ?></a></p>
            <?php endif; ?>
          </article>
        <?php endforeach; endif; ?>
      </div>
    </section>

    <section class="fasp-card">
      <h3><?php echo esc_html__( 'Your resources', 'fasp' ); ?></h3>
      <div class="fasp-cards-row">
        <?php if ( empty( $resources ) ) : ?>
          <p><?php echo esc_html__( 'No resources available yet.', 'fasp' ); ?></p>
        <?php else : foreach ( $resources as $r ) : ?>
          <article class="fasp-resource">
            <h4><?php echo esc_html( $r->post_title ); ?></h4>
            <p><?php echo wp_kses_post( wp_trim_words( $r->post_content, 20 ) ); ?></p>
            <p><a class="fasp-cta-small" href="<?php echo esc_url( get_permalink( $r->ID ) ); ?>"><?php echo esc_html__( 'View Resource', 'fasp' ); ?></a></p>
          </article>
        <?php endforeach; endif; ?>
      </div>
    </section>

    <section class="fasp-card fasp-card--wide">
      <h3><?php echo esc_html__( 'Your progress', 'fasp' ); ?></h3>
      <div style="position:relative;">
        <canvas id="faspProgressChart" aria-label="<?php echo esc_attr__( 'Your progress chart', 'fasp' ); ?>" role="img"></canvas>
      </div>
    </section>
  </div>
</div>
