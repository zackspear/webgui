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
$docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "$docroot/webGui/include/Markdown.php";
require_once "$docroot/webGui/include/Wrappers.php";

$webgui = parse_plugin_cfg('dynamix',true);
?>
<!DOCTYPE HTML>
<html>
<head>
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-<?=$webgui['display']['theme']?>.css">
</head>
<body style="margin:14px 10px;font-size:12px">
<?
$file = $_GET['file'];
if (file_exists($file) && strpos(realpath($file), '/tmp/plugins/') === 0 && substr($file, -4) == '.txt') echo Markdown(file_get_contents($file)); else echo Markdown("*No release notes available!*");
?>
<br><div style="text-align:center"><input type="button" value="Done" onclick="top.Shadowbox.close()"></div>
</body>
</html>
