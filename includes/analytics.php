<?php if (!defined('ABSPATH')) exit;
add_action('admin_post_fasp_export_csv', function(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=fasp-analytics.csv');
  $out = fopen('php://output','w');
  fputcsv($out, ['Resource','Views A','Clicks A','CTR A','Views B','Clicks B','CTR B']);
  $posts=get_posts(['post_type'=>'fasp_resource','numberposts'=>-1,'post_status'=>'any']);
  foreach ($posts as $p){
    $va=(int)get_post_meta($p->ID,'_fasp_views_A',true); $ca=(int)get_post_meta($p->ID,'_fasp_clicks_A',true);
    $vb=(int)get_post_meta($p->ID,'_fasp_views_B',true); $cb=(int)get_post_meta($p->ID,'_fasp_clicks_B',true);
    $ctra = $va ? round($ca*100/$va,2).'%' : '0%'; $ctrb = $vb ? round($cb*100/$vb,2).'%' : '0%';
    fputcsv($out, [get_the_title($p), $va,$ca,$ctra,$vb,$cb,$ctrb]);
  }
  fclose($out); exit;
});
if (!function_exists('fasp_render_analytics')){
function fasp_render_analytics(){
  echo '<h2 class="title">Analytics (A/B)</h2>';
  echo '<p><a class="button" href="'.esc_url(admin_url('admin-post.php?action=fasp_export_csv')).'">Export CSV</a></p>';
  $posts=get_posts(['post_type'=>'fasp_resource','numberposts'=>-1,'post_status'=>'any']);
  echo '<table class="widefat striped"><thead><tr><th>Resource</th><th>Views A</th><th>Clicks A</th><th>CTR A</th><th>Views B</th><th>Clicks B</th><th>CTR B</th></tr></thead><tbody>';
  foreach ($posts as $p){
    $va=(int)get_post_meta($p->ID,'_fasp_views_A',true); $ca=(int)get_post_meta($p->ID,'_fasp_clicks_A',true);
    $vb=(int)get_post_meta($p->ID,'_fasp_views_B',true); $cb=(int)get_post_meta($p->ID,'_fasp_clicks_B',true);
    $ctra = $va ? round($ca*100/$va,2).'%' : '0%'; $ctrb = $vb ? round($cb*100/$vb,2).'%' : '0%';
    echo '<tr><td>'.esc_html(get_the_title($p)).'</td><td>'.$va.'</td><td>'.$ca.'</td><td>'.$ctra.'</td><td>'.$vb.'</td><td>'.$cb.'</td><td>'.$ctrb.'</td></tr>';
  }
  echo '</tbody></table>';
}}
