<?php if (!defined('ABSPATH')) exit;

/**
 * Coaches CPT (fasp_coach_event) — ultra-safe v2: no closures, no inline JS, simple strings only.
 */

/* Register CPT */
function fasp_register_coach_cpt(){
  register_post_type('fasp_coach_event', array(
    'labels'=>array(
      'name'=>'Forex Coaches','singular_name'=>'Coach','add_new_item'=>'Add Coach','edit_item'=>'Edit Coach'
    ),
    'public'=>true,'show_in_menu'=>'fasp_settings_top','menu_icon'=>'dashicons-groups',
    'supports'=>array('title','editor','thumbnail','excerpt','custom-fields','revisions'),
    'show_in_rest'=>true,'rewrite'=>array('slug'=>'coach')
  ));
}
add_action('init', 'fasp_register_coach_cpt');

/* Metaboxes */
function fasp_add_coach_metaboxes(){
  add_meta_box('fasp_coach_profile','Coach Profile','fasp_coach_profile_box','fasp_coach_event','normal','high');
  add_meta_box('fasp_coach_links','Links & Media','fasp_coach_links_box','fasp_coach_event','normal','default');
  add_meta_box('fasp_coach_offer','Offer / CTA','fasp_coach_offer_box','fasp_coach_event', 'normal','high');
}
add_action('add_meta_boxes','fasp_add_coach_metaboxes');

function fasp_coach_profile_box($post){
  wp_nonce_field('fasp_coach_save','fasp_coach_nonce');
  $role      = get_post_meta($post->ID,'_fasp_coach_role',true);
  $intro     = get_post_meta($post->ID,'_fasp_coach_intro',true);
  $photo_id  = intval(get_post_meta($post->ID,'_fasp_coach_photo_id', true));
  $photo_img = '';
  if ($photo_id){
    $photo_img = wp_get_attachment_image($photo_id,'thumbnail',false,array('style'=>'width:90px;height:auto;border-radius:8px'));
  }
  echo '<style>.fasp-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fasp-grid p{margin:0 0 8px}</style>';
  echo '<div class="fasp-grid">';
  echo '<p style="grid-column:1/-1"><label><strong>Role / Title</strong><br><input class="widefat" name="fasp_coach_role" value="'.esc_attr($role).'"></label></p>';
  echo '<p style="grid-column:1/-1"><label><strong>Short Introduction</strong><br><textarea class="large-text code" rows="3" name="fasp_coach_intro">'.esc_textarea($intro).'</textarea></label></p>';
  echo '<p><label><strong>Headshot (attachment ID)</strong><br><input type="number" class="small-text" name="fasp_coach_photo_id" value="'.intval($photo_id).'"></label></p>';
  if ($photo_img){
    echo '<p>'.$photo_img.'</p>';
  }
  echo '<p><em>Tip: Set a Featured Image if you prefer; headshot ID overrides when provided.</em></p>';
  echo '</div>';
}

