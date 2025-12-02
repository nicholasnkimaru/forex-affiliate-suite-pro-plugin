<?php
if (!defined('ABSPATH')) exit;
// [fasp_consent] shortcode — shows a button to accept tracking
add_shortcode('fasp_consent', function($atts){
  $a = shortcode_atts(['label'=>'Accept analytics & improve my experience'], $atts, 'fasp_consent');
  ob_start(); ?>
  <div class="fasp-consent-box" style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;">
    <p style="margin:0 0 8px;color:#6b7280;">We use first‑party cookies to measure which campaigns work best. You can opt in below.</p>
    <button class="button" id="fasp-consent-accept"><?php echo esc_html($a['label']); ?></button>
  </div>
  <script>
  (function(){
    var b=document.getElementById('fasp-consent-accept'); if(!b) return;
    b.addEventListener('click', function(){
      var d=new Date(); d.setTime(d.getTime()+365*24*60*60*1000);
      document.cookie='fasp_consent=1; path=/; expires='+d.toUTCString()+'; SameSite=Lax';
      b.disabled=true; b.innerText='Thanks — enabled';
    });
  })();
  </script>
  <?php return ob_get_clean();
});
