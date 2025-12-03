<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Per-page gating meta box
 */
if ( ! function_exists( 'fasp_register_gating_meta_box' ) ) {
    add_action( 'add_meta_boxes', 'fasp_register_gating_meta_box' );
    function fasp_register_gating_meta_box() {
        $screens = array( 'post', 'page' );
        foreach ( $screens as $screen ) {
            add_meta_box( 'fasp_gating_meta', __( 'FASP Gating', 'fasp' ), 'fasp_render_gating_meta_box', $screen, 'side', 'default' );
        }
    }
}

if ( ! function_exists( 'fasp_render_gating_meta_box' ) ) {
    function fasp_render_gating_meta_box( $post ) {
        wp_nonce_field( 'fasp_gating_meta_save', 'fasp_gating_meta_nonce' );
        $value = get_post_meta( $post->ID, '_fasp_gating_override', true );
        if ( ! in_array( $value, array( 'inherit', 'allow', 'deny' ), true ) ) $value = 'inherit';
        ?>
        <p><?php echo esc_html__( 'Override global gating for this page.', 'fasp' ); ?></p>
        <label><input type="radio" name="fasp_gating_override" value="inherit" <?php checked( $value, 'inherit' ); ?> /> <?php echo esc_html__( 'Inherit', 'fasp' ); ?></label><br/>
        <label><input type="radio" name="fasp_gating_override" value="allow" <?php checked( $value, 'allow' ); ?> /> <?php echo esc_html__( 'Allow', 'fasp' ); ?></label><br/>
        <label><input type="radio" name="fasp_gating_override" value="deny" <?php checked( $value, 'deny' ); ?> /> <?php echo esc_html__( 'Deny', 'fasp' ); ?></label>
        <?php
    }
}

if ( ! function_exists( 'fasp_save_gating_meta_box' ) ) {
    add_action( 'save_post', 'fasp_save_gating_meta_box' );
    function fasp_save_gating_meta_box( $post_id ) {
        if ( ! isset( $_POST['fasp_gating_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['fasp_gating_meta_nonce'] ), 'fasp_gating_meta_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( isset( $_POST['fasp_gating_override'] ) ) {
            $val = sanitize_text_field( wp_unslash( $_POST['fasp_gating_override'] ) );
            if ( ! in_array( $val, array( 'inherit', 'allow', 'deny' ), true ) ) $val = 'inherit';
            update_post_meta( $post_id, '_fasp_gating_override', $val );
        }
    }
}
