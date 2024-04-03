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

$opmPlugin			= "dynamix";

/* UI config file location. */
$plg_config_file	= "/boot/config/proxy.cfg";

/* Output config file location for set_proxy script. */
$proxy_config_file = "/boot/config/proxy.cfg";

/* Outgoing Proxy Manager logging tag. */
$opm_log	= "Outgoing Proxy Manager";

/* Outgoing Proxy logging. */
function outgoingproxy_log($m) {
	global $opm_log;

	$m		= print_r($m,true);
	$m		= str_replace("\n", " ", $m);
	$m		= str_replace('"', "'", $m);
	exec("/usr/bin/logger"." ".escapeshellarg($m)." -t ".escapeshellarg($opm_log));
}

/* Parse plugin config file. */
function parse_plugin_config() {
	global $plg_config_file;

	$cfg = is_file($plg_config_file) ? @parse_ini_file($plg_config_file, true) : array();

	return($cfg);
}

/* Check to see if the proxy is online and available. */
function proxy_online($proxyUrl) {

	$rc	= true;

	if ($proxyUrl) {
		/* Initialize cURL session. */
		$ch = curl_init("http://www.msftncsi.com/ncsi.txt");

		/* Set cURL options. */
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);	/* Timeout in seconds. */
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);		/* Url is a proxy. */
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); /* Return transfer as a string. */

		/* Execute cURL request. */
		$response = curl_exec($ch);

		if ($response === false) {
			/* Proxy is not available. */
			$rc	= false;
		}

		/* Close cURL session. */
		curl_close($ch);
	}

	return ($rc);
}

/* Get the URL with the user and password parsed from the url. */
function get_url($cfg_url) {

	$return	= [
		'url' => '',
		'user' => '',
		'pass' => '',
	];

	if ($cfg_url) {
		/* Parse the URL by removing the user and password. */
		$urlComponents = parse_url($cfg_url);

		/* Parse user, password, host, and port from stored URL. */
		$host		= isset($urlComponents['host']) ? $urlComponents['host'] : '';
		$port		= isset($urlComponents['port']) ? $urlComponents['port'] : '';
		$user		= isset($urlComponents['user']) ? $urlComponents['user'] : '';
		$pass		= isset($urlComponents['pass']) ? $urlComponents['pass'] : '';

		/* Return array of url, user, and password. */
		$return['url']		= "http://".$host.':'.$port;

		/* Extract the credentials. */
		if (strpos($cfg_url, '%') !== false) {
			/* The credentials are urlencoded. */
			$return['user']		= urldecode($user);
			$return['pass']		= urldecode($pass);
		} else {
			/* The credentials are not urlencoded. */
			$return['user']		= $user;
			$return['pass']		= $pass;
		}
	}

	return($return);
}
?>
