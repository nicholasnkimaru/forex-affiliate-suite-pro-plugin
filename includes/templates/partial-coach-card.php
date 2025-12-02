<?php
$ID = get_the_ID();
$name = get_the_title();
$role = get_post_meta($ID,'_fasp_coach_role',true);
$tagline = get_post_meta($ID,'_fasp_coach_tagline',true);
$live = get_post_meta($ID,'_fasp_coach_live',true);
$img = get_the_post_thumbnail_url($ID,'medium'); if(!$img) $img = 'https://placehold.co/600x400?text=Coach';
$link = get_permalink($ID);
?>
<article class="fasp-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">
  <a href="<?php echo esc_url($link); ?>" style="text-decoration:none;display:block;">
    <div style="aspect-ratio:4/3;background:#f3f4f6 url('<?php echo esc_url($img); ?>') center/cover no-repeat;"></div>
    <div style="padding:12px;">
      <h3 style="margin:0 0 4px;color:#111827;"><?php echo esc_html($name); ?></h3>
      <?php if($role): ?><div style="color:#6b7280;font-size:14px;"><?php echo esc_html($role); ?></div><?php endif; ?>
      <?php if($tagline): ?><div style="color:#6b7280;font-size:13px;margin-top:6px;"><?php echo esc_html($tagline); ?></div><?php endif; ?>
      <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
        <span class="fasp-pill">Profile</span>
        <?php if($live): ?><span class="fasp-pill">Live coaching</span><?php endif; ?>
      </div>
    </div>
  </a>
</article>
