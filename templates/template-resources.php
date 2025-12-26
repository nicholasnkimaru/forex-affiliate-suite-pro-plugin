<?php
/**
 * Resources Template
 *
 * Displays educational resources, guides, and materials.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Get resources
$resources = get_posts([
  'post_type' => 'fasp_resource',
  'posts_per_page' => 12,
  'post_status' => 'publish',
  'orderby' => 'date',
  'order' => 'DESC'
]);

?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Learning Resources', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Educational materials, guides, and resources to help you succeed.', 'fasp'); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <?php if (!empty($resources)): ?>
      <?php foreach ($resources as $resource): ?>
        <?php 
        $thumbnail = get_the_post_thumbnail_url($resource->ID, 'medium');
        $excerpt = $resource->post_excerpt ?: wp_trim_words($resource->post_content, 20);
        $permalink = get_permalink($resource->ID);
        ?>
        
        <div class="fasp-card fasp-card--half">
          <?php if ($thumbnail): ?>
            <div style="margin: -18px -18px 12px -18px; border-radius: 10px 10px 0 0; overflow: hidden; height: 160px;">
              <img src="<?php echo esc_url($thumbnail); ?>" 
                   alt="<?php echo esc_attr($resource->post_title); ?>"
                   style="width: 100%; height: 100%; object-fit: cover;">
            </div>
          <?php endif; ?>
          
          <h3 style="margin-top: 0;">
            <a href="<?php echo esc_url($permalink); ?>" style="text-decoration: none; color: inherit;">
              <?php echo esc_html($resource->post_title); ?>
            </a>
          </h3>
          
          <p class="fasp-muted" style="margin-bottom: 16px; font-size: 14px;">
            <?php echo esc_html($excerpt); ?>
          </p>
          
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <a class="button" href="<?php echo esc_url($permalink); ?>">
              <?php esc_html_e('Read More', 'fasp'); ?>
            </a>
            <span style="font-size: 12px; color: #999;">
              <?php echo get_the_date('M j, Y', $resource->ID); ?>
            </span>
          </div>
        </div>
        
      <?php endforeach; ?>
      
      <!-- View All Link -->
      <div class="fasp-card fasp-card--wide" style="text-align: center; padding: 24px;">
        <p>
          <a class="button button-primary" href="<?php echo esc_url(get_post_type_archive_link('fasp_resource')); ?>">
            <?php esc_html_e('View All Resources', 'fasp'); ?>
          </a>
        </p>
      </div>
      
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php esc_html_e('No Resources Available', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php esc_html_e('Check back soon for educational resources and guides.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>

    <!-- Resource Categories -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('Resource Categories', 'fasp'); ?></h2>
      <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
        <a href="#" class="button"><?php esc_html_e('Trading Basics', 'fasp'); ?></a>
        <a href="#" class="button"><?php esc_html_e('Platform Guides', 'fasp'); ?></a>
        <a href="#" class="button"><?php esc_html_e('Market Analysis', 'fasp'); ?></a>
        <a href="#" class="button"><?php esc_html_e('Risk Management', 'fasp'); ?></a>
        <a href="#" class="button"><?php esc_html_e('Video Tutorials', 'fasp'); ?></a>
        <a href="#" class="button"><?php esc_html_e('FAQ', 'fasp'); ?></a>
      </div>
    </div>

  </div>
</div>
