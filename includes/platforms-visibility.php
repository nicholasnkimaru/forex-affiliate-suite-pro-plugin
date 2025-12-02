<?php if (!defined('ABSPATH')) exit;
function fasp_render_visibility(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $plats=function_exists('fasp_get_platforms')?fasp_get_platforms():[];
  if($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_vis','fasp_vis_nonce')){
    $rows=[];
    if(!empty($_POST['plat']) && is_array($_POST['plat'])){
      foreach($_POST['plat'] as $i=>$r){
        $rows[]=[
          'key'=>sanitize_key($r['key']??('p'.$i)),
          'name'=>sanitize_text_field($r['name']??''),
          'link'=>esc_url_raw($r['link']??''),
          'enabled'=>!empty($r['enabled'])?1:0,
          'visible_to_users'=>!empty($r['visible_to_users'])?1:0,
          'show_clicks_to_users'=>!empty($r['show_clicks_to_users'])?1:0,
        ];
      }
    }
    fasp_save_platforms($rows);
    $plats=fasp_get_platforms();
    echo '<div class="updated"><p>Saved.</p></div>';
  }
  echo '<div class="wrap"><h1>Platform Visibility</h1><form method="post">'; wp_nonce_field('fasp_vis','fasp_vis_nonce');
  echo '<table class="widefat striped"><thead><tr><th>Key</th><th>Name</th><th>Link</th><th>Enabled</th><th>Visible</th><th>Show Clicks</th></tr></thead><tbody>';
  foreach($plats as $i=>$p){
    $en=!empty($p['enabled'])?'checked':''; $vi=!empty($p['visible_to_users'])?'checked':''; $sc=!empty($p['show_clicks_to_users'])?'checked':'';
    echo '<tr><td><input type="text" name="plat['.$i.'][key]" value="'.esc_attr($p['key']).'"></td>
              <td><input type="text" name="plat['.$i.'][name]" value="'.esc_attr($p['name']).'"></td>
              <td><input type="url"  name="plat['.$i.'][link]" value="'.esc_attr($p['link']).'"></td>
              <td style="text-align:center"><input type="checkbox" name="plat['.$i.'][enabled]" value="1" '.$en.'></td>
              <td style="text-align:center"><input type="checkbox" name="plat['.$i.'][visible_to_users]" value="1" '.$vi.'></td>
              <td style="text-align:center"><input type="checkbox" name="plat['.$i.'][show_clicks_to_users]" value="1" '.$sc.'></td></tr>';
  }
  echo '</tbody></table>'; submit_button('Save'); echo '</form></div>';
}
