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
require_once "$docroot/webGui/include/Secure.php";
require_once "$docroot/webGui/include/Helpers.php";

// Get the webGui configuration preferences
extract(parse_plugin_cfg('dynamix',true));

// set tty window font size
exec("sed -ri 's/fontSize=[0-9]+/fontSize=${display['tty']}/' /etc/default/ttyd");

function command($path,$file) {
  return (file_exists($file) && substr($file,0,strlen($path))==$path) ? "tail -n 40 -f '$file'" : "bash --login --restricted";
}
switch ($_GET['tag']) {
case 'ttyd':
  // check if ttyd already running
  exec("pgrep -f '/var/run/ttyd.sock'", $ttyd_pid, $retval);
  if ($retval == 0) {
      // check if there are any child processes, ie, curently open tty windows
      exec("pgrep -P ${ttyd_pid[0]}", $output, $retval);
      if ($retval != 0) {
        // no child processes, restart ttyd to pick up possible font size change
        exec("kill ${ttyd_pid[0]}");
      }
  }
  if ($retval != 0)
    exec("ttyd-exec -i '/var/run/ttyd.sock' bash --login");
  break;
case 'syslog':
  $path = '/var/log/';
  $file = realpath($path.$_GET['name']);
  exec("ttyd-exec -o -i '/var/run/syslog.sock' ".command($path,$file));
  @unlink('/var/run/syslog.sock');
  break;
case 'log':
  $path = '/var/log/';
  $name = unbundle($_GET['name']);
  $file = realpath($path.$_GET['more']);
  exec("ttyd-exec -o -i '/var/tmp/$name.sock' ".command($path,$file));
  @unlink('/var/tmp/$name.sock');
  break;
case 'docker':
  $name = unbundle($_GET['name']);
  $more = unbundle($_GET['more']) ?: 'sh';
  $exec = strlen($more)!=12; // container-id
  $sock = $exec ? $name : $more;
  $command = $exec ? "docker exec -it '$name' $more" : "docker logs -f -n 40 '$name'";
  exec("ttyd-exec -o -i '/var/tmp/$sock.sock' $command");
  @unlink("/var/tmp/$sock.sock");
  break;
case 'command':
  $name = unbundle($_GET['name']);
  $command = unscript($_GET['more']);
  exec("ttyd-exec -o -i '/var/tmp/$name.sock' $command");
  @unlink("/var/tmp/$name.sock");
  break;
}
?>
