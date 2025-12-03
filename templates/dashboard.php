<?php
/**
 * Forex Trading Dashboard Template
 * Enhanced user-facing dashboard with Get Started checklist, Platforms, Resources, Coaches, and CTAs.
 */
if (!defined('ABSPATH')) { exit; }

wp_enqueue_style('fasp-front');

// Get dashboard data using helper function
$dashboard_data = function_exists('fasp_get_user_dashboard_data') 
    ? fasp_get_user_dashboard_data() 
    : array();

$current_user   = wp_get_current_user();
$user_id        = get_current_user_id();
$is_logged_in   = $user_id > 0;
$is_admin       = current_user_can('manage_options');
$is_preview     = function_exists('fasp_is_preview_user_mode') && fasp_is_preview_user_mode();

// Get platforms
$platforms_raw  = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
$platforms      = function_exists('fasp_filter_platforms_for_user') 
    ? fasp_filter_platforms_for_user($platforms_raw) 
    : $platforms_raw;
$clicks_opt     = get_option('fasp_clicks', array());

// URLs
$myaccount_url  = function_exists('wc_get_page_permalink') 
    ? wc_get_page_permalink('myaccount') 
    : home_url('/my-account/');
$preview_url_on = add_query_arg('fasp_preview_user', '1', $myaccount_url . 'forex-dashboard/');
$preview_url_off = remove_query_arg('fasp_preview_user', $myaccount_url . 'forex-dashboard/');

// Deriv connection
$deriv_app_id   = function_exists('fasp_get_option') 
    ? fasp_get_option('deriv_app_id', '') 
    : get_option('fasp_deriv_app_id', '');
$callback       = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
$deriv_url      = $deriv_app_id 
    ? 'https://oauth.deriv.com/oauth2/authorize?app_id=' . rawurlencode($deriv_app_id) . '&scope=read&redirect_uri=' . rawurlencode($callback) 
    : '';
$deriv_verified = get_user_meta($user_id, '_fasp_deriv_verified', true) === '1';

// Progress steps
$progress_steps = isset($dashboard_data['progress']) ? $dashboard_data['progress'] : array(
    array('key' => 'verified', 'label' => 'Verify Platform Account', 'done' => $deriv_verified),
    array('key' => 'downloaded', 'label' => 'Download Resource', 'done' => get_user_meta($user_id, '_fasp_downloaded', true) === '1'),
    array('key' => 'booked', 'label' => 'Book Coach Session', 'done' => get_user_meta($user_id, '_fasp_booked', true) === '1'),
    array('key' => 'deposit', 'label' => 'First Deposit', 'done' => get_user_meta($user_id, '_fasp_deposit', true) === '1'),
    array('key' => 'trade', 'label' => 'First Trade', 'done' => get_user_meta($user_id, '_fasp_trade', true) === '1'),
);
$completed_steps = 0;
foreach ($progress_steps as $step) {
    if (!empty($step['done'])) {
        $completed_steps++;
    }
}
$progress_percent = count($progress_steps) > 0 ? round(($completed_steps / count($progress_steps)) * 100) : 0;

// Gating info
$gating_opts = get_option('fasp_platform_gating', array());
$require_login = !empty($gating_opts['require_login']);
$is_gated = $require_login && !$is_logged_in;

