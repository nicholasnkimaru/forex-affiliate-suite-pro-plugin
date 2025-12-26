<?php
/**
 * Platforms Template
 *
 * Displays available trading platforms and verification status.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];

?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Trading Platforms', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Connect and verify your trading platform accounts.', 'fasp'); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <?php if (!empty($platforms)): ?>
      <?php foreach ($platforms as $platform_key => $platform): ?>
        <?php 
        $is_verified = function_exists('fasp_is_user_verified_for_platform') 
          ? fasp_is_user_verified_for_platform(get_current_user_id(), $platform_key) 
          : false;
        
        $signup_url = !empty($platform['signup_url']) ? $platform['signup_url'] : $platform['affiliate_url'];
        $has_oauth = ($platform['method'] === 'oauth' && $platform_key === 'deriv');
        $oauth_url = '';
        
        if ($has_oauth && function_exists('fasp_deriv_authorize_url')) {
          $oauth_url = fasp_deriv_authorize_url();
        }
        ?>
        
        <div class="fasp-card fasp-card--half">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <?php if (!empty($platform['logo_url'])): ?>
              <img src="<?php echo esc_url($platform['logo_url']); ?>" 
                   alt="<?php echo esc_attr($platform['name']); ?>" 
                   style="width: 48px; height: 48px; object-fit: contain;">
            <?php else: ?>
              <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          border-radius: 8px; display: flex; align-items: center; justify-content: center; 
                          color: white; font-weight: 700; font-size: 18px;">
                <?php echo esc_html(strtoupper(substr($platform['name'], 0, 1))); ?>
              </div>
            <?php endif; ?>
            
            <div style="flex: 1;">
              <h3 style="margin: 0;"><?php echo esc_html($platform['name']); ?></h3>
              <span style="font-size: 12px; color: #666;">
                <?php echo esc_html($platform_key); ?>
              </span>
            </div>
            
            <?php if ($is_verified): ?>
              <span style="padding: 4px 10px; background: #d1fae5; color: #065f46; 
                           border-radius: 12px; font-size: 12px; font-weight: 600;">
                ✅ <?php esc_html_e('Verified', 'fasp'); ?>
              </span>
            <?php else: ?>
              <span style="padding: 4px 10px; background: #fee2e2; color: #991b1b; 
                           border-radius: 12px; font-size: 12px; font-weight: 600;">
                ❌ <?php esc_html_e('Not Verified', 'fasp'); ?>
              </span>
            <?php endif; ?>
          </div>
          
          <p class="fasp-muted" style="margin-bottom: 16px; font-size: 14px;">
            <?php 
            if ($is_verified) {
              esc_html_e('Your account is verified and connected.', 'fasp');
            } else {
              esc_html_e('Connect your account to access exclusive resources and features.', 'fasp');
            }
            ?>
          </p>
          
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php if (!$is_verified): ?>
              <?php if ($has_oauth && $oauth_url): ?>
                <a class="button button-primary" href="<?php echo esc_url($oauth_url); ?>">
                  <?php esc_html_e('Verify with OAuth', 'fasp'); ?>
                </a>
              <?php elseif ($signup_url): ?>
                <a class="button button-primary" href="<?php echo esc_url($signup_url); ?>" target="_blank" rel="noopener">
                  <?php esc_html_e('Open Account', 'fasp'); ?>
                </a>
              <?php else: ?>
                <button class="button" disabled>
                  <?php esc_html_e('Contact Support', 'fasp'); ?>
                </button>
              <?php endif; ?>
            <?php else: ?>
              <span class="button" style="background: #f0fdf4; color: #15803d; border-color: #86efac;">
                <?php esc_html_e('Connected', 'fasp'); ?>
              </span>
            <?php endif; ?>
            
            <?php if (!empty($platform['affiliate_url'])): ?>
              <a class="button" href="<?php echo esc_url($platform['affiliate_url']); ?>" target="_blank" rel="noopener">
                <?php esc_html_e('Visit Platform', 'fasp'); ?>
              </a>
            <?php endif; ?>
          </div>
          
          <?php if ($platform['kyc_required'] === '1'): ?>
            <p style="margin-top: 12px; font-size: 12px; color: #dc2626;">
              ⚠️ <?php esc_html_e('KYC verification required', 'fasp'); ?>
            </p>
          <?php endif; ?>
        </div>
        
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php esc_html_e('No Platforms Available', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php esc_html_e('No trading platforms have been configured yet. Contact the administrator.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>

    <!-- Platform Setup Help -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('How to Connect Your Account', 'fasp'); ?></h2>
      <ol style="margin: 12px 0; padding-left: 20px; line-height: 1.8;">
        <li><?php esc_html_e('Click "Open Account" or "Verify with OAuth" on your preferred platform above.', 'fasp'); ?></li>
        <li><?php esc_html_e('Complete the registration or authentication process on the platform website.', 'fasp'); ?></li>
        <li><?php esc_html_e('Return to this page to see your verified status and access gated resources.', 'fasp'); ?></li>
        <li><?php esc_html_e('Some platforms may require KYC (Know Your Customer) verification for full access.', 'fasp'); ?></li>
      </ol>
      <p class="fasp-muted">
        <?php esc_html_e('Need help? Contact support for assistance with platform verification.', 'fasp'); ?>
      </p>
    </div>

  </div>
</div>
