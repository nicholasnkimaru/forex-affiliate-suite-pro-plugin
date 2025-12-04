#!/usr/bin/env bash
# apply-marketing-secret-fix-now.sh
# One-shot: backup, overwrite includes/admin-marketing.php with safer version,
# write migrate-secrets.php, php -l check, commit & push if changes, run migration via WP-CLI.
#
# Usage (from repo root in Codespace):
#   chmod +x apply-marketing-secret-fix-now.sh
#   ./apply-marketing-secret-fix-now.sh
set -euo pipefail

timestamp() { date -u +"%Y%m%dT%H%M%SZ"; }
REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
TARGET="includes/admin-marketing.php"
MIGRATE="migrate-secrets.php"
BACKUP_DIR="${REPO_ROOT}/.backup-admin-marketing"
TS="$(timestamp)"
BACKUP="${BACKUP_DIR}/admin-marketing.${TS}.php.bak"

# Ensure git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Not a git repo. Run from repository root." >&2
  exit 1
fi

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
echo "On branch: ${BRANCH}"

# Backup original if exists
mkdir -p "${BACKUP_DIR}"
if [ -f "${TARGET}" ]; then
  echo "Backing up ${TARGET} -> ${BACKUP}"
  cp "${TARGET}" "${BACKUP}"
else
  echo "No existing ${TARGET} found; will create it."
fi

# Ensure includes dir exists
mkdir -p "$(dirname "${TARGET}")"

