<?php
if (!defined('ABSPATH')) {
    exit;
}
wp_enqueue_style('fasp-front');

// Get dashboard data using the helper function
$dashboard_data = function_exists('fasp_get_user_dashboard_data') ? fasp_get_user_dashboard_data() : array();

$current_user   = wp_get_current_user();
$platforms_raw  = function_exists('fasp_get_platforms') ? fasp_get_platforms() : array();
$platforms      = function_exists('fasp_filter_platforms_for_user') ? fasp_filter_platforms_for_user($platforms_raw) : $platforms_raw;
$clicks_opt     = get_option('fasp_clicks', array());
$is_admin       = current_user_can('manage_options');
$is_preview     = function_exists('fasp_is_preview_user_mode') && fasp_is_preview_user_mode();
$myaccount_url  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$preview_url_on = add_query_arg('fasp_preview_user', '1', $myaccount_url . 'forex-dashboard/');
$preview_url_off = remove_query_arg('fasp_preview_user', $myaccount_url . 'forex-dashboard/');
$deriv_app_id   = function_exists('fasp_get_option') ? fasp_get_option('deriv_app_id', '') : get_option('fasp_deriv_app_id', '');
$callback       = add_query_arg('fasp_deriv_callback', '1', home_url('/'));
$deriv_url      = $deriv_app_id ? ('https://oauth.deriv.com/oauth2/authorize?app_id=' . rawurlencode($deriv_app_id) . '&scope=read&redirect_uri=' . rawurlencode($callback)) : '';
$deriv_verified = get_user_meta(get_current_user_id(), '_fasp_deriv_verified', true) === '1';

// Progress steps
$progress_steps = array(
    array('key' => 'verified', 'label' => __('Verify Deriv Account', 'forex-affiliate-suite-pro'), 'icon' => '✓'),
    array('key' => 'downloaded', 'label' => __('Download Trading eBook', 'forex-affiliate-suite-pro'), 'icon' => '📚'),
    array('key' => 'booked', 'label' => __('Book Coaching Session', 'forex-affiliate-suite-pro'), 'icon' => '📅'),
    array('key' => 'deposit', 'label' => __('Make First Deposit', 'forex-affiliate-suite-pro'), 'icon' => '💰'),
    array('key' => 'trade', 'label' => __('Complete First Trade', 'forex-affiliate-suite-pro'), 'icon' => '📈')
);

$progress_count = function_exists('fasp_get_progress_count') ? fasp_get_progress_count() : 0;
$total_steps = count($progress_steps);
$progress_percent = $total_steps > 0 ? round(($progress_count / $total_steps) * 100) : 0;

// Check gating awareness
$gating_enabled = get_option('fasp_gating_require_login', false);
$geo_gating_enabled = get_option('fasp_geo_enabled', false);