function fasp_coach_links_box($post){
  $is_live  = intval(get_post_meta($post->ID,'_fasp_coach_live', true));
  $live_url = get_post_meta($post->ID,'_fasp_coach_live_url',true);
  $tuts_url = get_post_meta($post->ID,'_fasp_coach_tuts_url',true);
  $video    = get_post_meta($post->ID,'_fasp_coach_video_url',true);
  $email    = get_post_meta($post->ID,'_fasp_coach_email',true);
  $phone    = get_post_meta($post->ID,'_fasp_coach_phone',true);
  $whatsapp = get_post_meta($post->ID,'_fasp_coach_whatsapp',true);
  $telegram = get_post_meta($post->ID,'_fasp_coach_telegram',true);
  $calendly = get_post_meta($post->ID,'_fasp_coach_calendly',true);
  $twitter  = get_post_meta($post->ID,'_fasp_coach_twitter',true);
  $linkedin = get_post_meta($post->ID,'_fasp_coach_linkedin',true);
  $youtube  = get_post_meta($post->ID,'_fasp_coach_youtube',true);
  $facebook = get_post_meta($post->ID,'_fasp_coach_facebook',true);
  $live_checked = $is_live ? ' checked="checked"' : '';
  $aff1_label = get_post_meta($post->ID,'_fasp_coach_aff1_label',true);
  $aff1_url   = get_post_meta($post->ID,'_fasp_coach_aff1_url',true);
  $aff2_label = get_post_meta($post->ID,'_fasp_coach_aff2_label',true);
  $aff2_url   = get_post_meta($post->ID,'_fasp_coach_aff2_url',true);
  $aff3_label = get_post_meta($post->ID,'_fasp_coach_aff3_label',true);
  $aff3_url   = get_post_meta($post->ID,'_fasp_coach_aff3_url',true);

  echo '<style>.fasp-grid2{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.fasp-grid2 p{margin:0 0 8px}</style>';
  echo '<div class="fasp-grid2">';
  echo '<p><label><strong>Live Coach?</strong> <input type="checkbox" name="fasp_coach_live" value="1"'.$live_checked.'></label></p>';
  echo '<p><label><strong>Live URL</strong><br><input class="widefat" name="fasp_coach_live_url" value="'.esc_attr($live_url).'"></label></p>';
  echo '<p><label><strong>Tutorials URL</strong><br><input class="widefat" name="fasp_coach_tuts_url" value="'.esc_attr($tuts_url).'"></label></p>';
  echo '<p><label><strong>Intro Video URL</strong><br><input class="widefat" name="fasp_coach_video_url" value="'.esc_attr($video).'"></label></p>';
  echo '<p style="grid-column:1/-1"><strong>Affiliate Links (up to 3)</strong></p>';
  echo '<p><label>Label #1<br><input class="widefat" name="fasp_coach_aff1_label" value="'.esc_attr($aff1_label).'"></label></p>';
  echo '<p><label>URL #1<br><input class="widefat" name="fasp_coach_aff1_url" value="'.esc_attr($aff1_url).'"></label></p>';
  echo '<p><label>Label #2<br><input class="widefat" name="fasp_coach_aff2_label" value="'.esc_attr($aff2_label).'"></label></p>';
  echo '<p><label>URL #2<br><input class="widefat" name="fasp_coach_aff2_url" value="'.esc_attr($aff2_url).'"></label></p>';
  echo '<p><label>Label #3<br><input class="widefat" name="fasp_coach_aff3_label" value="'.esc_attr($aff3_label).'"></label></p>';
  echo '<p><label>URL #3<br><input class="widefat" name="fasp_coach_aff3_url" value="'.esc_attr($aff3_url).'"></label></p>';
  echo '<p style="grid-column:1/-1"><strong>Contact</strong></p>';
  echo '<p><label>Email<br><input class="widefat" name="fasp_coach_email" value="'.esc_attr($email).'"></label></p>';
  echo '<p><label>Phone<br><input class="widefat" name="fasp_coach_phone" value="'.esc_attr($phone).'"></label></p>';
  echo '<p><label>WhatsApp<br><input class="widefat" name="fasp_coach_whatsapp" value="'.esc_attr($whatsapp).'"></label></p>';
  echo '<p><label>Telegram<br><input class="widefat" name="fasp_coach_telegram" value="'.esc_attr($telegram).'"></label></p>';
  echo '<p><label>Calendly / Booking URL<br><input class="widefat" name="fasp_coach_calendly" value="'.esc_attr($calendly).'"></label></p>';
  echo '<p style="grid-column:1/-1"><strong>Socials</strong></p>';
  echo '<p><label>Twitter/X URL<br><input class="widefat" name="fasp_coach_twitter" value="'.esc_attr($twitter).'"></label></p>';
  echo '<p><label>LinkedIn URL<br><input class="widefat" name="fasp_coach_linkedin" value="'.esc_attr($linkedin).'"></label></p>';
  echo '<p><label>YouTube URL<br><input class="widefat" name="fasp_coach_youtube" value="'.esc_attr($youtube).'"></label></p>';
  echo '<p><label>Facebook URL<br><input class="widefat" name="fasp_coach_facebook" value="'.esc_attr($facebook).'"></label></p>';
  echo '</div>';
}

function fasp_coach_offer_box($post){
  $cta_label = get_post_meta($post->ID,'_fasp_coach_cta_label',true);
  $cta_url   = get_post_meta($post->ID,'_fasp_coach_cta_url',true);
  wp_nonce_field('fasp_coach_offer_save','fasp_coach_offer_nonce');
  echo '<p><label>CTA Label<br><input class="widefat" name="fasp_coach_cta_label" value="'.esc_attr($cta_label).'"></label></p>';
  echo '<p><label>CTA URL<br><input class="widefat" name="fasp_coach_cta_url" value="'.esc_attr($cta_url).'"></label></p>';
}

