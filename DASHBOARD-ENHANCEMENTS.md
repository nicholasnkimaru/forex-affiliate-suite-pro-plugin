# Forex Dashboard Enhancement Documentation

## Overview
This document consolidates the dashboard enhancement work for the Forex Affiliate Suite PRO plugin. It resolves routing conflicts, describes role-based personalization, documents demo→live integration, and lists analytics, styling, and testing details. The goal: maximize conversions with no duplicate pages, correct routing, and role-tailored UX.

---

## Changes Summary

### 1. Routing consolidation & broken-link fixes
What was wrong
- `forex-dashboard` endpoint registered multiple times across files causing hook/template conflicts.
- `woocommerce_account_forex-dashboard_endpoint` was hooked more than once.
- Legacy inline handler (`includes/dashboard.php`) conflicted with template loaders.

What was done
- Consolidated endpoint registrations and routing into `includes/woocommerce-dashboard.php`.
- Deprecated `includes/dashboard.php` (kept as documentation/backwards-compatibility stub but not active).
- Ensured endpoints `forex-dashboard`, `platforms`, `resources`, and `coaches` route to their respective templates and that `forex-affiliate` is NOT exposed on the frontend menu.
- Verified template existence before include and provided graceful fallback messages if templates are missing.
- Removed duplicate rewrite/endpoint registrations from activation hooks and other files.

Files/handlers to review (already updated)
- `includes/woocommerce-dashboard.php` — central endpoint registration & renderer delegates
- `templates/dashboard.php`, `templates/platforms.php`, `templates/resources.php`, `templates/coaches.php` — template loaders and placeholders

Notes: before adding or changing an endpoint, confirm the corresponding template exists to avoid creating orphan links.

---

### 2. Role-based personalization (user segmentation)
Objective: show tailored content for three segments: Novice, Regular Trader, Affiliate.

New helpers (consolidated into `includes/dashboard-helpers.php`)
- `fasp_is_affiliate( $user_id = null )` — checks admin, 'affiliate' role, or user meta `fasp_is_affiliate`.
- `fasp_get_user_experience_level( $user_id = null )` — returns `novice|intermediate|experienced` based on explicit meta, verified platforms count, and account age.
- `fasp_get_onboarding_checklist( $user_id = null )` — returns checklist status array:
  - `complete_profile`, `verify_email`, `connect_platform`, `complete_tutorial`, `make_first_trade`.
- `fasp_get_user_demo_account( $user_id = null )` / `fasp_get_user_live_account( $user_id = null )` — read account arrays from user meta.
- `fasp_get_user_referral_stats( $user_id = null )` — returns affiliate KPIs (total_referrals, active_referrals, clicks, conversion_rate, total_commission).

Dashboard behavior
- Novice: show onboarding checklist, getting-started CTA, filtered beginner resources.
- Regular Trader: show performance analytics, advanced tools card, progress indicators.
- Affiliate: show referral performance widget, earnings and quick links to affiliate tools.

Implementation notes
- All helpers are guarded with `function_exists()` to avoid redeclaration.
- Capability checks ensure sensitive affiliate UI is shown only to proper users.
- Helpers aim to be efficient and cacheable (using transients where appropriate).

---

### 3. Demo → Live integration & performance comparison
Goals
- Let users compare demo and live performance to motivate conversion.
- Provide an upgrade CTA when appropriate.

What was added
- Side-by-side performance widget on the dashboard:
  - Demo card: `balance`, `profit_loss` (%), trades summary.
  - Live card: `balance`, `profit_loss` (%), live metrics.
  - Color-coded indicators: `.fasp-positive`, `.fasp-negative`.
- Smart CTA `fasp_should_show_demo_to_live_cta( $user_id )` shows when:
  - User has demo data but no live account; or
  - Demo performance meets thresholds (e.g., 10+ demo trades and win rate ≥ 50%).

User meta keys used
- `fasp_demo_account` — array: `balance`, `profit_loss`, `last_updated`, `total_trades`, `win_rate`, etc.
- `fasp_live_account` — same shape for live metrics.
- `fasp_demo_trade_stats`, `fasp_live_trade_stats` — optional extended stats.

Storage guidance
- Keep user meta shape consistent and versioned.
- If heavy stats required, consider separate tables (future enhancement).

---

### 4. Analytics, heatmaps & tracking
Purpose: measure feature usage and conversion funnel bottlenecks.

What was implemented
- Frontend tracker `assets/js/fasp-analytics.js` (lightweight) for user interactions:
  - Page views, card clicks, CTA clicks, scroll depth, time on page.
- Backend logging and aggregation in `includes/dashboard-analytics.php`.
- Optional DB table: `wp_fasp_analytics` (id, user_id, activity, meta, created_at) — created only if needed.
- AJAX endpoint `wp_ajax_fasp_track_activity` to receive events (nonce-protected).

Privacy & performance
- Events are minimal JSON entries; consider batching.
- IPs or PII are not stored unless necessary; all inputs sanitized.
- Tracking opt-out respects user privacy and site policies.