// Get UTM parameters for display
$utm_params = array();
$utm_keys = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term');
foreach ($utm_keys as $key) {
    if (isset($_GET[$key])) {
        $value = sanitize_text_field(wp_unslash($_GET[$key]));
        if (preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
            $utm_params[$key] = $value;
        }
    }
}
?>
<div class="fasp-wrap fasp-dashboard">
    <!-- Hero Section -->
    <div class="fasp-hero">
        <h1><?php echo esc_html__('Forex Trading Dashboard', 'forex-affiliate-suite-pro'); ?></h1>
        <p class="fasp-sub"><?php
            /* translators: %s: user display name */
            printf(esc_html__('Welcome back, %s!', 'forex-affiliate-suite-pro'), esc_html($current_user->display_name ?: $current_user->user_login));
        ?></p>
        
        <div class="fasp-toolbar">
            <?php if ($is_admin) : ?>
                <?php if (!$is_preview) : ?>
                    <a class="button" href="<?php echo esc_url($preview_url_on); ?>"><?php esc_html_e('Preview as User', 'forex-affiliate-suite-pro'); ?></a>
                    <span class="fasp-admin-pill"><?php esc_html_e('Admin View', 'forex-affiliate-suite-pro'); ?></span>
                <?php else : ?>
                    <a class="button" href="<?php echo esc_url($preview_url_off); ?>"><?php esc_html_e('Exit Preview', 'forex-affiliate-suite-pro'); ?></a>
                    <span class="fasp-admin-pill"><?php esc_html_e('Preview Mode', 'forex-affiliate-suite-pro'); ?></span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!$deriv_verified && $deriv_url) : ?>
                <a class="button button-primary" href="<?php echo esc_url($deriv_url); ?>"><?php esc_html_e('Connect Deriv', 'forex-affiliate-suite-pro'); ?></a>
            <?php endif; ?>
        </div>
        
        <!-- KPI Strip -->
        <div class="fasp-kpi">
            <div class="k">
                <span class="label"><?php esc_html_e('Deriv Status:', 'forex-affiliate-suite-pro'); ?></span>
                <?php if ($deriv_verified) : ?>
                    <span class="fasp-badge ok"><?php esc_html_e('Verified', 'forex-affiliate-suite-pro'); ?></span>
                <?php else : ?>
                    <span class="fasp-badge muted"><?php esc_html_e('Not verified', 'forex-affiliate-suite-pro'); ?></span>
                <?php endif; ?>
            </div>
            <div class="k">
                <span class="label"><?php esc_html_e('Platforms:', 'forex-affiliate-suite-pro'); ?></span>
                <strong><?php echo is_array($platforms) ? count($platforms) : 0; ?></strong>
            </div>
            <div class="k">
                <span class="label"><?php esc_html_e('Progress:', 'forex-affiliate-suite-pro'); ?></span>
                <strong><?php echo esc_html($progress_count . '/' . $total_steps); ?></strong>
            </div>
        </div>
    </div>

    <div class="fasp-grid">
        <!-- Get Started Checklist -->
        <div class="fasp-card fasp-checklist-card">
            <h2><?php esc_html_e('Get Started Checklist', 'forex-affiliate-suite-pro'); ?></h2>
            <div class="fasp-progress-bar">
                <div class="fasp-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
            </div>
            <p class="fasp-progress-text">
                <?php
                /* translators: 1: completed steps, 2: total steps, 3: percentage */
                printf(esc_html__('%1$d of %2$d steps completed (%3$d%%)', 'forex-affiliate-suite-pro'), $progress_count, $total_steps, $progress_percent);
                ?>
            </p>
            <ul class="fasp-checklist">
                <?php foreach ($progress_steps as $step) :
                    $meta_key = $step['key'] === 'verified' ? '_fasp_verified_deriv' : '_fasp_' . $step['key'];
                    $is_done = get_user_meta(get_current_user_id(), $meta_key, true) === '1';
                ?>
                    <li class="<?php echo $is_done ? 'completed' : ''; ?>">
                        <span class="icon"><?php echo $is_done ? '✓' : esc_html($step['icon']); ?></span>
                        <span class="text"><?php echo esc_html($step['label']); ?></span>
                        <?php if ($is_done) : ?>
                            <span class="status fasp-badge ok"><?php esc_html_e('Done', 'forex-affiliate-suite-pro'); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Platforms List -->
        <div class="fasp-card">
            <h2><?php esc_html_e('Trading Platforms', 'forex-affiliate-suite-pro'); ?></h2>
            <?php if (empty($platforms)) : ?>
                <div class="fasp-note">
                    <?php
                    /* translators: %s: link text */
                    printf(esc_html__('No platforms available. Add them in %s and set visibility.', 'forex-affiliate-suite-pro'), '<em>' . esc_html__('Forex Trading → Setup', 'forex-affiliate-suite-pro') . '</em>');
                    ?>
                </div>
            <?php else : ?>
                <div class="fasp-platforms-grid">
                    <?php foreach ($platforms as $p) :
                        $k = isset($p['slug']) ? sanitize_key($p['slug']) : (isset($p['key']) ? sanitize_key($p['key']) : sanitize_key($p['name'] ?? ''));
                        $count = absint($clicks_opt[$k] ?? 0);
                        $showClicks = !empty($p['show_clicks_to_users']) || $is_admin;
                        $verified = get_user_meta(get_current_user_id(), '_fasp_verified_' . $k, true) === '1';
                        $logo = !empty($p['logo_url']) ? $p['logo_url'] : '';
                    ?>
                        <div class="fasp-platform-card">
                            <?php if ($logo) : ?>
                                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($p['name'] ?? $k); ?>" class="platform-logo">
                            <?php else : ?>
                                <div class="platform-logo-placeholder"><?php echo esc_html(strtoupper(substr($p['name'] ?? $k, 0, 1))); ?></div>
                            <?php endif; ?>
                            <div class="platform-info">
                                <h3><?php echo esc_html($p['name'] ?? $k); ?></h3>
                                <?php if ($verified) : ?>
                                    <span class="fasp-badge ok"><?php esc_html_e('Verified', 'forex-affiliate-suite-pro'); ?></span>
                                <?php else : ?>
                                    <span class="fasp-badge muted"><?php esc_html_e('Not verified', 'forex-affiliate-suite-pro'); ?></span>
                                <?php endif; ?>
                                <?php if ($showClicks) : ?>
                                    <span class="click-count"><?php
                                        /* translators: %d: number of clicks */
                                        printf(esc_html__('%d clicks', 'forex-affiliate-suite-pro'), $count);
                                    ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="platform-actions">
                                <a class="button" href="<?php echo esc_url(add_query_arg('fasp_click', rawurlencode($k), home_url('/'))); ?>"><?php esc_html_e('Open', 'forex-affiliate-suite-pro'); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resources Section -->
        <div class="fasp-card">
            <h2><?php esc_html_e('Trading Resources', 'forex-affiliate-suite-pro'); ?></h2>
            <?php
            $res = get_posts(array('post_type' => 'fasp_resource', 'numberposts' => 8, 'post_status' => 'publish'));
            if (empty($res)) :
            ?>
                <div class="fasp-note"><?php esc_html_e('No resources available yet.', 'forex-affiliate-suite-pro'); ?></div>
            <?php else : ?>
                <div class="fasp-resources">
                    <?php foreach ($res as $r) :
                        $type = get_post_meta($r->ID, '_fasp_type', true) ?: 'n/a';
                        $mon = get_post_meta($r->ID, '_fasp_monetization', true) ?: 'free';
                        $prim = get_post_meta($r->ID, '_fasp_primary_url', true);
                        $reqd = get_post_meta($r->ID, '_fasp_require_deriv', true) ? true : false;
                        $cid = absint(get_post_meta($r->ID, '_fasp_cover_id', true));
                        $img = $cid ? wp_get_attachment_image_url($cid, 'medium') : get_the_post_thumbnail_url($r, 'medium');
                        $initial = strtoupper(mb_substr(wp_strip_all_tags(get_the_title($r)), 0, 1));
                        $intro = get_the_excerpt($r) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $r)), 22, '…');

                        $cta_url = get_permalink($r);
                        $cta_txt = __('View', 'forex-affiliate-suite-pro');
                        $target = '';

                        if ($mon === 'external') {
                            $cta_url = add_query_arg(array('fasp_aff_click' => 'resource', 'id' => $r->ID), home_url('/'));
                            $cta_txt = __('Open', 'forex-affiliate-suite-pro');
                            $target = ' target="_blank" rel="noopener nofollow"';
                        } elseif ($mon === 'woo') {
                            $uid = get_current_user_id();
                            $has = function_exists('fasp_user_has_access_to_resource') ? fasp_user_has_access_to_resource($r->ID, $uid) : true;
                            $products = function_exists('fasp_get_products_for_resource') ? fasp_get_products_for_resource($r->ID) : array();
                            if ($has) {
                                $cta_url = $prim ?: get_permalink($r);
                                $cta_txt = __('Open', 'forex-affiliate-suite-pro');
                            } else {
                                $cta_url = !empty($products) ? get_permalink($products[0]) : get_permalink($r);
                                $cta_txt = __('Buy', 'forex-affiliate-suite-pro');
                            }
                        } else {
                            if ($prim) {
                                $cta_url = $prim;
                                $cta_txt = __('Open', 'forex-affiliate-suite-pro');
                                $target = ' target="_blank" rel="noopener"';
                            }
                        }
                    ?>
                        <div class="fasp-res">
                            <?php if ($img) : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title($r)); ?>">
                            <?php else : ?>
                                <div class="ph" aria-hidden="true"><?php echo esc_html($initial); ?></div>
                            <?php endif; ?>
                            <div class="meta">
                                <h3><?php echo esc_html(get_the_title($r)); ?></h3>
                                <div class="badges">
                                    <span class="b"><?php echo esc_html(ucfirst($type)); ?></span>
                                    <?php if ($mon === 'free') : ?>
                                        <span class="b ok"><?php esc_html_e('Free', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($mon === 'woo') : ?>
                                        <span class="b"><?php esc_html_e('Premium', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($mon === 'external') : ?>
                                        <span class="b"><?php esc_html_e('External', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($reqd) : ?>
                                        <span class="b warn"><?php esc_html_e('Deriv required', 'forex-affiliate-suite-pro'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($intro) : ?>
                                    <div class="intro"><?php echo esc_html($intro); ?></div>
                                <?php endif; ?>
                                <div class="actions">
                                    <a class="button"<?php echo $target; ?> href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_txt); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coaches Section -->
        <div class="fasp-card">
            <h2><?php esc_html_e('Trading Coaches', 'forex-affiliate-suite-pro'); ?></h2>
            <?php
            $cls = get_posts(array('post_type' => 'fasp_coach_event', 'numberposts' => 6, 'post_status' => 'publish'));
            if (empty($cls)) :
            ?>
                <div class="fasp-note">
                    <?php
                    /* translators: %s: link text */
                    printf(esc_html__('No coaches available yet. Add some under %s.', 'forex-affiliate-suite-pro'), '<em>' . esc_html__('Forex Coaches', 'forex-affiliate-suite-pro') . '</em>');
                    ?>
                </div>
            <?php else : ?>
                <div class="fasp-coaches">
                    <?php foreach ($cls as $c) :
                        $name = get_post_meta($c->ID, '_fasp_coach_name', true) ?: get_the_title($c);
                        $role = get_post_meta($c->ID, '_fasp_coach_role', true);
                        $intro = get_post_meta($c->ID, '_fasp_coach_intro', true);
                        $live = get_post_meta($c->ID, '_fasp_coach_live', true);
                        $aff = get_post_meta($c->ID, '_fasp_coach_affiliate', true);
                        $pid = absint(get_post_meta($c->ID, '_fasp_coach_photo_id', true));
                        $img = $pid ? wp_get_attachment_image_url($pid, 'medium') : get_the_post_thumbnail_url($c, 'medium');
                        $initial = strtoupper(mb_substr(wp_strip_all_tags($name), 0, 1));

                        $turl = ($aff || $live) ? add_query_arg(array('fasp_aff_click' => 'coach', 'id' => $c->ID), home_url('/')) : '';
                    ?>
                        <div class="fasp-coach">
                            <?php if ($img) : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>">
                            <?php else : ?>
                                <div class="ph" aria-hidden="true"><?php echo esc_html($initial); ?></div>
                            <?php endif; ?>
                            <div class="meta">
                                <h3><?php echo esc_html($name); ?><?php if ($role) : ?> <span class="role">— <?php echo esc_html($role); ?></span><?php endif; ?></h3>
                                <?php if ($intro) : ?>
                                    <div class="intro"><?php echo wp_kses_post($intro); ?></div>
                                <?php endif; ?>
                                <div class="actions">
                                    <?php if ($turl) : ?>
                                        <a class="button" target="_blank" rel="noopener nofollow" href="<?php echo esc_url($turl); ?>"><?php echo $aff ? esc_html__('Join Coaching', 'forex-affiliate-suite-pro') : esc_html__('Join Live', 'forex-affiliate-suite-pro'); ?></a>
                                    <?php endif; ?>
                                    <a class="button" href="<?php echo esc_url(get_permalink($c)); ?>"><?php esc_html_e('Profile', 'forex-affiliate-suite-pro'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- UTM Parameters Display (if present) -->
        <?php if (!empty($utm_params)) : ?>
            <div class="fasp-card fasp-utm-card">
                <h2><?php esc_html_e('Campaign Tracking', 'forex-affiliate-suite-pro'); ?></h2>
                <p class="fasp-sub"><?php esc_html_e('You arrived via a tracked campaign:', 'forex-affiliate-suite-pro'); ?></p>
                <div class="fasp-utm-params">
                    <?php foreach ($utm_params as $key => $value) : ?>
                        <span class="fasp-utm-tag">
                            <strong><?php echo esc_html(str_replace('utm_', '', $key)); ?>:</strong>
                            <?php echo esc_html($value); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Gating Awareness (for admins) -->
        <?php if ($is_admin && !$is_preview) : ?>
            <div class="fasp-card fasp-gating-info">
                <h2><?php esc_html_e('Access Controls', 'forex-affiliate-suite-pro'); ?></h2>
                <div class="fasp-gating-status">
                    <div class="gating-item">
                        <span class="label"><?php esc_html_e('Login Gating:', 'forex-affiliate-suite-pro'); ?></span>
                        <?php if ($gating_enabled) : ?>
                            <span class="fasp-badge ok"><?php esc_html_e('Enabled', 'forex-affiliate-suite-pro'); ?></span>
                        <?php else : ?>
                            <span class="fasp-badge muted"><?php esc_html_e('Disabled', 'forex-affiliate-suite-pro'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="gating-item">
                        <span class="label"><?php esc_html_e('Geo Gating:', 'forex-affiliate-suite-pro'); ?></span>
                        <?php if ($geo_gating_enabled) : ?>
                            <span class="fasp-badge ok"><?php esc_html_e('Enabled', 'forex-affiliate-suite-pro'); ?></span>
                        <?php else : ?>
                            <span class="fasp-badge muted"><?php esc_html_e('Disabled', 'forex-affiliate-suite-pro'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="fasp-note">
                    <?php
                    printf(
                        /* translators: %s: link to settings */
                        esc_html__('Configure access controls in %s.', 'forex-affiliate-suite-pro'),
                        '<a href="' . esc_url(admin_url('admin.php?page=fasp_settings')) . '">' . esc_html__('Settings', 'forex-affiliate-suite-pro') . '</a>'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Quick Actions CTA -->
        <div class="fasp-card fasp-cta-card">
            <h2><?php esc_html_e('Quick Actions', 'forex-affiliate-suite-pro'); ?></h2>
            <div class="fasp-cta-buttons">
                <?php if (!$deriv_verified && $deriv_url) : ?>
                    <a class="button button-primary" href="<?php echo esc_url($deriv_url); ?>"><?php esc_html_e('Connect Deriv Account', 'forex-affiliate-suite-pro'); ?></a>
                <?php endif; ?>
                <?php if (!empty($platforms)) : ?>
                    <?php
                    // PHP 7.3+ compatibility: use key() instead of array_key_first()
                    reset($platforms);
                    $first_platform_key = key($platforms);
                    ?>
                    <a class="button" href="<?php echo esc_url(add_query_arg('fasp_click', rawurlencode($first_platform_key), home_url('/'))); ?>"><?php esc_html_e('Start Trading', 'forex-affiliate-suite-pro'); ?></a>
                <?php endif; ?>
                <?php if (!empty($cls)) : ?>
                    <a class="button" href="<?php echo esc_url(get_post_type_archive_link('fasp_coach_event')); ?>"><?php esc_html_e('Find a Coach', 'forex-affiliate-suite-pro'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>