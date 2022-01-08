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
$certFile = "/boot/config/ssl/certs/certificate_bundle.pem";
$text = $_POST['text'] ?? '';

file_put_contents("{$certFile}.new", $text);

//validate certificate_bundle.pem.new is for *.unraid.net before moving it over to certificate_bundle.pem
$certSubject = exec("/usr/bin/openssl x509 -in {$certFile}.new -subject -noout 2>&1");
$isLEcert    = preg_match('/.*\.myunraid\.net$/', $certSubject) || preg_match('/.*\.unraid\.net$/', $certSubject);
if ($isLEcert) {
  rename("{$certFile}.new", "$certFile");
  syslog(LOG_NOTICE, 'Updated *.hash.myunraid.net certificate: '.$certFile);
} else {
  unlink("{$certFile}.new");
}
?>