// UTM tracking info
$utm_source = isset($_GET['utm_source']) ? sanitize_text_field(wp_unslash($_GET['utm_source'])) : '';
$utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field(wp_unslash($_GET['utm_campaign'])) : '';
?>
<div class="fasp-wrap fasp-dashboard">
  <!-- Hero Section -->
  <div class="fasp-hero">
    <h1>Forex Trading Dashboard</h1>
    <?php if ($is_logged_in): ?>
      <p class="fasp-sub">Welcome back, <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>!</p>
    <?php else: ?>
      <p class="fasp-sub">Welcome to the Forex Trading Hub. Please log in to access all features.</p>
    <?php endif; ?>
    
    <div class="fasp-toolbar">
      <?php if ($is_admin): ?>
        <?php if (!$is_preview): ?>
          <a class="button" href="<?php echo esc_url($preview_url_on); ?>">Preview as User</a>
          <span class="fasp-admin-pill">Admin View</span>
        <?php else: ?>
          <a class="button" href="<?php echo esc_url($preview_url_off); ?>">Exit Preview</a>
          <span class="fasp-admin-pill">Preview Mode</span>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php if ($is_logged_in && !$deriv_verified && $deriv_url): ?>
        <a class="fasp-button" href="<?php echo esc_url($deriv_url); ?>">Connect Deriv Account</a>
      <?php endif; ?>
      
      <?php if (!$is_logged_in): ?>
        <a class="fasp-button" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Log In to Get Started</a>
      <?php endif; ?>
    </div>
    
    <!-- Status KPIs -->
    <div class="fasp-kpi">
      <div class="k">
        <span class="k-label">Account Status:</span>
        <?php if ($deriv_verified): ?>
          <span class="fasp-badge ok">Verified ✓</span>
        <?php elseif ($is_logged_in): ?>
          <span class="fasp-badge muted">Not verified</span>
        <?php else: ?>
          <span class="fasp-badge muted">Not logged in</span>
        <?php endif; ?>
      </div>
      <div class="k">
        <span class="k-label">Platforms Available:</span>
        <strong><?php echo is_array($platforms) ? count($platforms) : 0; ?></strong>
      </div>
      <div class="k">
        <span class="k-label">Progress:</span>
        <strong><?php echo esc_html($completed_steps); ?>/<?php echo count($progress_steps); ?></strong>
        <span class="fasp-badge"><?php echo esc_html($progress_percent); ?>%</span>
      </div>
    </div>
    
    <?php if ($utm_source || $utm_campaign): ?>
    <div class="fasp-utm-info">
      <small class="fasp-muted">
        <?php if ($utm_source): ?>Referred by: <?php echo esc_html($utm_source); ?><?php endif; ?>
        <?php if ($utm_campaign): ?> | Campaign: <?php echo esc_html($utm_campaign); ?><?php endif; ?>
      </small>
    </div>
    <?php endif; ?>
  </div>

  <!-- Gating Notice -->
  <?php if ($is_gated): ?>
  <div class="fasp-card fasp-gating-notice">
    <h3>🔒 Login Required</h3>
    <p class="fasp-muted">Some features on this page require you to be logged in. Please <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">log in</a> or <a href="<?php echo esc_url(wp_registration_url()); ?>">register</a> to access all trading resources.</p>
  </div>
  <?php endif; ?>

  <div class="fasp-grid">
    
    <!-- Get Started Checklist -->
    <div class="fasp-card fasp-checklist-card">
      <h2>🚀 Get Started with Forex Trading</h2>
      <p class="fasp-sub">Complete these steps to begin your trading journey.</p>
      
      <div class="fasp-progress-bar">
        <div class="fasp-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
      </div>
      <p class="fasp-muted" style="margin-bottom: 16px;"><?php echo esc_html($completed_steps); ?> of <?php echo count($progress_steps); ?> steps completed</p>
      
      <div class="fasp-checklist">
        <?php foreach ($progress_steps as $step): ?>
          <div class="fasp-step <?php echo !empty($step['done']) ? 'done' : ''; ?>">
            <span class="fasp-step-icon"><?php echo !empty($step['done']) ? '✓' : '○'; ?></span>
            <span class="fasp-step-label"><?php echo esc_html($step['label']); ?></span>
            <?php if (!empty($step['done'])): ?>
              <span class="fasp-badge ok">Complete</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (!$deriv_verified && $deriv_url && $is_logged_in): ?>
      <div class="fasp-cta-box">
        <p><strong>Next Step:</strong> Connect your Deriv account to unlock all features.</p>
        <a class="fasp-button" href="<?php echo esc_url($deriv_url); ?>">Connect Deriv Account →</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Trading Platforms -->
    <div class="fasp-card">
      <h2>📈 Trading Platforms</h2>
      <?php if (empty($platforms)): ?>
        <div class="fasp-note">No trading platforms available yet. Check back soon!</div>
      <?php else: ?>
        <div class="fasp-platforms-list">
          <?php foreach ($platforms as $p):
            $slug = isset($p['slug']) ? $p['slug'] : (isset($p['name']) ? sanitize_title($p['name']) : '');
            $name = isset($p['name']) ? $p['name'] : $slug;
            $logo = isset($p['logo_url']) ? $p['logo_url'] : '';
            $enabled = isset($p['enabled']) ? $p['enabled'] : '1';
            $show_in_dash = isset($p['show_in_dashboard']) ? $p['show_in_dashboard'] : '1';
            
            if ($enabled !== '1' || $show_in_dash !== '1') continue;
            
            $count = isset($clicks_opt[$slug]) ? intval($clicks_opt[$slug]) : 0;
            $show_clicks = (!empty($p['show_clicks_to_users']) || $is_admin);
            $verified_platform = get_user_meta($user_id, '_fasp_verified_' . $slug, true) === '1';
            $join_url = home_url('/fasp-go/' . $slug . '?dest=signup');
          ?>
            <div class="fasp-platform-item">
              <div class="fasp-platform-info">
                <?php if ($logo): ?>
                  <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name); ?>" class="fasp-platform-logo">
                <?php endif; ?>
                <div class="fasp-platform-meta">
                  <strong><?php echo esc_html($name); ?></strong>
                  <?php if ($verified_platform): ?>
                    <span class="fasp-badge ok">Verified ✓</span>
                  <?php endif; ?>
                  <?php if ($show_clicks && $count > 0): ?>
                    <span class="fasp-muted"><?php echo esc_html($count); ?> clicks</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="fasp-platform-actions">
                <a class="button" href="<?php echo esc_url($join_url); ?>">
                  <?php echo $verified_platform ? 'Open' : 'Join'; ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Forex Coaches -->
    <div class="fasp-card">
      <h2>👨‍🏫 Forex Trading Coaches</h2>
      <?php
        $coaches = get_posts(array('post_type' => 'fasp_coach_event', 'numberposts' => 6, 'post_status' => 'publish'));
        if (empty($coaches)):
      ?>
        <div class="fasp-note">No coaches available yet. Check back soon for expert trading guidance!</div>
      <?php else: ?>
        <div class="fasp-coaches">
          <?php foreach ($coaches as $c):
            $name = get_post_meta($c->ID, '_fasp_coach_name', true) ?: get_the_title($c);
            $role = get_post_meta($c->ID, '_fasp_coach_role', true);
            $intro = get_post_meta($c->ID, '_fasp_coach_intro', true);
            $live = get_post_meta($c->ID, '_fasp_coach_live', true);
            $aff = get_post_meta($c->ID, '_fasp_coach_affiliate', true);
            $pid = intval(get_post_meta($c->ID, '_fasp_coach_photo_id', true));
            $img = $pid ? wp_get_attachment_image_url($pid, 'medium') : get_the_post_thumbnail_url($c, 'medium');
            $initial = strtoupper(mb_substr(wp_strip_all_tags($name), 0, 1));
            $turl = ($aff || $live) ? add_query_arg(array('fasp_aff_click' => 'coach', 'id' => $c->ID), home_url('/')) : '';
          ?>
            <div class="fasp-coach">
              <?php if ($img): ?>
                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>">
              <?php else: ?>
                <div class="ph" aria-hidden="true"><?php echo esc_html($initial); ?></div>
              <?php endif; ?>
              <div class="meta">
                <h3><?php echo esc_html($name); ?>
                  <?php if ($role): ?>
                    <span class="role">— <?php echo esc_html($role); ?></span>
                  <?php endif; ?>
                </h3>
                <?php if ($intro): ?>
                  <div class="intro"><?php echo wp_kses_post($intro); ?></div>
                <?php endif; ?>
                <div class="actions">
                  <?php if ($turl): ?>
                    <a class="button" target="_blank" rel="noopener nofollow" href="<?php echo esc_url($turl); ?>">
                      <?php echo $aff ? 'Join Coaching' : 'Join Live'; ?>
                    </a>
                  <?php endif; ?>
                  <a class="button" href="<?php echo esc_url(get_permalink($c)); ?>">View Profile</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Trading Resources -->
    <div class="fasp-card">
      <h2>📚 Trading Resources</h2>
      <?php
        $resources = get_posts(array('post_type' => 'fasp_resource', 'numberposts' => 8, 'post_status' => 'publish'));
        if (empty($resources)):
      ?>
        <div class="fasp-note">No resources available yet. Check back soon for eBooks, guides, and tools!</div>
      <?php else: ?>
        <div class="fasp-resources">
          <?php foreach ($resources as $r):
            $type = get_post_meta($r->ID, '_fasp_type', true) ?: 'resource';
            $mon = get_post_meta($r->ID, '_fasp_monetization', true) ?: 'free';
            $prim = get_post_meta($r->ID, '_fasp_primary_url', true);
            $reqd = get_post_meta($r->ID, '_fasp_require_deriv', true) ? true : false;
            $cid = intval(get_post_meta($r->ID, '_fasp_cover_id', true));
            $img = $cid ? wp_get_attachment_image_url($cid, 'medium') : get_the_post_thumbnail_url($r, 'medium');
            $initial = strtoupper(mb_substr(wp_strip_all_tags(get_the_title($r)), 0, 1));
            $intro = get_the_excerpt($r) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $r)), 22, '…');

            $cta_url = get_permalink($r);
            $cta_txt = 'View';
            $target = '';

            if ($mon === 'external') {
                $cta_url = add_query_arg(array('fasp_aff_click' => 'resource', 'id' => $r->ID), home_url('/'));
                $cta_txt = 'Open';
                $target = ' target="_blank" rel="noopener nofollow"';
            } elseif ($mon === 'woo') {
                $has = function_exists('fasp_user_has_access_to_resource') ? fasp_user_has_access_to_resource($r->ID, $user_id) : true;
                $products = function_exists('fasp_get_products_for_resource') ? fasp_get_products_for_resource($r->ID) : array();
                if ($has) {
                    $cta_url = $prim ?: get_permalink($r);
                    $cta_txt = 'Open';
                } else {
                    $cta_url = !empty($products) ? get_permalink($products[0]) : get_permalink($r);
                    $cta_txt = 'Get Access';
                }
            } else {
                if ($prim) {
                    $cta_url = $prim;
                    $cta_txt = 'Open';
                    $target = ' target="_blank" rel="noopener"';
                }
            }

            // Check if user needs to verify to access
            $needs_verification = $reqd && !$deriv_verified;
          ?>
            <div class="fasp-res <?php echo $needs_verification ? 'fasp-res-gated' : ''; ?>">
              <?php if ($img): ?>
                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title($r)); ?>">
              <?php else: ?>
                <div class="ph" aria-hidden="true"><?php echo esc_html($initial); ?></div>
              <?php endif; ?>
              <div class="meta">
                <h3><?php echo esc_html(get_the_title($r)); ?></h3>
                <div class="badges">
                  <span class="b"><?php echo esc_html(ucfirst($type)); ?></span>
                  <?php if ($mon === 'free'): ?>
                    <span class="b ok">Free</span>
                  <?php endif; ?>
                  <?php if ($mon === 'woo'): ?>
                    <span class="b">Premium</span>
                  <?php endif; ?>
                  <?php if ($mon === 'external'): ?>
                    <span class="b">External</span>
                  <?php endif; ?>
                  <?php if ($reqd): ?>
                    <span class="b warn">Platform Verification Required</span>
                  <?php endif; ?>
                </div>
                <?php if ($intro): ?>
                  <div class="intro"><?php echo esc_html($intro); ?></div>
                <?php endif; ?>
                <div class="actions">
                  <?php if ($needs_verification): ?>
                    <span class="fasp-muted">🔒 Verify account to access</span>
                  <?php else: ?>
                    <a class="button"<?php echo $target; ?> href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_txt); ?></a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick Actions CTA -->
    <div class="fasp-card fasp-cta-card">
      <h2>⚡ Quick Actions</h2>
      <div class="fasp-quick-actions">
        <?php if (!$is_logged_in): ?>
          <a class="fasp-button" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Log In</a>
          <a class="button" href="<?php echo esc_url(wp_registration_url()); ?>">Create Account</a>
        <?php else: ?>
          <?php if (!$deriv_verified && $deriv_url): ?>
            <a class="fasp-button" href="<?php echo esc_url($deriv_url); ?>">Connect Deriv</a>
          <?php endif; ?>
          <?php 
          $resources_page = get_post_type_archive_link('fasp_resource');
          if ($resources_page): 
          ?>
            <a class="button" href="<?php echo esc_url($resources_page); ?>">Browse All Resources</a>
          <?php endif; ?>
          <?php 
          $coaches_page = get_post_type_archive_link('fasp_coach_event');
          if ($coaches_page): 
          ?>
            <a class="button" href="<?php echo esc_url($coaches_page); ?>">Find a Coach</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Licensing/Support -->
    <div class="fasp-card">
      <h2>📜 Your Licenses & Support</h2>
      <p class="fasp-sub">After purchase, your license keys and support access will appear here.</p>
      <?php if ($is_logged_in): ?>
        <p class="fasp-muted">No active licenses found. Purchase a premium resource to get started.</p>
      <?php else: ?>
        <p class="fasp-muted"><a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Log in</a> to view your licenses.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* Enhanced Dashboard Styles */
