<?php if (!defined('ABSPATH')) exit;

add_action('init', function(){
  register_post_type('fasp_resource', [
    'labels'=>[
      'name'=>'Forex Resources','singular_name'=>'Resource',
      'add_new'=>'Add Resource','add_new_item'=>'Add New Resource','edit_item'=>'Edit Resource','new_item'=>'New Resource'
    ],
    'public'=>true,'has_archive'=>false,'menu_icon'=>'dashicons-media-document','show_in_rest'=>true,
    'show_in_menu'=>'fasp_settings_top',
    'supports'=>['title','editor','excerpt','thumbnail','custom-fields','revisions']
  ]);
});

add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_meta','Resource Details','fasp_res_meta_cb','fasp_resource','normal','high');
});

function fasp_res_meta_cb($post){
  wp_nonce_field('fasp_res_meta_save','fasp_res_meta_nonce');
  $get = function($k,$d='') use ($post){ $v=get_post_meta($post->ID,$k,true); return $v===''?$d:$v; };
  $type    = $get('_fasp_type','ebook');
  $mon     = $get('_fasp_monetization','free');
  $prim    = $get('_fasp_primary_url','');
  $ext     = $get('_fasp_external_url','');
  $aff     = $get('_fasp_affiliate_url','');
  $plat    = (array)$get('_fasp_required_platforms',[]);
  $logic   = $get('_fasp_platform_logic','any');
  $allow   = (array)$get('_fasp_allowed_gateways',[]);
  $gates   = function_exists('fasp_get_woo_gateways') ? fasp_get_woo_gateways() : [];
  $plats   = function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];

  echo '<style>.fasp-box{border:1px solid #e5e7eb;background:#fff;border-radius:12px;padding:14px;margin:10px 0}.fasp-inline{display:flex;gap:14px;flex-wrap:wrap}.fasp-inline .field{flex:1 1 260px}.fasp-muted{color:#64748b;font-size:12px}</style>';

  echo '<div class="fasp-box"><div class="fasp-inline">
    <div class="field">
      <label><strong>Resource title</strong></label><br>
      <input type="text" name="fasp_res_title" class="regular-text" value="'.esc_attr(get_the_title($post)).'" placeholder="e.g. Beginner Forex Guide">
      <p class="fasp-muted">Displayed as the public name.</p>
    </div>
  </div></div>';

  echo '<div class="fasp-box"><div class="fasp-inline">
      <div class="field">
        <label><strong>Type</strong></label><br>
        <select name="fasp_type">';
  foreach (['ebook','video','software','course','webinar'] as $opt){
    echo '<option value="'.$opt.'" '.selected($type,$opt,false).'>'.ucfirst($opt).'</option>';
  }
  echo    '</select></div>
      <div class="field">
        <label><strong>Monetization</strong></label><br>
        <select name="fasp_monetization">';
  foreach (['free','paid','external'] as $opt){
    echo '<option value="'.$opt.'" '.selected($mon,$opt,false).'>'.ucfirst($opt).'</option>';
  }
  echo    '</select>
      </div>
    </div>
    <p class="fasp-muted">Paid → Woo checkout; External → send user to external link.</p>
  </div>';

  echo '<div class="fasp-box"><div class="fasp-inline">
      <div class="field">
        <label><strong>Primary file URL</strong> (for free/paid)</label><br>
        <input type="url" class="regular-text" name="fasp_primary_url" value="'.esc_attr($prim).'" placeholder="https://cdn.example.com/file.pdf">
      </div>
      <div class="field">
        <label><strong>External URL</strong> (if Monetization = External)</label><br>
        <input type="url" class="regular-text" name="fasp_external_url" value="'.esc_attr($ext).'" placeholder="https://...">
      </div>
      <div class="field">
        <label><strong>Affiliate link</strong></label><br>
        <input type="url" class="regular-text" name="fasp_affiliate_url" value="'.esc_attr($aff).'" placeholder="Your affiliate tracking link">
      </div>
    </div>
  </div>';

  echo '<div class="fasp-box"><h3>Allowed Payment Methods</h3><div>';
  if (empty($gates)){ echo '<em>No gateways found (install/activate WooCommerce).</em>'; }
  else{
    echo '<ul style="columns:2;list-style:disc;padding-left:18px">';
    foreach ($gates as $id=>$title){
      $chk=in_array($id,$allow)?'checked':'';
      echo '<li><label><input type="checkbox" name="fasp_allowed_gateways[]" value="'.$id.'" '.$chk.'> '.esc_html($title).' <code>'.$id.'</code></label></li>';
    }
    echo '</ul>';
  }
  echo '</div><p class="fasp-muted">Checkout will only allow intersection of (Gateway supports country) ∩ (Allowed list here).</p></div>';

  echo '<div class="fasp-box"><h3>Access (Platform verification)</h3><div class="fasp-inline">';
  echo '<div class="field"><label><strong>Required platforms</strong></label><br>';
  if (empty($plats)){ echo '<em>No platforms configured. See Platform Setup.</em>'; }
  else{
    foreach ($plats as $p){
      $k = sanitize_key((isset($p['key']) ? $p['key'] : '')); $name = esc_html($p['name']);
      $chk = in_array($k,$plat)?'checked':'';
      echo '<label style="display:inline-block;margin:0 14px 8px 0"><input type="checkbox" name="fasp_required_platforms[]" value="'.$k.'" '.$chk.'> '.$name.' <code>'.$k.'</code></label>';
    }
  }
  echo '</div>';
  echo '<div class="field"><label><strong>Logic</strong></label><br>
    <label><input type="radio" name="fasp_platform_logic" value="any" '.checked($logic,'any',false).'> Any of selected</label> &nbsp; 
    <label><input type="radio" name="fasp_platform_logic" value="all" '.checked($logic,'all',false).'> All selected</label>
  </div>';
  echo '</div></div>';
}

add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_meta_nonce']) || !wp_verify_nonce($_POST['fasp_res_meta_nonce'],'fasp_res_meta_save')) return;
  update_post_meta($post_id,'_fasp_type', sanitize_text_field($_POST['fasp_type'] ?? ''));
  update_post_meta($post_id,'_fasp_monetization', sanitize_text_field($_POST['fasp_monetization'] ?? ''));
  update_post_meta($post_id,'_fasp_primary_url', esc_url_raw($_POST['fasp_primary_url'] ?? ''));
  update_post_meta($post_id,'_fasp_external_url', esc_url_raw($_POST['fasp_external_url'] ?? ''));
  update_post_meta($post_id,'_fasp_affiliate_url', esc_url_raw($_POST['fasp_affiliate_url'] ?? ''));
  $plat = isset($_POST['fasp_required_platforms']) && is_array($_POST['fasp_required_platforms']) ? array_map('sanitize_key', $_POST['fasp_required_platforms']) : [];
  update_post_meta($post_id,'_fasp_required_platforms', $plat);
  $logic = ($_POST['fasp_platform_logic'] ?? 'any')==='all'?'all':'any';
  update_post_meta($post_id,'_fasp_platform_logic', $logic);
  $allow = isset($_POST['fasp_allowed_gateways']) && is_array($_POST['fasp_allowed_gateways']) ? array_map('sanitize_text_field', $_POST['fasp_allowed_gateways']) : [];
  update_post_meta($post_id,'_fasp_allowed_gateways', $allow);
});
