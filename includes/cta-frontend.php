<?php if (!defined('ABSPATH')) exit;
add_action('wp_enqueue_scripts', function(){
  if (is_singular('fasp_resource')){
    wp_enqueue_script('fasp-cta', FASP_URL.'assets/js/fasp-cta.js', ['jquery'], FASP_VER, true);
    wp_localize_script('fasp-cta','FASP_CTA', ['ajax'=> admin_url('admin-ajax.php'), 'nonce'=> wp_create_nonce('fasp_cta_nonce')]);
  }
});
function fasp_ab_bucket($post_id){
  if (!get_post_meta($post_id,'_fasp_ab_enabled',true)) return 'A';
  if (!isset($_COOKIE['fasp_ab_bucket'])){ $b = (rand(0,100) < 50) ? 'A':'B'; setcookie('fasp_ab_bucket',$b,time()+2592000, COOKIEPATH?COOKIEPATH:'/', COOKIE_DOMAIN, is_ssl(), true); $_COOKIE['fasp_ab_bucket']=$b; }
  return ($_COOKIE['fasp_ab_bucket']==='B') ? 'B':'A';
}
add_filter('the_content', function($c){
  if (!is_singular('fasp_resource') || is_admin()) return $c;
  $id = get_the_ID(); $bucket = fasp_ab_bucket($id);
  $labelA = get_post_meta($id,'_fasp_cta_label',true) ?: __('Get Access','forex-affiliate-suite');
  $labelB = get_post_meta($id,'_fasp_cta_label_b',true) ?: $labelA;
  $label = ($bucket==='B') ? $labelB : $labelA;
  $useAff = ($bucket==='B') ? (int)get_post_meta($id,'_fasp_cta_use_aff_b',true) : (int)get_post_meta($id,'_fasp_cta_use_aff',true);
  $affB  = get_post_meta($id,'_fasp_affiliate_url_b',true);
  $affA  = get_post_meta($id,'_fasp_affiliate_url',true);
  $aff   = ($bucket==='B' && $useAff) ? $affB : ($useAff ? $affA : '');
  $landingA = (int)get_post_meta($id,'_fasp_linked_landing',true);
  $landingB = (int)get_post_meta($id,'_fasp_linked_landing_b',true);
  $landing  = ($bucket==='B') ? $landingB : $landingA;
  $href = $landing ? get_permalink($landing) : ($aff ?: '#');
  $href = add_query_arg(['rid'=>get_current_user_id() ?: 0, 'ab'=>$bucket], $href);
  $btn = '<p class="fasp-cta-wrap"><a href="'.esc_url($href).'" class="button fasp-cta" data-rid="'.esc_attr($id).'" data-bucket="'.esc_attr($bucket).'" rel="nofollow noopener">'.$label.'</a></p>';
  return $c.$btn;
}, 40);
add_action('wp', function(){
  if (is_singular('fasp_resource')){ $id=get_queried_object_id(); $b=fasp_ab_bucket($id); $key=($b==='B') ? '_fasp_views_B' : '_fasp_views_A'; $n=(int)get_post_meta($id,$key,true); update_post_meta($id,$key,$n+1); }
});
add_action('wp_ajax_fasp_cta_click','fasp_cta_click');
add_action('wp_ajax_nopriv_fasp_cta_click','fasp_cta_click');
function fasp_cta_click(){
  check_ajax_referer('fasp_cta_nonce','nonce');
  $rid = intval($_POST['rid'] ?? 0); $bucket = ($_POST['bucket'] ?? 'A')==='B' ? 'B':'A';
  if (!$rid) wp_send_json_error(['msg'=>'no rid']);
  $key = ($bucket==='B') ? '_fasp_clicks_B' : '_fasp_clicks_A'; $n=(int)get_post_meta($rid,$key,true); update_post_meta($rid,$key,$n+1);
  $wh = get_post_meta($rid,'_fasp_cta_webhook',true);
  if ($wh){ $headers=[]; $h=get_post_meta($rid,'_fasp_cta_webhook_auth',true); if ($h) $headers['Authorization']=$h;
    $payload=['resource_id'=>$rid,'bucket'=>$bucket,'user_id'=>get_current_user_id(),'utm'=>['utm_source'=>$_COOKIE['fasp_utm_source'] ?? '','utm_medium'=>$_COOKIE['fasp_utm_medium'] ?? '','utm_campaign'=>$_COOKIE['fasp_utm_campaign'] ?? '','utm_term'=>$_COOKIE['fasp_utm_term'] ?? '','utm_content'=>$_COOKIE['fasp_utm_content'] ?? '','rid'=>$_COOKIE['fasp_rid'] ?? '','ab'=>$_COOKIE['fasp_ab'] ?? '']];
    wp_remote_post($wh, ['timeout'=>3,'headers'=>$headers,'body'=>$payload]);
  }
  $fb=get_post_meta($rid,'_fasp_fb_pixel',true) ?: get_option('fasp_fb_pixel_id',''); $ga=get_post_meta($rid,'_fasp_ga4_id',true) ?: get_option('fasp_gtag_id','');
  wp_send_json_success(['ok'=>1,'fb'=>$fb?1:0,'ga'=>$ga?1:0]);
}
