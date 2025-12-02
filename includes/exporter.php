<?php if (!defined('ABSPATH')) exit;
function fasp_render_exporter(){
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  if (!class_exists('ZipArchive')){ echo '<div class="wrap"><h1>Export Plugin</h1><p>PHP ZipArchive extension is required.</p></div>'; return; }
  $slug='forex-affiliate-suite-pro'; $src=rtrim(FASP_PATH,'/'); $tmp=wp_tempnam($slug.'.zip');
  $zip=new ZipArchive(); if($zip->open($tmp, ZipArchive::OVERWRITE)!==true) wp_die('Cannot open zip.');
  $len=strlen($src)+1;
  $iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
  foreach($iter as $f){ if($f->isFile()){ $path=$f->getPathname(); $rel=substr($path,$len); $zip->addFile($path,$rel); } }
  $zip->close();
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="'.$slug.'-FULL.zip"');
  header('Content-Length: '.filesize($tmp));
  readfile($tmp); @unlink($tmp); exit;
}
