# Forex Affiliate Suite PRO - Dashboard Enhancements

## Overview
This document describes the new dashboard enhancements implemented in version r14.9+, focusing on user segmentation, demo vs live trading tracking, educational resources, and mobile optimization.

## New Features

### 1. User Segmentation

The dashboard now automatically segments users into three categories:

- **Novice**: New users (< 30 days), no platform verifications, onboarding incomplete
- **Regular Trader**: Active users with verified platforms and completed onboarding
- **Affiliate**: Users with affiliate role or admin permissions

**Implementation Files:**
- `includes/user-segmentation.php` - Core segmentation logic
- Functions: `fasp_get_user_segment($user_id)`

### 2. Demo vs Live Trade Comparison Widget

Visual comparison widget showing performance metrics for both demo and live accounts:

**Tracked Metrics:**
- Total trades
- Win rate (%)
- Total profit
- Average profit per trade
- Best/worst trade

**Implementation Files:**
- `includes/demo-live-tracking.php` - Trade statistics functions
- Functions:
  - `fasp_get_demo_trade_stats($user_id)`
  - `fasp_get_live_trade_stats($user_id)`
  - `fasp_update_demo_trade_stats($user_id, $stats)`
  - `fasp_update_live_trade_stats($user_id, $stats)`

**User Meta Keys:**
- `fasp_demo_trade_stats` - Serialized array of demo stats
- `fasp_live_trade_stats` - Serialized array of live stats

### 3. Demo-to-Live Conversion CTA

Smart call-to-action that appears when:
- User has demo trades but no live trades
- User has 10+ demo trades with 50%+ win rate

**Function:** `fasp_should_show_demo_to_live_cta($user_id)`

### 4. Onboarding Progress Tracking

Visual progress bar and checklist for novice users:

**Checklist Items:**
1. Complete profile
2. Connect and verify a trading platform
3. Browse educational resources
4. Explore coaching options
5. Execute first trade

**Implementation:**
- `fasp_get_user_progress($user_id)` - Returns 0-100 percentage
- `fasp_get_onboarding_checklist($user_id)` - Returns checklist array

**User Meta Keys:**
- `fasp_viewed_resources` - Timestamp when user viewed resources
- `fasp_viewed_coaches` - Timestamp when user viewed coaches
- `fasp_onboarding_complete` - Boolean completion status
- `fasp_first_trade_complete` - Boolean first trade status

### 5. Affiliate Dashboard Section

Exclusive section for affiliates showing:
- Total referrals count
- Total earnings
- Current month earnings

**User Meta Keys:**
- `fasp_total_referrals` - Integer count
- `fasp_total_earnings` - Float amount
- `fasp_month_earnings` - Float amount

### 6. Educational Resources Panel

Organized sections for:
- Tutorials and guides
- FAQs
- Market news

Links to resources page with automatic tracking.

### 7. Dashboard Analytics & Heatmaps

Comprehensive activity tracking system:

**Tracked Events:**
- Page views (dashboard, platforms, resources, coaches)
- Card clicks
- Button clicks
- Platform interactions
- Resource/coach clicks
- Scroll depth (25%, 50%, 75%, 100%)
- Time on page

**Implementation Files:**
- `includes/dashboard-analytics.php` - Backend tracking
- `assets/js/fasp-analytics.js` - Frontend event tracking

**Database:**
- Table: `wp_fasp_analytics`
- Columns: id, user_id, activity, meta, created_at

**Functions:**
- `fasp_log_dashboard_activity($user_id, $activity, $meta)`
- `fasp_get_dashboard_analytics($days)` - Get statistics
- `fasp_get_activity_heatmap()` - Get heatmap data

**AJAX Endpoint:** `wp_ajax_fasp_track_activity`

### 8. Mobile Optimization

**Responsive Design Enhancements:**
- Stack cards on screens < 768px
- Reduce padding on mobile
- Single column layouts for trade stats, affiliate grid, education panel
- Smaller header font sizes
- Touch-friendly button sizes

**CSS File:** `assets/css/fasp-dashboard.css`

**Breakpoints:**
- 900px - Half-width cards become full width
- 768px - Mobile-specific optimizations

### 9. Motivational Nudges

Contextual encouragement messages:
- Appears when user is 80%+ through onboarding
- Shown in yellow highlighted box with emoji
- Encourages completion of remaining steps

## UI Components

### Progress Bar
```html
<div class="fasp-progress-container">
  <div class="fasp-progress-bar">
    <div class="fasp-progress-fill" style="width: X%"></div>
  </div>
  <span class="fasp-progress-text">X% Complete</span>
</div>
```

### Checklist
```html
<ul class="fasp-checklist">
  <li class="completed">
    <span class="fasp-check-icon">✅</span>
    Item text
  </li>
</ul>
```

### Trade Comparison Widget
```html
<div class="fasp-trade-comparison">
  <div class="fasp-trade-stats-grid">
    <div class="fasp-trade-stat-box fasp-demo">...</div>
    <div class="fasp-trade-stat-box fasp-live">...</div>
  </div>
</div>
```

## CSS Variables

```css
--fasp-primary: #0073aa;
--fasp-success: #16a34a;
--fasp-warning: #f59e0b;
--fasp-demo: #3b82f6;
--fasp-live: #10b981;
```

## Testing

Test file: `tests/test-user-dashboard.php`

**Test Coverage:**
- User segmentation detection
- Progress calculation
- Checklist structure
- Demo/live trade stats structure
- Dashboard data integrity

**Run Tests:**
```bash
phpunit tests/test-user-dashboard.php
```

## Usage Examples

### Update Demo Trade Stats
```php
$stats = array(
    'total_trades' => 25,
    'winning_trades' => 15,
    'losing_trades' => 10,
    'win_rate' => 60,
    'total_profit' => 1250.50,
    'average_profit' => 50.02,
    'best_trade' => 200.00,
    'worst_trade' => -150.00,
);
fasp_update_demo_trade_stats($user_id, $stats);
```

### Track Custom Activity
```php
fasp_log_dashboard_activity(
    get_current_user_id(),
    'completed_tutorial',
    array('tutorial' => 'Getting Started')
);
```

### Get User Segment
```php
$segment = fasp_get_user_segment($user_id);
if ($segment === 'novice') {
    // Show beginner content
}
```

## Future Enhancements

Potential additions:
1. Real-time market data integration
2. Social trading features
3. Achievement badges
4. Leaderboards for affiliates
5. AI-powered trading recommendations
6. Multi-language support
7. Dark mode theme
8. Email notifications for milestones

## Changelog

### r14.9
- Added user segmentation (Novice/Regular/Affiliate)
- Implemented demo vs live trade comparison widget
- Added onboarding progress tracking with checklist
- Created affiliate-specific dashboard section
- Added educational resources panel
- Implemented dashboard analytics and heatmap tracking
- Enhanced mobile responsiveness
- Added motivational nudges for users near completion
- Integrated demo-to-live conversion CTA

## Support

For issues or questions, please contact support or file an issue in the repository.
