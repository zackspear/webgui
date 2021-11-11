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
// add translations
$_SERVER['REQUEST_URI'] = 'settings';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/webGui/include/Helpers.php";

function host_lookup_ip($host) {
  $result = dns_get_record($host, DNS_A);
  $ip = $result[0]['ip'];
  return($ip);
}
function format_port($port) {
  return ($port != 80 && $port != 443) ? ':'.$port : '';
}
function anonymize_host($host) {
  global $anon;
  return ($anon) ? preg_replace('/.*\.unraid\.net/', 'hash.unraid.net', $host) : $host;
}
function anonymize_ip($ip) {
  global $anon;
  if ($anon && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
    $ip = "[redacted]";
  }
  return $ip;
}
function verbose_output($httpcode, $result) {
  global $cli, $verbose, $anon, $post, $var, $certhostname, $isRegistered, $isCertUnraidNet, $rebindDisabled, $remote;
  global $icon_warn, $icon_ok;
  if (!$cli || !$verbose) return;
  
  if ($anon) echo "(Output is anonymized, use '-vv' to see full details)".PHP_EOL;
  echo "Unraid OS {$var['version']}".PHP_EOL;
  echo ($isRegistered) ? "{$icon_ok}Signed in to Unraid.net as {$remote['username']}".PHP_EOL : "{$icon_warn}Not signed in to Unraid.net".PHP_EOL ;
  echo "Use SSL is {$var['USE_SSL']}".PHP_EOL;
  echo ($rebindDisabled) ? "{$icon_ok}Rebind protection is disabled".PHP_EOL : "{$icon_warn}Rebind protection is enabled".PHP_EOL;
  if ($post) {
    // check the data
    if ($certhostname) {
      $certhostip = host_lookup_ip($certhostname);
      $certhosterr = ($certhostip != $post['internalip']);
    }
    if ($post['internalhostname'] != $certhostname) {
      $internalhostip = host_lookup_ip($post['internalhostname']);
      $internalhosterr = ($internalhostip != $post['internalip']);
    }
    // anonymize data
    if ($anon) {
      if ($certhostip) $certhostip = anonymize_ip($certhostip);
      if ($certhostname) $certhostname = anonymize_host($certhostname);
      if ($internalhostip) $internalhostip = anonymize_ip($internalhostip);
      if ($post['internalip']) $post['internalip'] = anonymize_ip($post['internalip']);
      if ($post['internalhostname']) $post['internalhostname'] = anonymize_host($post['internalhostname']);
    }
    // output notes
    if ($post['internalprotocol'] && $post['internalhostname'] && $post['internalport']) {
      $localurl = $post['internalprotocol']."://".$post['internalhostname'].format_port($post['internalport']);
      echo 'Local Access url: '.$localurl.PHP_EOL;
      if ($internalhostip) {
        // $internalhostip will not be defined for .local domains, ok to skip
        echo ($internalhosterr) ? $icon_warn : $icon_ok;
        echo "{$post['internalhostname']} resolves to {$internalhostip}";
        echo ($internalhosterr) ? ", it should resolve to {$post['internalip']}" : "";
        echo PHP_EOL;
      }
      if ($certhostname) {
        echo ($certhosterr) ? $icon_warn : $icon_ok;
        echo "{$certhostname} ";
        echo ($certhostip) ? "resolves to {$certhostip}" : "does not resolve to an IP address";
        echo ($certhosterr) ? ", it should resolve to {$post['internalip']}" : "";
        echo PHP_EOL;
      }
    }
    // output post data
    if ($post['keyfile']) $post['keyfile'] = substr($post['keyfile'], 0, 5)."...";
    echo PHP_EOL.'Request:'.PHP_EOL;
    echo @json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
  }
  if ($result) {
    echo "Response (HTTP $httpcode):".PHP_EOL;
    echo @json_encode(@json_decode($result, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
  }
}

function response_complete($httpcode, $result, $cli_success_msg='') {
  global $cli, $verbose;
  if ($cli) {
    if ($verbose) verbose_output($httpcode, $result);
    $json = @json_decode($result,true);
    if (!empty($json['error'])) {
      echo 'Error: '.$json['error'].PHP_EOL;
      exit(1);
    }
    exit($cli_success_msg.PHP_EOL);
  }
  header('Content-Type: application/json');
  http_response_code($httpcode);
  exit((string)$result);
}

$cli = php_sapi_name()=='cli';
$verbose = $anon = false;
if ($cli && $argv[1] == "-v") {
  $verbose = true;
  $anon = true;
}
if ($cli && $argv[1] == "-vv") {
  $verbose = true;
}
$var = parse_ini_file('/var/local/emhttp/var.ini');
$dnserr = false;
$icon_warn = "⚠️  ";
$icon_ok = "✅  ";

// parse myservers.cfg
if (file_exists('/boot/config/plugins/dynamix.my.servers/myservers.cfg')) {
  @extract(parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true));
}
$remoteaccess = 'no';
$isRegistered = !empty($remote) && !empty($remote['username']);

$certpath = '/boot/config/ssl/certs/certificate_bundle.pem';
$hasCert = file_exists($certpath);
$certhostname = $hasCert ? trim(exec("/usr/bin/openssl x509 -subject -noout -in $certpath | awk -F' = ' '{print $2}'")) : '';
// handle wildcard certs
$certhostname = str_replace('*', $var['NAME'], $certhostname);
$isCertUnraidNet = preg_match('/.*\.unraid\.net$/', $certhostname);

// protocols, hostnames, ports
$internalprotocol = 'http';
$internalport = $var['PORT'];
$internalhostname = $var['NAME'] . (empty($var['LOCAL_TLD']) ? '' : '.'.$var['LOCAL_TLD']);

if ($var['USE_SSL']!='no' && $hasCert) {
  $internalprotocol = 'https';
  $internalport = $var['PORTSSL'];
  $internalhostname = $certhostname;
}

// only proceed when signed in
if (!$isRegistered) {
  response_complete(406, '{"error":"'._('Nothing to do').'"}');
}

// keyfile
$keyfile = @file_get_contents($var['regFILE']);
if ($keyfile === false) {
  response_complete(406, '{"error":"'._('Registration key required').'"}');
}
$keyfile = @base64_encode($keyfile);

// internalip
extract(parse_ini_file('/var/local/emhttp/network.ini',true));
$ethX       = 'eth0';
$internalip = ipaddr($ethX);

// My Servers version
$plgversion = 'base-'.$var['version'];

// DNS Rebind Protection
$rebindDisabled = (host_lookup_ip("rebindtest.unraid.net") == "192.168.42.42");

// build post array
$post = [
  'keyfile' => $keyfile,
  'plgversion' => $plgversion
];
if ($isCertUnraidNet) {
  // if there is an unraid.net cert, enable local ddns regardless of use_ssl value
  $post['internalip'] = is_array($internalip) ? $internalip[0] : $internalip;
  // if DNS Rebind Protection is disabled and host.unraid.net does not resolve to the internalip, disable caching
  if ($rebindDisabled && (host_lookup_ip($certhostname) != $post['internalip'])) $dnserr = true;
}
if ($isRegistered) {
  $post['internalhostname'] = $internalhostname;
  $post['internalport'] = $internalport;
  $post['internalprotocol'] = $internalprotocol;
  $post['servercomment'] = $var['COMMENT'];
  $post['servername'] = $var['NAME'];
}

// maxage is 36 hours
$maxage = 36*60*60;
if ($dnserr || $verbose) $maxage = 0;
$datafile = "/tmp/UpdateDNS.txt";
$dataprev = @file_get_contents($datafile) ?: '';
$datanew = implode("\n",$post)."\n";
if ($datanew == $dataprev && (time()-filemtime($datafile) < $maxage)) {
  response_complete(204, null, _('No change to report'));
}
file_put_contents($datafile,$datanew);

// report necessary server details to limetech for DNS updates
$ch = curl_init('https://keys.lime-technology.com/account/server/register');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($result === false) {
  // delete cache file to retry submission on next run
  @unlink($datafile);
  response_complete(500, '{"error":"'.$error.'"}');
}

response_complete($httpcode, $result, _('success'));
?>
