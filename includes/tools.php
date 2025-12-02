<?php
if (!defined('ABSPATH')) exit;

/**
 * Tools → UTM Builder
 */
if (!function_exists('fasp_render_tools_utm')){
  function fasp_render_tools_utm(){
    echo '<div class="wrap fasp-admin"><h1>UTM Builder</h1><div class="fasp-card">';
    echo '<p>Base <input class="regular-text" id="fasp_utm_base" value="' . esc_attr( home_url('/fasp-go/deriv') ) . '"> ';
    echo 'Source <input class="regular-text" id="fasp_utm_src" value="ads"> ';
    echo 'Campaign <input class="regular-text" id="fasp_utm_cmp" value="launch"></p>';
    echo '<p>Result <input class="large-text code" id="fasp_utm_out" value="" readonly></p>';
    echo '<script>jQuery(function($){function b(){var a=$("#fasp_utm_base").val(),s=$("#fasp_utm_src").val(),c=$("#fasp_utm_cmp").val();$("#fasp_utm_out").val(a+"?utm_source="+encodeURIComponent(s)+"&utm_campaign="+encodeURIComponent(c));}$("#fasp_utm_base,#fasp_utm_src,#fasp_utm_cmp").on("input",b);b();});</script>';
    echo '</div></div>';
  }
}

/**
 * Tools → Export CSV
 */
if (!function_exists('fasp_render_tools_export')){
  function fasp_render_tools_export(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_export'])){
      check_admin_referer('fasp_export_csv');

      $type  = isset($_POST['fasp_export']['type'])  ? sanitize_text_field($_POST['fasp_export']['type'])  : 'clicks';
      $since = isset($_POST['fasp_export']['since']) ? sanitize_text_field($_POST['fasp_export']['since']) : date('Y-m-01');
      $until = isset($_POST['fasp_export']['until']) ? sanitize_text_field($_POST['fasp_export']['until']) : date('Y-m-d');

      // YYYY-MM-DD validation
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) $since = date('Y-m-01');
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) $until = date('Y-m-d');

      header('Content-Type: text/csv');
      $fname = 'fasp-' . str_replace(':', '-', $type) . '-' . date('Y-m-d') . '.csv';
      header('Content-Disposition: attachment; filename="' . $fname . '"');

      $out = fopen('php://output', 'w');
      if ($type === 'clicks'){
        fputcsv($out, array('date','platform','action','count'));
        // write your rows here...
      } else {
        fputcsv($out, array('date','metric','value'));
        // write your rows here...
      }
      fclose($out);
      exit;
    }

    echo '<div class="wrap fasp-admin"><h1>Export CSV</h1><div class="fasp-card"><form method="post">';
    wp_nonce_field('fasp_export_csv');
    echo '<p><label>Type ';
    echo '<select name="fasp_export[type]"><option value="clicks">Clicks Summary</option><option value="reports">Reports</option></select>';
    echo '</label></p>';
    echo '<p><label>From <input type="date" name="fasp_export[since]" value="' . esc_attr( date('Y-m-01') ) . '"></label> ';
    echo '<label>To <input type="date" name="fasp_export[until]" value="' . esc_attr( date('Y-m-d') ) . '"></label></p>';
    submit_button('Download CSV');
    echo '</form></div></div>';
  }
}

/**
 * Tools → Settings Backup (export/import JSON)
 */
if (!function_exists('fasp_render_tools_backup')){
  function fasp_render_tools_backup(){

    // Export JSON
    if (isset($_POST['fasp_backup_export'])){
      check_admin_referer('fasp_backup');

      $dump = array(
        'payments' => get_option('fasp_payments', array()),
        'leads'    => get_option('fasp_leads', array()),
        'gating'   => get_option('fasp_platform_gating', array()),
        'geo'      => get_option('fasp_geo_gating', array()),
      );

      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="fasp-settings.json"');
      echo wp_json_encode($dump);
      exit;
    }

    // Import JSON
    if (isset($_POST['fasp_backup_import']) && !empty($_FILES['fasp_json']['tmp_name'])){
      check_admin_referer('fasp_backup');

      $raw  = file_get_contents($_FILES['fasp_json']['tmp_name']);
      $data = json_decode($raw, true);

      if (is_array($data)){
        foreach($data as $k => $v){
          if (is_array($v)){
            update_option('fasp_' . $k, $v);
          }
        }
        echo '<div class="updated"><p>Settings imported.</p></div>';
      } else {
        echo '<div class="error"><p>Invalid JSON.</p></div>';
      }
    }

    echo '<div class="wrap fasp-admin"><h1>Settings Backup</h1><div class="fasp-card"><form method="post" enctype="multipart/form-data">';
    wp_nonce_field('fasp_backup');
    echo '<p><button class="button button-primary" name="fasp_backup_export" value="1">Export JSON</button></p>';
    echo '<p><input type="file" name="fasp_json" accept="application/json"> <button class="button" name="fasp_backup_import" value="1">Import</button></p>';
    echo '</form></div></div>';
  }
}

