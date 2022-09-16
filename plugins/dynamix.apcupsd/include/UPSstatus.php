<?PHP
/* Copyright 2005-2022, Lime Technology
 * Copyright 2012-2022, Bergware International.
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

$state = [
  'TRIM ONLINE'  => _('Online (trim)'),
  'BOOST ONLINE' => _('Online (boost)'),
  'ONLINE'       => _('Online'),
  'SLAVE'        => '('._('Slave').')',
  'ONBATT'       => _('On battery'),
  'COMMLOST'     => _('Lost communication'),
  'NOBATT'       => _('No battery detected')
];

$red     = "class='red-text'";
$green   = "class='green-text'";
$orange  = "class='orange-text'";
$status  = array_fill(0,6,"<td>-</td>");
$result  = [];
$level   = $_POST['level'] ?: 10;
$runtime = $_POST['runtime'] ?: 5;

if (file_exists("/var/run/apcupsd.pid")) {
  exec("/sbin/apcaccess 2>/dev/null", $rows);
  for ($i=0; $i<count($rows); $i++) {
    [$key,$val] = array_map('trim', explode(':', $rows[$i], 2));
    switch ($key) {
    case 'STATUS':
      $var = strtr($val, $state);
      $status[0] = $var ? (stripos($var,'online')!==false ? "<td $green>$var</td>" : "<td $red>$var</td>") : "<td $orange>"._('Refreshing')."...</td>";
      break;
    case 'BCHARGE':
      [$charge,$unit] = explode(' ', $val, 2);
      $charge = intval($charge);
      $status[1] = $charge>$level ? "<td $green>$charge $unit</td>" : "<td $red>$charge $unit</td>";
      break;
    case 'TIMELEFT':
      [$left,$unit] = explode(' ', $val, 2);
      $left = intval($left);
      $status[2] = $left>$runtime ? "<td $green>$left $unit</td>" : "<td $red>$left $unit</td>";
      break;
    case 'NOMPOWER':
      $power = strtok($val,' ');
      $status[3] = $power==0 ? "<td $red>$val</td>" : "<td $green>$val</td>";
      break;
    case 'LOADPCT':
      $load = strtok($val,' ');
      $status[4] = $val;
      break;
    case 'OUTPUTV':
      $output = strtok($val,' ');
      $status[5] = $val;
      break;
    case 'NOMINV':
      $volt = strtok($val,' ');
      $minv = $volt / 1.1; // +/- 10% tolerance
      $maxv = $volt * 1.1;
      break;
    case 'LINEFREQ':
      $freq = $val;
      break;
    }
    if ($i%2==0) $result[] = "<tr>";
    $result[]= "<td><strong>$key</strong></td><td>$val</td>";
    if ($i%2==1) $result[] = "</tr>";
  }
  if (count($rows)%2==1) $result[] = "<td></td><td></td></tr>";
  if ($power && isset($load)) $status[4] = ($load<90 ? "<td $green>" : "<td $red>").intval($power*$load/100)." W (".$status[4].")</td>";
  elseif (isset($load)) $status[4] = ($load<90 ? "<td $green>" : "<td $red>").$status[4]."</td>";
  $status[5] = $output ? (($output<$minv||$output>$maxv ? "<td $red>" : "<td $green>").$status[5].($freq ? " / $freq" : "")."</td>") : $status[5];
}
if (!$rows) $result[] = "<tr><td colspan='4' style='text-align:center'>"._('No information available')."</td></tr>";

echo "<tr>",implode($status),"</tr>\n",implode($result);
?>
