<?PHP
/* Copyright 2005-2017, Lime Technology
 * Copyright 2012-2017, Bergware International.
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
$index = $_GET['index'];
$tests = explode(',',$_GET['test']);
if ($index < count($tests)) {
  $test = $tests[$index];
  list($name,$size) = explode(':',$test);
  if (!$size) {
    if ($index>0) $test .= '|tail -1';
    echo shell_exec("/usr/sbin/cryptsetup benchmark -h $test");
  } else {
    if ($index>5) $size .= '|tail -1';
    echo preg_replace('/^# Tests.*\n/',"\n",shell_exec("/usr/sbin/cryptsetup benchmark -c $name -s $size"));
  }
} else {
  $bm = popen('/usr/sbin/cryptsetup --help','r');
  while (!feof($bm)) {
    $text = fgets($bm);
    if (strpos($text,'Default PBKDF2 iteration time for LUKS')!==false) echo "\n$text";
    elseif (strpos($text,'Default compiled-in device cipher parameters')!==false) echo "\n$text";
    elseif (strpos($text,'LUKS1:')!==false) echo str_replace("\t"," ",$text);
  }
  pclose($bm);
  echo "<div style='text-align:center'><input type='button' value='Done' onclick='top.Shadowbox.close()'></div>";
}
?>
