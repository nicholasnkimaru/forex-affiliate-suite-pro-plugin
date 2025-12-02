<?php if (!defined('ABSPATH')) exit;
function fasp_render_user_verify(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $plats=function_exists('fasp_get_platforms')?fasp_get_platforms():[]; $user=null; $msg='';
  if (isset($_GET['uid'])){ $user=get_user_by('id',intval($_GET['uid'])); }
  if ($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_uv','fasp_uv_nonce')){
    $uid=intval($_POST['uid'] ?? 0); $user=get_user_by('id',$uid);
    if ($user){ foreach ($plats as $p){ $k=sanitize_key($p['key']); $val=!empty($_POST['plat'][$k])?1:0; if ($val) update_user_meta($uid,'_fasp_verified_'.$k,1); else delete_user_meta($uid,'_fasp_verified_'.$k); } $msg='Saved.'; }
  }
  echo '<div class="wrap"><h1>User Verification</h1>'; if ($msg) echo '<div class="updated"><p>'.$msg.'</p></div>';
  echo '<form method="get"><input type="hidden" name="page" value="fasp_user_verify"><p><label>User ID <input type="number" name="uid" value="'.esc_attr($_GET['uid'] ?? '').'" style="width:120px"> <button class="button">Load</button></label></p></form>';
  if ($user){ echo '<h3>'.esc_html($user->display_name).' (#'.intval($user->ID).')</h3><form method="post">'; wp_nonce_field('fasp_uv','fasp_uv_nonce'); echo '<input type="hidden" name="uid" value="'.intval($user->ID).'">';
    echo '<table class="widefat striped"><thead><tr><th>Platform</th><th>Verified</th></tr></thead><tbody>';
    foreach ($plats as $p){ $k=sanitize_key($p['key']); $has=get_user_meta($user->ID,'_fasp_verified_'.$k,true)?'checked':''; echo '<tr><td>'.esc_html($p['name']).' <code>'.$k.'</code></td><td><input type="checkbox" name="plat['.$k.']" value="1" '.$has.'></td></tr>'; }
    echo '</tbody></table>'; submit_button('Save'); echo '</form>'; }
  echo '</div>';
}
