<?php if (!defined('ABSPATH')) exit;
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_pixels','Per-Resource Pixels & Webhook','fasp_res_pixels_box','fasp_resource','side','low');
});
function fasp_res_pixels_box($post){
  $fb = get_post_meta($post->ID,'_fasp_fb_pixel_id',true);
  $gt = get_post_meta($post->ID,'_fasp_gtag_id',true);
  $wh = get_post_meta($post->ID,'_fasp_webhook_url',true);
  wp_nonce_field('fasp_res_pixels_save','fasp_res_pixels_nonce');
  echo '<p><label>Facebook Pixel ID<br><input type="text" name="fasp_fb_pixel_id" value="'.esc_attr($fb).'" class="regular-text"></label></p>';
  echo '<p><label>Google Measurement ID (G-XXXX)<br><input type="text" name="fasp_gtag_id" value="'.esc_attr($gt).'" class="regular-text"></label></p>';
  echo '<p><label>Webhook URL (on CTA click)<br><input type="url" name="fasp_webhook_url" value="'.esc_url($wh).'" class="regular-text"></label></p>';
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_pixels_nonce']) || !wp_verify_nonce($_POST['fasp_res_pixels_nonce'],'fasp_res_pixels_save')) return;
  update_post_meta($post_id,'_fasp_fb_pixel_id', sanitize_text_field($_POST['fasp_fb_pixel_id'] ?? ''));
  update_post_meta($post_id,'_fasp_gtag_id', sanitize_text_field($_POST['fasp_gtag_id'] ?? ''));
  update_post_meta($post_id,'_fasp_webhook_url', esc_url_raw($_POST['fasp_webhook_url'] ?? ''));
});