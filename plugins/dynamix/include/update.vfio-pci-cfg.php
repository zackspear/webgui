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

$vfio = '/boot/config/vfio-pci.cfg';
$old  = is_file($vfio) ? rtrim(file_get_contents($vfio)) : '';
$new  = $_GET["cfg"]??'';

if ($old !== $new) {
  exec("cp -f $vfio $vfio.bak");
  exec("echo \"$new\" >$vfio", $output, $myreturn );
  if ($myreturn !== "0") {echo "1";}
}
?>
