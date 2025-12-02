<?php if (!defined('ABSPATH')) exit;
add_filter('the_content', function($c){
  if(!is_singular('fasp_resource') || is_admin()) return $c;
  $n=(int)get_option('fasp_ad_inject_after',0);
  if($n<=0) return $c;
  $parts=preg_split('/(<\/p>)/i',$c,-1,PREG_SPLIT_DELIM_CAPTURE);
  if(count($parts)<2) return $c;
  $out=''; $pcount=0; $ad=get_option('fasp_ad_above_global','');
  foreach($parts as $chunk){
    $out.=$chunk;
    if(stripos($chunk,'</p>')!==false){
      $pcount++;
      if($pcount==$n){ $out.='<div class="ad ad-mid">'.$ad.'</div>'; }
    }
  }
  return $out;
},20);
