<?php
/**
 * Affiliate Tools Template
 *
 * Displays referral link generator, marketing materials, and affiliate resources.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Check if user is affiliate
$is_affiliate = false;
if ($current_user && $current_user->ID) {
  if (current_user_can('manage_options')) {
    $is_affiliate = true;
  } elseif (in_array('affiliate', (array) $current_user->roles, true)) {
    $is_affiliate = true;
  } elseif (get_user_meta($current_user->ID, 'fasp_is_affiliate', true)) {
    $is_affiliate = true;
  }
}

if (!$is_affiliate) {
  echo '<div class="woocommerce-MyAccount-content">';
  echo '<h2>' . esc_html__('Affiliate Tools', 'fasp') . '</h2>';
  echo '<p>' . esc_html__('This page is only available to affiliates. Contact the administrator for access.', 'fasp') . '</p>';
  echo '</div>';
  return;
}

// Generate or retrieve user's referral code
$referral_code = get_user_meta($current_user->ID, 'fasp_referral_code', true);
if (empty($referral_code)) {
  $referral_code = 'ref' . $current_user->ID . '_' . substr(md5($current_user->user_login), 0, 6);
  update_user_meta($current_user->ID, 'fasp_referral_code', $referral_code);
}

// Get platforms for referral links
$platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];
$site_url = home_url();

?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Affiliate Tools', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo sprintf(esc_html__('Welcome %s — use these tools to promote and earn commissions.', 'fasp'), esc_html($current_user->display_name ?: $current_user->user_login)); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <!-- Referral Link Generator -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Your Referral Links', 'fasp'); ?></h2>
      <p class="fasp-muted"><?php esc_html_e('Share these links to track referrals and earn commissions.', 'fasp'); ?></p>
      
      <div style="margin: 16px 0;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600;">
          <?php esc_html_e('Your Referral Code:', 'fasp'); ?>
        </label>
        <input type="text" readonly value="<?php echo esc_attr($referral_code); ?>" 
               class="fasp-copy-input"
               style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
      </div>

      <?php if (!empty($platforms)): ?>
        <h3 style="margin-top: 20px;"><?php esc_html_e('Platform Referral Links', 'fasp'); ?></h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($platforms as $platform): ?>
            <?php 
            $base_url = !empty($platform['affiliate_url']) ? $platform['affiliate_url'] : $site_url;
            $referral_url = add_query_arg('ref', $referral_code, $base_url);
            ?>
            <div style="padding: 12px; background: rgba(255,255,255,0.5); border: 1px solid rgba(0,0,0,0.06); border-radius: 6px;">
              <strong><?php echo esc_html($platform['name']); ?></strong>
              <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center;">
                <input type="text" readonly value="<?php echo esc_url($referral_url); ?>" 
                       class="fasp-copy-input"
                       style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; background: white;">
                <a href="<?php echo esc_url($referral_url); ?>" target="_blank" class="button button-small">
                  <?php esc_html_e('Test Link', 'fasp'); ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Marketing Materials -->
    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Marketing Materials', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Download banners, creatives, and promotional content.', 'fasp'); ?></p>
      
      <div style="margin-top: 16px;">
        <h4 style="font-size: 14px; margin-bottom: 8px;"><?php esc_html_e('Available Banners', 'fasp'); ?></h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
            <span><?php esc_html_e('300x250 Banner', 'fasp'); ?></span>
            <a href="#" style="float: right; font-size: 12px;"><?php esc_html_e('Download', 'fasp'); ?></a>
          </li>
          <li style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
            <span><?php esc_html_e('728x90 Banner', 'fasp'); ?></span>
            <a href="#" style="float: right; font-size: 12px;"><?php esc_html_e('Download', 'fasp'); ?></a>
          </li>
          <li style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
            <span><?php esc_html_e('160x600 Banner', 'fasp'); ?></span>
            <a href="#" style="float: right; font-size: 12px;"><?php esc_html_e('Download', 'fasp'); ?></a>
          </li>
        </ul>
        <p style="margin-top: 12px; font-size: 12px; color: #999;">
          <?php esc_html_e('More materials coming soon. Contact support for custom creatives.', 'fasp'); ?>
        </p>
      </div>
    </div>

    <!-- Embed Code Generator -->
    <div class="fasp-card fasp-card--half">
      <h3><?php esc_html_e('Embed Code', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Copy and paste this code into your website.', 'fasp'); ?></p>
      
      <div style="margin-top: 16px;">
        <textarea readonly class="fasp-copy-textarea" style="width: 100%; height: 120px; padding: 8px; font-family: monospace; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9; cursor: pointer;"><a href="<?php echo esc_url(add_query_arg('ref', $referral_code, $site_url)); ?>" target="_blank">
  Join the best trading platform
</a></textarea>
        <p style="margin-top: 8px; font-size: 12px; color: #666;">
          <?php esc_html_e('Click to copy the embed code', 'fasp'); ?>
        </p>
      </div>
    </div>

    <!-- Social Sharing -->
    <div class="fasp-card fasp-card--wide">
      <h3><?php esc_html_e('Share on Social Media', 'fasp'); ?></h3>
      <p class="fasp-muted"><?php esc_html_e('Quick share buttons for your referral links.', 'fasp'); ?></p>
      
      <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
        <?php 
        $share_url = add_query_arg('ref', $referral_code, $site_url);
        $share_text = urlencode('Join me on this amazing trading platform!');
        ?>
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($share_url); ?>&text=<?php echo $share_text; ?>" 
           target="_blank" class="button" style="background: #1DA1F2; color: white; border: none;">
          <?php esc_html_e('Share on Twitter', 'fasp'); ?>
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>" 
           target="_blank" class="button" style="background: #4267B2; color: white; border: none;">
          <?php esc_html_e('Share on Facebook', 'fasp'); ?>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($share_url); ?>" 
           target="_blank" class="button" style="background: #0077b5; color: white; border: none;">
          <?php esc_html_e('Share on LinkedIn', 'fasp'); ?>
        </a>
        <a href="https://wa.me/?text=<?php echo $share_text; ?>%20<?php echo urlencode($share_url); ?>" 
           target="_blank" class="button" style="background: #25D366; color: white; border: none;">
          <?php esc_html_e('Share on WhatsApp', 'fasp'); ?>
        </a>
      </div>
    </div>

  </div>
</div>

<script>
// Modern clipboard copy with fallback for older browsers
function faspCopyToClipboard(text, button) {
  // Try modern Clipboard API first
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(function() {
      showCopySuccess(button);
    }).catch(function() {
      fallbackCopy(text, button);
    });
  } else {
    fallbackCopy(text, button);
  }
}

function fallbackCopy(text, button) {
  var textArea = document.createElement('textarea');
  textArea.value = text;
  textArea.style.position = 'fixed';
  textArea.style.top = '0';
  textArea.style.left = '0';
  textArea.style.width = '2em';
  textArea.style.height = '2em';
  textArea.style.padding = '0';
  textArea.style.border = 'none';
  textArea.style.outline = 'none';
  textArea.style.boxShadow = 'none';
  textArea.style.background = 'transparent';
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    var successful = document.execCommand('copy');
    if (successful) {
      showCopySuccess(button);
    }
  } catch (err) {
    console.error('Unable to copy', err);
  }
  
  document.body.removeChild(textArea);
}

function showCopySuccess(element) {
  var originalBg = element.style.background;
  element.style.background = '#10b981';
  var originalText = element.textContent || element.value;
  
  if (element.tagName === 'INPUT') {
    var parent = element.parentElement;
    if (parent) {
      var notice = document.createElement('span');
      notice.textContent = '✓ Copied!';
      notice.style.color = '#10b981';
      notice.style.marginLeft = '8px';
      notice.style.fontSize = '14px';
      notice.style.fontWeight = '600';
      parent.appendChild(notice);
      setTimeout(function() {
        parent.removeChild(notice);
      }, 2000);
    }
  } else {
    alert('✓ Copied to clipboard!');
  }
  
  setTimeout(function() {
    element.style.background = originalBg;
  }, 300);
}

// Attach to all readonly inputs and textareas on page
document.addEventListener('DOMContentLoaded', function() {
  var copyInputs = document.querySelectorAll('input[readonly][value], textarea[readonly]');
  copyInputs.forEach(function(input) {
    input.style.cursor = 'pointer';
    input.addEventListener('click', function() {
      this.select();
      faspCopyToClipboard(this.value || this.textContent, this);
    });
  });
});
</script>
