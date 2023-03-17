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
$docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "$docroot/webGui/include/Wrappers.php";

if ($_POST['mode']>0) {
  $hour = isset($_POST['hour']) ? $_POST['hour'] : '*';
  $min  = isset($_POST['min'])  ? $_POST['min']  : '*';
  $dotm = isset($_POST['dotm']) ? $_POST['dotm'] : '*';
  $day  = isset($_POST['day'])  ? $_POST['day']  : '*';
  $cron = "# Generated ssd trim schedule:\n$min $hour $dotm * $day /sbin/fstrim -a -v | logger &> /dev/null\n";
  exec("/usr/sbin/zpool status|grep -Po 'pool: \K.+'",$zfs_pools);
  foreach ($zfs_pools as $pool) if ($pool) $cron .= "$min $hour $dotm * $day /usr/sbin/zpool trim $pool 2> /dev/null\n";
} else {
  $cron = "";
}
parse_cron_cfg('dynamix', 'ssd-trim', $cron);
?>