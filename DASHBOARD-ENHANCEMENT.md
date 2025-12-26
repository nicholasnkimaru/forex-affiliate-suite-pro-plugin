# Dashboard Enhancement - Implementation Guide

## Overview
This update enhances the user dashboard with new functionality for affiliates and regular users, including:
- Complete referral link generator
- Tracking statistics display
- Marketing materials section
- Platform connection pages
- Resources and coaches browsers

## New Endpoints Added

The following WooCommerce My Account endpoints have been registered:

1. **forex-dashboard** - Main trading dashboard (existing, enhanced)
2. **forex-affiliate** - Affiliate tools and referral link generator
3. **referrals** - Referral statistics and earnings tracking
4. **platforms** - Trading platforms connection and verification
5. **resources** - Educational resources browser
6. **coaches** - Trading coaches listing and booking

## Installation & Activation

After updating the plugin, you **MUST** flush rewrite rules:

### Option 1: Use the Admin Notice
1. Navigate to the WordPress admin dashboard
2. You'll see a blue notice at the top: "FASP Dashboard Update"
3. Click the "Flush Rewrite Rules" button

### Option 2: Manual Flush
1. Go to Settings → Permalinks
2. Click "Save Changes" (no changes needed, just save)

### Option 3: Deactivate/Reactivate
1. Go to Plugins
2. Deactivate "Forex Affiliate Suite PRO"
3. Reactivate the plugin

## Features by Page

### Main Dashboard (forex-dashboard)
**For All Users:**
- Welcome message
- Connected platforms count
- Quick action buttons
- Links to other dashboard sections

**For Affiliates:**
- Total clicks statistics
- Conversion count and rate
- Total earnings display
- Pending payout amount
- Quick access to referral tools

### Affiliate Tools (forex-affiliate)
**Available to:** Admins, users with 'affiliate' role, or users with 'fasp_is_affiliate' meta
**Features:**
- Unique referral code generation (auto-created)
- Platform-specific referral links
- One-click copy to clipboard
- Marketing materials download section
- Embed code generator
- Social media sharing buttons (Twitter, Facebook, LinkedIn, WhatsApp)

### Referrals Page (referrals)
**Available to:** Affiliates only
**Features:**
- Total clicks counter
- Conversions counter
- Conversion rate calculation
- Total earnings display
- Pending vs paid payout breakdown
- Recent referral activity table (last 10 events)
- Integration with fasp_clicks database table

### Platforms Page (platforms)
**Available to:** All users
**Features:**
- List of all configured trading platforms
- Verification status for each platform
- OAuth connection buttons (e.g., Deriv)
- Direct signup links
- Platform logos and descriptions
- KYC requirement indicators

### Resources Page (resources)
**Available to:** All users
**Features:**
- Grid display of educational resources
- Thumbnail images
- Excerpts and publication dates
- Direct links to full resources
- Category filter buttons
- Archive link for all resources

### Coaches Page (coaches)
**Available to:** All users
**Features:**
- Coach profiles with avatars/photos
- Specialty areas display
- Rating system (star display)
- Profile and booking links
- Explanation of coaching process

## User Role Configuration

To make a user an affiliate:
1. Go to Users → Edit User
2. Scroll to "FASP Affiliate" section
3. Check "Mark as affiliate"
4. Save the user

Affiliates will see:
- "Affiliate Tools" menu item
- "Referrals" menu item
- Enhanced statistics on main dashboard

## Database Integration

The dashboard integrates with:
- **wp_fasp_clicks** - Tracks referral clicks and conversions
- **wp_usermeta** - Stores referral codes and affiliate status
- **wp_posts** - Displays resources and coaches
- **wp_options** - Reads platform configurations

## Customization Points

### Referral Commission Rate
Edit `templates/template-referrals.php` line 45:
```php
$total_earnings = $total_conversions * 10; // Change 10 to your commission per conversion
```

### Marketing Materials
Edit `templates/template-forex-affiliate.php` lines 90-105 to add real banner download links.

### Platform Links
Configure in admin: Platform Setup page to set affiliate_url and signup_url for each platform.

## Styling

All dashboard pages use the unified card system from `assets/css/fasp-dashboard.css`:
- `.fasp-dashboard` - Main grid container
- `.fasp-card` - Individual card
- `.fasp-card--half` - Half-width card
- `.fasp-card--wide` - Full-width card
- `.fasp-muted` - Muted text color

## Troubleshooting

### 404 Errors on New Pages
**Solution:** Flush rewrite rules (see Installation section)

### "This page is only available to affiliates" message
**Solution:** Mark the user as an affiliate (see User Role Configuration)

### No click data showing
**Solution:** Ensure tracking is enabled and the fasp_clicks table exists

### Referral links not generating
**Solution:** Referral codes are auto-generated on first page visit. Check user meta for 'fasp_referral_code'

## Technical Notes

### Endpoints Registration
Endpoints are registered in `includes/woocommerce-dashboard.php` on the `init` hook.

### Menu Items
Menu items are added via `woocommerce_account_menu_items` filter with affiliate role check.

### Template Loading
Templates are loaded from `/templates/` directory using `fasp_load_template()` function.

### Assets
CSS and JS are enqueued only on dashboard pages via `woocommerce-dashboard-assets.php`.

## Future Enhancements

Potential additions:
- Real-time earnings tracking
- Payout request system
- Commission tier system
- Advanced analytics charts
- Email notifications for new referrals
- Multi-level marketing (MLM) support
- Custom banner uploader
- A/B testing for referral links
