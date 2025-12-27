<?php
/**
 * Template: Platforms Page
 * Shows available trading platforms with verification status
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get platforms from options
$platforms = array();
$opt = get_option('fasp_platforms', array());
if (is_array($opt) && !empty($opt)) {
    foreach ($opt as $slug => $p) {
        if (isset($p['visible_in_dashboard']) && !$p['visible_in_dashboard']) {
            continue;
        }
        $platforms[] = array(
            'slug'     => $slug,
            'name'     => isset($p['name']) ? $p['name'] : $slug,
            'excerpt'  => isset($p['excerpt']) ? $p['excerpt'] : '',
            'affiliate'=> isset($p['affiliate_url']) ? $p['affiliate_url'] : '',
        );
    }
} else {
    // Fallback platform
    $platforms[] = array(
        'slug' => 'deriv',
        'name' => 'Deriv',
        'excerpt' => 'Open a Deriv account to get started with trading',
        'affiliate' => home_url('/fasp-go/deriv'),
    );
}

$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url(home_url('/my-account/' . $endpoint . '/'));
};
?>

<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Trading Platforms', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Connect your trading accounts and verify your platform access.', 'fasp'); ?></p>
    <p><a class="button" href="<?php echo esc_url($account_url('forex-dashboard')); ?>"><?php echo esc_html__('← Back to Dashboard', 'fasp'); ?></a></p>
  </header>

  <div class="fasp-dashboard">
    <?php if (!empty($platforms)): ?>
      <?php foreach ($platforms as $platform): ?>
        <?php
        $slug = $platform['slug'];
        $name = $platform['name'];
        $excerpt = $platform['excerpt'];
        $affiliate_url = $platform['affiliate'];
        
        // Check if user is verified for this platform
        $is_verified = false;
        if (function_exists('fasp_is_user_verified_for_platform')) {
            $is_verified = fasp_is_user_verified_for_platform($user_id, $slug);
        } elseif ($user_id) {
            $is_verified = get_user_meta($user_id, '_fasp_verified_' . $slug, true);
        }
        
        // Special handling for Deriv OAuth
        $verify_button = '';
        if ($slug === 'deriv' && function_exists('fasp_deriv_authorize_url')) {
            $auth_url = fasp_deriv_authorize_url();
            if ($auth_url) {
                $verify_button = '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . esc_html__('Connect with Deriv', 'fasp') . '</a>';
            }
        } elseif (!empty($affiliate_url)) {
            $verify_button = '<a class="button button-primary" href="' . esc_url($affiliate_url) . '" target="_blank" rel="noopener">' . esc_html__('Open Account', 'fasp') . '</a>';
        }
        ?>
        <div class="fasp-card fasp-card--half">
          <h3><?php echo esc_html($name); ?></h3>
          <?php if ($excerpt): ?>
            <p class="fasp-muted"><?php echo esc_html($excerpt); ?></p>
          <?php endif; ?>
          <p>
            <strong><?php echo esc_html__('Status:', 'fasp'); ?></strong>
            <?php if ($is_verified): ?>
              <span class="fasp-status-verified">✅ <?php echo esc_html__('Verified', 'fasp'); ?></span>
            <?php else: ?>
              <span class="fasp-status-unverified">❌ <?php echo esc_html__('Not Verified', 'fasp'); ?></span>
            <?php endif; ?>
          </p>
          <?php if ($verify_button && !$is_verified): ?>
            <p><?php echo $verify_button; ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <p class="fasp-muted"><?php echo esc_html__('No platforms available at this time. Please contact support.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>
