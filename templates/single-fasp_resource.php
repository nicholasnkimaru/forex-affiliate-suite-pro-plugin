<?php
if (!defined('ABSPATH')) exit;
wp_enqueue_style('fasp-front'); get_header();
$id=get_the_ID();
$gallery=array_filter(array_map('intval', explode(',', get_post_meta($id,'_fasp_gallery_ids',true))));
$gate_name=get_post_meta($id,'_fasp_gate_name',true);
$gate_logo=(int)get_post_meta($id,'_fasp_gate_logo',true);
$adA=get_post_meta($id,'_fasp_ad_above',true);
$adB=get_post_meta($id,'_fasp_ad_below',true);
?>
<div class="fasp-resource">
  <section class="hero">
    <h1><?php the_title(); ?></h1>
    <p class="meta"><?php echo esc_html(get_the_date()); ?></p>
    <?php if ($gate_logo) echo wp_get_attachment_image($gate_logo,'thumbnail', false, ['class'=>'gate-logo','loading'=>'lazy','decoding'=>'async']); ?>
  </section>

  <?php if ($gallery): ?>
  <section class="gallery-strip">
    <div class="strip">
      <?php foreach ($gallery as $gid) echo wp_get_attachment_image($gid,'large', false, ['loading'=>'lazy','decoding'=>'async']); ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($adA): ?><div class="ad ad-above"><?php echo $adA; ?></div><?php endif; ?>

  <section class="content">
    <?php while(have_posts()){ the_post(); the_content(); } ?>
  </section>

  <?php if ($adB): ?><div class="ad ad-below"><?php echo $adB; ?></div><?php endif; ?>
</div>
<?php get_footer(); ?>
