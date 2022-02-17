<?PHP
/* Copyright 2005-2022, Lime Technology
 * Copyright 2015-2022, Bergware International
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
require_once "$docroot/webGui/include/Secure.php";

$mount = unscript($_GET['mount']??'');
if ($mount) {
  exec("ps -C btrfs -o cmd=|awk '/\/mnt\/$mount\$/{print $2}'",$action);
  echo implode(',',$action);
} else {
  echo exec('pgrep -cf /sbin/btrfs')>0 ? 'disable' : 'enable';
}
?>