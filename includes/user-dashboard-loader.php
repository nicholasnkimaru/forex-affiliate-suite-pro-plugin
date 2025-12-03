<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central loader: registers canonical endpoint 'forex-dashboard' and controls My Account menu item.
 * Enqueues front-end dashboard assets only when needed.
 */

if ( ! function_exists( 'fasp_register_forex_dashboard_endpoint' ) ) {
    add_action( 'init', 'fasp_register_forex_dashboard_endpoint' );
    function fasp_register_forex_dashboard_endpoint() {
        add_rewrite_endpoint( 'forex-dashboard', EP_ROOT | EP_PAGES );
    }
}

if ( ! function_exists( 'fasp_enqueue_dashboard_assets' ) ) {
    add_action( 'wp_enqueue_scripts', 'fasp_enqueue_dashboard_assets', 20 );
    function fasp_enqueue_dashboard_assets() {
        $need = false;
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            global $wp;
            if ( isset( $wp->query_vars['forex-dashboard'] ) ) {
                $need = true;
            }
        }
        if ( ! $need && function_exists( 'has_shortcode' ) ) {
            $post_id = get_queried_object_id();
            if ( $post_id && has_shortcode( get_post_field( 'post_content', $post_id ), 'fasp_dashboard' ) ) {
                $need = true;
            }
        }

        if ( $need ) {
            wp_register_style( 'fasp-dashboard', plugin_dir_url( __DIR__ ) . 'assets/css/fasp-dashboard.css', array(), '1.3.0' );
            wp_enqueue_style( 'fasp-dashboard' );

            // Chart.js (via CDN), local dashboard script
            wp_register_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );
            wp_register_script( 'fasp-dashboard-js', plugin_dir_url( __DIR__ ) . 'assets/js/fasp-dashboard.js', array( 'chartjs' ), '1.1.0', true );
            $sample = array(
                'chart' => array(
                    'labels' => array( 'Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5' ),
                    'values' => array( 2, 8, 5, 11, 9 ),
                ),
            );
            wp_localize_script( 'fasp-dashboard-js', 'fasp_dashboard_data', $sample );
            wp_enqueue_script( 'chartjs' );
            wp_enqueue_script( 'fasp-dashboard-js' );
        }
    }
}

if ( ! function_exists( 'fasp_add_forex_dashboard_myaccount_tab' ) ) {
    add_filter( 'woocommerce_account_menu_items', 'fasp_add_forex_dashboard_myaccount_tab', 50 );
    function fasp_add_forex_dashboard_myaccount_tab( $items ) {
        foreach ( $items as $key => $label ) {
            if ( 'forex-affiliate' === $key ) {
                unset( $items[ $key ] );
                continue;
            }
            if ( is_string( $label ) ) {
                $lower = mb_strtolower( $label );
                if ( false !== mb_strpos( $lower, 'affiliate' ) || false !== mb_strpos( $lower, 'forex' ) ) {
                    unset( $items[ $key ] );
                    continue;
                }
            }
        }

        if ( ! isset( $items['forex-dashboard'] ) ) {
            $new = array();
            $inserted = false;
            foreach ( $items as $key => $label ) {
                $new[ $key ] = $label;
                if ( 'dashboard' === $key && ! $inserted ) {
                    $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
                    $inserted = true;
                }
            }
            if ( ! $inserted ) {
                $new['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
            }
            return $new;
        }

        $items['forex-dashboard'] = __( 'Forex Trading', 'fasp' );
        return $items;
    }
}

if ( ! function_exists( 'fasp_add_forex_dashboard_endpoint_content' ) ) {
    add_action( 'woocommerce_account_forex-dashboard_endpoint', 'fasp_add_forex_dashboard_endpoint_content' );
    function fasp_add_forex_dashboard_endpoint_content() {
        $plugin_root = dirname( __DIR__ );
        $tpl = $plugin_root . '/templates/dashboard.php';
        if ( file_exists( $tpl ) ) {
            $helper = $plugin_root . '/includes/user-dash.php';
            if ( file_exists( $helper ) ) {
                require_once $helper;
            }
            include $tpl;
        } else {
            echo '<p>' . esc_html__( 'Dashboard template missing', 'fasp' ) . '</p>';
        }
    }
}
