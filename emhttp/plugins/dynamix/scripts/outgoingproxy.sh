#!/usr/bin/php
<?php
/* Copyright 2024, Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */

$opmPlugin	= "dynamix";
require_once("plugins/".$opmPlugin."/include/OutgoingProxyLib.php");

/* Save settings and update config. */
function apply() {
	global $opmPlugin, $plg_config_file, $proxy_config_file;

	/* Process the new configuration. */
	$cfg = parse_plugin_config();

	for ($i = 1; $i <= 3; $i++) {
		$proxy_name	= "proxy_name_".$i;
		$name		= trim($cfg[$proxy_name]);
		$proxy_url	= "proxy_url_".$i;
		$url		= trim($cfg[$proxy_url]);
		$proxy_user	= "proxy_user_".$i;
		$proxy_pass	= "proxy_pass_".$i;
		if (($name) && ($url)) {
			/* Confirm the url is in the proper format. */
			if (strpos($url, 'http://') !== false && preg_match('/:\d+$/', $url)) {
				/* The string contains 'http://' and a port designation at the end */

				/* Parse the URL components. */
				$urlComponents = parse_url($url);

				/* Replace user and password in the url. */
				$host = isset($urlComponents['host']) ? $urlComponents['host'] : '';
				$port = isset($urlComponents['port']) ? $urlComponents['port'] : '';
				$user = isset($urlComponents['user']) ? $urlComponents['user'] : '';
				$pass = isset($urlComponents['pass']) ? $urlComponents['pass'] : '';

				/* Remove credentials from the entered URL. */
				$cfg[$proxy_url]	= "http://".$host.':'.$port;

				/* Use the entered user if not blank. */
				$cfg_user			= $cfg[$proxy_user] ?? "";
				$cfg[$proxy_user]	= $cfg_user ? $cfg_user : urldecode($user);
				$encodedUser		= (strpos($cfg[$proxy_user], '%') === false) ? urlencode($cfg[$proxy_user]) : $cfg[$proxy_user];

				/* Use the entered pass if not blank. */
				$cfg_pass			= $cfg[$proxy_pass] ?? "";
				$cfg[$proxy_pass]	= $cfg_pass ? $cfg_pass : urldecode($pass);
				$encodedPass		= (strpos($cfg[$proxy_pass], '%') === false) ? urlencode($cfg[$proxy_pass]) : $cfg[$proxy_pass];
				$cfg[$proxy_pass]	= encrypt_data($cfg[$proxy_pass]);
			} else {
				/* The string does not contain 'http://' and/or a port designation at the end */
				$cfg[$proxy_url]	= "";
			}
		} else if (! $name) {
			$cfg[$proxy_url]	= "";
		}
	}
	
	/* Rewrite config file. */
	/* Convert the array to an INI string. */
	$iniString = '';
	foreach ($cfg as $key => $value) {
		$iniString .= "$key=\"$value\"\n";
	}

	/* Write the INI string to the plugin config file. */
	file_put_contents($plg_config_file, $iniString);

	/* Create the proxy file for the set_proxy script. */
	exec("/plugins/".$opmPlugin."/create_proxy.sh");

	/* Let things settle. */
	sleep(1);

	/* Now run the proxy setup script. */
	if (is_executable("/usr/local/sbin/set_proxy")) {
		exec("at -M -f /usr/local/sbin/set_proxy now 2>/dev/null");

		outgoingproxy_log("'set_proxy' script executed");
	} else {
		outgoingproxy_log("'set_proxy' script does not exist");
	}
}

/* Main entry point, */
switch ($argv[1]) {
	case 'apply':
		apply();
		break;

	default:
		echo("Error: 'outgoingproxy.sh {$argv[1]}' not understood\n");
		echo("outgoingproxy.sh usage: 'apply'\n");
		exit(0);
		break;
}
?>
