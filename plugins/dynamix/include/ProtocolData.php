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

switch ($_GET['protocol']??'') {
  case 'smb': $data = parse_ini_file('state/sec.ini',true); break;
  case 'nfs': $data = parse_ini_file('state/sec_nfs.ini',true); break;
}
echo json_encode($data[unscript($_GET['name']??'')]);
?>
