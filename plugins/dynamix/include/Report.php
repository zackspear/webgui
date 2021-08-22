<?PHP
/* Copyright 2005-2021, Lime Technology
 * Copyright 2012-2021, Bergware International.
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
switch ($_POST['cmd']) {
case 'config':
  $config = "/boot/config";
  $files  = ['disk','docker','domain','flash','ident','network','share']; // config files to check
  foreach ($files as $file) if (file_exists("$config/$file.cfg") && !$test=@parse_ini_file("$config/$file.cfg")) {echo 1; break;}
  echo 0;
  break;
case 'notice':
  $docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
  require_once "$docroot/webGui/include/Secure.php";
  $tmp = "/tmp/reboot_notifications";
  $notices = file_exists($tmp) ? file($tmp,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) : [];
  echo implode("\n",array_map('unbundle',$notices));
  break;
}
?>
