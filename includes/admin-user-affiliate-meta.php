<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add a simple checkbox to user profile edit screens so admins can mark a user
 * as an affiliate (sets usermeta fasp_is_affiliate = 1).
 */

// Show checkbox on profile
add_action( 'show_user_profile', 'fasp_user_affiliate_meta_field' );
add_action( 'edit_user_profile', 'fasp_user_affiliate_meta_field' );

function fasp_user_affiliate_meta_field( $user ) {
  if ( ! current_user_can( 'manage_options' ) ) return; // only admins
  $is_aff = get_user_meta( $user->ID, 'fasp_is_affiliate', true );
  ?>
  <h2><?php esc_html_e( 'FASP Affiliate', 'fasp' ); ?></h2>
  <table class="form-table">
    <tr>
      <th><label for="fasp_is_affiliate"><?php esc_html_e( 'Mark as affiliate', 'fasp' ); ?></label></th>
      <td>
        <input type="checkbox" name="fasp_is_affiliate" id="fasp_is_affiliate" value="1" <?php checked( $is_aff, 1 ); ?> />
        <p class="description"><?php esc_html_e( 'Check to enable affiliate UI for this user (Affiliate Tools menus, referrals).', 'fasp' ); ?></p>
      </td>
    </tr>
  </table>
  <?php
}

// Save checkbox
add_action( 'personal_options_update', 'fasp_save_user_affiliate_meta' );
add_action( 'edit_user_profile_update', 'fasp_save_user_affiliate_meta' );

function fasp_save_user_affiliate_meta( $user_id ) {
  if ( ! current_user_can( 'manage_options' ) ) return;
  $val = isset( $_POST['fasp_is_affiliate'] ) && $_POST['fasp_is_affiliate'] ? 1 : 0;
  update_user_meta( $user_id, 'fasp_is_affiliate', $val );
}
?>