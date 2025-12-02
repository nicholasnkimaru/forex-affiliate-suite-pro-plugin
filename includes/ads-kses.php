<?php if (!defined('ABSPATH')) exit;
function fasp_kses_ad($html){
  $allowed=['a'=>['href'=>true,'target'=>true,'rel'=>true,'title'=>true],
    'img'=>['src'=>true,'alt'=>true,'width'=>true,'height'=>true,'loading'=>true,'decoding'=>true,'srcset'=>true,'sizes'=>true,'style'=>true],
    'div'=>['class'=>true,'style'=>true],'span'=>['class'=>true,'style'=>true],'p'=>['class'=>true,'style'=>true],
    'strong'=>[],'em'=>[],'br'=>[],'ul'=>['class'=>true,'style'=>true],'ol'=>['class'=>true,'style'=>true],'li'=>['class'=>true,'style'=>true]];
  return wp_kses($html,$allowed);
}
