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
case 'sha256':
  $boot  = "/boot";
  $check = "/var/tmp/check.sha256";
  $image = ['bzroot','bzimage']; // image files to check
  if (!file_exists($check)) foreach ($image as $file) file_put_contents($check,trim(file_get_contents("$boot/$file.sha256"))."  $boot/$file\n",FILE_APPEND);
  passthru("sha256sum --status -c $check",$status);
  echo $status;
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
