<?PHP
/* Copyright 2005-2025, Lime Technology
 * Copyright 2012-2025, Bergware International.
 * Copyright 2012-2025, Simon Fairweather.
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
require_once "$docroot/webGui/include/Helpers.php";
$savedfile = "/boot/config/savedpcidata.json";
$saved = loadSavedData($savedfile);
if (!$saved) {echo "ERROR"; return;};

$pciaddr = $_POST['pciid'];
switch ($_POST['action']??'') {
case 'removed':
  unset($saved[$pciaddr]);
  break;
case 'changed':
case 'added':
  $current = loadCurrentPCIData();
  $saved[$pciaddr] = $current[$pciaddr];
  break;
}
file_put_contents($savedfile,json_encode($saved,JSON_PRETTY_PRINT));
echo "OK";
?>