/* Save handler */
function fasp_save_coach_post($post_id){
  if (!isset($_POST['fasp_coach_nonce']) || !wp_verify_nonce($_POST['fasp_coach_nonce'],'fasp_coach_save')) return;
  $map = array(
    '_fasp_coach_role' => 'sanitize_text_field', '_fasp_coach_intro' => 'sanitize_textarea_field',
    '_fasp_coach_photo_id' => 'intval', '_fasp_coach_live' => 'intval', '_fasp_coach_live_url' => 'esc_url_raw',
    '_fasp_coach_tuts_url' => 'esc_url_raw', '_fasp_coach_video_url' => 'esc_url_raw',
    '_fasp_coach_email' => 'sanitize_email', '_fasp_coach_phone' => 'sanitize_text_field', '_fasp_coach_whatsapp' => 'sanitize_text_field',
    '_fasp_coach_telegram' => 'sanitize_text_field', '_fasp_coach_calendly' => 'esc_url_raw',
    '_fasp_coach_twitter' => 'esc_url_raw', '_fasp_coach_linkedin' => 'esc_url_raw', '_fasp_coach_youtube' => 'esc_url_raw', '_fasp_coach_facebook' => 'esc_url_raw',
    '_fasp_coach_cta_label' => 'sanitize_text_field', '_fasp_coach_cta_url' => 'esc_url_raw'
  );
  foreach ($map as $key=>$fn){
    $name = ltrim($key,'_');
    if (isset($_POST[$name])) update_post_meta($post_id, $key, call_user_func($fn, $_POST[$name]));
    elseif (isset($_POST[$key])) update_post_meta($post_id, $key, call_user_func($fn, $_POST[$key]));
  }
  /* Explicit affiliate fields to avoid any dynamic concatenation */
  if (isset($_POST['fasp_coach_aff1_label'])) update_post_meta($post_id,'_fasp_coach_aff1_label', sanitize_text_field($_POST['fasp_coach_aff1_label'])); else update_post_meta($post_id,'_fasp_coach_aff1_label','');
  if (isset($_POST['fasp_coach_aff1_url']))   update_post_meta($post_id,'_fasp_coach_aff1_url',   esc_url_raw($_POST['fasp_coach_aff1_url'])); else update_post_meta($post_id,'_fasp_coach_aff1_url','');
  if (isset($_POST['fasp_coach_aff2_label'])) update_post_meta($post_id,'_fasp_coach_aff2_label', sanitize_text_field($_POST['fasp_coach_aff2_label'])); else update_post_meta($post_id,'_fasp_coach_aff2_label','');
  if (isset($_POST['fasp_coach_aff2_url']))   update_post_meta($post_id,'_fasp_coach_aff2_url',   esc_url_raw($_POST['fasp_coach_aff2_url'])); else update_post_meta($post_id,'_fasp_coach_aff2_url','');
  if (isset($_POST['fasp_coach_aff3_label'])) update_post_meta($post_id,'_fasp_coach_aff3_label', sanitize_text_field($_POST['fasp_coach_aff3_label'])); else update_post_meta($post_id,'_fasp_coach_aff3_label','');
  if (isset($_POST['fasp_coach_aff3_url']))   update_post_meta($post_id,'_fasp_coach_aff3_url',   esc_url_raw($_POST['fasp_coach_aff3_url'])); else update_post_meta($post_id,'_fasp_coach_aff3_url','');
}
add_action('save_post_fasp_coach_event','fasp_save_coach_post');

/* Admin columns */
function fasp_coach_columns($cols){
  $cols['role'] = 'Role';
  $cols['live'] = 'Live';
  $cols['tuts'] = 'Tutorials';
  return $cols;
}
add_filter('manage_fasp_coach_event_posts_columns','fasp_coach_columns');

function fasp_coach_columns_content($col,$post_id){
  if ($col==='role') echo esc_html(get_post_meta($post_id,'_fasp_coach_role',true));
  if ($col==='live') echo get_post_meta($post_id,'_fasp_coach_live',true) ? 'Yes' : 'No';
  if ($col==='tuts') { $u=get_post_meta($post_id,'_fasp_coach_tuts_url',true); if($u) echo '<a href="'.esc_url($u).'" target="_blank">Open</a>'; else echo '-'; }
}
add_action('manage_fasp_coach_event_posts_custom_column','fasp_coach_columns_content',10,2);
