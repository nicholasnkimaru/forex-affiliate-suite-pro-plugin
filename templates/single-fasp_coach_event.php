<?php if (!defined('ABSPATH')) exit; get_header(); the_post(); ?>
<main class="fasp-coach">
  <style>
    .coach-wrap{max-width:920px;margin:0 auto;padding:20px}
    .coach-hero{display:grid;grid-template-columns:100px 1fr auto;gap:16px;align-items:center;margin-bottom:16px;border-bottom:1px solid rgba(0,0,0,.06);padding-bottom:16px}
    .coach-hero img{width:100px;height:100px;object-fit:cover;border-radius:12px}
    .coach-name{font-size:1.8rem;margin:0}
    .coach-role{color:#666;margin-top:4px}
    .coach-live{background:#e11d48;color:#fff;border-radius:8px;padding:6px 10px;font-weight:600}
    .coach-actions a{margin-right:8px}
    .coach-section{margin:18px 0}
    .coach-aff a{display:inline-block;margin:4px 8px 4px 0}
    .coach-video{margin:12px 0}
  </style>
  <div class="coach-wrap">
    <?php
      $id = get_the_ID();
      $photo_id = (int)get_post_meta($id,'_fasp_coach_photo_id',true);
      $img = $photo_id ? wp_get_attachment_image($photo_id,'medium',false,['loading'=>'lazy','decoding'=>'async']) : get_the_post_thumbnail($id, 'medium', ['loading'=>'lazy','decoding'=>'async']);
      $role = get_post_meta($id,'_fasp_coach_role',true);
      $intro = get_post_meta($id,'_fasp_coach_intro',true);
      $live  = (int)get_post_meta($id,'_fasp_coach_live',true);
      $live_url = get_post_meta($id,'_fasp_coach_live_url',true);
      $tuts = get_post_meta($id,'_fasp_coach_tuts_url',true);
      $vid  = get_post_meta($id,'_fasp_coach_video_url',true);
      $cta_label = get_post_meta($id,'_fasp_coach_cta_label',true) ?: 'Book a Session';
      $cta_url   = get_post_meta($id,'_fasp_coach_cta_url',true);
      $calendly  = get_post_meta($id,'_fasp_coach_calendly',true);
    ?>
    <header class="coach-hero">
      <div class="coach-photo"><?php echo $img ?: ''; ?></div>
      <div>
        <h1 class="coach-name"><?php the_title(); ?></h1>
        <?php if ($role){ echo '<div class="coach-role">'.esc_html($role).'</div>'; } ?>
      </div>
      <div class="coach-actions">
        <?php if ($live && $live_url) echo '<a class="coach-live" href="'.esc_url($live_url).'" target="_blank">LIVE</a>'; ?>
      </div>
    </header>

    <?php if ($intro){ echo '<div class="coach-section"><p>'.esc_html($intro).'</p></div>'; } ?>

    <div class="coach-section"><?php the_content(); ?></div>

    <?php
      $affs=[];
      for($i=1;$i<=3;$i++){
        $lab = get_post_meta($id,"_fasp_coach_aff{$i}_label",true);
        $url = get_post_meta($id,"_fasp_coach_aff{$i}_url",true);
        if ($lab && $url) $affs[]=['l'=>$lab,'u'=>$url];
      }
      if ($tuts || $vid || $affs || $cta_url || $calendly){
        echo '<div class="coach-section">';
        if ($tuts) echo '<a class="button" href="'.esc_url($tuts).'" target="_blank">Tutorials</a> ';
        if ($vid) echo '<a class="button" href="#intro-video">Intro Video</a> ';
        if ($cta_url) echo '<a class="button" href="'.esc_url($cta_url).'" target="_blank">'.esc_html($cta_label).'</a> ';
        if ($calendly) echo '<a class="button" href="'.esc_url($calendly).'" target="_blank">Book on Calendly</a> ';
        echo '</div>';
      }
      if ($affs){
        echo '<div class="coach-section coach-aff"><h3>Recommended Platforms</h3>';
        foreach ($affs as $a){ echo '<a class="button" rel="nofollow noopener" target="_blank" href="'.esc_url($a['u']).'">'.esc_html($a['l']).'</a> '; }
        echo '</div>';
      }
    ?>

    <?php if ($vid){
      echo '<div class="coach-section coach-video" id="intro-video"><h3>Introduction Video</h3>';
      $embed = function_exists('wp_oembed_get') ? wp_oembed_get($vid) : '';
      if (!$embed && preg_match('#\.(mp4|webm|ogg)$#i', $vid)){
        $embed = '<video controls playsinline style="max-width:100%"><source src="'.esc_url($vid).'"></video>';
      }
      echo $embed ?: '<p><a href="'.esc_url($vid).'" target="_blank">Watch video</a></p>';
      echo '</div>';
    } ?>
  </div>
</main>
<?php get_footer(); ?>
