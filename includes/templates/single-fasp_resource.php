<?php
if (!defined('ABSPATH')) exit;
get_header(); the_post();
$ID = get_the_ID();
$title = get_the_title();
$thumb = get_the_post_thumbnail_url($ID,'large');
$thumb = $thumb ?: 'https://placehold.co/1200x630?text=Resource';
$primary_slug = 'deriv';
$verified = is_user_logged_in() && (get_user_meta(get_current_user_id(), '_fasp_verified_'.$primary_slug, true) === '1');
?>
<style>
.fasp-res-wrap{max-width:1100px;margin:0 auto;padding:16px;}
.fasp-sticky{position:sticky;top:12px;z-index:10;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:flex;gap:12px;align-items:center;}
.fasp-sticky .btn{background:#22c55e;color:#063;border:1px solid #16a34a;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700;}
.fasp-hero{border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;margin:8px 0;}
.fasp-hero .img{aspect-ratio:16/9;background:#f3f4f6 url('<?php echo esc_url($thumb); ?>') center/cover no-repeat;}
.fasp-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:16px;}
.fasp-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;}
.fasp-muted{color:#6b7280;}
.fasp-steps li{margin:6px 0;}
</style>
<div class="fasp-res-wrap">
  <div class="fasp-sticky">
    <strong><?php echo esc_html($title); ?></strong>
    <a class="btn" href="<?php echo esc_url(home_url('/fasp-go/'.$primary_slug.'?utm_source=fb&utm_campaign=resource&utm_medium=sticky')); ?>">Create Deriv account</a>
    <?php if($verified): ?><span class="fasp-muted">Verified ✓</span><?php else: ?><span class="fasp-muted">Step 1 of 2</span><?php endif; ?>
  </div>
  <div class="fasp-hero"><div class="img"></div></div>

  <div class="fasp-grid">
    <article class="fasp-card">
      <h1><?php echo esc_html($title); ?></h1>
      <p class="fasp-muted">Get the resource and start right.</p>
      <div class="content"><?php the_content(); ?></div>

      <hr>
      <h2>How to get this resource</h2>
      <ol class="fasp-steps">
        <li><strong>Create your Deriv account</strong> — click the button below.</li>
        <li>Return here; your download will unlock automatically.</li>
      </ol>
      <p>
        <a class="btn" href="<?php echo esc_url(home_url('/fasp-go/'.$primary_slug.'?dest=signup&utm_source=fb&utm_campaign=resource')); ?>">Create Deriv account</a>
      </p>

      <?php if($verified): ?>
        <div class="fasp-card" style="margin-top:12px;">
          <h3>Your download</h3>
          <?php
            $atts = get_attached_media('', $ID);
            if ($atts){
              echo '<ul>';
              foreach($atts as $a){
                $url = wp_get_attachment_url($a->ID);
                echo '<li><a href="'.esc_url($url).'" target="_blank" rel="noopener">Download '.esc_html($a->post_title).'</a></li>';
              }
              echo '</ul>';
            } else {
              echo '<p class="fasp-muted">No files attached yet. Edit this resource and upload files in the Media box.</p>';
            }
          ?>
        </div>
      <?php else: ?>
        <div class="fasp-card" style="margin-top:12px;">
          <h3>Unlock after sign-up</h3>
          <p class="fasp-muted">Once you complete registration and come back, this section will show your downloads automatically.</p>
        </div>
      <?php endif; ?>
    </article>
    <aside class="fasp-card">
      <h3>Get coaching</h3>
      <p class="fasp-muted">Guided onboarding shortens the learning curve.</p>
      <?php echo do_shortcode('[fasp_coaches per_page="6"]'); ?>
    </aside>
  </div>
</div>
<?php get_footer(); ?>