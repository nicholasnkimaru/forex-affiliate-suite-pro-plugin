<?php
if (!defined('ABSPATH')) exit;

// A) Gallery meta (non-destructive)
if (!function_exists('fasp_res_gallery_meta')){
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_gallery','Gallery Images','fasp_res_gallery_meta','fasp_resource','side','default');
});
function fasp_res_gallery_meta($post){
  $ids = get_post_meta($post->ID,'_fasp_gallery_ids',true);
  if (!is_array($ids)) $ids = array_filter(array_map('intval', explode(',', (string)$ids)));
  wp_nonce_field('fasp_res_gallery','fasp_res_gallery_nonce'); ?>
  <div id="fasp-gallery-thumbs" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
    <?php foreach($ids as $id): if ($s = wp_get_attachment_image_src($id,'thumbnail')): ?>
      <div class="fasp-thumb" data-id="<?php echo (int)$id; ?>" style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;position:relative;">
        <img src="<?php echo esc_url($s[0]); ?>" style="width:70px;height:70px;object-fit:cover;">
        <a href="#" class="fasp-remove" style="position:absolute;top:2px;right:4px;text-decoration:none;">✕</a>
      </div>
    <?php endif; endforeach; ?>
  </div>
  <input type="hidden" id="fasp_gallery_ids" name="fasp_gallery_ids" value="<?php echo esc_attr(implode(',',$ids)); ?>">
  <p><button type="button" class="button" id="fasp-gallery-add">Add images</button></p>
  <script>
  jQuery(function($){
    let frame; 
    $('#fasp-gallery-add').on('click', function(e){
      e.preventDefault();
      if (frame){ frame.open(); return; }
      frame = wp.media({ title:'Select Images', multiple:true, library:{type:'image'} });
      frame.on('select', function(){
        const sel = frame.state().get('selection'); 
        const ids = $('#fasp_gallery_ids').val().split(',').filter(Boolean);
        sel.each(function(att){
          const a = att.toJSON();
          ids.push(a.id);
          $('#fasp-gallery-thumbs').append(
            '<div class="fasp-thumb" data-id="'+a.id+'" style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;position:relative;">'
            +'<img src="'+a.url+'" style="width:70px;height:70px;object-fit:cover;"><a href="#" class="fasp-remove" style="position:absolute;top:2px;right:4px;">✕</a></div>'
          );
        });
        $('#fasp_gallery_ids').val(ids.join(','));
      });
      frame.open();
    });
    $('#fasp-gallery-thumbs').on('click','.fasp-remove', function(e){
      e.preventDefault();
      const box=$(this).closest('.fasp-thumb'), id=String(box.data('id'));
      box.remove();
      const ids=$('#fasp_gallery_ids').val().split(',').filter(Boolean).filter(x=>x!==id);
      $('#fasp_gallery_ids').val(ids.join(','));
    });
  });
  </script>
  <?php
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_gallery_nonce']) || !wp_verify_nonce($_POST['fasp_res_gallery_nonce'],'fasp_res_gallery')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post',$post_id)) return;
  $ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['fasp_gallery_ids'] ?? ''))));
  update_post_meta($post_id,'_fasp_gallery_ids',$ids);
});
}

