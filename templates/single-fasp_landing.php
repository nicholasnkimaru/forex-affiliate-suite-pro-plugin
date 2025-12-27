<?php if (!defined('ABSPATH')) exit;
get_header(); ?>
<main class="fasp-landing">
  <section class="hero">
    <h1><?php the_title(); ?></h1>
    <p class="excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
  </section>
  <section class="content">
    <?php while(have_posts()){ the_post(); the_content(); } ?>
    <p class="cta-button"><a class="button" href="<?php echo esc_url( add_query_arg('rid', get_current_user_id() ?: 0, home_url('/my-account/forex-dashboard/')) ); ?>">Go to Dashboard</a></p>
  </section>
</main>
<?php get_footer(); ?>
