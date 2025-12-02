<?php if (!defined('ABSPATH')) exit;
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_pixels','Pixels & Webhook','fasp_res_pixels_box','fasp_resource','side','default');
});
function fasp_res_pixels_box($post){
  wp_nonce_field('fasp_res_pixels_save','fasp_res_pixels_nonce');
  $fb = get_post_meta($post->ID,'_fasp_fb_pixel',true);
  $ga = get_post_meta($post->ID,'_fasp_ga4_id',true);
  $wh = get_post_meta($post->ID,'_fasp_cta_webhook',true);
  $ha = get_post_meta($post->ID,'_fasp_cta_webhook_auth',true);
  echo '<p><label>FB Pixel ID<br><input type="text" name="fasp_fb_pixel" value="'.esc_attr($fb).'"></label></p>';
  echo '<p><label>GA4 ID<br><input type="text" name="fasp_ga4_id" value="'.esc_attr($ga).'"></label></p>';
  echo '<p><label>CTA Webhook URL<br><input type="url" class="widefat" name="fasp_cta_webhook" value="'.esc_attr($wh).'"></label></p>';
  echo '<p><label>Webhook Auth<br><input type="text" class="widefat" name="fasp_cta_webhook_auth" value="'.esc_attr($ha).'"></label></p>';
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_pixels_nonce']) || !wp_verify_nonce($_POST['fasp_res_pixels_nonce'],'fasp_res_pixels_save')) return;
  update_post_meta($post_id,'_fasp_fb_pixel', sanitize_text_field($_POST['fasp_fb_pixel'] ?? ''));
  update_post_meta($post_id,'_fasp_ga4_id', sanitize_text_field($_POST['fasp_ga4_id'] ?? ''));
  update_post_meta($post_id,'_fasp_cta_webhook', esc_url_raw($_POST['fasp_cta_webhook'] ?? ''));
  update_post_meta($post_id,'_fasp_cta_webhook_auth', sanitize_text_field($_POST['fasp_cta_webhook_auth'] ?? ''));
});
