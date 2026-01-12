<?php
/**
 * Dashboard Helper Functions
 * Provides utilities for dashboard personalization and user role detection
 */

if (!defined('ABSPATH')) exit;

/**
 * Determine if user is an affiliate
 * 
 * @param int $user_id User ID
 * @return bool True if user is affiliate
 */
function fasp_is_affiliate($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // Check if admin
    if (in_array('administrator', $user->roles)) {
        return true;
    }
    
    // Check if has affiliate role
    if (in_array('affiliate', $user->roles)) {
        return true;
    }
    
    // Check usermeta flag
    if (get_user_meta($user_id, 'fasp_is_affiliate', true)) {
        return true;
    }
    
    return false;
}

/**
 * Determine user experience level
 * 
 * @param int $user_id User ID
 * @return string 'novice', 'intermediate', or 'experienced'
 */
function fasp_get_user_experience_level($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Check if experience level is explicitly set
    $level = get_user_meta($user_id, 'fasp_experience_level', true);
    if ($level) {
        return $level;
    }
    
    // Get user data once for reuse
    $user = get_userdata($user_id);
    if (!$user) {
        return 'novice';
    }
    
    // Auto-detect based on activity
    $verified_platforms = 0;
    $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
    
    foreach ($platforms as $platform) {
        $slug = isset($platform['slug']) ? $platform['slug'] : (isset($platform['key']) ? $platform['key'] : '');
        if ($slug && get_user_meta($user_id, '_fasp_verified_' . $slug, true)) {
            $verified_platforms++;
        }
    }
    
    // Check account age
    $account_age_days = 0;
    $registered = strtotime($user->user_registered);
    $account_age_days = floor((time() - $registered) / (60 * 60 * 24));
    
    // Determine experience level
    if ($verified_platforms >= 2 || $account_age_days > 90) {
        return 'experienced';
    } elseif ($verified_platforms >= 1 || $account_age_days > 30) {
        return 'intermediate';
    } else {
        return 'novice';
    }
}

/**
 * Get user's demo account data
 * 
 * @param int $user_id User ID
 * @return array|false Array with demo account data or false if none
 */
function fasp_get_user_demo_account($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $demo_data = get_user_meta($user_id, 'fasp_demo_account', true);
    
    if (!$demo_data || !is_array($demo_data)) {
        return false;
    }
    
    return $demo_data;
}

/**
 * Get user's live trading account data
 * 
 * @param int $user_id User ID
 * @return array|false Array with live account data or false if none
 */
function fasp_get_user_live_account($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $live_data = get_user_meta($user_id, 'fasp_live_account', true);
    
    if (!$live_data || !is_array($live_data)) {
        return false;
    }
    
    return $live_data;
}

/**
 * Get user's referral stats (for affiliates)
 * 
 * @param int $user_id User ID
 * @return array Referral statistics
 */
function fasp_get_user_referral_stats($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $stats = array(
        'total_referrals' => 0,
        'active_referrals' => 0,
        'total_clicks' => 0,
        'conversion_rate' => 0,
        'total_commission' => 0,
    );
    
    // Get referral data from post meta or custom table
    // This is a placeholder - implement based on actual data structure
    $referral_data = get_user_meta($user_id, 'fasp_referral_stats', true);
    
    if ($referral_data && is_array($referral_data)) {
        $stats = wp_parse_args($referral_data, $stats);
    }
    
    return $stats;
}

/**
 * Get onboarding checklist for user
 * 
 * @param int $user_id User ID
 * @return array Onboarding checklist with completion status
 */
function fasp_get_onboarding_checklist($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $checklist = array(
        'complete_profile' => false,
        'verify_email' => false,
        'connect_platform' => false,
        'complete_tutorial' => false,
        'make_first_trade' => false,
    );
    
    // Check profile completion
    $user = get_userdata($user_id);
    if ($user && $user->first_name && $user->last_name) {
        $checklist['complete_profile'] = true;
    }
    
    // Check email verification (WooCommerce handles this)
    if ($user && !empty($user->user_email)) {
        $checklist['verify_email'] = true;
    }
    
    // Check if any platform is connected
    $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
    foreach ($platforms as $platform) {
        $slug = isset($platform['slug']) ? $platform['slug'] : (isset($platform['key']) ? $platform['key'] : '');
        if ($slug && get_user_meta($user_id, '_fasp_verified_' . $slug, true)) {
            $checklist['connect_platform'] = true;
            break;
        }
    }
    
    // Check tutorial completion
    if (get_user_meta($user_id, 'fasp_tutorial_completed', true)) {
        $checklist['complete_tutorial'] = true;
    }
    
    // Check first trade
    if (get_user_meta($user_id, 'fasp_first_trade', true)) {
        $checklist['make_first_trade'] = true;
    }
    
    return $checklist;
}
