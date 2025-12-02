<?php
if (!defined('ABSPATH')) exit;
function fasp_consent_allows_tracking(){
  if (!empty($_COOKIE['fasp_consent']) && $_COOKIE['fasp_consent']==='deny') return false;
  return true;
}
add_filter('fasp/consent', function($ok){ return fasp_consent_allows_tracking(); });