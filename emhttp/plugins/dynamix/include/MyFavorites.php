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
$permit = ['del','add'];
$action = $_POST['action']??'';
$page = glob("$docroot/plugins/*/{$_POST['page']}.page",GLOB_NOSORT)[0];
// validate input
if (!$page || !in_array($action,$permit)) exit;

$file = fopen($page,'r');
// get current Menu settings
extract(parse_ini_string(fgets($file)));
fclose($file);

$Menu = str_replace(' MyFavorites','',$Menu);
switch ($action) {
case $permit[0]:
  break;
case $permit[1]:
  $Menu .= ' MyFavorites';
  break;
}
// update Menu settings
exec("sed -ri '0,/^Menu=\".+\"$/s//Menu=\"$Menu\"/' $page");
