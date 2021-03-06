<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2012-2020, Bergware International.
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

require_once "$docroot/webGui/include/Helpers.php";
/* add translations */
$_SERVER['REQUEST_URI'] = '';
require_once "$docroot/webGui/include/Translations.php";

$var = (array)parse_ini_file('state/var.ini');
$license_state = strtoupper(empty($var['regCheck']) ? $var['regTy'] : $var['regCheck']);
$key_contents = str_replace(['+','/','='], ['-','_',''], trim(base64_encode(@file_get_contents($var['regFILE']))));
if (file_exists('/boot/config/plugins/dynamix.my.servers/myservers.cfg')) {
  @extract(parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true));
}

$arr = [];
if (empty($remote['apikey']) || empty($var['regFILE']) || empty($remote['username']) || empty($remote['avatar'])) {
  $arr['registered'] = 0;
  $arr['username'] = '';
  $arr['avatar'] = '';
  $arr['sendCrashInfo'] = 0;
} else {
  $arr['registered'] = 1;
  $arr['username'] = $remote['username'];
  $arr['avatar'] = $remote['avatar'];
  $arr['sendCrashInfo'] = $remote['sendCrashInfo'] || 'no';
}
$arr['event'] = 'STATE';
$arr['ts'] = time();
$arr['deviceCount'] = $var['deviceCount'];
$arr['guid'] = $var['flashGUID'];
$arr['regGuid'] = $var['regGUID'];
$arr['state'] = $license_state;
$arr['keyfile'] = $key_contents;
$arr['reggen'] = $var['regGen'];
$arr['flashproduct'] = $var['flashProduct'];
$arr['flashvendor'] = $var['flashVendor'];
$arr['servername'] = $var['NAME'];
$arr['serverip'] = $_SERVER['SERVER_ADDR'];
$arr['internalip'] = $_SERVER['SERVER_ADDR'];
$arr['internalport'] = $_SERVER['SERVER_PORT'];
$arr['protocol'] = $_SERVER['REQUEST_SCHEME'];
$arr['locale'] = $_SESSION['locale'] ? $_SESSION['locale'] : 'en_US';

echo json_encode($arr);
?>
