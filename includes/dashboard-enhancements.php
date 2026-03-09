<?php
/**
 * Dashboard Enhancement Helper Functions
 * 
 * Contains all functions for gamification, XP, badges, missions, etc.
 * 
 * @package FASP
 * @version 2.0
 * @since r14.9
 */

if (!defined('ABSPATH')) exit;

/**
 * Get daily missions for a user
 * 
 * @param int $user_id User ID
 * @return array Array of daily missions
 */
if (!function_exists('fasp_get_daily_missions')) {
    function fasp_get_daily_missions($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return [];
        }

        $today = current_time('Y-m-d');
        $last_missions_date = get_user_meta($user_id, '_fasp_missions_date', true);

        // Reset missions if new day
        if ($last_missions_date !== $today) {
            delete_user_meta($user_id, '_fasp_daily_missions_completed');
            update_user_meta($user_id, '_fasp_missions_date', $today);
        }

        $completed_missions = get_user_meta($user_id, '_fasp_daily_missions_completed', true) ?: [];

        // Define all possible missions
        $all_missions = [
            [
                'id' => 'daily_login',
                'title' => __('Daily Login', 'fasp'),
                'description' => __('Log in to your account', 'fasp'),
                'difficulty' => 1,
                'reward_xp' => 5,
                'time_estimate' => '1 min',
                'cta_text' => __('Done', 'fasp'),
                'cta_url' => '#',
            ],
            [
                'id' => 'view_charts',
                'title' => __('Analyze a Chart', 'fasp'),
                'description' => __('Study one trading chart for 5 minutes', 'fasp'),
                'difficulty' => 2,
                'reward_xp' => 10,
                'time_estimate' => '5 min',
                'cta_text' => __('View Charts', 'fasp'),
                'cta_url' => '#',
            ],
            [
                'id' => 'execute_trade',
                'title' => __('Place a Trade', 'fasp'),
                'description' => __('Execute one demo or live trade', 'fasp'),
                'difficulty' => 3,
                'reward_xp' => 25,
                'time_estimate' => '10 min',
                'cta_text' => __('Start Trading', 'fasp'),
                'cta_url' => '#',
            ],
            [
                'id' => 'watch_tutorial',
                'title' => __('Watch a Tutorial', 'fasp'),
                'description' => __('Watch a 5-minute trading tutorial', 'fasp'),
                'difficulty' => 1,
                'reward_xp' => 15,
                'time_estimate' => '5 min',
                'cta_text' => __('Watch Now', 'fasp'),
                'cta_url' => '#',
            ],
            [
                'id' => 'share_profile',
                'title' => __('Share Your Profile', 'fasp'),
                'description' => __('Share your trading journey on social media', 'fasp'),
                'difficulty' => 2,
                'reward_xp' => 20,
                'time_estimate' => '3 min',
                'cta_text' => __('Share', 'fasp'),
                'cta_url' => '#',
            ],
        ];

        // Add status to each mission
        foreach ($all_missions as &$mission) {
            $mission['status'] = in_array($mission['id'], $completed_missions) ? 'completed' : 'pending';
        }

        return apply_filters('fasp_daily_missions', $all_missions, $user_id);
    }
}

/**
 * Mark a mission as complete
 * 
 * @param int $user_id User ID
 * @param string $mission_id Mission ID
 * @return bool Success
 */
if (!function_exists('fasp_complete_mission')) {
    function fasp_complete_mission($user_id, $mission_id) {
        if (!$user_id || !$mission_id) {
            return false;
        }

        $completed = get_user_meta($user_id, '_fasp_daily_missions_completed', true) ?: [];
        
        if (in_array($mission_id, $completed)) {
            return false; // Already completed
        }

        $completed[] = $mission_id;
        update_user_meta($user_id, '_fasp_daily_missions_completed', $completed);

        // Get mission info and award XP
        $missions = fasp_get_daily_missions($user_id);
        foreach ($missions as $mission) {
            if ($mission['id'] === $mission_id) {
                fasp_award_xp($user_id, $mission['reward_xp'], $mission_id);
                
                // Fire analytics
                do_action('fasp_mission_completed', $user_id, $mission_id, $mission);
                
                return true;
            }
        }

        return false;
    }
}

