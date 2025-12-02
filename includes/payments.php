<?php if (!defined('ABSPATH')) exit;
add_filter('woocommerce_available_payment_gateways', function($gateways){
  if (!function_exists('WC') || (is_admin() && !defined('DOING_AJAX'))) return $gateways;
  if (empty($gateways) || !WC()->cart) return $gateways;
  $sets=[];
  foreach(WC()->cart->get_cart() as $it){
    $pid=intval($it['product_id'] ?? 0);
    $rid=intval(get_post_meta($pid,'_fasp_resource_id',true));
    if(!$rid) continue;
    $allow=get_post_meta($rid,'_fasp_allowed_gateways',true);
    if(is_array($allow) && $allow){ $sets[] = array_map('sanitize_text_field',$allow); }
  }
  if(!$sets) return $gateways;
  $inter=array_shift($sets);
  foreach($sets as $s){ $inter=array_values(array_intersect($inter,$s)); }
  if(!$inter){
    wc_add_notice(__('No common payment method is available for these items. Remove one item or contact support.','forex-affiliate-suite'),'error');
    return [];
  }
  foreach($gateways as $id=>$gw){ if(!in_array($id,$inter,true)) unset($gateways[$id]); }
  if(!$gateways){
    wc_add_notice(__('No payment method available for your selection. Please contact support.','forex-affiliate-suite'),'error');
  }
  return $gateways;
},20);

add_filter('woocommerce_add_to_cart_validation', function($valid,$product_id,$qty){
  $rid=intval(get_post_meta($product_id,'_fasp_resource_id',true));
  if(!$rid) return $valid;
  $req=get_post_meta($rid,'_fasp_required_platforms',true);
  $mode=get_post_meta($rid,'_fasp_gate_mode',true)?:'any';
  if(!is_array($req) || empty($req)) return $valid;
  $uid=get_current_user_id();
  if(!$uid){ wc_add_notice(__('Please log in to access this gated resource.','forex-affiliate-suite'),'error'); return false; }
  $ok=0; $need=count($req);
  foreach($req as $k){ if(function_exists('fasp_is_user_verified_for_platform') && fasp_is_user_verified_for_platform($uid,$k)) $ok++; }
  if(($mode==='any' && $ok<1) || ($mode==='all' && $ok<$need)){
    wc_add_notice(__('You must verify your account with the required partner platform(s).','forex-affiliate-suite'),'error'); return false;
  }
  return $valid;
},11,3);

add_action('woocommerce_order_status_completed', function($order_id){
  if(!function_exists('wc_get_order')) return;
  $order=wc_get_order($order_id); if(!$order) return;
  $gateway_id=$order->get_payment_method();
  foreach($order->get_items() as $item){
    $pid=$item->get_product_id();
    $rid=intval(get_post_meta($pid,'_fasp_resource_id',true)); if(!$rid) continue;
    $qty=(int)$item->get_quantity(); $total=(float)$item->get_total()+(float)$item->get_total_tax();
    update_post_meta($rid,'_fasp_sales_count', (int)get_post_meta($rid,'_fasp_sales_count',true)+$qty);
    update_post_meta($rid,'_fasp_sales_gross', (float)get_post_meta($rid,'_fasp_sales_gross',true)+$total);
    if($gateway_id){ $k='_fasp_sales_gateway_'.sanitize_key($gateway_id); update_post_meta($rid,$k,(float)get_post_meta($rid,$k,true)+$total); }
  }
});
