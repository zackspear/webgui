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
$text = $_POST['text'] ?? '';

file_put_contents('/boot/config/ssl/certs/certificate_bundle.pem.new', $text);

//validate certificate_bundle.pem.new is for *.unraid.net before moving it over to certificate_bundle.pem
if (preg_match('/CN=([0-9a-f]{40}\.unraid\.net)$/', exec('openssl x509 -in /boot/config/ssl/certs/certificate_bundle.pem.new -subject -noout 2>&1'), $matches)) {
  // Successful cases:
  //   If unraid.net and <hash>.unraid.net both fail then the dns servers are inaccessible ==> cross-fingers and hope their browser has proper dns
  //   If unraid.net and <hash>.unraid.net both resolve ==> dns rebinding protection isn't going to be a issue
  //
  // Failure case:
  //   If unraid.net resolves but <hash>.unraid.net fails ==> dns rebinding protection is a issue
  if (count(dns_get_record('unraid.net', DNS_A)) !== count(dns_get_record($matches[1], DNS_A))) {
    http_response_code(406);
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Your router or configured DNS servers are protecting against DNS rebinding thus preventing this SSL certificate from working.  See help for more details and workarounds']);
    exit;
  }

  rename('/boot/config/ssl/certs/certificate_bundle.pem.new', '/boot/config/ssl/certs/certificate_bundle.pem');
} else {
  unlink('/boot/config/ssl/certs/certificate_bundle.pem.new');
}
?>
