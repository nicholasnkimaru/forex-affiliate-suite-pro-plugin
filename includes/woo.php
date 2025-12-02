<?php if (!defined('ABSPATH')) exit;
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_prod_link','Forex Resource Link','fasp_prod_link_box','product','side','default');
});
function fasp_prod_link_box($post){
  wp_nonce_field('fasp_prod_link_save','fasp_prod_link_nonce');
  $rid=(int)get_post_meta($post->ID,'_fasp_resource_id',true);
  echo '<p>Map this product to a Resource so checkout/gating/analytics apply.</p>';
  wp_dropdown_pages(['post_type'=>'fasp_resource','name'=>'fasp_resource_id','show_option_none'=>'— None —','selected'=>$rid]);
}
add_action('save_post_product', function($post_id){
  if(!isset($_POST['fasp_prod_link_nonce']) || !wp_verify_nonce($_POST['fasp_prod_link_nonce'],'fasp_prod_link_save')) return;
  update_post_meta($post_id,'_fasp_resource_id', intval($_POST['fasp_resource_id'] ?? 0));
});
