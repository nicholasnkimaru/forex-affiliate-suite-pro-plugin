<?php if (!defined('ABSPATH')) exit;

/**
 * Resources: Ads & Gallery (ULTRA-SAFE)
 * - Manual CSV of gallery IDs (no media frame)
 * - Ad Above / Ad Below (strict sanitization)
 */

function fasp_res_ads_gallery_metabox_cb($post){
  wp_nonce_field('fasp_res_ads_gallery_save','fasp_res_ads_gallery_nonce');

  $gids     = get_post_meta($post->ID, '_fasp_gallery_ids', true);
  $ad_above = get_post_meta($post->ID, '_fasp_ad_above',   true);
  $ad_below = get_post_meta($post->ID, '_fasp_ad_below',   true);

  echo '<style>.fasp-grid{display:grid;grid-template-columns:1fr;gap:12px}.fasp-grid p{margin:0 0 8px}</style>';
  echo '<div class="fasp-grid">';
  echo '<p><label><strong>Gallery Image IDs (CSV)</strong><br><input type="text" name="fasp_gallery_ids" class="regular-text" value="'.esc_attr($gids).'"></label></p>';
  echo '<p><em>Enter attachment IDs separated by commas (e.g., 12,34,56).</em></p>';
  echo '<p><label><strong>Ad Above (HTML)</strong><br><textarea name="fasp_ad_above" class="large-text code" rows="5">'.esc_textarea($ad_above).'</textarea></label></p>';
  echo '<p><label><strong>Ad Below (HTML)</strong><br><textarea name="fasp_ad_below" class="large-text code" rows="5">'.esc_textarea($ad_below).'</textarea></label></p>';
  echo '</div>';
}

function fasp_res_ads_gallery_add_box(){
  add_meta_box(
    'fasp_res_ads_gallery',
    'Per-Resource Ads & Media',
    'fasp_res_ads_gallery_metabox_cb',
    'fasp_resource',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes','fasp_res_ads_gallery_add_box');

function fasp_res_ads_gallery_save($post_id){
  if (!isset($_POST['fasp_res_ads_gallery_nonce'])) return;
  if (!wp_verify_nonce($_POST['fasp_res_ads_gallery_nonce'],'fasp_res_ads_gallery_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post',$post_id)) return;

  // Gallery IDs: keep only digits and commas
  $gids = isset($_POST['fasp_gallery_ids']) ? $_POST['fasp_gallery_ids'] : '';
  $gids = preg_replace('/[^0-9,]/', '', $gids);
  update_post_meta($post_id,'_fasp_gallery_ids',$gids);

  // Ads: sanitize (prefer plugin helper if present)
  $ad_above = isset($_POST['fasp_ad_above']) ? $_POST['fasp_ad_above'] : '';
  $ad_below = isset($_POST['fasp_ad_below']) ? $_POST['fasp_ad_below'] : '';

  if (function_exists('fasp_kses_ad')){
    $ad_above = fasp_kses_ad($ad_above);
    $ad_below = fasp_kses_ad($ad_below);
  } else {
    $ad_above = wp_kses_post($ad_above);
    $ad_below = wp_kses_post($ad_below);
  }

  update_post_meta($post_id,'_fasp_ad_above',$ad_above);
  update_post_meta($post_id,'_fasp_ad_below',$ad_below);
}
add_action('save_post_fasp_resource','fasp_res_ads_gallery_save');
