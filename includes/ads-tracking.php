<?php if (!defined('ABSPATH')) exit;
if (!function_exists('fasp_render_ads_tracking')){
function fasp_render_ads_tracking(){
  if ($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_ads_save','fasp_ads_nonce')){
    update_option('fasp_ad_above_global', fasp_kses_ad($_POST['ad_above']??''));
    update_option('fasp_ad_below_global', fasp_kses_ad($_POST['ad_below']??''));
    update_option('fasp_ad_inject_after', max(0, intval($_POST['inject_after']??0)));
    update_option('fasp_fb_pixel_id', sanitize_text_field($_POST['fb_pixel']??''));
    update_option('fasp_gtag_id', sanitize_text_field($_POST['gtag_id']??''));
    echo '<div class="updated"><p>Saved.</p></div>';
  }
  $aa=get_option('fasp_ad_above_global',''); $ab=get_option('fasp_ad_below_global',''); $n=(int)get_option('fasp_ad_inject_after',0);
  $fb=get_option('fasp_fb_pixel_id',''); $ga=get_option('fasp_gtag_id','');
  echo '<form method="post">'; wp_nonce_field('fasp_ads_save','fasp_ads_nonce');
  echo '<h2>Global Ads & Pixels</h2>';
  echo '<p><strong>Ad Above</strong><br><textarea name="ad_above" class="large-text code" rows="4">'.esc_textarea($aa).'</textarea></p>';
  echo '<p><strong>Ad Below</strong><br><textarea name="ad_below" class="large-text code" rows="4">'.esc_textarea($ab).'</textarea></p>';
  echo '<p><strong>Auto-inject after paragraph</strong> <input type="number" name="inject_after" value="'.esc_attr($n).'" min="0" style="width:80px"></p>';
  echo '<p><strong>FB Pixel ID</strong> <input type="text" name="fb_pixel" value="'.esc_attr($fb).'"> &nbsp; <strong>GA4 ID</strong> <input type="text" name="gtag_id" value="'.esc_attr($ga).'"></p>';
  submit_button('Save');
  echo '</form>';
}}
