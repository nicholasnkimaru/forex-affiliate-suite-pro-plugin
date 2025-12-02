<?php
if (!defined('ABSPATH')) exit;

/**
 * Canonical capability for all FASP admin pages.
 * Grants manage_fasp to administrators (and optionally shop_manager).
 */
if (!function_exists('fasp_bootstrap_caps')) {
  function fasp_bootstrap_caps() {
    $roles = array('administrator'); // add 'shop_manager' if desired
    foreach ($roles as $r) {
      $role = get_role($r);
      if ($role && !$role->has_cap('manage_fasp')) {
        $role->add_cap('manage_fasp');
      }
    }
  }
  add_action('admin_init', 'fasp_bootstrap_caps', 5);
  if (function_exists('register_activation_hook')) {
    register_activation_hook(dirname(__FILE__,2) . '/forex-affiliate-suite-pro.php', 'fasp_bootstrap_caps');
  }
}
