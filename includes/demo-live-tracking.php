<?php
/**
 * Demo and Live Trade Tracking
 * 
 * Handles demo vs live trade data and comparison
 */

if (!defined('ABSPATH')) exit;

/**
 * Get demo trade statistics for user
 * 
 * @param int $user_id User ID
 * @return array Demo trade stats
 */
function fasp_get_demo_trade_stats($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return array();
    
    $demo_data = get_user_meta($user_id, 'fasp_demo_trade_stats', true);
    
    if (!is_array($demo_data) || empty($demo_data)) {
        return array(
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'total_profit' => 0,
            'average_profit' => 0,
            'best_trade' => 0,
            'worst_trade' => 0,
        );
    }
    
    return wp_parse_args($demo_data, array(
        'total_trades' => 0,
        'winning_trades' => 0,
        'losing_trades' => 0,
        'win_rate' => 0,
        'total_profit' => 0,
        'average_profit' => 0,
        'best_trade' => 0,
        'worst_trade' => 0,
    ));
}

/**
 * Get live trade statistics for user
 * 
 * @param int $user_id User ID
 * @return array Live trade stats
 */
function fasp_get_live_trade_stats($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return array();
    
    $live_data = get_user_meta($user_id, 'fasp_live_trade_stats', true);
    
    if (!is_array($live_data) || empty($live_data)) {
        return array(
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'total_profit' => 0,
            'average_profit' => 0,
            'best_trade' => 0,
            'worst_trade' => 0,
        );
    }
    
    return wp_parse_args($live_data, array(
        'total_trades' => 0,
        'winning_trades' => 0,
        'losing_trades' => 0,
        'win_rate' => 0,
        'total_profit' => 0,
        'average_profit' => 0,
        'best_trade' => 0,
        'worst_trade' => 0,
    ));
}

/**
 * Check if user should see demo-to-live CTA
 * 
 * @param int $user_id User ID
 * @return bool True if CTA should be shown
 */
function fasp_should_show_demo_to_live_cta($user_id = 0) {
    $user_id = intval($user_id) ?: get_current_user_id();
    if (!$user_id) return false;
    
    $demo_stats = fasp_get_demo_trade_stats($user_id);
    $live_stats = fasp_get_live_trade_stats($user_id);
    
    // Show CTA if user has demo trades but no live trades
    if ($demo_stats['total_trades'] > 0 && $live_stats['total_trades'] == 0) {
        return true;
    }
    
    // Show CTA if demo win rate is good (>50%) and has at least 10 trades
    if ($demo_stats['total_trades'] >= 10 && $demo_stats['win_rate'] >= 50 && $live_stats['total_trades'] == 0) {
        return true;
    }
    
    return false;
}

/**
 * Update demo trade stats
 * 
 * @param int $user_id User ID
 * @param array $stats Stats to update
 * @return bool Success
 */
function fasp_update_demo_trade_stats($user_id, $stats) {
    $user_id = intval($user_id);
    if (!$user_id || !is_array($stats)) return false;
    
    return update_user_meta($user_id, 'fasp_demo_trade_stats', $stats);
}

/**
 * Update live trade stats
 * 
 * @param int $user_id User ID
 * @param array $stats Stats to update
 * @return bool Success
 */
function fasp_update_live_trade_stats($user_id, $stats) {
    $user_id = intval($user_id);
    if (!$user_id || !is_array($stats)) return false;
    
    return update_user_meta($user_id, 'fasp_live_trade_stats', $stats);
}
