<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2012-2020, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

$cmd       = $_POST['cmd'] ?? 'load';
$path      = $_POST['path'] ?? '';
$file      = rawurldecode($_POST['filename']);
$temp      = "/var/tmp";
$plugins   = "/boot/config/plugins";
$boot      = "/boot/config/plugins/dynamix";
$safepaths = [$boot];
$safeexts  = ['.png'];

switch ($cmd) {
case 'load':
  if (isset($_POST['filedata'])) {
    exec("rm -f $temp/*.png");
    $result = file_put_contents("$temp/".basename($file),base64_decode(str_replace(['data:image/png;base64,',' '],['','+'],$_POST['filedata'])));
  }
  break;
case 'save':
  foreach ($safepaths as $safepath) {
    if (strpos(dirname("$path/{$_POST['output']}"),$safepath)===0 && in_array(substr(basename($_POST['output']),-4),$safeexts)) {
      exec("mkdir -p ".escapeshellarg(realpath($path)));
      $result = @rename("$temp/".basename($file), "$path/{$_POST['output']}");
      break;
    }
  }
  break;
case 'delete':
  foreach ($safepaths as $safepath) {
    if (strpos(realpath("$path/$file"), $safepath) === 0 && in_array(substr(realpath("$path/$file"), -4), $safeexts)) {
      exec("rm -f ".escapeshellarg(realpath("$path/$file")));
      $result = true;
      break;
    }
  }
  break;
case 'add':
  $path = "$docroot/languages/$file";
  exec("mkdir -p ".escapeshellarg($path));
  $result = file_put_contents("/$boot/$file.lang.zip",base64_decode(preg_replace('/^data:.*;base64,/','',$_POST['filedata'])));
  if ($result) {
    foreach (glob("$path/*.dot",GLOB_NOSORT) as $dot) unlink($dot);
    @unlink("$docroot/webGui/javascript/translate.$file.js");
    exec("unzip -qqjLo -d ".escapeshellarg($path)." ".escapeshellarg("$boot/$file.lang.zip"));
  }
  $installed = [];
  foreach (glob("$docroot/languages/*",GLOB_ONLYDIR) as $dir) $installed[] = basename($dir);
  if ($result) exit(implode(',',$installed));
case 'rm':
  $path = "$docroot/languages/$file";
  if ($result = is_dir($path)) {
    exec("rm -rf ".escapeshellarg($path));
    @unlink("$docroot/webGui/javascript/translate.$file.js");
    @unlink("$plugins/dynamix.$file.xml");
    @unlink("$boot/$file.lang.zip");
  }
  $installed = [];
  foreach (glob("$docroot/languages/*",GLOB_ONLYDIR) as $dir) $installed[] = basename($dir);
  if ($result) exit(implode(',',$installed));
}
exit($result ? 'OK 200' : 'Internal Error 500');
?>
