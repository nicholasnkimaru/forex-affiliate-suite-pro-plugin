<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Basic UTM Builder admin page (scaffold)
 * - base_url, utm_source, utm_campaign, utm_medium, utm_content, utm_term
 * - presets (facebook, google_ads, tiktok, email)
 * - copy button + QR generation (client-side)
 */

if ( ! function_exists( 'fasp_register_utm_builder_admin' ) ) {
    add_action( 'admin_menu', 'fasp_register_utm_builder_admin' );
    function fasp_register_utm_builder_admin() {
        $parent = 'forex-affiliate';
        if ( ! menu_page_url( $parent, false ) ) $parent = 'options-general.php';
        add_submenu_page( $parent, __( 'UTM Builder', 'fasp' ), __( 'UTM Builder', 'fasp' ), 'manage_options', 'fasp_utm_builder', 'fasp_admin_utm_builder_page' );
    }
}

if ( ! function_exists( 'fasp_admin_utm_builder_page' ) ) {
    function fasp_admin_utm_builder_page() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'fasp' ) );

        // simple save not required; presets stored if admin saves
        if ( isset( $_POST['fasp_utm_save'] ) ) {
            check_admin_referer( 'fasp_utm_save', 'fasp_utm_nonce' );
            $presets = isset( $_POST['fasp_utm_presets'] ) ? wp_kses_post( wp_unslash( $_POST['fasp_utm_presets'] ) ) : '';
            update_option( 'fasp_utm_presets', $presets );
            add_settings_error( 'fasp_utm', 'saved', __( 'UTM presets saved.', 'fasp' ), 'updated' );
        }

        settings_errors( 'fasp_utm' );
        $presets = get_option( 'fasp_utm_presets', '' );
        ?>
        <div class="wrap">
          <h1><?php echo esc_html__( 'UTM Builder', 'fasp' ); ?></h1>
          <p class="description"><?php echo esc_html__( 'Build campaign URLs quickly. Use presets for facebook, google_ads, tiktok, email.', 'fasp' ); ?></p>
          <form method="post" action="">
            <?php wp_nonce_field( 'fasp_utm_save', 'fasp_utm_nonce' ); ?>
            <table class="form-table">
              <tr>
                <th><?php echo esc_html__( 'Base URL', 'fasp' ); ?></th>
                <td><input type="url" name="fasp_base_url" id="fasp_base_url" value="<?php echo esc_attr( home_url() ); ?>" style="width:420px;" /></td>
              </tr>
              <tr>
                <th><?php echo esc_html__( 'UTM Source', 'fasp' ); ?></th>
                <td><input type="text" name="fasp_utm_source" id="fasp_utm_source" style="width:320px;" /></td>
              </tr>
              <tr>
                <th><?php echo esc_html__( 'UTM Campaign', 'fasp' ); ?></th>
                <td><input type="text" name="fasp_utm_campaign" id="fasp_utm_campaign" style="width:320px;" /></td>
              </tr>
              <tr>
                <th><?php echo esc_html__( 'UTM Medium (optional)', 'fasp' ); ?></th>
                <td><input type="text" name="fasp_utm_medium" id="fasp_utm_medium" style="width:320px;" /></td>
              </tr>
              <tr>
                <th><?php echo esc_html__( 'UTM Content (optional)', 'fasp' ); ?></th>
                <td><input type="text" name="fasp_utm_content" id="fasp_utm_content" style="width:320px;" /></td>
              </tr>
            </table>

            <p class="submit"><button type="button" id="fasp_build_btn" class="button button-primary"><?php echo esc_html__( 'Build URL', 'fasp' ); ?></button></p>
          </form>

          <h2><?php echo esc_html__( 'Result', 'fasp' ); ?></h2>
          <p><input type="text" id="fasp_result_url" readonly style="width:90%;" /></p>
          <p><button id="fasp_copy_btn" class="button"><?php echo esc_html__( 'Copy', 'fasp' ); ?></button> <button id="fasp_qr_btn" class="button"><?php echo esc_html__( 'QR', 'fasp' ); ?></button></p>

          <script>
          (function(){
            document.getElementById('fasp_build_btn').addEventListener('click', function(){
              var base = document.getElementById('fasp_base_url').value;
              var src = document.getElementById('fasp_utm_source').value;
              var camp = document.getElementById('fasp_utm_campaign').value;
              var med = document.getElementById('fasp_utm_medium').value;
              var cont = document.getElementById('fasp_utm_content').value;
              if (!base || !src || !camp) { alert('Base, source and campaign are required'); return; }
              var params = new URLSearchParams();
              params.set('utm_source', src);
              params.set('utm_campaign', camp);
              if (med) params.set('utm_medium', med);
              if (cont) params.set('utm_content', cont);
              var result = base + (base.indexOf('?') === -1 ? '?' : '&') + params.toString();
              document.getElementById('fasp_result_url').value = result;
            });
            document.getElementById('fasp_copy_btn').addEventListener('click', function(){
              var el = document.getElementById('fasp_result_url');
              el.select(); document.execCommand('copy');
              alert('Copied');
            });
            document.getElementById('fasp_qr_btn').addEventListener('click', function(){
              var u = document.getElementById('fasp_result_url').value;
              if (!u) return alert('No URL built');
              var src = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' + encodeURIComponent(u);
              var w = window.open('', '_blank');
              w.document.write('<img src="'+src+'">');
            });
          })();
          </script>
        </div>
        <?php
    }
}
