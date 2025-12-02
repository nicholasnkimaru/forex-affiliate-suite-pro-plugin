<?php if (!defined('ABSPATH')) exit;
/** JSON-LD for Coaches (Person or Event) + optional VideoObject */
add_action('wp_head', function(){
  if (!is_singular('fasp_coach_event')) return;
  global $post;
  $name = get_the_title($post);
  $desc = get_post_meta($post->ID,'_fasp_coach_intro',true) ?: wp_strip_all_tags(get_the_excerpt($post));
  $img  = get_post_meta($post->ID,'_fasp_coach_photo_id',true) ? wp_get_attachment_image_url(get_post_meta($post->ID,'_fasp_coach_photo_id',true), 'full') : get_the_post_thumbnail_url($post,'full');
  $role = get_post_meta($post->ID,'_fasp_coach_role',true);
  $email= get_post_meta($post->ID,'_fasp_coach_email',true);
  $phone= get_post_meta($post->ID,'_fasp_coach_phone',true);

  $sameAs = array_values(array_filter([
    get_post_meta($post->ID,'_fasp_coach_twitter',true),
    get_post_meta($post->ID,'_fasp_coach_linkedin',true),
    get_post_meta($post->ID,'_fasp_coach_youtube',true),
    get_post_meta($post->ID,'_fasp_coach_facebook',true),
    get_post_meta($post->ID,'_fasp_coach_telegram',true),
    get_post_meta($post->ID,'_fasp_coach_calendly',true),
  ]));

  $data = ['@context'=>'https://schema.org','@type'=>'Person','name'=>$name,'description'=>$desc,'jobTitle'=>$role ?: 'Coach','url'=>get_permalink($post),'sameAs'=>$sameAs];
  if ($img) $data['image']=$img;
  if ($email) $data['email']='mailto:'.$email;
  if ($phone) $data['telephone']=$phone;

  $video = get_post_meta($post->ID,'_fasp_coach_video_url',true);
  if ($video){
    $data['subjectOf'] = [
      '@type'=>'VideoObject',
      'name'=> $name.' — Introduction',
      'thumbnailUrl'=> $img ?: null,
      'uploadDate'=> get_the_date('c', $post),
      'description'=> $desc,
      'embedUrl'=> $video
    ];
  }
  echo '<script type="application/ld+json">'.wp_json_encode(array_filter($data)).'</script>';
}, 5);
