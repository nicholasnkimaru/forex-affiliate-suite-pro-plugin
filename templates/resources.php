<?php
/**
 * Template: Resources Page
 * Browse educational resources and guides
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Get resources from CPT
$resources = get_posts(array(
    'post_type'      => 'fasp_resource',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
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
    <h1><?php echo esc_html__('Resources & Guides', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Educational materials, tutorials, and guides to help you succeed in trading.', 'fasp'); ?></p>
    <p><a class="button" href="<?php echo esc_url($account_url('forex-dashboard')); ?>"><?php echo esc_html__('← Back to Dashboard', 'fasp'); ?></a></p>
  </header>

  <div class="fasp-dashboard">
    <?php if (!empty($resources)): ?>
      <?php foreach ($resources as $resource): ?>
        <?php
        $title = get_the_title($resource);
        $excerpt = get_the_excerpt($resource);
        $permalink = get_permalink($resource);
        $thumbnail = get_the_post_thumbnail($resource, 'medium');
        ?>
        <div class="fasp-card fasp-card--half">
          <?php if ($thumbnail): ?>
            <div class="fasp-resource-thumbnail">
              <?php echo $thumbnail; ?>
            </div>
          <?php endif; ?>
          <h3><?php echo esc_html($title); ?></h3>
          <?php if ($excerpt): ?>
            <p class="fasp-muted"><?php echo esc_html($excerpt); ?></p>
          <?php endif; ?>
          <p><a href="<?php echo esc_url($permalink); ?>" class="button"><?php echo esc_html__('Read More', 'fasp'); ?></a></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php echo esc_html__('No Resources Available', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php echo esc_html__('Resources are coming soon. Check back later for educational materials and guides.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>
  </div>

  <?php 
  // Only show archive link if archive is enabled and there are enough resources
  $archive_link = get_post_type_archive_link('fasp_resource');
  if (!empty($resources) && count($resources) >= 12 && $archive_link): 
  ?>
    <div class="fasp-archive-link">
      <p class="fasp-muted"><?php echo esc_html__('View all resources in the', 'fasp'); ?> 
        <a href="<?php echo esc_url($archive_link); ?>"><?php echo esc_html__('Resources Archive', 'fasp'); ?></a>
      </p>
    </div>
  <?php endif; ?>
</div>
