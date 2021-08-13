<?PHP
/* Copyright 2005-2021, Lime Technology
 * Copyright 2012-2021, Bergware International.
 * Copyright 2015, Dan Landon.
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
// add translations
$_SERVER['REQUEST_URI'] = 'settings';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/webGui/include/Secure.php";

$state = [
  'TRIM ONLINE'  => _('Online (trim)'),
  'BOOST ONLINE' => _('Online (boost)'),
  'ONLINE'       => _('Online'),
  'ONBATT'       => _('On battery'),
  'COMMLOST'     => _('Lost communication'),
  'NOBATT'       => _('No battery detected')
];

$red    = "class='red-text'";
$green  = "class='green-text'";
$orange = "class='orange-text'";
$status = array_fill(0,6,"<td>-</td>");
$all    = unscript($_GET['all']??'')=='true';
$result = [];

if (file_exists("/var/run/apcupsd.pid")) {
  exec("/sbin/apcaccess 2>/dev/null", $rows);
  for ($i=0; $i<count($rows); $i++) {
    $row = array_map('trim', explode(':', $rows[$i], 2));
    $key = $row[0];
    $val = strtr($row[1], $state);
    switch ($key) {
    case 'STATUS':
      $status[0] = $val ? (stripos($val,'online')===false ? "<td $red>$val</td>" : "<td $green>$val</td>") : "<td $orange>"._('Refreshing')."...</td>";
      break;
    case 'BCHARGE':
      $status[1] = strtok($val,' ')<=10 ? "<td $red>$val</td>" : "<td $green>$val</td>";
      break;
    case 'TIMELEFT':
      $status[2] = strtok($val,' ')<=5 ? "<td $red>$val</td>" : "<td $green>$val</td>";
      break;
    case 'NOMPOWER':
      $power = strtok($val,' ');
      $status[3] = $power==0 ? "<td $red>$val</td>" : "<td $green>$val</td>";
      break;
    case 'LOADPCT':
      $load = strtok($val,' ');
      $status[5] = $load>=90 ? "<td $red>$val</td>" : "<td $green>$val</td>";
      break;
    }
    if ($all) {
      if ($i%2==0) $result[] = "<tr>";
      $result[]= "<td><strong>$key</strong></td><td>$val</td>";
      if ($i%2==1) $result[] = "</tr>";
    }
  }
  if ($all && count($rows)%2==1) $result[] = "<td></td><td></td></tr>";
  if ($power && $load) $status[4] = ($load>=90 ? "<td $red>" : "<td $green>").intval($power*$load/100)." "._('Watts')."</td>";
}
if ($all && !$rows) $result[] = "<tr><td colspan='4' style='text-align:center'>"._('No information available')."</td></tr>";

echo "<tr>".implode('', $status)."</tr>";
if ($all) echo "\n".implode('', $result);
?>
