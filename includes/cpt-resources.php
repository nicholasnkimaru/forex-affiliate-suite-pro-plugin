<?php
if (!defined('ABSPATH')) { exit; }
function fasp_register_cpt_resource(){
    register_post_type('fasp_resource',[
        'labels'=>['name'=>'Forex Resources','singular_name'=>'Forex Resource','menu_name'=>'Forex Resources'],
        'public'=>true,'show_in_menu'=>false,'supports'=>['title','editor','excerpt','thumbnail','revisions'],'has_archive'=>true,'rewrite'=>['slug'=>'resources'],'show_in_rest'=>true
    ]);
}
add_action('init','fasp_register_cpt_resource');
function fasp_resource_meta_boxes(){ add_meta_box('fasp_resource_meta','Resource Settings','fasp_resource_meta_cb','fasp_resource','side','default'); }
add_action('add_meta_boxes','fasp_resource_meta_boxes');
function fasp_resource_meta_cb($post){
    $required = get_post_meta($post->ID,'_fasp_required_platform',true);
    $ext = get_post_meta($post->ID,'_fasp_download_url',true);
    $showpill = get_post_meta($post->ID,'_fasp_show_platform_pill',true);
    wp_nonce_field('fasp_resource_meta','fasp_resource_meta_nonce'); ?>
    <p><label><strong>Required Platform</strong><br><select name="fasp_required_platform">
        <?php $plats = function_exists('fasp_get_platforms')? fasp_get_platforms():[]; foreach($plats as $slug=>$pl){ ?>
            <option value="<?php echo esc_attr($slug); ?>" <?php selected($required,$slug); ?>><?php echo esc_html($pl['name']); ?></option>
        <?php } ?>
    </select></label></p>
    <p><label><strong>External Download URL</strong><br><input type="url" class="widefat" name="fasp_download_url" value="<?php echo esc_attr($ext); ?>"></label></p>
    <p><label><input type="checkbox" name="fasp_show_platform_pill" value="1" <?php checked($showpill,'1'); ?>> Show platform name pill</label></p>
<?php }
function fasp_resource_meta_save($post_id){
    if (!isset($_POST['fasp_resource_meta_nonce']) || !wp_verify_nonce($_POST['fasp_resource_meta_nonce'],'fasp_resource_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_fasp_required_platform',sanitize_text_field($_POST['fasp_required_platform'] ?? ''));
    update_post_meta($post_id,'_fasp_download_url',esc_url_raw($_POST['fasp_download_url'] ?? ''));
    update_post_meta($post_id,'_fasp_show_platform_pill',isset($_POST['fasp_show_platform_pill'])?'1':'0');
}
add_action('save_post_fasp_resource','fasp_resource_meta_save');

// === Gallery Uploader (multi-image) ===
if (!function_exists('fasp_resource_gallery_meta_boxes')){
function fasp_resource_gallery_meta_boxes(){ add_meta_box('fasp_resource_gallery','Gallery Images','fasp_resource_gallery_meta','fasp_resource','side','default'); }
add_action('add_meta_boxes','fasp_resource_gallery_meta_boxes');
function fasp_resource_gallery_meta($post){
    $ids = get_post_meta($post->ID,'_fasp_gallery_ids',true);
    if (!is_array($ids)) { $ids = array_filter(array_map('intval', explode(',', (string)$ids))); }
    wp_nonce_field('fasp_resource_gallery','fasp_resource_gallery_nonce'); ?>
    <div id="fasp-gallery-wrap">
        <div id="fasp-gallery-thumbs" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
            <?php foreach($ids as $id): $src = wp_get_attachment_image_src($id,'thumbnail'); if($src): ?>
                <div class="fasp-thumb" data-id="<?php echo intval($id); ?>" style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;position:relative;">
                    <img src="<?php echo esc_url($src[0]); ?>" style="width:70px;height:70px;object-fit:cover;">
                    <a href="#" class="fasp-remove" style="position:absolute;top:2px;right:4px;text-decoration:none;">✕</a>
                </div>
            <?php endif; endforeach; ?>
        </div>
        <input type="hidden" id="fasp_gallery_ids" name="fasp_gallery_ids" value="<?php echo esc_attr(implode(',', $ids)); ?>">
        <p><button type="button" class="button" id="fasp-gallery-add">Add images</button></p>
    </div>
    <script>
    jQuery(function($){
        var frame;
        $('#fasp-gallery-add').on('click', function(e){
            e.preventDefault();
            if (frame){ frame.open(); return; }
            frame = wp.media({ title: 'Select Images', multiple: true, library:{type:'image'} });
            frame.on('select', function(){
                var sel = frame.state().get('selection');
                var ids = $('#fasp_gallery_ids').val().split(',').filter(Boolean);
                sel.each(function(att){
                    ids.push(att.get('id'));
                    $('#fasp-gallery-thumbs').append('<div class="fasp-thumb" data-id="'+att.get('id')+'" style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;position:relative;"><img src="'+att.get('url')+'" style="width:70px;height:70px;object-fit:cover;"><a href="#" class="fasp-remove" style="position:absolute;top:2px;right:4px;">✕</a></div>');
                });
                $('#fasp_gallery_ids').val(ids.join(','));
            });
            frame.open();
        });
        $('#fasp-gallery-thumbs').on('click','.fasp-remove', function(e){
            e.preventDefault();
            var wrap = $(this).closest('.fasp-thumb'), id = wrap.data('id')+'';
            wrap.remove();
            var ids = $('#fasp_gallery_ids').val().split(',').filter(Boolean).filter(function(x){return x!==id;});
            $('#fasp_gallery_ids').val(ids.join(','));
        });
    });
    </script>
    <?php
}
add_action('save_post_fasp_resource', function($post_id){
    if (!isset($_POST['fasp_resource_gallery_nonce']) || !wp_verify_nonce($_POST['fasp_resource_gallery_nonce'],'fasp_resource_gallery')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;
    $csv = sanitize_text_field($_POST['fasp_gallery_ids'] ?? '');
    $ids = array_filter(array_map('intval', explode(',', $csv)));
    update_post_meta($post_id,'_fasp_gallery_ids',$ids);
});
add_action('admin_enqueue_scripts', function($hook){ if (($hook=='post.php' or $hook=='post-new.php')){ wp_enqueue_media(); } });
}
