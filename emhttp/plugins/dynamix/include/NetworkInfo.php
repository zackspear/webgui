<?PHP
/* Copyright 2005-2025, Lime Technology
 * Copyright 2012-2025, Bergware International.
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
// add translations
$_SERVER['REQUEST_URI'] = 'settings';
require_once "$docroot/webGui/include/Translations.php";

function port($eth) {
  $sys = "/sys/class/net";
  $x = preg_replace('/[^0-9]/','',$eth);
  return file_exists("$sys/br{$x}") ? "br${x}" : (file_exists("$sys/bond{$x}") ? "bond{$x}" : "eth{$x}");
}
exec("grep -Po 'nameserver \K.*' /etc/resolv.conf",$dns);
$eth    = $_POST['port'];
$vlan   = $_POST['vlan'];
$port   = port($eth).($vlan ? ".$vlan" : "");
$v6on   = @file_get_contents("/proc/sys/net/ipv6/conf/$port/disable_ipv6")!=1;
$none   = _('None');
$error  = "<span class='red-text'>"._('Missing')."</span>";
$note   = $eth=='eth0' && !$vlan ? $error : $none;
$link   = _(ucfirst(exec("ethtool $eth | awk '$1==\"Link\" {print $3;exit}'")))." (".exec("ethtool $eth | grep -Pom1 '^\s+Port: \K.*'").")";
$speed  = _(preg_replace(['/^(\d+)/','/!/'],['$1 ',''],exec("ethtool $eth | awk '$1==\"Speed:\" {print $2;exit}'")));
$ipv4   = exec("ip -4 -br addr show scope global $port | awk '{\$1=\$2=\"\";print;exit}' | sed -r 's/ metric [0-9]+//g; s/\/[0-9]+//g' | xargs") ?: $note;
$gw4    = exec("ip -4 route show default dev $port | awk '{print \$3;exit}'") ?: $note;
$dns4   = implode(' ',array_filter($dns,function($ns){return strpos($ns,':')===false;})) ?: $error;
$domain = exec("grep -Pom1 'domain \K.*' /etc/resolv.conf") ?: '---';

if ($v6on) {
  $ipv6 = exec("ip -6 -br addr show scope global $port | awk '{\$1=\$2=\"\";print;exit}' | sed -r 's/ metric [0-9]+//g; s/\/[0-9]+//g' | xargs") ?: $note;
  $gw6  = exec("ip -6 route show default dev $port | awk '{print \$3;exit}'") ?: $note;
  $dns6 = implode(' ',array_filter($dns,function($ns){return strpos($ns,':')!==false;})) ?: $error;
}

echo "<table style='text-align:left;font-size:1.2rem'>";
echo "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
echo "<tr><td>"._('Link detected').":</td><td>$link</td></tr>";
echo "<tr><td>"._('Interface speed').":</td><td>$speed</td></tr>";
echo "<tr><td>"._('IPv4 address').":</td><td>$ipv4</td></tr>";
echo "<tr><td>"._('IPv4 default gateway').":</td><td>$gw4</td></tr>";
echo "<tr><td>"._('IPv4 DNS servers').":</td><td>$dns4</td></tr>";
if ($v6on) {
  echo "<tr><td>"._('IPv6 address').":</td><td>$ipv6</td></tr>";
  echo "<tr><td>"._('IPv6 default gateway').":</td><td>$gw6</td></tr>";
  echo "<tr><td>"._('IPv6 DNS servers').":</td><td>$dns6</td></tr>";
}
echo "<tr><td>"._('Domain name').":</td><td>$domain</td></tr>";
echo "</table>";
?>
