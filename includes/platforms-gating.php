<?php if (!defined('ABSPATH')) exit;
function fasp_get_platform_gating(){ $g=get_option('fasp_platform_gating',[]); return is_array($g)?$g:[]; }
function fasp_save_platform_gating($arr){ if(!is_array($arr)) $arr=[]; update_option('fasp_platform_gating',$arr); }

function fasp_render_platform_setup(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $platforms=function_exists('fasp_get_platforms') ? fasp_get_platforms() : [];
  $gating=fasp_get_platform_gating();
  if($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_platform_setup_save','fasp_platform_setup_nonce')){
    $new=[];
    if(!empty($_POST['plat']) && is_array($_POST['plat'])){
      foreach($_POST['plat'] as $key=>$row){
        $k=sanitize_key($key);
        $new[$k]=[
          'method'=>sanitize_text_field($row['method']??'none'),
          'app_id'=>sanitize_text_field($row['app_id']??''),
          'client_secret'=>sanitize_text_field($row['client_secret']??''),
          'webhook_url'=>esc_url_raw($row['webhook_url']??''),
          'webhook_auth'=>sanitize_text_field($row['webhook_auth']??''),
          'fail_open'=>!empty($row['fail_open'])?1:0
        ];
      }
    }
    fasp_save_platform_gating($new); echo '<div class="updated"><p>Platform settings saved.</p></div>'; $gating=$new;
  }
  echo '<div class="wrap"><h1>Platform Setup</h1><form method="post">'; wp_nonce_field('fasp_platform_setup_save','fasp_platform_setup_nonce');
  echo '<table class="widefat striped"><thead><tr><th>Platform</th><th>Method</th><th>App ID</th><th>Client Secret</th><th>Webhook URL</th><th>Webhook Auth</th><th>Fail-open</th></tr></thead><tbody>';
  foreach($platforms as $p){
    $key=sanitize_key($p['key']); $row=$gating[$key]??[]; $m=$row['method']??'none';
    $app=esc_attr($row['app_id']??''); $sec=esc_attr($row['client_secret']??''); $wh=esc_attr($row['webhook_url']??''); $wa=esc_attr($row['webhook_auth']??''); $fo=!empty($row['fail_open'])?'checked':'';
    echo '<tr><td><strong>'.esc_html($p['name']).'</strong> <code>'.$key.'</code></td>
      <td><select name="plat['.$key.'][method]">
        <option value="none" '.selected($m,'none',false).'>None</option>
        <option value="oauth" '.selected($m,'oauth',false).'>OAuth</option>
        <option value="webhook" '.selected($m,'webhook',false).'>Webhook</option>
        <option value="manual" '.selected($m,'manual',false).'>Manual</option>
      </select></td>
      <td><input type="text" name="plat['.$key.'][app_id]" value="'.$app.'"></td>
      <td><input type="text" name="plat['.$key.'][client_secret]" value="'.$sec.'"></td>
      <td><input type="url" name="plat['.$key.'][webhook_url]" value="'.$wh.'"></td>
      <td><input type="text" name="plat['.$key.'][webhook_auth]" value="'.$wa.'"></td>
      <td style="text-align:center"><input type="checkbox" name="plat['.$key.'][fail_open]" value="1" '.$fo.'></td></tr>';
  }
  echo '</tbody></table><p class="submit"><button class="button button-primary">Save settings</button></p></form></div>';
}
