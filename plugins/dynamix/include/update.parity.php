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
require_once "$docroot/webGui/include/Wrappers.php";

$memory = '/tmp/memory.tmp';
$mdcmd  = '/usr/local/sbin/mdcmd';
$ctrl   = '/usr/local/emhttp/webGui/scripts/parity_control';

function month($m) {
  return array_filter(explode(',',$m),function($x){return $x>=date('m');})[0];
}

if (isset($_POST['#apply'])) {
  $cron = [];
  if ($_POST['mode']>0) {
    $time  = $_POST['hour'] ?? '* *';
    $dotm  = $_POST['dotm'] ?? '*';
    $month = $_POST['month'] ?? '*';
    $day   = $_POST['day'] ?? '*';
    $write = $_POST['write'] ?? '';
    $term  = $test = $end = '';
    switch ($dotm) {
      case '28-31':
        $term = '[[ $(date +%e -d +1day) -eq 1 ]] && ';
        $end  = ' || :';
        break;
      case 'W1':
        $dotm = '*';
        $term = '[[ $(date +%e) -le 7 ]] && ';
        $end  = ' || :';
        break;
      case 'W2':
        $dotm = '*';
        $term = '[[ $(date +%e -d -7days) -le 7 ]] && ';
        $end  = ' || :';
        break;
      case 'W3':
        $dotm = '*';
        $term = '[[ $(date +%e -d -14days) -le 7 ]] && ';
        $end  = ' || :';
        break;
      case 'W4':
        $dotm = '*';
        $term = '[[ $(date +%e -d -21days) -le 7 ]] && ';
        $end  = ' || :';
        break;
      case 'WL':
        $dotm = '*';
        $term = '[[ $(date +%e -d +7days) -le 7 ]] && ';
        $end  = ' || :';
        break;
    }
    $cron[] = "# Generated parity check schedule:";
    if ($_POST['cumulative']==1) {
      [$m, $h] = explode(' ',$time);
      $H = ($h + $_POST['duration']) % 24;
      if ($_POST['frequency']==7) {
        $test = '[[ $(date +%U -d @$(grep -Po "^sbSynced=\K\d+" /proc/mdstat) -ne $(date +%U) ]] && ';
        $end  = ' || :';
      }
      $cron[] = "$m $H * * * $ctrl pause &> /dev/null";
      $cron[] = "$time * * $day {$test}{$ctrl} resume &> /dev/null";
    }
    $cron[] = "$time $dotm $month $day {$term}{$mdcmd} check $write &> /dev/null$end";
  }
  parse_cron_cfg("dynamix", "parity-check", implode("\n",$cron)."\n");
  @unlink($memory);
} else {
  file_put_contents($memory, http_build_query($_POST));
  $save = false;
}
?>
