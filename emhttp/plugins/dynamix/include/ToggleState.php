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
$docroot ??= ($_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');
require_once "$docroot/webGui/include/Wrappers.php";

$device = $_POST['device']??'';
$name   = $_POST['name']??'';
$action = $_POST['action']??'';

function emcmd($cmd) {
  exec("emcmd $cmd");
}
switch ($device) {
case 'New':
  emcmd("cmdSpin{$action}={$name}");
  break;
case 'Clear':
  emcmd("clearStatistics=true");
  break;
default:
  if (!$name) {
    // spin up/down all devices
    emcmd("cmdSpin{$device}All=true");
    break;
  }
  if (substr($name,-1) != '*') {
    // spin up/down single device
    emcmd("cmdSpin{$action}={$name}");
    break;
  }
  // spin up/down group of devices
  $disks = (array)@parse_ini_file('state/disks.ini',true);
  // remove '*' from name
  $name = substr($name,0,-1);
  foreach ($disks as $disk) {
    if (_var($disk,'status') != 'DISK_OK') continue;
    $array = ($name=='array' && in_array(_var($disk,'type'),['Parity','Data']));
    if ($array || explode($_tilde_,prefix(_var($disk,'name')))[0]==$name) emcmd("cmdSpin{$action}="._var($disk,'name'));
  }
  break;
}
?>