// B) Landing/Gating meta + shortcode
if (!function_exists('fasp_res_gate_meta')){
add_action('add_meta_boxes', function(){
  add_meta_box('fasp_res_gate','Landing & Gating','fasp_res_gate_meta','fasp_resource','normal','default');
});
function fasp_res_gate_meta($post){
  $gate = wp_parse_args((array) get_post_meta($post->ID,'_fasp_gate',true),[
    'enabled'=>'0','platform'=>'deriv','headline'=>'Unlock this resource',
    'sub'=>'Join via our affiliate link, then come back to download.',
    'cta_label'=>'Join Deriv','post_join_message'=>'Thanks! Reload to download.',
    'require_verify'=>'1','show_email_capture'=>'1'
  ]);
  wp_nonce_field('fasp_res_gate','fasp_res_gate_nonce'); ?>
  <p><label><input type="checkbox" name="fasp_gate[enabled]" value="1" <?php checked($gate['enabled'],'1'); ?>> Enable landing/gating for this resource</label></p>
  <p><label>Platform Slug<br><input name="fasp_gate[platform]" class="regular-text" value="<?php echo esc_attr($gate['platform']); ?>"></label></p>
  <p><label>Headline<br><input name="fasp_gate[headline]" class="regular-text" value="<?php echo esc_attr($gate['headline']); ?>"></label></p>
  <p><label>Subheading<br><input name="fasp_gate[sub]" class="regular-text" value="<?php echo esc_attr($gate['sub']); ?>"></label></p>
  <p><label>CTA Label<br><input name="fasp_gate[cta_label]" class="regular-text" value="<?php echo esc_attr($gate['cta_label']); ?>"></label></p>
  <p><label>Post-Join Message<br><input name="fasp_gate[post_join_message]" class="regular-text" value="<?php echo esc_attr($gate['post_join_message']); ?>"></label></p>
  <p><label><input type="checkbox" name="fasp_gate[require_verify]" value="1" <?php checked($gate['require_verify'],'1'); ?>> Require verification on the platform before download</label></p>
  <p><label><input type="checkbox" name="fasp_gate[show_email_capture]" value="1" <?php checked($gate['show_email_capture'],'1'); ?>> Show email capture box (use your form shortcode in content)</label></p>
  <p class="description">Adds a conversion-friendly pre-lander. Does not remove your existing fields.</p>
  <?php
}
add_action('save_post_fasp_resource', function($post_id){
  if (!isset($_POST['fasp_res_gate_nonce']) || !wp_verify_nonce($_POST['fasp_res_gate_nonce'],'fasp_res_gate')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post',$post_id)) return;
  $in = (array) ($_POST['fasp_gate'] ?? []);
  $out = [
    'enabled'  => empty($in['enabled']) ? '0':'1',
    'platform' => sanitize_title($in['platform'] ?? 'deriv'),
    'headline' => sanitize_text_field($in['headline'] ?? ''),
    'sub'      => sanitize_text_field($in['sub'] ?? ''),
    'cta_label'=> sanitize_text_field($in['cta_label'] ?? ''),
    'post_join_message'=> sanitize_text_field($in['post_join_message'] ?? ''),
    'require_verify'=> empty($in['require_verify']) ? '0':'1',
    'show_email_capture'=> empty($in['show_email_capture']) ? '0':'1',
  ];
  update_post_meta($post_id,'_fasp_gate',$out);
});

add_shortcode('fasp_resource_landing', function($atts){
  $a = shortcode_atts(['id'=>0],$atts,'fasp_resource_landing');
  $id = (int) $a['id']; if (!$id) return '';
  $gate = (array) get_post_meta($id,'_fasp_gate',true);
  if (empty($gate['enabled'])) return '<div class="fasp-card">'.esc_html(get_the_title($id)).'</div>';
  $slug = $gate['platform'] ?: 'deriv';
  $verified = get_user_meta(get_current_user_id(), '_fasp_verified_'.$slug, true)==='1';
  ob_start(); ?>
  <div class="fasp-card" style="max-width:880px;margin:20px auto;border:1px solid #e5e7eb;border-radius:16px;padding:18px;background:#fff;">
    <h2 style="margin:0 0 6px;"><?php echo esc_html($gate['headline']); ?></h2>
    <p class="fasp-muted" style="margin-top:0;"><?php echo esc_html($gate['sub']); ?></p>
    <?php if(!$verified): ?>
      <p><a class="button button-primary fasp-join" href="<?php echo esc_url(home_url('/fasp-go/'.$slug.'?utm_source=resource&utm_campaign='.rawurlencode(sanitize_title(get_the_title($id))))); ?>">
        <?php echo esc_html($gate['cta_label']); ?>
      </a></p>
      <?php if(!empty($gate['show_email_capture'])) echo do_shortcode('[mc4wp_form]'); ?>
    <?php else: ?>
      <p class="notice notice-success" style="padding:10px;border-radius:8px;"><?php echo esc_html($gate['post_join_message']); ?></p>
      <div><?php echo apply_filters('the_content', get_post($id)->post_content); ?></div>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
});
}
