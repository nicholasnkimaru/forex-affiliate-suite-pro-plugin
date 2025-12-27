<?php
/**
 * User Segmentation and Role Detection
 * 
 * Determines user types: Novice, Regular Trader, or Affiliate
 */

if (!defined('ABSPATH')) exit;

/**
 * Determine user segment based on activity and role
 * 
 * @param int $user_id User ID (defaults to current user)
 * @return string 'novice', 'regular', or 'affiliate'
 */
function fasp_get_user_segment($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return 'novice';
    
    $user = get_userdata($user_id);
    if (!$user) return 'novice';
    
    // Check if user is affiliate (use user object instead of current_user_can for specific user)
    if (in_array('administrator', (array) $user->roles, true)) {
        return 'affiliate';
    }
    
    if (in_array('affiliate', (array) $user->roles, true)) {
        return 'affiliate';
    }
    
    if (get_user_meta($user_id, 'fasp_is_affiliate', true)) {
        return 'affiliate';
    }
    
    // Check user activity to determine novice vs regular
    $registration_date = strtotime($user->user_registered);
    $days_since_registration = (time() - $registration_date) / DAY_IN_SECONDS;
    
    // Get user's platform verifications
    $platforms = get_option('fasp_platforms', array());
    $verified_count = 0;
    foreach ($platforms as $slug => $p) {
        if (get_user_meta($user_id, '_fasp_verified_' . $slug, true)) {
            $verified_count++;
        }
    }
    
    // Get onboarding completion
    $onboarding_complete = get_user_meta($user_id, 'fasp_onboarding_complete', true);
    
    // Determine segment
    if ($days_since_registration < 30 && $verified_count == 0 && !$onboarding_complete) {
        return 'novice';
    }
    
    return 'regular';
}

/**
 * Get user progress percentage (0-100)
 * 
 * @param int $user_id User ID
 * @return int Progress percentage
 */
function fasp_get_user_progress($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return 0;
    
    $total_steps = 5;
    $completed_steps = 0;
    
    // Step 1: Profile completed
    $user = get_userdata($user_id);
    if ($user && $user->display_name && $user->user_email) {
        $completed_steps++;
    }
    
    // Step 2: Verified at least one platform
    $platforms = get_option('fasp_platforms', array());
    foreach ($platforms as $slug => $p) {
        if (get_user_meta($user_id, '_fasp_verified_' . $slug, true)) {
            $completed_steps++;
            break;
        }
    }
    
    // Step 3: Viewed resources
    if (get_user_meta($user_id, 'fasp_viewed_resources', true)) {
        $completed_steps++;
    }
    
    // Step 4: Connected with coach or viewed coaching
    if (get_user_meta($user_id, 'fasp_viewed_coaches', true)) {
        $completed_steps++;
    }
    
    // Step 5: Onboarding completed
    if (get_user_meta($user_id, 'fasp_onboarding_complete', true)) {
        $completed_steps++;
    }
    
    return intval(($completed_steps / $total_steps) * 100);
}

/**
 * Get onboarding checklist items for user
 * 
 * @param int $user_id User ID
 * @return array Checklist items with completion status
 */
function fasp_get_onboarding_checklist($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return array();
    
    $checklist = array();
    
    // Item 1: Complete profile
    $user = get_userdata($user_id);
    $checklist[] = array(
        'id' => 'complete_profile',
        'label' => __('Complete your profile', 'fasp'),
        'completed' => ($user && $user->display_name && $user->user_email),
    );
    
    // Item 2: Verify a platform
    $platforms = get_option('fasp_platforms', array());
    $verified = false;
    foreach ($platforms as $slug => $p) {
        if (get_user_meta($user_id, '_fasp_verified_' . $slug, true)) {
            $verified = true;
            break;
        }
    }
    $checklist[] = array(
        'id' => 'verify_platform',
        'label' => __('Connect and verify a trading platform', 'fasp'),
        'completed' => $verified,
    );
    
    // Item 3: Browse resources
    $checklist[] = array(
        'id' => 'view_resources',
        'label' => __('Browse educational resources', 'fasp'),
        'completed' => (bool) get_user_meta($user_id, 'fasp_viewed_resources', true),
    );
    
    // Item 4: Meet coaches
    $checklist[] = array(
        'id' => 'view_coaches',
        'label' => __('Explore coaching options', 'fasp'),
        'completed' => (bool) get_user_meta($user_id, 'fasp_viewed_coaches', true),
    );
    
    // Item 5: Make first trade (demo or live)
    $checklist[] = array(
        'id' => 'first_trade',
        'label' => __('Execute your first trade', 'fasp'),
        'completed' => (bool) get_user_meta($user_id, 'fasp_first_trade_complete', true),
    );
    
    return $checklist;
}
