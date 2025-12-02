<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Remove duplicate submenu items under our parent slug(s) without touching callbacks.
 * This runs late on admin_menu so all other registrations have happened.
 */
add_action('admin_menu', function(){
  global $submenu;
  if (!is_array($submenu)) return;

  $parents = array('forex-affiliate', 'fasp_hub', 'fasp_settings_top'); // cover known parent slugs
  foreach ($parents as $parent){
    if (empty($submenu[$parent]) || !is_array($submenu[$parent])) continue;

    $seen_slugs = array();
    $seen_titles = array();
    foreach ($submenu[$parent] as $idx => $item){
      // $item: [0] title, [1] capability, [2] slug
      $title = isset($item[0]) ? strtolower(trim(wp_strip_all_tags($item[0]))) : '';
      $slug  = isset($item[2]) ? $item[2] : '';

      if (isset($seen_slugs[$slug]) || isset($seen_titles[$title])){
        remove_submenu_page($parent, $slug);
      } else {
        $seen_slugs[$slug] = true;
        if ($title) $seen_titles[$title] = true;
      }
    }
  }
}, 9999);

/**
 * Top-level menu de-duplication.
 * If two "Forex Affiliate" parents exist (e.g., slugs 'forex-affiliate' and 'fasp_hub'),
 * keep the canonical 'forex-affiliate' and reparent the other's submenus to it.
 */
add_action('admin_menu', function(){
  global $menu, $submenu;
  if (!is_array($menu)) return;

  // Find all top-level entries whose title is "Forex Affiliate"
  $matches = array(); // slug => index
  foreach ($menu as $idx => $item) {
    $title = isset($item[0]) ? strtolower(trim(wp_strip_all_tags($item[0]))) : '';
    $slug  = isset($item[2]) ? $item[2] : '';
    if ($title === 'forex affiliate' && $slug) {
      $matches[$slug] = $idx;
    }
  }

  if (count($matches) > 1) {
    $canonical = isset($matches['forex-affiliate']) ? 'forex-affiliate' : array_key_first($matches);
    foreach ($matches as $slug => $idx) {
      if ($slug === $canonical) continue;

      // Reparent submenus (if any) to the canonical parent
      if (isset($submenu[$slug]) && is_array($submenu[$slug])) {
        foreach ($submenu[$slug] as $item) {
          // $item: [0] title, [1] capability, [2] slug, [3] page title (optional)
          $menu_title = isset($item[0]) ? $item[0] : '';
          $cap        = isset($item[1]) ? $item[1] : 'manage_options';
          $sub_slug   = isset($item[2]) ? $item[2] : '';
          if ($menu_title && $sub_slug) {
            add_submenu_page($canonical, $menu_title, $menu_title, $cap, $sub_slug);
          }
        }
      }

      // Remove the duplicate parent
      remove_menu_page($slug);
      unset($submenu[$slug]);
    }
  }
}, 9998);

/**
 * STRONG de-duplication:
 * 1) Merge duplicate parents titled "Forex Affiliate", keep canonical 'forex-affiliate' (or first found).
 * 2) Reparent all submenus from duplicates to canonical.
 * 3) De-duplicate submenus under EVERY parent by slug first, then by normalized title.
 */
add_action('admin_menu', function(){
  global $menu, $submenu;
  if (!is_array($menu)) return;

  // --- 1) Merge duplicate parents ---
  $matches = array(); // slug => index
  foreach ($menu as $idx => $item) {
    $title = isset($item[0]) ? strtolower(trim(wp_strip_all_tags($item[0]))) : '';
    $slug  = isset($item[2]) ? $item[2] : '';
    if ($title === 'forex affiliate' && $slug) {
      $matches[$slug] = $idx;
    }
  }

  if (count($matches) > 1) {
    $canonical = isset($matches['forex-affiliate']) ? 'forex-affiliate' : array_key_first($matches);

    foreach ($matches as $slug => $idx) {
      if ($slug === $canonical) continue;

      if (isset($submenu[$slug]) && is_array($submenu[$slug])) {
        foreach ($submenu[$slug] as $item) {
          $menu_title = isset($item[0]) ? $item[0] : '';
          $cap        = isset($item[1]) ? $item[1] : 'manage_options';
          $sub_slug   = isset($item[2]) ? $item[2] : '';
          if ($menu_title && $sub_slug) {
            add_submenu_page($canonical, $menu_title, $menu_title, $cap, $sub_slug);
          }
        }
      }

      remove_menu_page($slug);
      unset($submenu[$slug]);
    }
  }

  // --- 2) De-duplicate submenus under all parents ---
  if (!is_array($submenu)) return;
  foreach ($submenu as $parent_slug => $items) {
    $seen_slugs  = array();
    $seen_titles = array();
    if (!is_array($items)) continue;
    foreach ($items as $idx => $item) {
      $title = isset($item[0]) ? strtolower(trim(wp_strip_all_tags($item[0]))) : '';
      $slug  = isset($item[2]) ? $item[2] : '';

      // Prefer slug-level uniqueness first
      if ($slug && isset($seen_slugs[$slug])) {
        remove_submenu_page($parent_slug, $slug);
        continue;
      }
      $seen_slugs[$slug] = true;

      // Then title-level uniqueness (avoid duplicate labels even if slugs differ)
      if ($title && isset($seen_titles[$title])) {
        remove_submenu_page($parent_slug, $slug);
        continue;
      }
      if ($title) $seen_titles[$title] = true;
    }
  }
}, 99999);
