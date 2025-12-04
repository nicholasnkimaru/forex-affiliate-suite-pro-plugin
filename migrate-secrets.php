<?php
// migrate-secrets.php
// One-time helper: move secrets out of the autoloaded 'fasp_marketing' option into 'fasp_marketing_secrets' (non-autoload).
// Run with WP-CLI from your WordPress installation root:
//   wp eval-file migrate-secrets.php

if (!defined('ABSPATH')) {
  // This file is intended to be executed with WP-CLI (wp eval-file).
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
  // add_option allows specifying autoload = 'no'
  add_option('fasp_marketing_secrets', $secrets, '', 'no');
} else {
  update_option('fasp_marketing_secrets', $secrets);
}

// Ensure autoload is 'no' (best-effort)
global $wpdb;
$wpdb->update($wpdb->options, [ 'autoload' => 'no' ], [ 'option_name' => 'fasp_marketing_secrets' ]);

echo "Migration complete. Moved {$moved} keys.\n";
?>