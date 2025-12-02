<?php if (!defined('ABSPATH')) exit;
add_action('wp_head', function(){
  if (is_singular('fasp_resource')){
    $id=get_the_ID();
    $canon_to = get_post_meta($id,'_fasp_canonical_to',true);
    if ($canon_to==='landing'){
      $landing=(int)get_post_meta($id,'_fasp_linked_landing',true);
      if ($landing) echo '<link rel="canonical" href="'.esc_url(get_permalink($landing)).'">';
    }
    $d=[
      '@context'=>'https://schema.org','@type'=>'CreativeWork',
      'name'=>get_the_title(),
      'datePublished'=>get_the_date('c'),
      'url'=>get_permalink(),
      'image'=>get_the_post_thumbnail_url($id,'large'),
      'author'=>['@type'=>'Organization','name'=>get_bloginfo('name')]
    ];
    echo '<script type="application/ld+json">'.wp_json_encode($d).'</script>';
  } elseif (is_singular('fasp_coach_event')){
    $d=['@context'=>'https://schema.org','@type'=>'Event','name'=>get_the_title(),'startDate'=>get_the_date('c'),'eventStatus'=>'https://schema.org/EventScheduled','eventAttendanceMode'=>'https://schema.org/OnlineEventAttendanceMode','url'=>get_permalink()];
    echo '<script type="application/ld+json">'.wp_json_encode($d).'</script>';
  }
},20);
add_filter('wp_sitemaps_post_types', function($post_types){
  $post_types['fasp_resource']='fasp_resource';
  $post_types['fasp_coach_event']='fasp_coach_event';
  return $post_types;
});
