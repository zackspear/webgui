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

switch ($_GET['tag']) {
case 'ttyd':
  $pid = exec("pgrep -a ttyd|awk '/\\/var\\/run\\/ttyd.sock:{print \$1}'");
  if (!$pid) {
    @unlink('/var/run/ttyd.sock');
    exec("ttyd-exec -o -i '/var/run/ttyd.sock' bash --login");
  }
  break;
case 'syslog':
  $path = '/var/log/';
  $file = realpath($path.$_GET['name']);
  $pid = exec("pgrep -a ttyd|awk '/\\/var\\/run\\/syslog.sock:{print \$1}'");
  if (!$pid) {
    @unlink('/var/run/syslog.sock');
    $command = file_exists($file) ? "tail -n 40 -f '$file'" : "bash --login";
    exec("ttyd-exec -o -i '/var/run/syslog.sock' $command");
  }
  break;
case 'log':
  $path = '/var/log/';
  $name = unbundle($_GET['name']);
  $file = realpath($path.$_GET['more']);
  $pid = exec("pgrep -a ttyd|awk '/\\/var\\/tmp\\/$name.sock:{print \$1}'");
  if ($pid) exec("kill $pid");
  @unlink('/var/tmp/$name.sock');
  $command = file_exists($file) ? "tail -n 40 -f '$file'" : "bash --login";
  usleep(100000);
  exec("ttyd-exec -o -i '/var/tmp/$name.sock' $command");
  break;
case 'docker':
  $name = unbundle($_GET['name']);
  $shell = unbundle($_GET['more']) ?: 'sh';
  $exec = strlen($shell)!=12; // container-id
  $id = $exec ? $name : $shell;
  $pid = exec("pgrep -a ttyd|awk '/\\/var\\/tmp\\/$id\\.sock/{print \$1}'");
  if ($pid) exec("kill $pid");
  @unlink("/var/tmp/$id.sock");
  $command = $exec ? "docker exec -it '$name' $shell" : "docker logs -f -n 40 '$name'";
  usleep(100000);
  exec("ttyd-exec -o -i '/var/tmp/$id.sock' $command");
  break;
}
?>
