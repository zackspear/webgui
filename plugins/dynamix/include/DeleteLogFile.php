<?PHP
/* Copyright 2005-2023, Lime Technology
 * Copyright 2012-2023, Bergware International.
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
require_once "$docroot/webGui/include/Wrappers.php";

$dynamix = parse_plugin_cfg('dynamix',true);
$archive = $dynamix['notify']['path']."/archive";
$log     = $_POST['log']??'';
$filter  = $_POST['filter']??false;
$files   = strpos($log,'*')===false ? [realpath("$archive/$log")] : glob("$archive/$log",GLOB_NOSORT);

foreach ($files as $file) {
  // check file path
  if (strncmp($file,$archive,strlen($archive))!==0) continue;
  if (!$filter) {
    // delete all files
    @unlink($file);
  } else {
    // delete selective files
    if (exec("grep -om1 'importance=$filter' ".escapeshellarg($file))) @unlink($file);
  }
}
?>
