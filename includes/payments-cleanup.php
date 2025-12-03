<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Payments admin cleanup:
 * - move crypto block into tabbed payments UI (if you have a tab renderer)
 * - guard legacy HTML (replace raw legacy blocks with callable render functions)
 */

/* Example guard for legacy block replacement (for files that printed raw HTML) */
if ( ! function_exists( 'fasp_render_legacy_payment_block' ) ) {
    function fasp_render_legacy_payment_block() {
        // placeholder to avoid accidental output
        ?>
        <div class="wrap fasp-admin" style="display:none;">
            <h2><?php echo esc_html__( 'Legacy Payments (disabled)', 'fasp' ); ?></h2>
        </div>
        <?php
    }
}
