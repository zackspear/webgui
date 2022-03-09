<?PHP
/* Copyright 2005-2022, Lime Technology
 * Copyright 2012-2022, Bergware International.
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
$_SERVER['REQUEST_URI'] = '';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/webGui/include/Secure.php";
require_once "$docroot/webGui/include/Wrappers.php";

// Get the webGui configuration preferences
extract(parse_plugin_cfg('dynamix',true));

$rows = 80;
$wait = "read -N 1 -p '\n** "._('Press ANY KEY to close this window')." ** '";

// set tty window font size
if (isset($display['tty'])) exec("sed -ri 's/fontSize=[0-9]+/fontSize={$display['tty']}/' /etc/default/ttyd");

function command($path,$file) {
  global $rows,$wait;
  return (file_exists($file) && substr($file,0,strlen($path))==$path) ? "tail -f -n $rows '$file'" : $wait;
}
switch ($_GET['tag']) {
case 'ttyd':
  // check if ttyd already running
  $sock = "/var/run/ttyd.sock";
  exec("pgrep -f '$sock'", $ttyd_pid, $retval);
  if ($retval == 0) {
    // check if there are any child processes, ie, curently open tty windows
    exec("pgrep -P ".$ttyd_pid[0], $output, $retval);
    // no child processes, restart ttyd to pick up possible font size change
    if ($retval != 0) exec("kill ".$ttyd_pid[0]);
  }
  if ($retval != 0) exec("ttyd-exec -i '$sock' bash --login");
  break;
case 'syslog':
  // read syslog file
  $path = '/var/log/';
  $file = realpath($path.$_GET['name']);
  $sock = "/var/run/syslog.sock";
  exec("ttyd-exec -i '$sock' ".command($path,$file));
  break;
case 'log':
  // read vm log file
  $path = '/var/log/';
  $name = unbundle($_GET['name']);
  $file = realpath($path.$_GET['more']);
  $sock = "/var/tmp/$name.sock";
  exec("ttyd-exec -i '$sock' ".command($path,$file));
  break;
case 'docker':
  $name = unbundle($_GET['name']);
  $more = unbundle($_GET['more']) ?: 'sh';
  if ($more=='.log') {
    // read docker container log
    $sock = "/var/tmp/$name.log.sock";
    if (empty(exec("docker ps --filter=name='$name' --format={{.Names}}"))) {
      // container stopped - read log and wait for user input
      $docker = "/var/tmp/$name.run.sh";
      file_put_contents($docker,"#!/bin/bash\ndocker logs -n $rows '$name'\n$wait\n");
      chmod($docker,0755);
    } else {
      // container started - read log continuously
      $docker = "docker logs -f -n $rows '$name'";
    }
    exec("ttyd-exec -i '$sock' $docker");
  } else {
    // docker console command
    $sock = "/var/tmp/$name.sock";
    exec("ttyd-exec -i '$sock' docker exec -it '$name' $more");
  }
  break;
}
?>