/**
 * Settings (general)
 */
if (!function_exists('fasp_render_settings')){
  function fasp_render_settings(){
    echo '<div class="wrap fasp-admin"><h1>Settings</h1><div class="fasp-card"><p class="fasp-muted">General settings area.</p></div></div>';
  }
}

/**
 * Email & Leads
 */
if (!function_exists('fasp_render_leads')){
  function fasp_render_leads(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_leads'])){
      check_admin_referer('fasp_leads_save');
      $clean = array();
      foreach ( (array)($_POST['fasp_leads'] ?? array()) as $k => $v ){
        $clean[$k] = is_array($v) ? array_map('sanitize_text_field', $v) : sanitize_text_field($v);
      }
      update_option('fasp_leads', $clean);
      echo '<div class="updated"><p>Leads settings saved.</p></div>';
    }

    $opt = get_option('fasp_leads', array());
    echo '<div class="wrap fasp-admin"><h1>Email & Leads</h1><div class="fasp-card"><form method="post">';
    wp_nonce_field('fasp_leads_save');
    echo '<p><label>Webhook URL<br><input class="regular-text" name="fasp_leads[webhook]" value="' . esc_attr( $opt['webhook'] ?? '' ) . '"></label></p>';
    echo '<p><label>Default Tag<br><input class="regular-text" name="fasp_leads[tag]" value="' . esc_attr( $opt['tag'] ?? '' ) . '"></label></p>';
    submit_button('Save Leads Settings');
    echo '</form></div></div>';
  }
}

/**
 * Gating Setup
 */
if (!function_exists('fasp_render_platform_gating')){
  function fasp_render_platform_gating(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_gate'])){
      check_admin_referer('fasp_gate_save');
      $in = isset($_POST['fasp_gate']) ? wp_unslash($_POST['fasp_gate']) : array();
      $clean = array_map('sanitize_text_field', (array)$in);
      update_option('fasp_platform_gating', $clean);
      echo '<div class="updated"><p>Gating saved.</p></div>';
    }

    $o = get_option('fasp_platform_gating', array());
    echo '<div class="wrap fasp-admin"><h1>Gating Setup</h1><div class="fasp-card"><form method="post">';
    wp_nonce_field('fasp_gate_save');
    echo '<p><label>Allowed Roles (CSV)<br><input class="regular-text" name="fasp_gate[roles]" value="' . esc_attr( $o['roles'] ?? '' ) . '"></label></p>';
    echo '<p><label><input type="checkbox" name="fasp_gate[require_login]" value="1" ' . checked( $o['require_login'] ?? '', '1', false ) . '> Require login</label></p>';
    submit_button('Save');
    echo '</form></div></div>';
  }
}

/**
 * Geo Gating
 */
if (!function_exists('fasp_render_geo_gating')){
  function fasp_render_geo_gating(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fasp_geo'])){
      check_admin_referer('fasp_geo_save');
      $in = isset($_POST['fasp_geo']) ? wp_unslash($_POST['fasp_geo']) : array();
      $clean = array_map('sanitize_text_field', (array)$in);
      update_option('fasp_geo_gating', $clean);
      echo '<div class="updated"><p>Geo gating saved.</p></div>';
    }

    $o = get_option('fasp_geo_gating', array());
    echo '<div class="wrap fasp-admin"><h1>Geo Gating</h1><div class="fasp-card"><form method="post">';
    wp_nonce_field('fasp_geo_save');
    echo '<p><label>Allowed Countries (ISO CSV)<br><input class="regular-text" name="fasp_geo[allow]" value="' . esc_attr( $o['allow'] ?? '' ) . '"></label></p>';
    echo '<p><label>Blocked Countries (ISO CSV)<br><input class="regular-text" name="fasp_geo[block]" value="' . esc_attr( $o['block'] ?? '' ) . '"></label></p>';
    submit_button('Save');
    echo '</form></div></div>';
  }
}