/**
 * Award XP to user
 * 
 * @param int $user_id User ID
 * @param int $amount XP amount
 * @param string $source Source of XP (mission ID, action, etc.)
 * @return int New total XP
 */
if (!function_exists('fasp_award_xp')) {
    function fasp_award_xp($user_id, $amount, $source = '') {
        if (!$user_id || $amount <= 0) {
            return 0;
        }

        // Get current XP
        $current_xp = intval(get_user_meta($user_id, '_fasp_xp_balance', true)) ?: 0;
        $today_xp = intval(get_user_meta($user_id, '_fasp_xp_today', true)) ?: 0;

        // Update totals
        $new_total = $current_xp + $amount;
        $new_today = $today_xp + $amount;

        update_user_meta($user_id, '_fasp_xp_balance', $new_total);
        update_user_meta($user_id, '_fasp_xp_today', $new_today);

        // Check for level up
        $old_level = fasp_get_user_level($current_xp);
        $new_level = fasp_get_user_level($new_total);

        if ($new_level > $old_level) {
            do_action('fasp_user_level_up', $user_id, $new_level, $old_level);
        }

        // Log XP transaction
        do_action('fasp_xp_awarded', $user_id, $amount, $source, $new_total);

        return $new_total;
    }
}

/**
 * Get user's current level based on XP
 * 
 * @param int $xp XP amount
 * @return int Level (1-10)
 */
if (!function_exists('fasp_get_user_level')) {
    function fasp_get_user_level($xp = null) {
        if (null === $xp) {
            $user_id = get_current_user_id();
            $xp = intval(get_user_meta($user_id, '_fasp_xp_balance', true)) ?: 0;
        }

        // Level progression: 0-100 = 1, 100-250 = 2, 250-500 = 3, etc.
        $xp_thresholds = [
            1 => 0,
            2 => 100,
            3 => 250,
            4 => 500,
            5 => 1000,
            6 => 1500,
            7 => 2000,
            8 => 2500,
            9 => 3000,
            10 => 4000,
        ];

        $level = 1;
        foreach ($xp_thresholds as $lvl => $threshold) {
            if ($xp >= $threshold) {
                $level = $lvl;
            } else {
                break;
            }
        }

        return apply_filters('fasp_user_level', min($level, 10), $xp);
    }
}

/**
 * Update user's login streak
 * 
 * @param int $user_id User ID
 * @return int Current streak
 */
if (!function_exists('fasp_update_user_streak')) {
    function fasp_update_user_streak($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return 0;
        }

        $today = current_time('Y-m-d');
        $last_login_date = get_user_meta($user_id, '_fasp_last_login_date', true);
        $current_streak = intval(get_user_meta($user_id, '_fasp_login_streak', true)) ?: 0;

        // Check if already logged in today
        if ($last_login_date === $today) {
            return $current_streak;
        }

        // Check if yesterday
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($last_login_date === $yesterday) {
            // Continue streak
            $current_streak++;
        } else {
            // Reset streak
            $current_streak = 1;
        }

        update_user_meta($user_id, '_fasp_last_login_date', $today);
        update_user_meta($user_id, '_fasp_login_streak', $current_streak);

        // Check for streak badges
        if ($current_streak === 5) {
            fasp_award_badge($user_id, 'streak_5days');
        } elseif ($current_streak === 10) {
            fasp_award_badge($user_id, 'streak_10days');
        } elseif ($current_streak === 30) {
            fasp_award_badge($user_id, 'streak_30days');
        }

        return $current_streak;
    }
}

/**
 * Award a badge to user
 * 
 * @param int $user_id User ID
 * @param string $badge_id Badge ID
 * @return bool Success
 */