# Write safer includes/admin-marketing.php
cat > "${TARGET}" <<'PHP'
<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function(){
  add_submenu_page('fasp_hub','Marketing & Analytics','Marketing & Analytics','manage_options','fasp_marketing','fasp_marketing_page');
});
function fasp_marketing_settings(){
  $opt = get_option('fasp_marketing', []);
  return wp_parse_args($opt, [
    'alert_threshold'=> '5',
    'alert_window_hours'=>'24',
    'google_ads_enable'=>'0',
    'tiktok_enable'=>'0','tiktok_pixel_id'=>'','tiktok_access_token'=>'',
    'google_ads_conv_id'=>'',
    'tiktok_pixel_id'=>'' ,
    'enable_server_events'=>'1',
    'facebook_pixel_id'=>'',
    'facebook_access_token'=>'',
    'facebook_test_code'=>'',
    'ga4_measurement_id'=>'',
    'ga4_api_secret'=>'',
    'require_consent'=>'0',
    'slack_webhook'=>''
  ]);
}
function fasp_marketing_page(){
  if (!current_user_can('manage_options')) return;
  $o = fasp_marketing_settings();
  // Load separate secrets (non-autoloaded)
  $secrets = get_option('fasp_marketing_secrets', []);

  if (isset($_POST['fasp_mkt_save']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    // Non-secret settings (sanitized)
    $o['enable_server_events'] = empty($_POST['enable_server_events']) ? '0' : '1';
    $o['facebook_pixel_id'] = sanitize_text_field($_POST['facebook_pixel_id'] ?? '');
    $o['facebook_test_code'] = sanitize_text_field($_POST['facebook_test_code'] ?? '');
    $o['ga4_measurement_id'] = sanitize_text_field($_POST['ga4_measurement_id'] ?? '');
    $o['require_consent'] = empty($_POST['require_consent']) ? '0' : '1';
    $o['slack_webhook'] = esc_url_raw($_POST['slack_webhook'] ?? '');
    $o['alert_threshold'] = sanitize_text_field($_POST['alert_threshold'] ?? '5');
    $o['alert_window_hours'] = sanitize_text_field($_POST['alert_window_hours'] ?? '24');
    $o['google_ads_enable'] = empty($_POST['google_ads_enable']) ? '0' : '1';
    $o['tiktok_enable'] = empty($_POST['tiktok_enable']) ? '0' : '1';
    $o['tiktok_pixel_id'] = sanitize_text_field($_POST['tiktok_pixel_id'] ?? '');
    $o['google_ads_conv_id'] = sanitize_text_field($_POST['google_ads_conv_id'] ?? '');

    // Persist non-secret settings
    update_option('fasp_marketing', $o);

    // Handle secrets separately (non-autoloaded option)
    $secrets = get_option('fasp_marketing_secrets', []);

    // Overwrite secrets only when admin supplied a non-empty value
    if (!empty($_POST['facebook_access_token'])) {
      $secrets['facebook_access_token'] = trim($_POST['facebook_access_token']);
    }
    if (!empty($_POST['tiktok_access_token'])) {
      $secrets['tiktok_access_token'] = trim($_POST['tiktok_access_token']);
    }
    if (!empty($_POST['ga4_api_secret'])) {
      $secrets['ga4_api_secret'] = trim($_POST['ga4_api_secret']);
    }

    // Save secrets option (we'll ensure autoload=no below)
    update_option('fasp_marketing_secrets', $secrets);

    // Ensure autoload is set to 'no' (best-effort; requires $wpdb)
    global $wpdb;
    $wpdb->update($wpdb->options, ['autoload' => 'no'], ['option_name' => 'fasp_marketing_secrets']);

    echo '<div class="updated"><p>Saved.</p></div>';
  }

  // Test events
  if (isset($_POST['fasp_mkt_test']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    $eid = 'fasp-test-' . wp_generate_password(8,false,false);
    do_action('fasp/event/lead', ['event_id'=>$eid, 'custom_data'=>['value'=>0]]);
    do_action('fasp/event/complete_registration', ['event_id'=>$eid, 'custom_data'=>[]]);
    echo '<div class="updated"><p>Test events queued (Lead & CompleteRegistration). Event ID: '.esc_html($eid).'</p></div>';
  }
  // Dead letters replay
  if (isset($_POST['fasp_mkt_replay']) && check_admin_referer('fasp_mkt','fasp_mkt_nonce')){
    $dead = get_option('fasp_event_dead_letters', []);
    $sent=0; foreach($dead as $i=>$evt){ do_action('fasp/event/replay', $evt); $sent++; }
    echo '<div class="updated"><p>Replayed '.intval($sent).' failed events (they will drop out if successful).</p></div>';
  }
  $dead = get_option('fasp_event_dead_letters', []);
  $queue = get_option('fasp_event_queue', []);
  $last_ok = get_option('fasp_event_last_ok', '');
  ?>
  <div class="wrap fasp-admin">
    <h1>Marketing & Analytics</h1>
    <div class="fasp-wrap fasp-card">
      <form method="post"><?php wp_nonce_field('fasp_mkt','fasp_mkt_nonce'); ?>
        <h2>Server-side Events</h2>
        <p><label><input type="checkbox" name="enable_server_events" value="1" <?php checked($o['enable_server_events'],'1'); ?>> Enable server events (Meta CAPI + GA4 MP)</label></p>
        <p><label><input type="checkbox" name="require_consent" value="1" <?php checked($o['require_consent'],'1'); ?>> Require user consent before setting tracking cookies</label></p>
        <hr>
        <h2>Meta (Facebook)</h2>
        <p><label>Pixel ID<br><input class="regular-text" name="facebook_pixel_id" value="<?php echo esc_attr($o['facebook_pixel_id']); ?>"></label></p>
        <p><label>Access Token<br>
          <input type="password" class="regular-text" name="facebook_access_token" value="">
          <?php if (!empty($secrets['facebook_access_token'])) echo '<span class="description">••••••• (configured)</span>'; ?>
        </label></p>
        <p><label>Test Event Code (optional)<br><input class="regular-text" name="facebook_test_code" value="<?php echo esc_attr($o['facebook_test_code']); ?>"></label></p>
        <h2>Google Analytics 4</h2>
        <p><label>Measurement ID<br><input class="regular-text" name="ga4_measurement_id" value="<?php echo esc_attr($o['ga4_measurement_id']); ?>"></label></p>
        <p><label>API Secret<br>
          <input type="password" class="regular-text" name="ga4_api_secret" value="">
          <?php if (!empty($secrets['ga4_api_secret'])) echo '<span class="description">••••••• (configured)</span>'; ?>
          <span class="description">Leave empty to keep existing secret.</span>
        </label></p>
        <h2>Alerts</h2>
        <p><label>Slack Webhook URL<br><input class="large-text code" name="slack_webhook" value="<?php echo esc_attr($o['slack_webhook']); ?>"></label></p>
        <p><label>Alert if last <input style="width:60px" name="alert_window_hours" value="<?php echo esc_attr($o['alert_window_hours']); ?>"> hours conversion % &lt; <input style="width:60px" name="alert_threshold" value="<?php echo esc_attr($o['alert_threshold']); ?>">%</label></p>
        <h2>Google Ads & TikTok</h2>
        <p><label><input type="checkbox" name="google_ads_enable" value="1" <?php checked($o['google_ads_enable'],'1'); ?>> Enable Google Ads server conversions (placeholder)</label></p>
        <p><label>Google Ads Conversion ID<br><input class="regular-text" name="google_ads_conv_id" value="<?php echo esc_attr($o['google_ads_conv_id']); ?>"></label></p>
        <p><label><input type="checkbox" name="tiktok_enable" value="1" <?php checked($o['tiktok_enable'],'1'); ?>> Enable TikTok Events API (placeholder)</label></p>
        <p><label>TikTok Pixel ID<br><input class="regular-text" name="tiktok_pixel_id" value="<?php echo esc_attr($o['tiktok_pixel_id']); ?>"></label></p>
        <p><label>Access Token<br>
          <input type="password" class="regular-text" name="tiktok_access_token" value="">
          <?php if (!empty($secrets['tiktok_access_token'])) echo '<span class="description">••••••• (configured)</span>'; ?>
        </label></p>
        <p>
          <button class="button button-primary" name="fasp_mkt_save" value="1">Save settings</button>
          <button class="button" name="fasp_mkt_test" value="1">Send Test Events (Lead & CompleteRegistration)</button>
        </p>
      
    <h2>TikTok Events API</h2>
    <p><label><input type="checkbox" name="tiktok_enable" value="1" <?php checked(($o['tiktok_enable']??'0'),'1'); ?>> Enable TikTok Events</label></p>
    <p><label>Pixel Code<br><input class="regular-text" name="tiktok_pixel_id" value="<?php echo esc_attr($o['tiktok_pixel_id'] ?? ''); ?>"></label></p>
    <p><label>Access Token<br><input type="password" class="regular-text" name="tiktok_access_token" value=""></label></p>
    <p class="fasp-muted">Note: Google Ads is best fed by importing GA4 conversions. We already send GA4 events with click IDs (gclid/gbraid/wbraid) when present.</p>
    </form>
    </div>
    <div class="fasp-wrap fasp-card">
      <h2>Health & Queue</h2>
      <p class="fasp-muted">Queue depth: <?php echo is_array($queue)? count($queue):0; ?> | Dead letters: <?php echo is_array($dead)? count($dead):0; ?> | Last OK: <?php echo esc_html($last_ok); ?></p>
      <form method="post"><?php wp_nonce_field('fasp_mkt','fasp_mkt_nonce'); ?>
        <button class="button" name="fasp_mkt_replay" value="1" <?php disabled(empty($dead)); ?>>Replay failed events</button>
      </form>
      <?php if ($dead): ?>
        <table class="widefat fasp-table"><thead><tr><th>When</th><th>Provider</th><th>Event</th><th>Error</th></tr></thead><tbody>
        <?php foreach(array_slice(array_reverse($dead),0,50) as $e): ?>
           <tr><td><?php echo esc_html($e['ts'] ?? ''); ?></td><td><?php echo esc_html($e['provider'] ?? ''); ?></td><td><?php echo esc_html($e['name'] ?? ''); ?></td><td><?php echo esc_html($e['error'] ?? ''); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <?php
}
PHP

# Write migrate-secrets.php
cat > "${MIGRATE}" <<'PHP'
<?php
// migrate-secrets.php
// Run with: wp eval-file migrate-secrets.php
if (!defined('ABSPATH')) {
  // Intended to be run with WP-CLI.
}
$opt = get_option('fasp_marketing', []);
$secrets = get_option('fasp_marketing_secrets', []);
$move = ['facebook_access_token','tiktok_access_token','ga4_api_secret'];
$moved = 0;
foreach ($move as $k) {
  if (!empty($opt[$k]) && empty($secrets[$k])) {
    $secrets[$k] = $opt[$k];
    unset($opt[$k]);
    $moved++;
  }
}
update_option('fasp_marketing', $opt);
if (!get_option('fasp_marketing_secrets')) {
  add_option('fasp_marketing_secrets', $secrets, '', 'no');
} else {
  update_option('fasp_marketing_secrets', $secrets);
}
global $wpdb;
$wpdb->update($wpdb->options, ['autoload'=>'no'], ['option_name'=>'fasp_marketing_secrets']);
echo "Migration complete. Moved {$moved} keys.\n";
PHP

# php -l check
if command -v php >/dev/null 2>&1; then
  echo "Running php -l on ${TARGET} ..."
  php -l "${TARGET}"
else
  echo "php not present; skipping syntax check."
fi

# Stage commit & push only if changes
git add "${TARGET}" "${MIGRATE}" >/dev/null 2>&1 || true
if git diff --cached --quiet; then
  echo "No staged changes to commit (files unchanged vs index)."
else
  git commit -m "chore(secrets): mask marketing secrets in admin UI; store secrets in non-autoload option"
  git push origin "${BRANCH}"
fi

# Run migration via WP-CLI if available
if command -v wp >/dev/null 2>&1; then
  echo "Running migration via WP-CLI..."
  wp eval-file "${MIGRATE}" || echo "WP-CLI migration finished with non-zero exit code."
else
  echo "WP-CLI not available here. To migrate secrets, run in WP environment:"
  echo "  wp eval-file ${MIGRATE}"
fi

echo "Done. Backup saved at: ${BACKUP}"