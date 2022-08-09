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
require_once "$docroot/webGui/include/Secure.php";

function pgrep($proc) {
  return exec("pgrep -f $proc");
}

if (isset($_POST['kill']) && $_POST['kill'] > 1) {
  exec("kill ".$_POST['kill']);
  foreach (glob("/tmp/plugins/pluginPending/*") as $file) unlink($file);
  die();
}

$start = isset($_POST['start']) && $_POST['start'] == 1;
[$command,$args] = explode(' ',unscript($_POST['cmd']??''),2);

// find absolute path of command
foreach (glob("$docroot/plugins/*/scripts",GLOB_NOSORT) as $path) {
  if ($name = realpath("$path/$command")) break;
}

$pid = 0; // preset to not started
if ($command && strncmp($name,$path,strlen($path))===0) {
  if (isset($_POST['pid'])) {
    // return running pid
    $pid = pgrep($name);
  } elseif ($start or !pgrep($name)) {
    // start command in background and return pid
    $pid = exec("$docroot/webGui/scripts/start_command $name $args");
  }
}
echo $pid;
?>
