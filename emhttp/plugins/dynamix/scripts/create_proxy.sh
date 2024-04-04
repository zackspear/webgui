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

function create_proxy($proxy_file) {

	/* Read the config file. */
	$cfg		= parse_plugin_config();

	/* Get the current active proxy. */
	$tmp['proxy_active']	= $cfg['proxy_active'];

	for ($i = 1; $i <= 3; $i++) {
		$proxy_name	= "proxy_name_".$i;
		$proxy_url	= "proxy_url_".$i;
		$proxy_user	= "proxy_user_".$i;
		$proxy_pass	= "proxy_pass_".$i;

		/* Parse the url, user, and password from the full url for proxy 2. */
		$url_array			= get_proxy_info($cfg[$proxy_url] ?? "", $cfg[$proxy_user] ?? "", $cfg['proxy_pass_2'] ?? "");
		$tmp[$proxy_name]	= $cfg[$proxy_name];
		$tmp[$proxy_url]	= $url_array['full_url'];
		$tmp[$proxy_user]	= $url_array['user'];
		$tmp[$proxy_pass]	= $url_array['pass'];

	}

	/* Convert the array to an INI string. */
	$iniString = '';
	foreach ($tmp as $key => $value) {
		$iniString .= "$key=\"$value\"\n";
	}

	/* Write the INI string to the plugin config file. */
	$directoryPath = dirname($proxy_file);

	/* Check if the directory exists. */
	if (!is_dir($directoryPath)) {
		/* Create the directory if it doesn't exist. */
		mkdir($directoryPath, 0755, true);
	}

	file_put_contents($proxy_file, $iniString);
}

/* Main entry point, */
/* Use the default proxy config file if one isn't passed to the script. */
$proxy_file		= isset($argv[1]) ? $argv[1] : $proxy_config_file;
create_proxy($proxy_file);
?>
