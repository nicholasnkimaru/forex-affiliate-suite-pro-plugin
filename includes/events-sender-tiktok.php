<?php
if (!defined('ABSPATH')) exit;
function fasp_send_tiktok_event($item){
  if (!function_exists('wp_remote_post')) return true;
  $o = get_option('fasp_marketing', []);
  if (empty($o['tiktok_enable']) || $o['tiktok_enable']!=='1') return true;
  $pixel = trim($o['tiktok_pixel_id'] ?? '');
  $token = trim($o['tiktok_access_token'] ?? '');
  if (!$pixel || !$token) return true;
  $name = strtolower($item['name']);
  $payload = $item['payload'] ?? [];
  $event_url = isset($payload['event_source_url']) ? esc_url_raw($payload['event_source_url']) : home_url(add_query_arg(null,null));
  $user = fasp_evt_user_data();
  $ev = [ 'event' => $name, 'timestamp' => gmdate('c'), 'context' => [ 'page'=>['url'=>$event_url], 'user'=> $user ] ];
  $body = [ 'pixel_code'=>$pixel, 'event'=> $ev ];
  $resp = wp_remote_post('https://business-api.tiktok.com/open_api/v1.3/pixel/events/', [
    'timeout'=>8,
    'headers'=>['Content-Type'=>'application/json','Access-Token'=>$token],
    'body'=>wp_json_encode($body)
  ]);
  if (is_wp_error($resp)) return false;
  $code = wp_remote_retrieve_response_code($resp);
  return ($code>=200 && $code<300);
}
