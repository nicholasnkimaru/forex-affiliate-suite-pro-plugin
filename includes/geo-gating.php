<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Full Geo Gating with Select2 UI + region auto-select.
 * Uses data/countries.json and data/regions.json included in the repo.
 */

/* load countries and regions */
if ( ! function_exists( 'fasp_get_countries_list' ) ) {
    function fasp_get_countries_list() {
        $path = plugin_dir_path( __DIR__ ) . 'data/countries.json';
        if ( file_exists( $path ) ) {
            $json = file_get_contents( $path );
            $arr  = json_decode( $json, true );
            if ( is_array( $arr ) ) return $arr;
        }
        return array();
    }
}

if ( ! function_exists( 'fasp_get_regions_map' ) ) {
    function fasp_get_regions_map() {
        $path = plugin_dir_path( __DIR__ ) . 'data/regions.json';
        if ( file_exists( $path ) ) {
            $json = file_get_contents( $path );
            $arr  = json_decode( $json, true );
            if ( is_array( $arr ) ) return $arr;
        }
        return array();
    }
}

/* admin assets */
if ( ! function_exists( 'fasp_geo_admin_assets' ) ) {
    add_action( 'admin_enqueue_scripts', 'fasp_geo_admin_assets' );
    function fasp_geo_admin_assets( $hook ) {
        if ( isset( $_GET['page'] ) && 'fasp_geo_gating' === $_GET['page'] ) {
            wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
            wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_script( 'fasp-geo-admin', plugin_dir_url( __DIR__ ) . 'assets/js/geo-admin.js', array( 'jquery', 'select2-js' ), '1.0.0', true );
            wp_localize_script( 'fasp-geo-admin', 'fasp_geo_data', array( 'regions' => fasp_get_regions_map() ) );
            wp_enqueue_script( 'fasp-geo-admin' );
        }
    }
}

/* admin page */
if ( ! function_exists( 'fasp_register_geo_gating_admin' ) ) {
    add_action( 'admin_menu', 'fasp_register_geo_gating_admin' );
    function fasp_register_geo_gating_admin() {
        $parent = 'forex-affiliate';
        if ( ! menu_page_url( $parent, false ) ) $parent = 'options-general.php';
        add_submenu_page( $parent, __( 'Geo Gating', 'fasp' ), __( 'Geo Gating', 'fasp' ), 'manage_options', 'fasp_geo_gating', 'fasp_admin_geo_gating_page' );
    }
}

