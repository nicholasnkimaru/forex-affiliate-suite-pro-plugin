<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('fasp_parent_slug')){
  function fasp_parent_slug(){
    $candidates = array('forex-affiliate','fasp_hub','toplevel_page_forex-affiliate');
    global $submenu;
    foreach ($candidates as $slug){
      if (isset($submenu[$slug]) || $slug==='forex-affiliate') return $slug;
    }
    return 'forex-affiliate';
  }
}
if (!function_exists('fasp_prepare')){
  function fasp_prepare($query){
    $args = func_get_args();
    array_shift($args);
    global $wpdb;
    if (strpos($query, '%') === false){
      return $query;
    }
    return $wpdb->prepare($query, $args);
  }
}
if (!function_exists('fasp_db_column_exists')){
  function fasp_db_column_exists($table, $col){
    global $wpdb;
    $sql = fasp_prepare("SHOW COLUMNS FROM `$table` LIKE %s", $col);
    return (bool) $wpdb->get_var($sql);
  }
}