.fasp-dashboard .fasp-hero {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 20px;
}
.fasp-dashboard .fasp-hero h1 {
  color: #065f46;
  margin-bottom: 8px;
}
.fasp-dashboard .fasp-toolbar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin: 16px 0;
}
.fasp-dashboard .fasp-admin-pill {
  background: #fef3c7;
  border: 1px solid #fcd34d;
  color: #92400e;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}
.fasp-dashboard .fasp-kpi {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  margin-top: 16px;
}
.fasp-dashboard .fasp-kpi .k {
  display: flex;
  align-items: center;
  gap: 8px;
}
.fasp-dashboard .fasp-kpi .k-label {
  color: #6b7280;
}
.fasp-dashboard .fasp-utm-info {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #bbf7d0;
}
.fasp-dashboard .fasp-gating-notice {
  background: #fef3c7;
  border-color: #fcd34d;
}
.fasp-dashboard .fasp-checklist-card {
  background: linear-gradient(135deg, #fff 0%, #f0fdf4 100%);
}
.fasp-dashboard .fasp-progress-bar {
  background: #e5e7eb;
  border-radius: 999px;
  height: 8px;
  overflow: hidden;
  margin-bottom: 8px;
}
.fasp-dashboard .fasp-progress-fill {
  background: linear-gradient(90deg, #22c55e, #16a34a);
  height: 100%;
  border-radius: 999px;
  transition: width 0.3s ease;
}
.fasp-dashboard .fasp-checklist {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.fasp-dashboard .fasp-step {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}
.fasp-dashboard .fasp-step.done {
  background: #f0fdf4;
  border-color: #86efac;
}
.fasp-dashboard .fasp-step-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #e5e7eb;
  font-size: 14px;
}
.fasp-dashboard .fasp-step.done .fasp-step-icon {
  background: #22c55e;
  color: #fff;
}
.fasp-dashboard .fasp-cta-box {
  margin-top: 16px;
  padding: 16px;
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 10px;
}
.fasp-dashboard .fasp-platforms-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.fasp-dashboard .fasp-platform-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}
.fasp-dashboard .fasp-platform-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.fasp-dashboard .fasp-platform-logo {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  object-fit: contain;
}
.fasp-dashboard .fasp-platform-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.fasp-dashboard .fasp-quick-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.fasp-dashboard .fasp-res-gated {
  opacity: 0.7;
}
.fasp-dashboard .fasp-cta-card {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}
</style>