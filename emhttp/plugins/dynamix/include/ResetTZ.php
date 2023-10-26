<?PHP
/* Copyright 2005-2023, Lime Technology
 * Copyright 2012-2023, Bergware International.
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
$docroot ??= ($_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');

$pid = '/var/run/nchan.pid';
$nchan = 'webGui/nchan/update_3';

if (is_file($pid) && exec("grep -Pom1 '^$nchan' $pid")) {
  // restart update_3 script
  exec("pkill -f $nchan");
  exec("$docroot/$nchan &>/dev/null &");
}
?>
