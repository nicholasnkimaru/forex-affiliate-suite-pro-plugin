<?php
if (!defined('ABSPATH')) exit;
// Adds name (via title) + profile photo panel (non-destructive)
if (!function_exists('fasp_coach_identity_meta')){
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_coach_identity','Coach Identity','fasp_coach_identity_meta','fasp_coach', 'normal','high','high');
});
function fasp_coach_identity_meta($post){
  $photo_id  = (int) get_post_meta($post->ID,'_fasp_coach_photo_id',true);
  $photo_url = (string) get_post_meta($post->ID,'_fasp_coach_photo_url',true);
  $src = '';
  if ($photo_id){ $s = wp_get_attachment_image_src($photo_id,'thumbnail'); if ($s){ $src=$s[0]; } }
  if (!$src && $photo_url) $src = esc_url($photo_url);
  wp_nonce_field('fasp_coach_identity','fasp_coach_identity_nonce');
  ?>
  <p><strong>Name</strong><br><em>Use the Title field above.</em></p>
  <p><strong>Profile Photo</strong></p>
  <div id="fasp_coach_photo_preview" style="margin-bottom:8px;">
    <?php if($src): ?><img src="<?php echo esc_url($src); ?>" style="width:120px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:8px;"><?php endif; ?>
  </div>
  <input type="hidden" id="fasp_coach_photo_id" name="fasp_coach_photo_id" value="<?php echo esc_attr($photo_id); ?>">
  <p><button class="button" id="fasp_coach_select_photo">Select/Upload</button></p>
  <p><label>Or Photo URL<br><input type="url" class="widefat" name="fasp_coach_photo_url" value="<?php echo esc_attr($photo_url); ?>"></label></p>
  <script>
  jQuery(function($){
    let frame;
    $('#fasp_coach_select_photo').on('click', function(e){
      e.preventDefault();
      if (frame){ frame.open(); return; }
      frame = wp.media({ title:'Select Coach Photo', multiple:false, library:{type:'image'} });
      frame.on('select', function(){
        const att = frame.state().get('selection').first().toJSON();
        $('#fasp_coach_photo_id').val(att.id);
        $('#fasp_coach_photo_preview').html('<img src="'+att.url+'" style="width:120px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:8px;">');
      });
      frame.open();
    });
  });
  </script>
  <?php
}
add_action('admin_enqueue_scripts', function($hook){
  if ($hook==='post.php' || $hook==='post-new.php'){ wp_enqueue_media(); }
});
add_action('save_post_fasp_coach', function($post_id){
  if (!isset($_POST['fasp_coach_identity_nonce']) || !wp_verify_nonce($_POST['fasp_coach_identity_nonce'],'fasp_coach_identity')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post',$post_id)) return;
  update_post_meta($post_id,'_fasp_coach_photo_id', intval($_POST['fasp_coach_photo_id'] ?? 0));
  update_post_meta($post_id,'_fasp_coach_photo_url', esc_url_raw($_POST['fasp_coach_photo_url'] ?? ''));
});
}
