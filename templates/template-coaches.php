<?php
/**
 * Coaches Template
 *
 * Displays available coaches and booking information.
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

// Get coaches
$coaches = get_posts([
  'post_type' => 'fasp_coach_event',
  'posts_per_page' => 12,
  'post_status' => 'publish',
  'orderby' => 'menu_order',
  'order' => 'ASC'
]);

?>
<div class="fasp-dashboard-wrap">
  <header class="fasp-dashboard-header">
    <h1><?php echo esc_html__('Trading Coaches', 'fasp'); ?></h1>
    <p class="fasp-muted"><?php echo esc_html__('Book sessions with our expert coaches to accelerate your trading journey.', 'fasp'); ?></p>
  </header>

  <div class="fasp-dashboard fasp-grid">

    <?php if (!empty($coaches)): ?>
      <?php foreach ($coaches as $coach): ?>
        <?php 
        $thumbnail = get_the_post_thumbnail_url($coach->ID, 'medium');
        $excerpt = $coach->post_excerpt ?: wp_trim_words($coach->post_content, 15);
        $permalink = get_permalink($coach->ID);
        
        // Get coach meta (if available)
        $coach_specialty = get_post_meta($coach->ID, '_fasp_coach_specialty', true);
        $coach_rating = get_post_meta($coach->ID, '_fasp_coach_rating', true);
        ?>
        
        <div class="fasp-card fasp-card--half">
          <div style="display: flex; gap: 16px; align-items: start;">
            <?php if ($thumbnail): ?>
              <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                <img src="<?php echo esc_url($thumbnail); ?>" 
                     alt="<?php echo esc_attr($coach->post_title); ?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
              </div>
            <?php else: ?>
              <div class="fasp-avatar" data-initials="<?php echo esc_attr(substr($coach->post_title, 0, 2)); ?>"
                   style="width: 80px; height: 80px; flex-shrink: 0;">
              </div>
            <?php endif; ?>
            
            <div style="flex: 1;">
              <h3 style="margin: 0 0 4px 0;">
                <?php echo esc_html($coach->post_title); ?>
              </h3>
              
              <?php if ($coach_specialty): ?>
                <p style="margin: 0 0 8px 0; font-size: 12px; color: #666;">
                  <?php echo esc_html($coach_specialty); ?>
                </p>
              <?php endif; ?>
              
              <?php if ($coach_rating): ?>
                <div style="margin-bottom: 8px;">
                  <span style="color: #f59e0b; font-size: 14px;">
                    <?php echo str_repeat('⭐', min(5, (int)$coach_rating)); ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <p class="fasp-muted" style="margin: 12px 0; font-size: 14px;">
            <?php echo esc_html($excerpt); ?>
          </p>
          
          <div style="display: flex; gap: 8px;">
            <a class="button button-primary" href="<?php echo esc_url($permalink); ?>">
              <?php esc_html_e('View Profile', 'fasp'); ?>
            </a>
            <a class="button" href="<?php echo esc_url($permalink); ?>">
              <?php esc_html_e('Book Session', 'fasp'); ?>
            </a>
          </div>
        </div>
        
      <?php endforeach; ?>
      
    <?php else: ?>
      <div class="fasp-card fasp-card--wide">
        <h3><?php esc_html_e('No Coaches Available', 'fasp'); ?></h3>
        <p class="fasp-muted"><?php esc_html_e('Check back soon for coaching opportunities.', 'fasp'); ?></p>
      </div>
    <?php endif; ?>

    <!-- Coaching Information -->
    <div class="fasp-card fasp-card--wide">
      <h2><?php esc_html_e('How Coaching Works', 'fasp'); ?></h2>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 16px;">
        <div>
          <div style="font-size: 32px; margin-bottom: 8px;">📅</div>
          <h4 style="margin: 0 0 8px 0;"><?php esc_html_e('Book a Session', 'fasp'); ?></h4>
          <p class="fasp-muted" style="font-size: 14px;">
            <?php esc_html_e('Choose a coach and select a time that works for you.', 'fasp'); ?>
          </p>
        </div>
        <div>
          <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
          <h4 style="margin: 0 0 8px 0;"><?php esc_html_e('1-on-1 Guidance', 'fasp'); ?></h4>
          <p class="fasp-muted" style="font-size: 14px;">
            <?php esc_html_e('Get personalized advice and strategies from experts.', 'fasp'); ?>
          </p>
        </div>
        <div>
          <div style="font-size: 32px; margin-bottom: 8px;">📈</div>
          <h4 style="margin: 0 0 8px 0;"><?php esc_html_e('Improve Your Trading', 'fasp'); ?></h4>
          <p class="fasp-muted" style="font-size: 14px;">
            <?php esc_html_e('Apply what you learn to enhance your trading performance.', 'fasp'); ?>
          </p>
        </div>
      </div>
    </div>

  </div>
</div>
