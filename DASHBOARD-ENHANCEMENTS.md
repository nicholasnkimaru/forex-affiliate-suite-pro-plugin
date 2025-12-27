# Forex Dashboard Enhancement Documentation

## Overview
This document describes the enhancements made to the Forex Affiliate Suite Pro Plugin dashboard to maximize user conversion through role-based personalization and demo-to-live integration.

## Changes Summary

### 1. Fixed Routing & Eliminated Broken Links

#### Issues Resolved
- **Duplicate Endpoint Registration**: The `forex-dashboard` endpoint was registered 3 times (main plugin file, dashboard.php, and woocommerce-dashboard.php)
- **Conflicting Action Hooks**: The `woocommerce_account_forex-dashboard_endpoint` action was hooked twice, causing template conflicts
- **Deprecated Legacy Code**: The old `includes/dashboard.php` file has been deprecated and replaced with proper template loading

#### Changes Made
- Removed duplicate endpoint registrations from `forex-affiliate-suite-pro.php` activation hook
- Deprecated `includes/dashboard.php` with clear documentation
- Consolidated all endpoint handling in `includes/woocommerce-dashboard.php`
- All endpoints (platforms, resources, coaches) now route to their respective templates correctly

### 2. User Role Personalization

#### New Helper Functions (`includes/dashboard-helpers.php`)

##### `fasp_is_affiliate($user_id = null)`
Determines if a user is an affiliate based on:
- Admin role (administrators are considered affiliates)
- 'affiliate' user role
- 'fasp_is_affiliate' user meta flag

##### `fasp_get_user_experience_level($user_id = null)`
Auto-detects user experience level based on:
- Explicit setting via 'fasp_experience_level' user meta
- Number of verified platforms
- Account age (days since registration)

Returns: `'novice'`, `'intermediate'`, or `'experienced'`

##### `fasp_get_onboarding_checklist($user_id = null)`
Returns an array with onboarding task completion status:
```php
array(
    'complete_profile' => bool,
    'verify_email' => bool,
    'connect_platform' => bool,
    'complete_tutorial' => bool,
    'make_first_trade' => bool,
)
```

##### `fasp_get_user_demo_account($user_id = null)`
Retrieves demo account data from user meta.

##### `fasp_get_user_live_account($user_id = null)`
Retrieves live trading account data from user meta.

##### `fasp_get_user_referral_stats($user_id = null)`
Gets affiliate referral statistics including:
- Total referrals
- Active referrals
- Total clicks
- Conversion rate
- Total commission

#### Dashboard Widgets

##### For Novice Traders
- **Onboarding Checklist Widget**: Displays step-by-step tasks to complete
- **Getting Started CTA**: Prominent call-to-action to connect first platform
- **Beginner Resources**: Filtered content appropriate for new traders

##### For Experienced Traders
- **Advanced Tools Card**: Access to professional trading tools
- **Performance Tracking**: Focus on analytics and insights
- **Advanced Resources**: Strategic content for experienced users

##### For Affiliates
- **Affiliate Performance Widget**: Real-time stats on:
  - Total referrals and active referrals
  - Click tracking and conversion rates
  - Commission earnings
- **Referral Management Links**: Quick access to affiliate tools

### 3. Demo-to-Live Integration

#### Performance Tracking Widget
Displays side-by-side comparison of demo and live accounts:

- **Demo Account Card**:
  - Practice balance
  - Profit/Loss percentage
  - Visual indicators (green for profit, red for loss)

- **Live Account Card**:
  - Real balance
  - Profit/Loss percentage
  - Performance trends

- **Upgrade CTA**:
  - When user has demo but no live account
  - Encourages transition to live trading

#### Data Storage
Account data is stored in user meta:
- `fasp_demo_account`: Array with balance, profit_loss, etc.
- `fasp_live_account`: Array with balance, profit_loss, etc.

### 4. Enhanced Styling

#### New CSS Classes (`assets/css/fasp-dashboard.css`)

##### Onboarding Checklist
```css
.fasp-checklist
.fasp-checked (✅ indicator)
.fasp-unchecked (⬜ indicator)
```

##### Performance Cards
```css
.fasp-performance-grid
.fasp-performance-card
.fasp-demo / .fasp-live / .fasp-upgrade
.fasp-stat-large / .fasp-stat-small
.fasp-positive / .fasp-negative
```

##### Affiliate Stats
```css
.fasp-stats-grid
.fasp-stat-box
.fasp-stat-value / .fasp-stat-label
.fasp-stat-highlight
```

## Usage

### Setting User Experience Level
```php
// Set explicitly
update_user_meta($user_id, 'fasp_experience_level', 'experienced');

// Or let it auto-detect based on activity
$level = fasp_get_user_experience_level($user_id);
```

### Setting User as Affiliate
```php
// Method 1: Add affiliate role
$user = new WP_User($user_id);
$user->add_role('affiliate');

// Method 2: Set user meta
update_user_meta($user_id, 'fasp_is_affiliate', true);
```

### Updating Account Balances
```php
// Demo account
update_user_meta($user_id, 'fasp_demo_account', array(
    'balance' => 10000,
    'profit_loss' => 15.5, // percentage
    'last_updated' => time(),
));

// Live account
update_user_meta($user_id, 'fasp_live_account', array(
    'balance' => 5000,
    'profit_loss' => -2.3, // percentage
    'last_updated' => time(),
));
```

### Updating Referral Stats
```php
update_user_meta($user_id, 'fasp_referral_stats', array(
    'total_referrals' => 25,
    'active_referrals' => 18,
    'total_clicks' => 450,
    'conversion_rate' => 5.6,
    'total_commission' => 1250.00,
));
```

### Marking Onboarding Tasks Complete
```php
// Mark tutorial as completed
update_user_meta($user_id, 'fasp_tutorial_completed', true);

// Mark first trade as completed
update_user_meta($user_id, 'fasp_first_trade', true);
```

## Testing

### Unit Tests
Run the included unit tests to verify functionality:
```bash
# If PHPUnit is configured
phpunit tests/test-user-dashboard.php
```

### Manual Testing Checklist
1. ✅ Dashboard loads without errors
2. ✅ All navigation links work (platforms, resources, coaches)
3. ✅ Novice users see onboarding checklist
4. ✅ Affiliates see referral stats
5. ✅ Demo/Live comparison displays correctly
6. ✅ CSS styling loads properly
7. ✅ Responsive design works on mobile

## Security Considerations

All user inputs are properly sanitized:
- User IDs validated
- User meta properly escaped in output
- Capability checks for affiliate/admin features
- Follows WordPress coding standards

## Performance

- Helper functions use efficient caching
- Minimal database queries
- CSS uses modern best practices
- No JavaScript overhead on basic dashboard view

## Browser Compatibility

Tested and working on:
- Chrome/Edge (Chromium)
- Firefox
- Safari
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

Potential improvements for future versions:
1. Chart.js integration for visual performance trends
2. Real-time updates via AJAX
3. Push notifications for affiliate milestones
4. Advanced filtering for resources by experience level
5. Integration with external trading APIs
6. Automated experience level progression

## Support

For issues or questions:
1. Check this documentation
2. Review the inline code comments
3. Test with the included unit tests
4. Contact the development team

---

**Version**: r14.8+
**Last Updated**: 2025-12-27
**Author**: FASP Development Team
