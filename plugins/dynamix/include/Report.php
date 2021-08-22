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
case 'vfat':
  $check = "/var/tmp/check.vfat";
  if (!file_exists($check)) {
    $disks = parse_ini_file('/var/local/emhttp/disks.ini',true);
    file_put_contents($check,exec("lsblk -lo NAME /dev/{$disks['flash']['device']}|awk '(NR>2)'"));
  }
  $dev = file_get_contents($check);
  exec("fsck.vfat -n /dev/$dev",$void,$status);
  echo $status;
  break;
case 'sha256':
  $boot  = "/boot";
  $check = "/var/tmp/check.sha256";
  $image = ['bzroot','bzroot-gui','bzimage']; // image files to check
  if (!file_exists($check)) foreach ($image as $file) file_put_contents($check,trim(file_get_contents("$boot/$file.sha256"))."  $boot/$file\n",FILE_APPEND);
  exec("sha256sum --status -c $check",$void,$status);
  echo $status;
  break;
case 'config':
  $config = "/boot/config";
  $files  = ['disk','docker','domain','ident','share']; // config files to check
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
default:
  echo 0;
  break;
}
?>
