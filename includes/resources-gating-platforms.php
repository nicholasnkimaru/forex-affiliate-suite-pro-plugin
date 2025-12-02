<?php if (!defined('ABSPATH')) exit;
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_access_gate','Access & Gating (Platforms)','fasp_res_access_gate_box','fasp_resource','side','high');
});
function fasp_res_access_gate_box($post){
  wp_nonce_field('fasp_res_gate_save','fasp_res_gate_nonce');
  $req=get_post_meta($post->ID,'_fasp_required_platforms',true); if(!is_array($req)) $req=[];
  $mode=get_post_meta($post->ID,'_fasp_gate_mode',true)?:'any';
  $platforms = function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];
  echo '<p><label>Mode: <select name="fasp_gate_mode"><option value="any" '.selected($mode,'any',false).'>Any selected platform</option><option value="all" '.selected($mode,'all',false).'>All selected platforms</option></select></label></p>';
  if(!$platforms){ echo '<p><em>No platforms configured.</em></p>'; return; }
  foreach($platforms as $p){
    $k=sanitize_key((isset($p['key']) ? $p['key'] : '')); $t=esc_html($p['name']); $chk=in_array($k,$req,true)?'checked':'';
    echo '<label style="display:block;margin:6px 0"><input type="checkbox" name="fasp_required_platforms[]" value="'.$k.'" '.$chk.'> '.$t.' <code>'.$k.'</code></label>';
  }
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_gate_nonce']) || !wp_verify_nonce($_POST['fasp_res_gate_nonce'],'fasp_res_gate_save')) return;
  $mode = in_array(($_POST['fasp_gate_mode']??'any'),['any','all'],true) ? $_POST['fasp_gate_mode'] : 'any';
  update_post_meta($post_id,'_fasp_gate_mode',$mode);
  $req=[];
  if(!empty($_POST['fasp_required_platforms']) && is_array($_POST['fasp_required_platforms'])){
    foreach($_POST['fasp_required_platforms'] as $k){ $req[] = sanitize_key($k); }
  }
  update_post_meta($post_id,'_fasp_required_platforms',$req);
});
