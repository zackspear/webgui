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

if (!empty($_POST['kill']) && $_POST['kill'] > 1) {
  exec("kill ".$_POST['kill']);
  die;
}

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
  } elseif (!pgrep($name)) {
    // only execute when command and valid path is given and command not already running
    exec("echo \"$name $args\" | at -M now >/dev/null 2>&1");
    usleep(5000);
    $pid = pgrep($name); // started
  }
}
echo $pid;
?>
