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
function unscript($text) {
  return preg_replace('#<script(.*?)>(.+?)</script>#','',html_entity_decode($text));
}
function unhook($text) {
  return preg_replace("/['\"](.*)?['\"];?.+$/",'',unscript($text));
}
?>