---

### 5. UI / Styling / Mobile optimization
CSS & responsiveness
- Added and extended `assets/css/fasp-dashboard.css` with classes:
  - `.fasp-dashboard`, `.fasp-card`, `.fasp-card--half`, `.fasp-card--wide`
  - `.fasp-checklist`, `.fasp-checked`, `.fasp-unchecked`
  - `.fasp-performance-grid`, `.fasp-performance-card`, `.fasp-demo`, `.fasp-live`, `.fasp-upgrade`
  - `.fasp-stats-grid`, `.fasp-stat-box`, `.fasp-progress-container`, etc.
- Breakpoints:
  - ≤900px: half cards stack full-width.
  - ≤768px: mobile spacing reduced, single-column layout for key widgets.

JS
- `assets/js/fasp-dashboard.js` renders charts (if Chart.js present), avatars, and card click behavior.
- `assets/js/fasp-analytics.js` sends interaction events to server (non-blocking).

Accessibility & UX
- Buttons/taps are touch-friendly.
- All dynamic content has ARIA-friendly markup where applicable.
- Color contrasts considered for status badges.

---

## Implementation Files (high level)
- includes/
  - `includes/woocommerce-dashboard.php` (routing + template delegates)
  - `includes/dashboard-helpers.php` (new helpers)
  - `includes/user-segmentation.php` (compatibility)
  - `includes/demo-live-tracking.php` (stats read/write helpers)
  - `includes/dashboard-analytics.php` (event logging)
  - `includes/user-dashboard-loader.php` (defensive endpoint registration)
- templates/
  - `templates/dashboard.php` (updated dashboard with conditional widgets)
  - `templates/platforms.php`, `templates/resources.php`, `templates/coaches.php` (ensure exist)
- assets/
  - `assets/css/fasp-dashboard.css` (styling)
  - `assets/js/fasp-dashboard.js` (interactivity)
  - `assets/js/fasp-analytics.js` (tracking)
- tests/
  - `tests/test-user-dashboard.php` (unit tests for helpers & data shapes)

Before adding new templates or endpoints, verify template files exist; do not create duplicate templates.

---

## Usage examples

Set experience level:
```php
update_user_meta($user_id, 'fasp_experience_level', 'experienced');
$level = fasp_get_user_experience_level($user_id);
```

Mark user as affiliate:
```php
$user = new WP_User($user_id);
$user->add_role('affiliate'); // or:
update_user_meta($user_id, 'fasp_is_affiliate', true);
```

Update demo/live account:
```php
update_user_meta($user_id, 'fasp_demo_account', array(
  'balance' => 10000,
  'profit_loss' => 15.5,
  'total_trades' => 12,
  'win_rate' => 58.3,
  'last_updated' => time(),
));
```

Log activity:
```php
fasp_log_dashboard_activity( get_current_user_id(), 'clicked_upgrade_cta', array('source'=>'dashboard') );
```

---

## Testing

Automated
- PHPUnit test file: `tests/test-user-dashboard.php`
  - Tests segmentation detection, checklist structure, account meta shapes.

Manual checklist
1. Dashboard loads without PHP/JS errors.
2. Nav links lead to existing pages (platforms, resources, coaches) — no 404s.
3. Novice users (test account <30 days, no platforms) see onboarding checklist.
4. Affiliates see referral stats only if role/meta set.
5. Demo/live comparison shows correct values from user meta.
6. CTA logic triggers when demo-only conditions are met.
7. Analytics events log and aggregate correctly (sample events appear in DB or logs).
8. Mobile layout stacks and is touch-friendly.

CI & security scans
- Run CodeQL or other scanners; no new vulnerabilities expected if sanitization is used.

## Security Considerations
- All inputs sanitized with appropriate WP APIs (sanitize_text_field, esc_url_raw, absint, floatval).
- Nonces used for AJAX endpoints (`check_ajax_referer`).
- Capability checks (e.g., `current_user_can`) before showing admin/affiliate-only content.
- No sensitive provider secrets stored in user meta.

## Performance
- Helpers use transients/cache where frequent computations are necessary.
- Analytics designed to be write-light (batched or aggregated).
- JS is lazy-loaded on dashboard pages only.

## Future enhancements (prioritized)
1. Chart.js integration for richer trend visualization (deferred until Chart.js confirmed).
2. Real-time updates via WebSocket / server push for live account metrics.
3. Optional separate table for heavy trade stats for scale.
4. Affiliate leaderboards / gamification modules.
5. Multi-language & dark-mode support.

## Changelog (summary)
- r14.8 → r14.9: Routing consolidation, role-based personalization, demo/live comparison widget, onboarding progress, analytics tracking, mobile improvements, new CSS and unit tests.

## Support
- If you run into issues:
  1. Check the templates exist for endpoints before linking.
  2. Review `includes/dashboard-helpers.php` for helper behaviors.
  3. Run unit tests: `phpunit tests/test-user-dashboard.php`.
  4. File an issue in the repository with reproduction steps.

---

**End of consolidated document**