<?php
/**
 * Template: Coaches Page
 * Meet our trading coaches and book sessions
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Get coaches from CPT
$coaches = get_posts(array(
    'post_type'      => 'fasp_coach',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
));

$account_url = function($endpoint){
  if (function_exists('wc_get_account_endpoint_url')) {
    return wc_get_account_endpoint_url($endpoint);
  }
  return esc_url(home_url('/my-account/' . $endpoint . '/'));
};
?>

<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Meet Our Coaches', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Connect with experienced trading coaches to accelerate your learning journey.', 'fasp'); ?></p>
    <p><a class="button" href="<?php echo esc_url($account_url('forex-dashboard')); ?>"><?php echo esc_html__('← Back to Dashboard', 'fasp'); ?></a></p>
  </header>

  <div class="fasp-dashboard">
    <?php if (!empty($coaches)): ?>
      <?php foreach ($coaches as $coach): ?>
        <?php
        $title = get_the_title($coach);
        $permalink = get_permalink($coach);
        $role = get_post_meta($coach->ID, '_fasp_coach_role', true);
        $intro = get_post_meta($coach->ID, '_fasp_coach_intro', true);
        $photo_id = get_post_meta($coach->ID, '_fasp_coach_photo_id', true);
        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
        ?>
        <div class="fasp-card fasp-card--half">
          <div class="fasp-coach-header">
            <?php if ($photo_url): ?>
              <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($title); ?>" class="fasp-coach-photo">
            <?php endif; ?>
            <div class="fasp-coach-info">
              <h3><?php echo esc_html($title); ?></h3>
              <?php if ($role): ?>
                <p class="fasp-muted fasp-coach-role"><?php echo esc_html($role); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($intro): ?>
            <p class="fasp-muted"><?php echo esc_html(wp_trim_words($intro, 20)); ?></p>
          <?php endif; ?>
          <p><a href="<?php echo esc_url($permalink); ?>" class="button"><?php echo esc_html__('View Profile', 'fasp'); ?></a></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php echo esc_html__('No Coaches Available', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php echo esc_html__('Our coaching program is launching soon. Check back later to connect with expert traders.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>
  </div>

  <?php 
  // Only show archive link if archive is enabled and there are enough coaches
  $archive_link = get_post_type_archive_link('fasp_coach');
  if (!empty($coaches) && count($coaches) >= 12 && $archive_link): 
  ?>
    <div class="fasp-archive-link">
      <p class="fasp-muted"><?php echo esc_html__('View all coaches in the', 'fasp'); ?> 
        <a href="<?php echo esc_url($archive_link); ?>"><?php echo esc_html__('Coaches Directory', 'fasp'); ?></a>
      </p>
    </div>
  <?php endif; ?>
</div>
