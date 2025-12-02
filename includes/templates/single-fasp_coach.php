<?php
if (!defined('ABSPATH')) { exit; }
get_header();
the_post();
$ID = get_the_ID();
$name = get_the_title();
$role = get_post_meta($ID,'_fasp_coach_role',true);
$tagline = get_post_meta($ID,'_fasp_coach_tagline',true);
$live = get_post_meta($ID,'_fasp_coach_live',true);
$aff = get_post_meta($ID,'_fasp_coach_affiliate',true);
$video = get_post_meta($ID,'_fasp_coach_video',true);
$tz = get_post_meta($ID,'_fasp_coach_timezone',true);
$lang = get_post_meta($ID,'_fasp_coach_languages',true);
$years = get_post_meta($ID,'_fasp_coach_years',true);
$rate = get_post_meta($ID,'_fasp_coach_rate',true);
$wa = get_post_meta($ID,'_fasp_coach_whatsapp',true);
$tg = get_post_meta($ID,'_fasp_coach_telegram',true);
$tw = get_post_meta($ID,'_fasp_coach_twitter',true);
$img = get_the_post_thumbnail_url($ID,'large'); if(!$img) $img = 'https://placehold.co/800x450?text=Coach';
?>
<style>
.fasp-profile{max-width:1040px;margin:40px auto;padding:0 16px;}
.fasp-profile .hero{display:grid;grid-template-columns:180px 1fr;gap:20px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;}
.fasp-profile .hero img{width:180px;height:180px;object-fit:cover;border-radius:16px;border:1px solid #e5e7eb;}
.fasp-profile h1{margin:0 0 6px;font-size:28px;line-height:1.2;}
.fasp-profile .muted{color:#6b7280;}
.fasp-profile .pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
.fasp-profile .pill{background:#eef2ff;border:1px solid #e5e7eb;border-radius:999px;padding:4px 10px;color:#111827;font-size:12px;}
.fasp-profile .cta{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}
.fasp-profile .btn{display:inline-block;background:#22c55e;color:#063;border:1px solid #16a34a;border-radius:10px;padding:10px 14px;text-decoration:none;font-weight:700;}
.fasp-profile .grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:16px;}
.fasp-profile .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;}
.fasp-profile iframe, .fasp-profile video{width:100%;max-width:100%;border-radius:12px;border:1px solid #e5e7eb;}
</style>
<div class="fasp-profile">
  <div class="hero">
    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>">
    <div>
      <h1><?php echo esc_html($name); ?></h1>
      <?php if($role): ?><div class="muted"><?php echo esc_html($role); ?></div><?php endif; ?>
      <?php if($tagline): ?><div class="muted" style="margin-top:6px;"><?php echo esc_html($tagline); ?></div><?php endif; ?>
      <div class="pills">
        <?php if($tz): ?><span class="pill">Timezone: <?php echo esc_html($tz); ?></span><?php endif; ?>
        <?php if($lang): ?><span class="pill">Languages: <?php echo esc_html($lang); ?></span><?php endif; ?>
        <?php if($years): ?><span class="pill"><?php echo intval($years); ?> yrs exp.</span><?php endif; ?>
        <?php if($rate): ?><span class="pill">Rate: <?php echo esc_html($rate); ?></span><?php endif; ?>
      </div>
      <div class="cta">
        <?php if($live): ?><a class="btn" href="<?php echo esc_url($live); ?>" target="_blank" rel="nofollow noopener">Book Session</a><?php endif; ?>
        <?php if($aff): ?><a class="btn" href="<?php echo esc_url($aff); ?>" target="_blank" rel="nofollow noopener">Affiliate Link</a><?php endif; ?>
        <?php if($wa): ?><a class="btn" href="<?php echo esc_url($wa); ?>" target="_blank" rel="nofollow noopener">WhatsApp</a><?php endif; ?>
        <?php if($tg): ?><a class="btn" href="<?php echo esc_url($tg); ?>" target="_blank" rel="nofollow noopener">Telegram</a><?php endif; ?>
        <?php if($tw): ?><a class="btn" href="<?php echo esc_url($tw); ?>" target="_blank" rel="nofollow noopener">Twitter</a><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h2>About</h2>
      <div class="muted">The coach’s bio is below.</div>
      <div style="margin-top:10px;"><?php the_content(); ?></div>
      <?php if($video): ?>
      <div style="margin-top:16px;">
        <h3>Intro Video</h3>
        <div class="muted">A short intro from <?php echo esc_html($name); ?>.</div>
        <div style="margin-top:8px;">
          <?php echo wp_oembed_get($video) ?: '<a href="'.esc_url($video).'" target="_blank" rel="nofollow">Watch video</a>'; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="card">
      <h2>Quick Info</h2>
      <ul style="list-style:none;padding-left:0;margin:0;">
        <?php if($role): ?><li><strong>Role:</strong> <?php echo esc_html($role); ?></li><?php endif; ?>
        <?php if($tz): ?><li><strong>Timezone:</strong> <?php echo esc_html($tz); ?></li><?php endif; ?>
        <?php if($lang): ?><li><strong>Languages:</strong> <?php echo esc_html($lang); ?></li><?php endif; ?>
        <?php if($years): ?><li><strong>Experience:</strong> <?php echo intval($years); ?> years</li><?php endif; ?>
        <?php if($rate): ?><li><strong>Rate:</strong> <?php echo esc_html($rate); ?></li><?php endif; ?>
        <?php if($aff): ?><li><strong>Affiliate:</strong> <a href="<?php echo esc_url($aff); ?>" target="_blank" rel="nofollow noopener">Open</a></li><?php endif; ?>
        <?php if($live): ?><li><strong>Booking:</strong> <a href="<?php echo esc_url($live); ?>" target="_blank" rel="nofollow noopener">Open</a></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php get_footer(); ?>
