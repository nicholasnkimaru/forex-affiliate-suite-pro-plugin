<?php
if (!defined('ABSPATH')) exit;

// Central Hub cards
if (!function_exists('fasp_hub_home')){
  function fasp_hub_home(){
    echo '<div class="wrap fasp-admin"><h1>Forex Affiliate</h1>
      <div class="fasp-grid">
        <div class="fasp-card"><h3>Start here</h3><p class="muted">Connect platforms (Deriv etc.), add resources, and set up payments.</p></div>
        <div class="fasp-card"><h3>Run ads</h3><p class="muted">Use Creatives Lab to generate hooks/benefits/proofs; ship angles and track conversions.</p></div>
        <div class="fasp-card"><h3>Measure</h3><p class="muted">Attribution & Reports show which creatives and angles convert to verified signups.</p></div>
      </div></div>';
  }
}

// Reports lite page (explanations)
if (!function_exists('fasp_admin_reports_menu')){
  add_action('admin_menu', function(){
    add_submenu_page('fasp_hub','Reports','Reports','manage_options','fasp_reports_lite','fasp_reports_lite_page');
  });
}
if (!function_exists('fasp_reports_lite_page')){
  function fasp_reports_lite_page(){
    global $wpdb;
    $table = $wpdb->prefix.'fasp_clicks';
    $rows = $wpdb->get_results("SELECT platform, action, COUNT(*) as total FROM {$table} GROUP BY platform, action ORDER BY total DESC LIMIT 100", ARRAY_A);
    echo '<div class="wrap fasp-admin"><h1>Reports</h1>
      <div class="fasp-wrap fasp-card"><p class="muted">This table summarizes <strong>clicks</strong> and key actions by platform. Pair this with <em>Attribution</em> to see which ads/angles convert to <strong>verified accounts</strong>.</p></div>
      <div class="fasp-wrap fasp-card"><table class="widefat"><thead><tr><th>Platform</th><th>Action</th><th>Total</th></tr></thead><tbody>';
    if ($rows){
      foreach($rows as $r){
        echo '<tr><td>'.esc_html($r['platform']).'</td><td>'.esc_html($r['action']).'</td><td>'.intval($r['total']).'</td></tr>';
      }
    } else {
      echo '<tr><td colspan="3">No data yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
  }
}

// Promo Landings builder (simple page generator using shortcodes)
if (!function_exists('fasp_admin_landings_menu')){
  add_action('admin_menu', function(){
    add_submenu_page('fasp_hub','Promo Landings','Promo Landings','manage_options','fasp_landings_builder','fasp_landings_builder_page');
  });
}
if (!function_exists('fasp_landings_builder_page')){
  function fasp_landings_builder_page(){
    if (!current_user_can('manage_options')) return;
    if (!empty($_POST['fasp_land_create']) && check_admin_referer('fasp_land_build','fasp_land_build_nonce')){
      $title = sanitize_text_field($_POST['title'] ?? 'Forex Landing');
      $platform = sanitize_title($_POST['platform'] ?? 'deriv');
      $hero = sanitize_text_field($_POST['hero'] ?? 'Trade smarter with our team');
      $sub = sanitize_text_field($_POST['sub'] ?? 'Join via our partner link to unlock tools & coaching.');
      $content = "<h2>{$hero}</h2>
<p>{$sub}</p>
[fasp_join platform=\"{$platform}\"]
[fasp_resources per_page=\"12\"]
[fasp_coaches per_page=\"12\"]";
      $page_id = wp_insert_post(['post_title'=>$title,'post_type'=>'page','post_status'=>'publish','post_content'=>$content]);
      if (!is_wp_error($page_id)){
        echo '<div class="updated"><p>Landing created: <a href="'.esc_url(get_permalink($page_id)).'" target="_blank">View</a></p></div>';
      } else {
        echo '<div class="error"><p>Failed to create landing.</p></div>';
      }
    }
    ?>
    <div class="wrap fasp-admin">
      <h1>Promo Landings</h1>
      <div class="fasp-wrap fasp-card">
        <form method="post">
          <?php wp_nonce_field('fasp_land_build','fasp_land_build_nonce'); ?>
          <p><label>Page Title<br><input class="regular-text" name="title" value="Forex Landing"></label></p>
          <p><label>Platform Slug (e.g., deriv)<br><input class="regular-text" name="platform" value="deriv"></label></p>
          <p><label>Hero Title<br><input class="regular-text" name="hero" value="Trade smarter with our team"></label></p>
          <p><label>Subheading<br><input class="regular-text" name="sub" value="Join via our partner link to unlock tools & coaching."></label></p>
          <p><button class="button button-primary" name="fasp_land_create" value="1">Create Landing Page</button></p>
        </form>
      </div>
    </div>
    <?php
  }
}

// Creatives Lab (variants + angles scoreboard) — minimal; avoids re-declare
if (!function_exists('fasp_creatives_lab_page')){
  add_action('admin_menu', function(){
    add_submenu_page('fasp_hub','Creatives Lab','Creatives Lab','manage_options','fasp_creatives_lab','fasp_creatives_lab_page');
  });
  function fasp_creatives_lab_page(){
    if (!current_user_can('manage_options')) return;
    $hook = sanitize_text_field($_POST['hook'] ?? '');
    $benefits = sanitize_textarea_field($_POST['benefits'] ?? '');
    $proofs = sanitize_textarea_field($_POST['proofs'] ?? '');
    $cta = sanitize_text_field($_POST['cta'] ?? 'Create your Deriv account');
    $variants = [];
    if (!empty($_POST['gen'])){
      $H = array_filter(array_map('trim', explode("\n", $hook)));
      $B = array_filter(array_map('trim', explode("\n", $benefits)));
      $P = array_filter(array_map('trim', explode("\n", $proofs)));
      foreach ($H as $h){ foreach ($B as $b){ foreach ($P as $p){
        $variants[] = ['primary'=>$h, 'body'=>$b.' — '.$p, 'cta'=>$cta];
      } } }
    }
    global $wpdb; $clicks = $wpdb->prefix.'fasp_clicks';
    $since = gmdate('Y-m-d H:i:s', time()-30*86400);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$clicks} WHERE created_at >= %s", $since), ARRAY_A);
    $camp = [];
    foreach($rows as $r){
      $q=[]; $p = wp_parse_url($r['url']);
      if (!empty($p['query'])) parse_str($p['query'],$q);
      $angle = sanitize_key($q['angle'] ?? ($q['utm_content'] ?? 'na'));
      $camp[$angle] = ($camp[$angle]??0)+1;
    }
    $ver = get_users([ 'fields'=>['ID'], 'meta_key'=>'_fasp_verified_at', 'meta_value'=>$since, 'meta_compare'=>'>=' ]);
    $ver_map = [];
    foreach($ver as $u){ $a = get_user_meta($u->ID, '_fasp_variant_angle', true); if(!$a) $a='na'; $ver_map[$a] = ($ver_map[$a]??0)+1; }
    ?>
    <div class="wrap fasp-admin">
      <h1>Creatives Lab</h1>
      <div class="fasp-wrap fasp-card">
        <h2>Generate copy variants</h2>
        <form method="post">
          <p><label><strong>Hooks</strong> (one per line)<br><textarea class="large-text code" rows="4" name="hook"><?php echo esc_textarea($hook); ?></textarea></label></p>
          <p><label><strong>Benefits</strong> (one per line)<br><textarea class="large-text code" rows="4" name="benefits"><?php echo esc_textarea($benefits); ?></textarea></label></p>
          <p><label><strong>Proofs</strong> (one per line)<br><textarea class="large-text code" rows="4" name="proofs"><?php echo esc_textarea($proofs); ?></textarea></label></p>
          <p><label><strong>CTA</strong><br><input class="regular-text" name="cta" value="<?php echo esc_attr($cta); ?>"></label></p>
          <p><button class="button button-primary" name="gen" value="1">Generate</button></p>
        </form>
        <?php if($variants): ?>
          <h3>Variants</h3>
          <table class="widefat fasp-table"><thead><tr><th>Primary Text</th><th>Body</th><th>CTA</th></tr></thead><tbody>
            <?php foreach($variants as $v): ?><tr>
              <td><?php echo esc_html($v['primary']); ?></td>
              <td><?php echo esc_html($v['body']); ?></td>
              <td><?php echo esc_html($v['cta']); ?></td>
            </tr><?php endforeach; ?>
          </tbody></table>
        <?php endif; ?>
      </div>
      <div class="fasp-wrap fasp-card">
        <h2>Angles scoreboard (last 30 days)</h2>
        <table class="widefat fasp-table"><thead><tr><th>Angle</th><th>Clicks</th><th>Verified</th><th>Conv %</th></tr></thead><tbody>
          <?php ksort($camp); foreach($camp as $a=>$c){ $v=intval($ver_map[$a]??0); $cv=$c>0? round($v*100/$c,1):0; echo '<tr><td>'.esc_html($a).'</td><td>'.intval($c).'</td><td>'.$v.'</td><td>'.$cv.'%</td></tr>'; } if(!$camp) echo '<tr><td colspan="4">No data.</td></tr>'; ?>
        </tbody></table>
      </div>
    </div>
    <?php
  }
}
