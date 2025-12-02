<?php if (!defined('ABSPATH')) exit;
function fasp_get_email_settings(){ return get_option('fasp_email',['webhook_url'=>'','webhook_auth'=>'']); }
function fasp_save_email_settings($a){ if(!is_array($a))$a=[]; update_option('fasp_email',$a); }
function fasp_render_email_integration(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized'); $s=fasp_get_email_settings();
  if($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_email_save','fasp_email_nonce')){
    $s['webhook_url']=esc_url_raw($_POST['webhook_url']??''); $s['webhook_auth']=sanitize_text_field($_POST['webhook_auth']??''); fasp_save_email_settings($s); echo '<div class="updated"><p>Saved.</p></div>';
  }
  echo '<div class="wrap"><h1>Email & Leads</h1><form method="post">'; wp_nonce_field('fasp_email_save','fasp_email_nonce');
  echo '<table class="form-table"><tr><th>Webhook URL</th><td><input type="url" class="regular-text" name="webhook_url" value="'.esc_attr($s['webhook_url']).'"></td></tr><tr><th>Webhook Auth</th><td><input type="text" class="regular-text" name="webhook_auth" value="'.esc_attr($s['webhook_auth']).'"></td></tr></table>';
  echo '<p>Use shortcode <code>[fasp_capture]</code> to capture name/email and POST to the webhook with UTM cookies.</p>';
  submit_button('Save'); echo '</form></div>';
}
add_shortcode('fasp_capture', function(){
  $s=fasp_get_email_settings();
  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fasp_cap_nonce']) && wp_verify_nonce($_POST['fasp_cap_nonce'],'fasp_cap')){
    $name=sanitize_text_field($_POST['name']??''); $email=sanitize_email($_POST['email']??'');
    if ($email && $s['webhook_url']){
      $utm=[]; foreach(['utm_source','utm_medium','utm_campaign','utm_term','utm_content','rid','ab'] as $p){ if(!empty($_COOKIE['fasp_'.$p])) $utm[$p]=$_COOKIE['fasp_'.$p]; }
      $headers=[]; if(!empty($s['webhook_auth'])) $headers['Authorization']=$s['webhook_auth'];
      wp_remote_post($s['webhook_url'], ['timeout'=>5,'headers'=>$headers,'body'=>['name'=>$name,'email'=>$email,'utm'=>$utm]]);
      return '<div class="fasp-ok">Thanks! Please check your email.</div>';
    }
    return '<div class="fasp-err">Unable to submit.</div>';
  }
  ob_start(); ?>
  <form method="post" class="fasp-cap"><?php wp_nonce_field('fasp_cap','fasp_cap_nonce'); ?>
    <p><label>Name<br><input type="text" name="name"></label></p>
    <p><label>Email<br><input type="email" name="email" required></label></p>
    <p><button class="button button-primary" type="submit">Get Access</button></p>
  </form>
  <?php return ob_get_clean();
});