if ( ! function_exists( 'fasp_admin_geo_gating_page' ) ) {
    function fasp_admin_geo_gating_page() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'fasp' ) );
        if ( isset( $_POST['fasp_geo_save'] ) ) {
            check_admin_referer( 'fasp_geo_save', 'fasp_geo_nonce' );
            $allow = isset( $_POST['fasp_geo_allow'] ) ? array_map( 'sanitize_text_field', (array) $_POST['fasp_geo_allow'] ) : array();
            $block = isset( $_POST['fasp_geo_block'] ) ? array_map( 'sanitize_text_field', (array) $_POST['fasp_geo_block'] ) : array();
            $regions = isset( $_POST['fasp_geo_regions'] ) ? array_map( 'sanitize_text_field', (array) $_POST['fasp_geo_regions'] ) : array();
            update_option( 'fasp_geo_allow', array_values( $allow ) );
            update_option( 'fasp_geo_block', array_values( $block ) );
            update_option( 'fasp_geo_regions', array_values( $regions ) );
            $unknown = isset( $_POST['fasp_geo_unknown_blocked'] ) ? 1 : 0;
            update_option( 'fasp_geo_unknown_blocked', $unknown );
            add_settings_error( 'fasp_geo', 'saved', __( 'Geo gating settings saved.', 'fasp' ), 'updated' );
        }

        settings_errors( 'fasp_geo' );

        $allow_selected = get_option( 'fasp_geo_allow', array() );
        $block_selected = get_option( 'fasp_geo_block', array() );
        $regions_selected = get_option( 'fasp_geo_regions', array() );
        $unknown_blocked = get_option( 'fasp_geo_unknown_blocked', 0 );

        $countries = fasp_get_countries_list();
        $regions = fasp_get_regions_map();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Geo Gating', 'fasp' ); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'fasp_geo_save', 'fasp_geo_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th><?php echo esc_html__( 'Regions', 'fasp' ); ?></th>
                        <td>
                            <select name="fasp_geo_regions[]" id="fasp-geo-regions" multiple class="fasp-geo-regions" style="width:420px;">
                                <?php foreach ( $regions as $slug => $arr ) : ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( in_array( $slug, (array) $regions_selected, true ) ); ?>><?php echo esc_html( $slug ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php echo esc_html__( 'Select region(s) to quick-select countries. Selecting regions will highlight countries in the Allow/Block lists; you must Save to persist.', 'fasp' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo esc_html__( 'Allowlist (countries)', 'fasp' ); ?></th>
                        <td>
                            <select name="fasp_geo_allow[]" id="fasp-geo-allow" class="fasp-geo-multiselect" multiple style="width:420px;height:240px;">
                                <?php foreach ( $countries as $code => $label ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( in_array( $code, (array) $allow_selected, true ) ); ?>><?php echo esc_html( $label ); ?> (<?php echo esc_html( $code ); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php echo esc_html__( 'Select countries to explicitly allow. Allowlist wins over blocklist.', 'fasp' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo esc_html__( 'Blocklist (countries)', 'fasp' ); ?></th>
                        <td>
                            <select name="fasp_geo_block[]" id="fasp-geo-block" class="fasp-geo-multiselect" multiple style="width:420px;height:240px;">
                                <?php foreach ( $countries as $code => $label ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( in_array( $code, (array) $block_selected, true ) ); ?>><?php echo esc_html( $label ); ?> (<?php echo esc_html( $code ); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php echo esc_html__( 'Select countries to explicitly block. If allowlist is set, countries not in allowlist are implicitly blocked.', 'fasp' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo esc_html__( 'Unknown IP handling', 'fasp' ); ?></th>
                        <td>
                            <label><input type="checkbox" name="fasp_geo_unknown_blocked" value="1" <?php checked( 1, $unknown_blocked ); ?> /> <?php echo esc_html__( 'Treat unknown/unresolvable IP country as blocked', 'fasp' ); ?></label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="fasp_geo_save" class="button button-primary"><?php echo esc_html__( 'Save Changes', 'fasp' ); ?></button>
                </p>
            </form>
        </div>
        <?php
    }
}

/* IP lookup helper (MaxMind optional, fallback to ip-api) */
if ( ! function_exists( 'fasp_lookup_country_by_ip' ) ) {
    function fasp_lookup_country_by_ip( $ip ) {
        if ( empty( $ip ) ) return '';
        $db = plugin_dir_path( __DIR__ ) . 'data/GeoLite2-City.mmdb';
        if ( file_exists( $db ) && class_exists( 'GeoIp2\Database\Reader' ) ) {
            try {
                $reader = new GeoIp2\Database\Reader( $db );
                $rec = $reader->city( $ip );
                if ( isset( $rec->country ) && isset( $rec->country->isoCode ) ) return strtoupper( $rec->country->isoCode );
            } catch ( Exception $e ) { }
        }
        $resp = wp_remote_get( 'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=countryCode,status' );
        if ( is_wp_error( $resp ) ) return '';
        $body = wp_remote_retrieve_body( $resp );
        if ( empty( $body ) ) return '';
        $json = json_decode( $body, true );
        if ( is_array( $json ) && isset( $json['status'] ) && 'success' === $json['status'] && ! empty( $json['countryCode'] ) ) {
            return strtoupper( $json['countryCode'] );
        }
        return '';
    }
}

if ( ! function_exists( 'fasp_is_ip_allowed_by_geo' ) ) {
    function fasp_is_ip_allowed_by_geo( $ip ) {
        $code = fasp_lookup_country_by_ip( $ip );
        $allow = (array) get_option( 'fasp_geo_allow', array() );
        $block = (array) get_option( 'fasp_geo_block', array() );
        $unknown_blocked = (bool) get_option( 'fasp_geo_unknown_blocked', false );
        if ( empty( $code ) ) return ! $unknown_blocked;
        if ( ! empty( $allow ) ) return in_array( $code, $allow, true );
        if ( ! empty( $block ) && in_array( $code, $block, true ) ) return false;
        return true;
    }
}
