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
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
// add translations
$_SERVER['REQUEST_URI'] = 'main';
require_once "$docroot/webGui/include/Translations.php";

require_once "$docroot/webGui/include/Helpers.php";

$var = (array)parse_ini_file('state/var.ini');
extract(parse_plugin_cfg('dynamix',true));

function in_parity_log($log,$timestamp) {
  if (file_exists($log)) {
    $handle = fopen($log, 'r');
    while (($line = fgets($handle))!==false) {
      if (strpos($line,$timestamp)!==false) break;
    }
    fclose($handle);
  }
  return !empty($line);
}
function my_clock($time) {
  if (!$time) return _('less than a minute');
  $days = floor($time/1440);
  $hour = $time/60%24;
  $mins = $time%60;
  return plus($days,'day',($hour|$mins)==0).plus($hour,'hour',$mins==0).plus($mins,'minute',true);
}

$data = [];
if ($var['mdResyncPos']) {
  $data[] = my_scale($var['mdResyncSize']*1024,$unit,-1)." $unit";
  $data[] = _(my_clock(floor((time()-$var['sbSynced'])/60)),2).($var['mdResyncDt'] ? '' : ' ('._('paused').')');
  $data[] = my_scale($var['mdResyncPos']*1024,$unit)." $unit (".number_format(($var['mdResyncPos']/($var['mdResyncSize']/100+1)),1,$display['number'][0],'')." %)";
  $data[] = $var['mdResyncDt'] ? my_scale($var['mdResyncDb']*1024/$var['mdResyncDt'],$unit, 1)." $unit/sec" : '---';
  $data[] = $var['mdResyncDb'] ? _(my_clock(round(((($var['mdResyncDt']*(($var['mdResyncSize']-$var['mdResyncPos'])/($var['mdResyncDb']/100+1)))/100)/60),0)),2) : _('Unknown');
  $data[] = $var['sbSyncErrs'];
  echo implode(';',$data);
} else {
  if ($var['sbSynced']==0 || $var['sbSynced2']==0) exit;
  $log = '/boot/config/parity-checks.log';
  $timestamp = str_replace(['.0','.'],['  ',' '],date('M.d H:i:s',$var['sbSynced2']));
  if (in_parity_log($log,$timestamp)) exit;
  $duration = $var['sbSynced2'] - $var['sbSynced'];
  $status = $var['sbSyncExit'];
  $speed = ($status==0) ? my_scale($var['mdResyncSize']*1024/$duration,$unit,1)." $unit/s" : _('Unavailable');
  $error = $var['sbSyncErrs'];
  $year = date('Y',$var['sbSynced2']);
  if ($status==0||file_exists($log)) file_put_contents($log,"$year $timestamp|$duration|$speed|$status|$error\n",FILE_APPEND);
}
?>
