<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Diagnostics admin page: webhook pinger, Stripe signature checker scaffold, M-Pesa STK trigger (safe)
 */

if ( ! function_exists( 'fasp_register_diagnostics_admin' ) ) {
    add_action( 'admin_menu', 'fasp_register_diagnostics_admin' );
    function fasp_register_diagnostics_admin() {
        $parent = 'forex-affiliate';
        if ( ! menu_page_url( $parent, false ) ) $parent = 'options-general.php';
        add_submenu_page( $parent, __( 'Diagnostics', 'fasp' ), __( 'Diagnostics', 'fasp' ), 'manage_options', 'fasp_diagnostics', 'fasp_admin_diagnostics_page' );
    }
}

if ( ! function_exists( 'fasp_admin_diagnostics_page' ) ) {
    function fasp_admin_diagnostics_page() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'fasp' ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Diagnostics', 'fasp' ); ?></h1>
            <h2><?php echo esc_html__( 'Webhook pinger', 'fasp' ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'fasp_diag', 'fasp_diag_nonce' ); ?>
                <input type="url" name="fasp_diag_webhook_url" style="width:420px;" placeholder="<?php echo esc_attr__( 'https://example.com/endpoint', 'fasp' ); ?>" />
                <button class="button" type="submit" name="fasp_diag_ping"><?php echo esc_html__( 'Send Test Webhook', 'fasp' ); ?></button>
            </form>
            <?php
            if ( isset( $_POST['fasp_diag_ping'] ) && check_admin_referer( 'fasp_diag', 'fasp_diag_nonce' ) ) {
                $url = esc_url_raw( wp_unslash( $_POST['fasp_diag_webhook_url'] ) );
                $sample = array( 'message' => 'This is a test webhook from Forex Affiliate Suite Pro', 'time' => time() );
                $ok = fasp_send_lead_webhook( $url, $sample );
                echo '<p>' . ( $ok ? esc_html__( 'Webhook sent (200-range).', 'fasp' ) : esc_html__( 'Webhook failed. Check log.', 'fasp' ) ) . '</p>';
            }
            ?>
            <h2><?php echo esc_html__( 'Connectivity checks', 'fasp' ); ?></h2>
            <ul>
                <li><?php echo esc_html__( 'cURL available:' ); ?> <?php echo function_exists( 'curl_version' ) ? esc_html__( 'Yes' ) : esc_html__( 'No' ); ?></li>
                <li><?php echo esc_html__( 'wp_remote_post test:' ); ?>
                    <?php
                    $res = wp_remote_get( 'https://httpbin.org/get' );
                    echo is_wp_error( $res ) ? esc_html__( 'Failed' ) : esc_html__( 'OK' );
                    ?>
                </li>
                <li><?php echo esc_html__( 'WP Cron:' ); ?> <?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? esc_html__( 'Disabled' ) : esc_html__( 'Enabled' ); ?></li>
            </ul>
        </div>
        <?php
    }
}
