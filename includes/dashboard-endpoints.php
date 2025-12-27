<?php
if (!defined('ABSPATH')) exit;

/**
 * Register additional WooCommerce My Account endpoints for dashboard navigation
 * 
 * This adds:
 * - platforms: Browse and connect to trading platforms
 * - resources: Access educational resources and guides
 * - coaches: Meet and book coaching sessions
 */

// Register endpoints
add_action('init', function() {
    add_rewrite_endpoint('platforms', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('resources', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('coaches', EP_ROOT | EP_PAGES);
});

// Add to query vars
add_filter('query_vars', function($vars) {
    $vars[] = 'platforms';
    $vars[] = 'resources';
    $vars[] = 'coaches';
    return $vars;
});

// Add to WooCommerce My Account menu
add_filter('woocommerce_account_menu_items', function($items) {
    $new = array();
    
    foreach ($items as $key => $label) {
        $new[$key] = $label;
        
        // After forex-dashboard, add the additional endpoints
        if ($key === 'forex-dashboard') {
            $new['platforms'] = __('Platforms', 'fasp');
            $new['resources'] = __('Resources', 'fasp');
            $new['coaches'] = __('Coaches', 'fasp');
        }
    }
    
    return $new;
}, 25);

// Handle platforms endpoint
add_action('woocommerce_account_platforms_endpoint', function() {
    $current_user = wp_get_current_user();
    
    echo '<div class="woocommerce-MyAccount-content">';
    echo '<header class="fasp-dashboard-header">';
    echo '<h1>' . esc_html__('Trading Platforms', 'fasp') . '</h1>';
    echo '<p class="fasp-muted">' . esc_html__('Connect to supported trading platforms and start trading.', 'fasp') . '</p>';
    echo '</header>';
    
    // Get platforms from option
    $platforms = get_option('fasp_platforms', array());
    
    if (empty($platforms)) {
        // Default platform if none configured
        $platforms = array(
            'deriv' => array(
                'name' => 'Deriv',
                'excerpt' => 'Open a Deriv account and start trading',
                'affiliate_url' => home_url('/'),
                'visible_in_dashboard' => true
            )
        );
    }
    
    echo '<div class="fasp-dashboard fasp-grid">';
    
    foreach ($platforms as $slug => $platform) {
        // Skip if not visible
        if (isset($platform['visible_in_dashboard']) && !$platform['visible_in_dashboard']) {
            continue;
        }
        
        $name = isset($platform['name']) ? esc_html($platform['name']) : esc_html($slug);
        $excerpt = isset($platform['excerpt']) ? esc_html($platform['excerpt']) : '';
        $url = isset($platform['affiliate_url']) ? esc_url($platform['affiliate_url']) : home_url('/fasp-go/' . sanitize_title($slug));
        
        // Check if user is verified for this platform
        $is_verified = get_user_meta($current_user->ID, '_fasp_verified_' . $slug, true);
        
        echo '<div class="fasp-card fasp-card--half">';
        echo '<h3>' . $name . '</h3>';
        if ($excerpt) {
            echo '<p class="fasp-muted">' . $excerpt . '</p>';
        }
        echo '<p>';
        if ($is_verified) {
            echo '<span style="color:#16a34a;">✓ ' . esc_html__('Connected', 'fasp') . '</span> ';
        }
        echo '<a class="button" href="' . $url . '" target="_blank" rel="noopener">' . esc_html__('Open Platform', 'fasp') . '</a>';
        echo '</p>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
});

// Handle resources endpoint
add_action('woocommerce_account_resources_endpoint', function() {
    echo '<div class="woocommerce-MyAccount-content">';
    echo '<header class="fasp-dashboard-header">';
    echo '<h1>' . esc_html__('Educational Resources', 'fasp') . '</h1>';
    echo '<p class="fasp-muted">' . esc_html__('Browse guides, tutorials, and educational materials to improve your trading.', 'fasp') . '</p>';
    echo '</header>';
    
    // Get resources from CPT
    $resources = get_posts(array(
        'post_type' => 'fasp_resource',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($resources)) {
        echo '<p>' . esc_html__('No resources available yet. Check back soon!', 'fasp') . '</p>';
    } else {
        echo '<div class="fasp-dashboard fasp-grid">';
        
        foreach ($resources as $resource) {
            $thumbnail = get_the_post_thumbnail($resource->ID, 'medium', array('style' => 'width:100%;height:auto;border-radius:6px;'));
            $required_platform = get_post_meta($resource->ID, '_fasp_required_platform', true);
            
            echo '<div class="fasp-card fasp-card--half">';
            if ($thumbnail) {
                echo $thumbnail;
            }
            echo '<h3>' . esc_html($resource->post_title) . '</h3>';
            if ($resource->post_excerpt) {
                echo '<p class="fasp-muted">' . esc_html($resource->post_excerpt) . '</p>';
            }
            if ($required_platform) {
                $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
                if (isset($platforms[$required_platform])) {
                    echo '<p><small style="background:#f3f4f6;padding:2px 8px;border-radius:4px;">' . esc_html($platforms[$required_platform]['name']) . '</small></p>';
                }
            }
            echo '<p><a href="' . esc_url(get_permalink($resource->ID)) . '">' . esc_html__('Read More', 'fasp') . ' →</a></p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
});

// Handle coaches endpoint
add_action('woocommerce_account_coaches_endpoint', function() {
    echo '<div class="woocommerce-MyAccount-content">';
    echo '<header class="fasp-dashboard-header">';
    echo '<h1>' . esc_html__('Forex Coaches', 'fasp') . '</h1>';
    echo '<p class="fasp-muted">' . esc_html__('Connect with experienced forex coaches to accelerate your learning.', 'fasp') . '</p>';
    echo '</header>';
    
    // Get coaches from CPT
    $coaches = get_posts(array(
        'post_type' => 'fasp_coach',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($coaches)) {
        echo '<p>' . esc_html__('No coaches available yet. Check back soon!', 'fasp') . '</p>';
    } else {
        echo '<div class="fasp-dashboard fasp-grid">';
        
        foreach ($coaches as $coach) {
            $thumbnail = get_the_post_thumbnail($coach->ID, 'thumbnail', array('style' => 'width:80px;height:80px;border-radius:50%;object-fit:cover;'));
            $role = get_post_meta($coach->ID, '_fasp_coach_role', true);
            $tagline = get_post_meta($coach->ID, '_fasp_coach_tagline', true);
            $years = get_post_meta($coach->ID, '_fasp_coach_years', true);
            $rate = get_post_meta($coach->ID, '_fasp_coach_rate', true);
            
            echo '<div class="fasp-card fasp-card--half">';
            echo '<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">';
            if ($thumbnail) {
                echo $thumbnail;
            }
            echo '<div>';
            echo '<h3 style="margin:0;">' . esc_html($coach->post_title) . '</h3>';
            if ($role) {
                echo '<p class="fasp-muted" style="margin:4px 0 0 0;">' . esc_html($role) . '</p>';
            }
            echo '</div>';
            echo '</div>';
            
            if ($tagline) {
                echo '<p>' . esc_html($tagline) . '</p>';
            }
            
            if ($years || $rate) {
                echo '<p class="fasp-muted" style="font-size:0.9em;">';
                if ($years) {
                    echo esc_html($years) . ' ' . esc_html__('years experience', 'fasp');
                }
                if ($years && $rate) {
                    echo ' • ';
                }
                if ($rate) {
                    echo esc_html($rate) . ' ' . esc_html__('per hour', 'fasp');
                }
                echo '</p>';
            }
            
            echo '<p><a class="button" href="' . esc_url(get_permalink($coach->ID)) . '">' . esc_html__('View Profile', 'fasp') . '</a></p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
});
