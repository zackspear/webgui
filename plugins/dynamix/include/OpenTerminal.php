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

function command($path,$file) {
  return (file_exists($file) && substr($file,0,strlen($path))==$path) ? "tail -n 40 -f '$file'" : "bash --login --restricted";
}
switch ($_GET['tag']) {
case 'ttyd':
  @unlink('/var/run/ttyd.sock');
  exec("ttyd-exec -o -i '/var/run/ttyd.sock' bash --login");
  break;
case 'syslog':
  $path = '/var/log/';
  $file = realpath($path.$_GET['name']);
  @unlink('/var/run/syslog.sock');
  exec("ttyd-exec -o -i '/var/run/syslog.sock' ".command($path,$file));
  break;
case 'log':
  $path = '/var/log/';
  $name = unbundle($_GET['name']);
  $file = realpath($path.$_GET['more']);
  @unlink('/var/tmp/$name.sock');
  exec("ttyd-exec -o -i '/var/tmp/$name.sock' ".command($path,$file));
  break;
case 'docker':
  $name = unbundle($_GET['name']);
  $more = unbundle($_GET['more']) ?: 'sh';
  $exec = strlen($more)!=12; // container-id
  $sock = $exec ? $name : $more;
  @unlink("/var/tmp/$sock.sock");
  $command = $exec ? "docker exec -it '$name' $more" : "docker logs -f -n 40 '$name'";
  exec("ttyd-exec -o -i '/var/tmp/$sock.sock' $command");
  break;
}
?>
