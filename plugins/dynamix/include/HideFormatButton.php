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
$hide = '/var/tmp/hide_format_button.tmp';
$flag = file_exists($hide);

switch ($_POST['hide']) {
  case 'yes': if (!$flag) touch($hide); break;
  case 'no' : if ($flag) unlink($hide); break;
}
?>
