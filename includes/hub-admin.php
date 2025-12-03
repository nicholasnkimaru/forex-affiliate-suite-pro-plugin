<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Hub widgets and status cards
 */

if ( ! function_exists( 'fasp_hub_home' ) ) {
    function fasp_hub_home() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'fasp' ) );
        $stripe = function_exists( 'fasp_get_payments' ) ? fasp_get_payments() : array();
        $stripe_ok = ! empty( $stripe['stripe']['sk'] ) && ! empty( $stripe['stripe']['pk'] );
        $geo_set = ! empty( get_option( 'fasp_geo_allow', array() ) ) || ! empty( get_option( 'fasp_geo_block', array() ) );
        $webhook_url = get_option( 'fasp_webhook_url', '' );
        ?>
        <div class="wrap fasp-admin">
            <h1><?php echo esc_html__( 'Forex Affiliate', 'fasp' ); ?></h1>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;">
                <div style="border:1px solid #eee;padding:12px;border-radius:6px;min-width:220px;">
                    <strong><?php echo esc_html__( 'Gate', 'fasp' ); ?></strong>
                    <div><?php echo esc_html__( 'Status:' ); ?> <?php echo empty( get_option( 'fasp_gating_require_login', 0 ) ) ? esc_html__( 'OFF' ) : esc_html__( 'ON' ); ?></div>
                </div>
                <div style="border:1px solid #eee;padding:12px;border-radius:6px;min-width:220px;">
                    <strong><?php echo esc_html__( 'Geo rules', 'fasp' ); ?></strong>
                    <div><?php echo $geo_set ? esc_html__( 'Yes' ) : esc_html__( 'No' ); ?></div>
                </div>
                <div style="border:1px solid #eee;padding:12px;border-radius:6px;min-width:220px;">
                    <strong><?php echo esc_html__( 'Stripe keys', 'fasp' ); ?></strong>
                    <div><?php echo $stripe_ok ? esc_html__( 'Present' ) : esc_html__( 'Missing' ); ?></div>
                </div>
                <div style="border:1px solid #eee;padding:12px;border-radius:6px;min-width:220px;">
                    <strong><?php echo esc_html__( 'Webhook', 'fasp' ); ?></strong>
                    <div><?php echo ! empty( $webhook_url ) ? esc_html__( 'Set' ) : esc_html__( 'Not set' ); ?></div>
                </div>
            </div>

            <h2 style="margin-top:18px;"><?php echo esc_html__( 'Quick actions', 'fasp' ); ?></h2>
            <p>
                <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fasp_resource' ) ); ?>"><?php echo esc_html__( 'New Resource', 'fasp' ); ?></a>
                &nbsp;
                <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fasp_coach' ) ); ?>"><?php echo esc_html__( 'New Coach', 'fasp' ); ?></a>
                &nbsp;
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=fasp_geo_gating' ) ); ?>"><?php echo esc_html__( 'Geo Gating', 'fasp' ); ?></a>
            </p>
        </div>
        <?php
    }
}
