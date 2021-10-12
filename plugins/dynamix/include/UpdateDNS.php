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

$cli = php_sapi_name()=='cli';
$verbose = $cli && $argv[1] == "-v";

function response_complete($httpcode, $result, $cli_success_msg='') {
  global $cli, $verbose, $post, $var, $remote, $certhostname, $isRegistered, $remoteaccess;
  if ($cli) {
    if ($verbose) {
      echo "Unraid OS {$var['version']}".PHP_EOL;
      echo ($isRegistered) ? "Signed in to Unraid.net as {$remote['username']}".PHP_EOL : 'Not signed in to Unraid.net'.PHP_EOL ;
      echo "Use SSL is {$var['USE_SSL']}".PHP_EOL;
      if ($certhostname) {
        echo host_lookup($certhostname);
        if ($remoteaccess == 'yes') {
          echo host_lookup("www.".$certhostname);
        }
      }
      echo PHP_EOL;
      if ($post) {
        $post['keyfile'] = substr($post['keyfile'], 0, 5)."...";
        echo 'Request:'.PHP_EOL;
        echo @json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
      }
      if ($result) {
        echo "Response (HTTP $httpcode):".PHP_EOL;
        echo @json_encode(@json_decode($result, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
      }
    }
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

function host_lookup($host) {
  $output = $result = null;
  if (!file_exists("/usr/bin/host")) return('');
  exec("/usr/bin/host ".escapeshellarg($host), $output, $result);
  return($output[0].PHP_EOL);
}

$var = parse_ini_file('/var/local/emhttp/var.ini');

$remoteaccess = 'no';

// remoteaccess, externalport
if (file_exists('/boot/config/plugins/dynamix.my.servers/myservers.cfg')) {
  @extract(parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true));
}
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

// build post array
$post = [
  'keyfile' => $keyfile,
  'plgversion' => $plgversion
];
if ($isCertUnraidNet) {
  $post['internalip'] = is_array($internalip) ? $internalip[0] : $internalip;
}
if ($isRegistered) {
  $post['internalhostname'] = $internalhostname;
  $post['internalport'] = $internalport;
  $post['internalprotocol'] = $internalprotocol;
  $post['servercomment'] = $var['COMMENT'];
  $post['servername'] = $var['NAME'];
}

// if remote access disabled, maxage is 36 hours. If enabled, maxage is 9 mins 45 seconds
$maxage = ($remoteaccess == 'no') ? 36*60*60 : (10*60)-15;
if ($verbose) $maxage = 0;
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
  // TBD: delete $datafile?
  response_complete(500, '{"error":"'.$error.'"}');
}

response_complete($httpcode, $result, _('success'));
?>
