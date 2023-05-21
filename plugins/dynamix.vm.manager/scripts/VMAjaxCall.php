#!/usr/bin/php -q
<?PHP
/* Copyright 2005-2023, Lime Technology
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
require_once "$docroot/webGui/include/Wrappers.php";
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/plugins/dynamix.vm.manager/include/libvirt_helpers.php";
function write(...$messages){
  $com = curl_init();
  curl_setopt_array($com,[
    CURLOPT_URL => 'http://localhost/pub/plugins?buffer_length=1',
    CURLOPT_UNIX_SOCKET_PATH => '/var/run/nginx.socket',
    CURLOPT_POST => 1,
    CURLOPT_RETURNTRANSFER => true
  ]);
  foreach ($messages as $message) {
    curl_setopt($com, CURLOPT_POSTFIELDS, $message);
    curl_exec($com);
  }
  curl_close($com);
}
function execCommand_nchan($command) {
	$waitID = mt_rand();
	[$cmd,$args] = explode(' ',$command,2);
	write("<p class='logLine'></p>","addLog\0<fieldset class='docker'><legend>"._('Command execution')."</legend>".basename($cmd).' '.str_replace(" -","<br>&nbsp;&nbsp;-",htmlspecialchars($args))."<br><span id='wait-$waitID'>"._('Please wait')." </span><p class='logLine'></p></fieldset>","show_Wait\0$waitID");
	$proc = popen("$command 2>&1",'r');
	while ($out = fgets($proc)) {
	  $out = preg_replace("%[\t\n\x0B\f\r]+%", '',$out);
	  write("addLog\0".htmlspecialchars($out));
	}
	$retval = pclose($proc);
	$out = $retval ? _('The command failed').'.' : _('The command finished successfully').'!';
	write("stop_Wait\0$waitID","addLog\0<br><b>$out</b>");
	return $retval===0;
  }

#{action:"snap-", uuid:uuid , snapshotname:target , remove:remove, free:free ,removemeta:removemeta ,keep:keep, desc:desc}
$url = rawurldecode($argv[1]??'');
$waitID = "bob" ;
$style = ["<style>"];
$style[] = ".logLine{font-family:bitstream!important;font-size:1.2rem!important;margin:0;padding:0}";
$style[] = "fieldset.docker{border:solid thin;margin-top:8px}";
$style[] = "legend{font-size:1.1rem!important;font-weight:bold}";
$style[] = "</style>";
$path = "--current" ; $pivot = "yes" ; $action = " " ;
write(implode($style)."<p class='logLine'></p>");
write("<p class='logLine'></p>","addLog\0<fieldset class='docker'><legend>"._('Block Commit').": ".htmlspecialchars($path)."</legend><p class='logLine'></p><span id='wait-$waitID'>"._('Please wait')." </span></fieldset>","show_Wait\0$waitID");

foreach (explode('&', $url) as $chunk) {
    $param = explode("=", $chunk);
    if ($param) {
#        write("addLog\0Parm" . sprintf("Value for parameter \"%s\" is \"%s\"<br/>\n", urldecode($param[0]), urldecode($param[1])));
		${urldecode($param[0])} = urldecode($param[1]) ;
    }
}

write("VMName $name ");
write("SNAP $snapshotname ");
write("Base $targetbase ");
write("Top $targettop ");
$path = "--current" ; $pivot = "yes" ; $action = " " ;
vm_blockcommit($name,$snapshotname,$path,$targetbase,$targettop,$pivot,$action) ;
#execCommand_nchan("ls /root") ;
write('_DONE_','');

?>