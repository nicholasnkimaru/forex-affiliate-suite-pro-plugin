<?php if (!defined('ABSPATH')) exit;
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_cta','CTA & A/B','fasp_res_cta_box','fasp_resource','normal','default');
});
function fasp_res_cta_box($post){
  wp_nonce_field('fasp_res_cta_save','fasp_res_cta_nonce');
  $ab=(int)get_post_meta($post->ID,'_fasp_ab_enabled',true);
  $labA=get_post_meta($post->ID,'_fasp_cta_label',true);
  $labB=get_post_meta($post->ID,'_fasp_cta_label_b',true);
  $landA=(int)get_post_meta($post->ID,'_fasp_linked_landing',true);
  $landB=(int)get_post_meta($post->ID,'_fasp_linked_landing_b',true);
  $useAffA=(int)get_post_meta($post->ID,'_fasp_cta_use_aff',true);
  $useAffB=(int)get_post_meta($post->ID,'_fasp_cta_use_aff_b',true);
  $affB=get_post_meta($post->ID,'_fasp_affiliate_url_b',true);
  echo '<p><label><input type="checkbox" name="fasp_ab_enabled" value="1" '.checked($ab,1,false).'> Enable A/B test</label></p>';
  echo '<div style="display:flex;gap:16px;flex-wrap:wrap">';
  echo '<div><h3>A</h3><p>CTA label<br><input type="text" name="fasp_cta_label" value="'.esc_attr($labA).'"></p><p>Landing<br>';
  wp_dropdown_pages(['post_type'=>'fasp_landing','name'=>'fasp_linked_landing','show_option_none'=>'— Select —','selected'=>$landA]);
  echo '</p><p><label><input type="checkbox" name="fasp_cta_use_aff" value="1" '.checked($useAffA,1,false).'> Use Affiliate link</label></p></div>';
  echo '<div><h3>B</h3><p>CTA label (B)<br><input type="text" name="fasp_cta_label_b" value="'.esc_attr($labB).'"></p><p>Landing (B)<br>';
  wp_dropdown_pages(['post_type'=>'fasp_landing','name'=>'fasp_linked_landing_b','show_option_none'=>'— Select —','selected'=>$landB]);
  echo '</p><p><label><input type="checkbox" name="fasp_cta_use_aff_b" value="1" '.checked($useAffB,1,false).'> Use Affiliate link (B)</label></p>';
  echo '<p>Affiliate URL (B)<br><input type="url" name="fasp_affiliate_url_b" value="'.esc_attr($affB).'" class="regular-text"></p></div>';
  echo '</div>';
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_cta_nonce']) || !wp_verify_nonce($_POST['fasp_res_cta_nonce'],'fasp_res_cta_save')) return;
  update_post_meta($post_id,'_fasp_ab_enabled', !empty($_POST['fasp_ab_enabled'])?1:0);
  update_post_meta($post_id,'_fasp_cta_label', sanitize_text_field($_POST['fasp_cta_label'] ?? ''));
  update_post_meta($post_id,'_fasp_cta_label_b', sanitize_text_field($_POST['fasp_cta_label_b'] ?? ''));
  update_post_meta($post_id,'_fasp_linked_landing', intval($_POST['fasp_linked_landing'] ?? 0));
  update_post_meta($post_id,'_fasp_linked_landing_b', intval($_POST['fasp_linked_landing_b'] ?? 0));
  update_post_meta($post_id,'_fasp_cta_use_aff', !empty($_POST['fasp_cta_use_aff'])?1:0);
  update_post_meta($post_id,'_fasp_cta_use_aff_b', !empty($_POST['fasp_cta_use_aff_b'])?1:0);
  update_post_meta($post_id,'_fasp_affiliate_url_b', esc_url_raw($_POST['fasp_affiliate_url_b'] ?? ''));
});
