<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: collect dashboard data for a user (platforms, resources, coaches, gating state, utm)
 *
 * Backwards compatible; function_exists guards applied.
 */

if ( ! function_exists( 'fasp_get_user_dashboard_data' ) ) {
    function fasp_get_user_dashboard_data( $user_id = 0 ) {
        $user_id = intval( $user_id ) ?: get_current_user_id();
        $data = array();

        // Platforms: read plugin option or fallback
        $platforms = array();
        $opt = get_option( 'fasp_platforms', array() );
        if ( is_array( $opt ) && ! empty( $opt ) ) {
            foreach ( $opt as $slug => $p ) {
                if ( isset( $p['visible_in_dashboard'] ) && ! $p['visible_in_dashboard'] ) {
                    continue;
                }
                $platforms[] = array(
                    'slug'     => $slug,
                    'name'     => isset( $p['name'] ) ? $p['name'] : $slug,
                    'excerpt'  => isset( $p['excerpt'] ) ? $p['excerpt'] : '',
                    'affiliate'=> isset( $p['affiliate'] ) ? $p['affiliate'] : '',
                );
            }
        } else {
            $platforms[] = array(
                'slug' => 'deriv',
                'name' => 'Deriv',
                'excerpt' => 'Open a Deriv account',
                'affiliate' => '#',
            );
        }

        // Resources CPT
        $resources = get_posts( array(
            'post_type'      => 'fasp_resource',
            'posts_per_page' => 6,
            'post_status'    => 'publish',
        ) );

        // Coaches CPT
        $coaches = get_posts( array(
            'post_type'      => 'fasp_coach',
            'posts_per_page' => 6,
            'post_status'    => 'publish',
        ) );

        // Gating: quick check
        $gating_blocked = false;
        $gating_message = '';
        $gating_redirect = '';
        $gating_opt = get_option( 'fasp_platform_gating', array() );
        if ( ! empty( $gating_opt ) && is_array( $gating_opt ) ) {
            if ( isset( $gating_opt['require_login'] ) && $gating_opt['require_login'] && ! is_user_logged_in() ) {
                $gating_blocked = true;
                $gating_message = __( 'Please log in to access resources.', 'fasp' );
            }
        }

        // UTM detection (querystring or cookie)
        $utm = array();
        $utm_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );
        foreach ( $utm_keys as $k ) {
            if ( isset( $_GET[ $k ] ) ) {
                $utm[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) );
            } elseif ( isset( $_COOKIE[ $k ] ) ) {
                $utm[ $k ] = sanitize_text_field( wp_unslash( $_COOKIE[ $k ] ) );
            }
        }

        $data['platforms'] = $platforms;
        $data['resources'] = $resources;
        $data['coaches'] = $coaches;
        $data['gating'] = array(
            'blocked'  => $gating_blocked,
            'message'  => $gating_message,
            'redirect' => $gating_redirect,
        );
        $data['utm'] = $utm;

        return $data;
    }
}

if ( ! function_exists( 'fasp_render_deriv_connect_button' ) ) {
    function fasp_render_deriv_connect_button() {
        if ( function_exists( 'fasp_deriv_connect_url' ) ) {
            $url = fasp_deriv_connect_url();
            return '<a class="fasp-cta" href="' . esc_url( $url ) . '">' . esc_html__( 'Connect Deriv', 'fasp' ) . '</a>';
        }
        return '<a class="fasp-cta" href="' . esc_url( home_url( '/connect-deriv/' ) ) . '">' . esc_html__( 'Connect Deriv', 'fasp' ) . '</a>';
    }
}

if ( ! function_exists( 'fasp_dashboard_shortcode' ) ) {
    function fasp_dashboard_shortcode( $atts = array() ) {
        ob_start();
        $plugin_root = dirname( __DIR__ );
        $tpl = $plugin_root . '/templates/dashboard.php';
        if ( file_exists( $tpl ) ) {
            include $tpl;
        } else {
            echo '<p>' . esc_html__( 'Dashboard template missing', 'fasp' ) . '</p>';
        }
        return ob_get_clean();
    }
    add_shortcode( 'fasp_dashboard', 'fasp_dashboard_shortcode' );
}
