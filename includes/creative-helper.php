<?php if (!defined('ABSPATH')) exit;
function fasp_render_creative_helper_page(){
  echo '<div class="wrap"><h1>Creative Helper</h1>';
  echo '<p>Starter creatives (swap logos/text as needed).</p>';
  echo '<div class="card"><h2>Banner A</h2><img src="'.esc_url(plugins_url('assets/img/creative-a.jpg', dirname(__FILE__))).'" style="max-width:520px;height:auto"></div>';
  echo '<div class="card"><h2>Banner B</h2><img src="'.esc_url(plugins_url('assets/img/creative-b.jpg', dirname(__FILE__))).'" style="max-width:520px;height:auto"></div>';
  echo '</div>';
}
