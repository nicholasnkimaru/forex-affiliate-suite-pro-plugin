<?php if (!defined('ABSPATH')) exit;
function fasp_render_geo_rules(){
  if (!current_user_can('manage_options')) return;
  $enabled=get_option('fasp_geo_enabled',0);
  $map=get_option('fasp_geo_gateway_allow',[]); if(!is_array($map)) $map=[];

  if($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('fasp_geo_save','fasp_geo_nonce')){
    $enabled=!empty($_POST['fasp_geo_enabled'])?1:0;
    $newmap=[];
    if(!empty($_POST['fasp_geo'])){
      foreach($_POST['fasp_geo'] as $gw=>$codes){
        $codes=strtoupper(preg_replace('/[^A-Za-z,]/','',$codes));
        $arr=array_values(array_filter(array_map('trim', explode(',',$codes))));
        if($arr) $newmap[sanitize_key($gw)]=$arr;
      }
    }
    update_option('fasp_geo_enabled',$enabled);
    update_option('fasp_geo_gateway_allow',$newmap);
    echo '<div class="updated"><p>Geo rules saved.</p></div>';
    $map=$newmap;
  }

  $gateways = class_exists('WC_Payment_Gateways') ? WC_Payment_Gateways::instance()->payment_gateways() : [];
  echo '<div class="wrap"><h2>Geo Rules</h2><form method="post">'; wp_nonce_field('fasp_geo_save','fasp_geo_nonce');
  echo '<p><label><input type="checkbox" name="fasp_geo_enabled" value="1" '.checked($enabled,1,false).'> Enable</label></p>';
  echo '<table class="widefat striped"><thead><tr><th>Gateway</th><th>Allow in countries (ISO, comma-separated)</th></tr></thead><tbody>';
  if(!$gateways){ echo '<tr><td colspan="2">No gateways found.</td></tr>'; }
  foreach($gateways as $id=>$gw){
    $title=method_exists($gw,'get_title')?$gw->get_title():$id;
    $val=isset($map[$id])?implode(',',$map[$id]):'';
    echo '<tr><td><strong>'.esc_html($title).'</strong> <code>'.$id.'</code></td><td><input type="text" class="regular-text" name="fasp_geo['.esc_attr($id).']" value="'.esc_attr($val).'" placeholder="KE, UG, TZ"></td></tr>';
  }
  echo '</tbody></table>'; submit_button('Save Geo Rules'); echo '</form></div>';
}
add_filter('woocommerce_available_payment_gateways', function($gateways){
  $enabled=get_option('fasp_geo_enabled',0); if(!$enabled || empty($gateways)) return $gateways;
  $country='';
  if(isset($_POST['billing_country'])) $country=strtoupper(sanitize_text_field(wp_unslash($_POST['billing_country'])));
  if(!$country && function_exists('WC') && WC()->customer) $country=strtoupper(WC()->customer->get_billing_country());
  if(!$country && class_exists('WC_Geolocation')){ $geo=WC_Geolocation::geolocate_ip(); if(!empty($geo['country'])) $country=strtoupper($geo['country']); }
  if(!$country) return $gateways;
  $map=get_option('fasp_geo_gateway_allow',[]); if(!is_array($map)||!$map) return $gateways;
  foreach($gateways as $id=>$gw){ if(!empty($map[$id]) && is_array($map[$id]) && !in_array($country,$map[$id],true)) unset($gateways[$id]); }
  return $gateways;
},30);
