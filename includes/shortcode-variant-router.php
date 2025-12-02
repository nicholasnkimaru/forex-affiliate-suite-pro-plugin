<?php
if (!defined('ABSPATH')) exit;
// [fasp_variant_router key="expkey" base="/landing"]
add_shortcode('fasp_variant_router', function($atts){
  $a = shortcode_atts(['key'=>'','base'=>''], $atts, 'fasp_variant_router');
  $vars = get_option('fasp_variants', []);
  $k = sanitize_key($a['key']); if (!$k || empty($vars[$k])) return '';
  $ex = $vars[$k];
  $p = $ex['param'] ?? 'v';
  $weights = $ex['weights'] ?? [];
  $values = $ex['values'] ?? [];
  $base = $a['base'] ? esc_url(home_url($a['base'])) : esc_url(home_url(add_query_arg(NULL, NULL)));
  ob_start(); ?>
  <script>
  (function(){
    try{
      var p = <?php echo json_encode($p); ?>;
      var vals = <?php echo json_encode($values); ?>;
      var weights = <?php echo json_encode($weights); ?>;
      var qs = new URLSearchParams(window.location.search);
      if (qs.get(p)) return; // respect chosen variant
      // sticky by cookie
      var c = document.cookie.match(new RegExp('(?:^|; )' + 'fasp_var_'+p + '=([^;]*)'));
      if (c){ var v=decodeURIComponent(c[1]); if (vals.indexOf(v)>=0){ qs.set(p, v); window.location.replace(window.location.pathname+'?'+qs.toString()); return; } }
      // weighted pick
      var pool = []; for (var i=0;i<vals.length;i++){ var v=vals[i], w=parseInt(weights[v]||0); for (var k2=0;k2<w;k2++){ pool.push(v); } }
      var pick = pool.length? pool[Math.floor(Math.random()*pool.length)] : (vals[0]||'a');
      qs.set(p, pick);
      window.location.replace(window.location.pathname+'?'+qs.toString());
    }catch(e){}
  })();
  </script>
  <?php return ob_get_clean();
});
