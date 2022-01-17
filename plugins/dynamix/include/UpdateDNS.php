<?PHP
/* Copyright 2005-2022, Lime Technology
 * Copyright 2012-2022, Bergware International.
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

// add 'ipaddr' function for 6.9 backwards compatibility
if (!function_exists('ipaddr')) {
  function ipaddr($ethX='eth0', $prot=4) {
    global $$ethX;
    switch ($$ethX['PROTOCOL:0']) {
    case 'ipv4':
      return $$ethX['IPADDR:0'];
    case 'ipv6':
      return $$ethX['IPADDR6:0'];
    case 'ipv4+ipv6':
      switch ($prot) {
      case 4: return $$ethX['IPADDR:0'];
      case 6: return $$ethX['IPADDR6:0'];
      default:return [$$ethX['IPADDR:0'],$$ethX['IPADDR6:0']];}
    default:
      return $$ethX['IPADDR:0'];
    }
  }
}

function host_lookup_ip($host) {
  $result = @dns_get_record($host, DNS_A);
  $ip = ($result) ? $result[0]['ip'] : '';
  return($ip);
}
function format_port($port) {
  return ($port != 80 && $port != 443) ? ':'.$port : '';
}
function anonymize_host($host) {
  global $anon;
  if ($anon) {
    $host = preg_replace('/.*\.myunraid\.net/', '*.hash.myunraid.net', $host);
    $host = preg_replace('/.*\.unraid\.net/', 'hash.unraid.net', $host);
  }
  return $host;
}
function anonymize_ip($ip) {
  global $anon;
  if ($anon && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
    $ip = "[redacted]";
  }
  return $ip;
}
function generate_internal_host($host, $ip) {
  if (strpos($host,'.myunraid.net') !== false) {
    $host = str_replace('*', str_replace('.', '-', $ip), $host);
  }
  return $host;
}
function generate_external_host($host, $ip) {
  if (strpos($host,'.myunraid.net') !== false) {
    $host = str_replace('*', str_replace('.', '-', $ip), $host);
  } elseif (strpos($host,'.unraid.net') !== false) {
    $host = "www.".$host;
  }
  return $host;
}
function verbose_output($httpcode, $result) {
  global $cli, $verbose, $anon, $post, $var, $certhostname, $isRegistered, $rebindDisabled, $remote, $restartNginx;
  global $remoteaccess;
  global $icon_warn, $icon_ok;
  if (!$cli || !$verbose) return;
  
  if ($anon) echo "(Output is anonymized, use '-vv' to see full details)".PHP_EOL;
  echo "Unraid OS {$var['version']}".((strpos($post['plgversion'], "base-") === false) ? " with My Servers plugin version {$post['plgversion']}" : '').PHP_EOL;
  echo ($isRegistered) ? "{$icon_ok}Signed in to Unraid.net as {$remote['username']}".PHP_EOL : "{$icon_warn}Not signed in to Unraid.net".PHP_EOL ;
  echo "Use SSL is {$var['USE_SSL']}".PHP_EOL;
  echo ($rebindDisabled) ? "{$icon_ok}Rebind protection is disabled".PHP_EOL : "{$icon_warn}Rebind protection is enabled".PHP_EOL;
  if ($post) {
    $wanip = trim(@file_get_contents("https://wanip4.unraid.net/"));
    // check the data
    if ($certhostname) {
      $certhostip = host_lookup_ip(generate_internal_host($certhostname, $post['internalip']));
      $certhosterr = ($certhostip != $post['internalip']);
    }
    if ($post['internalhostname'] != $certhostname) {
      $internalhostip = host_lookup_ip(generate_internal_host($post['internalhostname'], $post['internalip']));
      $internalhosterr = ($internalhostip != $post['internalip']);
    }
    if ($post['externalhostname']) {
      $externalhostip = host_lookup_ip(generate_external_host($post['externalhostname'], $wanip));
      $externalhosterr = ($externalhostip != $wanip);
    }
    // anonymize data. no caclulations can be done with this data beyond this point.
    if ($anon) {
      if ($certhostip) $certhostip = anonymize_ip($certhostip);
      if ($certhostname) $certhostname = anonymize_host($certhostname);
      if ($internalhostip) $internalhostip = anonymize_ip($internalhostip);
      if ($externalhostip) $externalhostip = anonymize_ip($externalhostip);
      if ($wanip) $wanip = anonymize_ip($wanip);
      if ($post['internalip']) $post['internalip'] = anonymize_ip($post['internalip']);
      if ($post['internalhostname']) $post['internalhostname'] = anonymize_host($post['internalhostname']);
      if ($post['externalhostname']) $post['externalhostname'] = anonymize_host($post['externalhostname']);
      if ($post['externalport']) $post['externalport'] = "[redacted]";
    }
    // output notes
    if ($post['internalprotocol'] && $post['internalhostname'] && $post['internalport']) {
      $localurl = $post['internalprotocol']."://".generate_internal_host($post['internalhostname'], $post['internalip']).format_port($post['internalport']);
      echo 'Local Access url: '.$localurl.PHP_EOL;
      if ($internalhostip) {
        // $internalhostip will not be defined for .local domains, ok to skip
        echo ($internalhosterr) ? $icon_warn : $icon_ok;
        echo generate_internal_host($post['internalhostname'], $post['internalip'])." resolves to {$internalhostip}";
        echo ($internalhosterr) ? ", it should resolve to {$post['internalip']}" : "";
        echo PHP_EOL;
      }
      if ($certhostname) {
        echo ($certhosterr) ? $icon_warn : $icon_ok;
        echo generate_internal_host($certhostname, $post['internalip']).' ';
        echo ($certhostip) ? "resolves to {$certhostip}" : "does not resolve to an IP address";
        echo ($certhosterr) ? ", it should resolve to {$post['internalip']}" : "";
        echo PHP_EOL;
      }
      if ($remoteaccess == 'yes' && $post['externalprotocol'] && $post['externalhostname'] && $post['externalport']) {
        $remoteurl = $post['externalprotocol']."://".generate_external_host($post['externalhostname'], $wanip).format_port($post['externalport']);
        echo 'Remote Access url: '.$remoteurl.PHP_EOL;
        echo ($externalhosterr) ? $icon_warn : $icon_ok;
        echo generate_external_host($post['externalhostname'], $wanip).' ';
        echo ($externalhosterr) ? "does not resolve to an IP address" : "resolves to {$externalhostip}";
        echo PHP_EOL;
      }
      if ($restartNginx) {
        echo "IP address changes were detected, nginx was reloaded".PHP_EOL;
      } else {
        echo "IP address changes were not detected, nginx was not reloaded".PHP_EOL;
      }
    }
    // output post data
    if ($post['keyfile']) $post['keyfile'] = "[redacted]";
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
$ver = parse_ini_file("/etc/unraid-version");
extract(parse_ini_file('/var/local/emhttp/network.ini',true));
$nginx = parse_ini_file('/var/local/nginx/state.ini');
$restartNginx = false;
// *******************
// fake a few values (remove this when state file is fully populated)
if ($nginx['LANFQDN']) {
  if (!$nginx['CERTNAME']) {
    $nginx['CERTNAME']=preg_replace('/([^\.]*)\.(.*)/', "*.$2", $nginx['LANFQDN']);
  }
  if (!$nginx['CERTPATH']) {
    if (preg_match('/.*unraid\.net$/', $nginx['CERTNAME'])) {
      $nginx['CERTPATH']='/boot/config/ssl/certs/certificate_bundle.pem';
    } else {
      $nginx['CERTPATH']="/boot/config/ssl/certs/{$var['NAME']}_unraid_bundle.pem";
    }
  }
}
if (!$nginx['LANIP']) {
  $nginx['LANIP'] = ipaddr();
}
if (!$nginx['WANIP']) {
  $nginx['WANIP'] = trim(@file_get_contents("https://wanip4.unraid.net/"));
}
// *******************

$dnserr = false;
$icon_warn = "⚠️  ";
$icon_ok = "✅  ";

// parse myservers.cfg
if (file_exists('/boot/config/plugins/dynamix.my.servers/myservers.cfg')) {
  @extract(parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true));
}
if (empty($remote)) {
  $remote = [
    "apikey" => "",
    "wanaccess" => "no",
    "wanport" => "443"
  ];
}
if (empty($remote['wanport'])) {
  $remote['wanport'] = 443;
}
// remoteaccess, externalport
if ($cli) {
  $remoteaccess = $remote['wanaccess'];
  $externalport = $remote['wanport'];
} else {
  $remoteaccess = $_POST['remoteaccess'];
  $externalport = intval($_POST['externalport']);

  if ($remoteaccess != 'yes') {
    $remoteaccess = 'no';
  }

  if ($externalport < 1 || $externalport > 65535) {
    $externalport = 443;
  }

  if ($remote['wanaccess'] != $remoteaccess) {
    // update the wanaccess ini value
    $orig = @parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true);
    if ($orig === false) {
      $orig = ['remote' => $remote];
    }
    $orig['remote']['wanaccess'] = $remoteaccess;
    $text = '';
    foreach ($orig as $section => $block) {
      $pairs = "";
      foreach ($block as $key => $value) if (strlen($value)) $pairs .= "$key=\"$value\"\n";
      if ($pairs) $text .= "[$section]\n".$pairs;
    }
    if ($text) file_put_contents('/boot/config/plugins/dynamix.my.servers/myservers.cfg', $text);
    // need nginx reload
    $restartNginx = true;
  }
}
$isRegistered = !empty($remote) && !empty($remote['username']);

// protocols, hostnames, ports
$internalprotocol = 'http';
$internalport = $var['PORT'];
$internalhostname = $nginx['LANMDNS'];
$externalprotocol = 'https';
// keyserver will expand *.hash.myunraid.net or add www to hash.unraid.net as needed
$externalhostname = $nginx['CERTNAME'];
$isLegacyCert = preg_match('/.*\.unraid\.net$/', $nginx['CERTNAME']);
$isWildcardCert = preg_match('/.*\.myunraid\.net$/', $nginx['CERTNAME']);

// if not signed in, or if certs were deleted after configuring remote access, disable
if (!$isRegistered || (!$isLegacyCert && !$isWildcardCert) || $remoteaccess != 'yes') $remoteaccess = 'no';

if ($var['USE_SSL']=='yes') {
  // certificate assumed to exist
  // the subject of the certificate must match $var['NAME'].$var['LOCAL_TLD']
  $internalprotocol = 'https';
  $internalport = $var['PORTSSL'];
}
if ($var['USE_SSL']=='auto') {
  // certificate assumed to exist
  $internalprotocol = 'https';
  $internalport = $var['PORTSSL'];
  // keyserver will expand *.hash.myunraid.net as needed
  $internalhostname = $nginx['CERTNAME'];
}

// only proceed when when signed in or when legacy unraid.net SSL certificate exists
if (!$isRegistered && !$isLegacyCert) {
    response_complete(406, '{"error":"'._('Nothing to do').'"}');
}

// keyfile
$keyfile = @file_get_contents($var['regFILE']);
if ($keyfile === false) {
  response_complete(406, '{"error":"'._('Registration key required').'"}');
}
$keyfile = @base64_encode($keyfile);

// internalip
$ethX       = 'eth0';
$internalip = ipaddr($ethX);

// My Servers version
$plgversion = file_exists("/var/log/plugins/dynamix.unraid.net.plg") ? trim(@exec('/usr/local/sbin/plugin version /var/log/plugins/dynamix.unraid.net.plg 2>/dev/null'))
  : file_exists("/var/log/plugins/dynamix.unraid.net.staging.plg") ? trim(@exec('/usr/local/sbin/plugin version /var/log/plugins/dynamix.unraid.net.staging.plg 2>/dev/null'))
  : 'base-'.$var['version'];

// DNS Rebind Protection
$rebindDisabled = (host_lookup_ip("rebindtest.unraid.net") == "192.168.42.42");

// build post array
$post = [
  'keyfile' => $keyfile,
  'plgversion' => $plgversion
];
if ($isLegacyCert) {
  // sign in not required to maintain local ddns for unraid.net cert
  // enable local ddns regardless of use_ssl value
  $post['internalip'] = $internalip;
  // if DNS Rebind Protection is disabled and host.unraid.net does not resolve to the internalip, disable caching
  if ($rebindDisabled && host_lookup_ip(generate_internal_host($certhostname, $post['internalip'])) != $post['internalip']) $dnserr = true;
}
if ($isRegistered) {
  // if signed in, send data needed to maintain My Servers Dashboard
  $post['internalhostname'] = $internalhostname;
  $post['internalport'] = $internalport;
  $post['internalprotocol'] = $internalprotocol;
  $post['remoteaccess'] = $remoteaccess;
  $post['servercomment'] = $var['COMMENT'];
  $post['servername'] = $var['NAME'];
  if ($isWildcardCert) {
    // keyserver needs the internalip to generate the local access url
    $post['internalip'] = $internalip;
  }
  if ($remoteaccess == 'yes') {
    // include wanip in the cache file so we can track if it changes
    $post['_wanip'] = trim(@file_get_contents("https://wanip4.unraid.net/"));
    $post['externalhostname'] = $externalhostname;
    $post['externalport'] = $externalport;
    $post['externalprotocol'] = $externalprotocol;
    // if wanip.hash.myunraid.net or www.hash.unraid.net does not resolve to the wanip, disable caching
    if (host_lookup_ip(generate_external_host($post['externalhostname'], $post['_wanip'])) != $post['_wanip']) $dnserr = true;
  }
}

// if WANIP has changed since nginx started, need reload
if ($post['_wanip'] && ($post['_wanip'] != $nginx['WANIP'])) $restartNginx = true;
if (version_compare($ver['version'],"6.10.0-rc2","<=")) $restartNginx = false;
if ($restartNginx) {
  // we actually use 'reload'
  exec("/etc/rc.d/rc.nginx reload &>/dev/null");
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

// do not submit the wanip, it will be captured from the submission if needed for remote access
unset($post['_wanip']);

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