if (!function_exists('fasp_award_badge')) {
    function fasp_award_badge($user_id, $badge_id) {
        if (!$user_id || !$badge_id) {
            return false;
        }

        $badges = get_user_meta($user_id, '_fasp_badges', true) ?: [];

        // Don't award if already has it
        if (in_array($badge_id, $badges)) {
            return false;
        }

        $badges[] = $badge_id;
        update_user_meta($user_id, '_fasp_badges', $badges);

        // Fire action
        do_action('fasp_badge_earned', $user_id, $badge_id);

        return true;
    }
}

/**
 * Check if user has earned a badge
 * 
 * @param int $user_id User ID
 * @param string $badge_id Badge ID
 * @return bool Has badge
 */
if (!function_exists('fasp_has_badge')) {
    function fasp_has_badge($user_id, $badge_id) {
        $badges = get_user_meta($user_id, '_fasp_badges', true) ?: [];
        return in_array($badge_id, $badges);
    }
}

/**
 * Get all badges for a user
 * 
 * @param int $user_id User ID
 * @return array Badge IDs
 */
if (!function_exists('fasp_get_user_badges')) {
    function fasp_get_user_badges($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, '_fasp_badges', true) ?: [];
    }
}

/**
 * Get badge definitions
 * 
 * @param string $badge_id Optional: get specific badge
 * @return array Badge definition(s)
 */
if (!function_exists('fasp_get_badge_definitions')) {
    function fasp_get_badge_definitions($badge_id = null) {
        $badges = [
            'first_steps' => [
                'title' => __('First Steps', 'fasp'),
                'description' => __('Complete onboarding', 'fasp'),
                'icon' => '👟',
                'rarity' => 'common',
            ],
            'fundamentals_master' => [
                'title' => __('Fundamentals Master', 'fasp'),
                'description' => __('Complete Module 1', 'fasp'),
                'icon' => '📚',
                'rarity' => 'common',
            ],
            'profitable_trader' => [
                'title' => __('Profitable Trader', 'fasp'),
                'description' => __('5+ winning trades', 'fasp'),
                'icon' => '💰',
                'rarity' => 'uncommon',
            ],
            'streak_5days' => [
                'title' => __('5 Day Streak', 'fasp'),
                'description' => __('Log in 5 consecutive days', 'fasp'),
                'icon' => '🔥',
                'rarity' => 'uncommon',
            ],
            'streak_10days' => [
                'title' => __('10 Day Streak', 'fasp'),
                'description' => __('Log in 10 consecutive days', 'fasp'),
                'icon' => '🔥🔥',
                'rarity' => 'rare',
            ],
            'streak_30days' => [
                'title' => __('Monthly Master', 'fasp'),
                'description' => __('Log in 30 consecutive days', 'fasp'),
                'icon' => '🏆',
                'rarity' => 'legendary',
            ],
            'community_star' => [
                'title' => __('Community Star', 'fasp'),
                'description' => __('20+ community posts', 'fasp'),
                'icon' => '⭐',
                'rarity' => 'rare',
            ],
            'gold_trader' => [
                'title' => __('Gold Trader', 'fasp'),
                'description' => __('$5,000+ account balance', 'fasp'),
                'icon' => '🥇',
                'rarity' => 'rare',
            ],
        ];

        if ($badge_id) {
            return $badges[$badge_id] ?? [];
        }

        return apply_filters('fasp_badge_definitions', $badges);
    }
}

/**
 * Hook into user login to update streak and award XP
 */
add_action('wp_login', function($user_login) {
    $user = get_user_by('login', $user_login);
    if ($user) {
        fasp_update_user_streak($user->ID);
        fasp_award_xp($user->ID, 5, 'daily_login');
    }
}, 10, 1);

/**
 * Reset daily XP at midnight
 */
add_action('fasp_daily_reset', function() {
    // Get all users and reset daily XP
    $users = get_users(['fields' => 'ids']);
    foreach ($users as $user_id) {
        update_user_meta($user_id, '_fasp_xp_today', 0);
        delete_user_meta($user_id, '_fasp_daily_missions_completed');
    }
});

// Schedule daily reset
if (!wp_next_scheduled('fasp_daily_reset')) {
    wp_schedule_event(strtotime('tomorrow 00:00'), 'daily', 'fasp_daily_reset');
}
