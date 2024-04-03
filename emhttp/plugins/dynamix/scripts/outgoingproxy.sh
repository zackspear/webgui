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
	global $opmPlugin, $proxy_config_file;

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
				$proxy_user	= "proxy_user_".$i;
				$proxy_pass	= "proxy_pass_".$i;

				/* Parse the URL components. */
				$urlComponents = parse_url($url);

				/* Replace user and password in the url. */
				$host = isset($urlComponents['host']) ? $urlComponents['host'] : '';
				$port = isset($urlComponents['port']) ? $urlComponents['port'] : '';
				$user = isset($urlComponents['user']) ? $urlComponents['user'] : '';
				$pass = isset($urlComponents['pass']) ? $urlComponents['pass'] : '';

				/* Use the entered user if not blank. */
				$cfg_user		= $cfg[$proxy_user] ?? "";
				$user			= $cfg_user ? $cfg_user : $user;
				$encodedUser	= (strpos($user, '%') === false) ? urlencode($user) : $user;

				/* Use the entered pass if not blank. */
				$cfg_pass		= $cfg[$proxy_pass] ?? "";
				$pass 			= $cfg_pass ? $cfg_pass : $pass;
				$encodedPass	= (strpos($pass, '%') === false) ? urlencode($pass) : $pass;

				/* Reconstruct the URL with new credentials. */
				if (($host) && ($port)) {
					$constructedUrl = 'http://';
					if (($encodedUser) && ($encodedPass)) {
						$constructedUrl .= $encodedUser.':'.$encodedPass.'@';
					}
					$constructedUrl .= $host.':'.$port;
				} else {
					$constructedUrl	= "";
				}
			} else {
				/* The string does not contain 'http://' and/or a port designation at the end */
				$constructedUrl	= "";
			}

			/* Save the constructed url. */
			$cfg[$proxy_url]	= $constructedUrl;
		} else if (! $name) {
			$cfg[$proxy_url]	= "";
		}

		/* Remove user and pass from the configuration file. */
		unset($cfg[$proxy_user]);
		unset($cfg[$proxy_pass]);
	}

	/* Rewrite config file. */
	/* Convert the array to an INI string. */
	$iniString = '';
	foreach ($cfg as $key => $value) {
		$iniString .= "$key=\"$value\"\n";
	}

	/* Write the INI string to a file. */
	file_put_contents($proxy_config_file, $iniString);

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
