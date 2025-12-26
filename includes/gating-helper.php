<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gating helpers: options getter and fasp_is_user_allowed_by_gating
 */

if ( ! function_exists( 'fasp_get_gating_options' ) ) {
    function fasp_get_gating_options() {
        $roles   = get_option( 'fasp_gating_roles', array() );
        if ( ! is_array( $roles ) ) {
            $roles = array_filter( array_map( 'trim', explode( ',', (string) $roles ) ) );
        }

        $require_login = get_option( 'fasp_gating_require_login', false );
        $blocked_msg   = get_option( 'fasp_gating_blocked_message', '' );
        $blocked_redir = get_option( 'fasp_gating_blocked_redirect', '' );

        return array(
            'roles'           => $roles,
            'require_login'   => (bool) $require_login,
            'blocked_message' => (string) $blocked_msg,
            'blocked_redirect'=> (string) $blocked_redir,
        );
    }
}

if ( ! function_exists( 'fasp_is_user_allowed_by_gating' ) ) {
    function fasp_is_user_allowed_by_gating( $user_id = null, $post_id = null ) {
        $result = array( 'allowed' => true, 'message' => null, 'redirect' => null );
        $user_id = $user_id ? intval( $user_id ) : ( is_user_logged_in() ? get_current_user_id() : 0 );

        if ( $post_id ) {
            $override = get_post_meta( $post_id, '_fasp_gating_override', true );
            if ( 'allow' === $override ) { $result['allowed'] = true; return $result; }
            if ( 'deny' === $override ) { $result['allowed'] = false; $result['message'] = __( 'Access denied for this page.', 'fasp' ); return $result; }
        }

        $opt = fasp_get_gating_options();

        if ( $opt['require_login'] && ! is_user_logged_in() ) {
            $result['allowed'] = false;
            $result['message'] = $opt['blocked_message'] ? $opt['blocked_message'] : __( 'Please log in to access this content.', 'fasp' );
            $result['redirect'] = $opt['blocked_redirect'] ? $opt['blocked_redirect'] : '';
            return $result;
        }

        if ( ! empty( $opt['roles'] ) ) {
            if ( ! $user_id ) {
                $result['allowed'] = false;
                $result['message'] = $opt['blocked_message'] ? $opt['blocked_message'] : __( 'Your account does not have permission to view this content.', 'fasp' );
                return $result;
            }
            $user = get_userdata( $user_id );
            if ( ! $user ) {
                $result['allowed'] = false;
                $result['message'] = __( 'User not found.', 'fasp' );
                return $result;
            }
            $has = false;
            foreach ( (array) $opt['roles'] as $r ) {
                if ( in_array( $r, (array) $user->roles, true ) ) { $has = true; break; }
            }
            if ( ! $has ) {
                $result['allowed'] = false;
                $result['message'] = $opt['blocked_message'] ? $opt['blocked_message'] : __( 'Your role is not permitted to view this content.', 'fasp' );
                return $result;
            }
        }

        // Geo gating: check IP if geo options exist and helper present
        if ( function_exists( 'fasp_is_ip_allowed_by_geo' ) ) {
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
            $geo_allowed = fasp_is_ip_allowed_by_geo( $ip );
            if ( ! $geo_allowed ) {
                $result['allowed'] = false;
                $result['message'] = __( 'Access not available in your region.', 'fasp' );
                return $result;
            }
        }

        return $result;
    }
}
