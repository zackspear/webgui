<?PHP
/* Copyright 2005-2016, Lime Technology
 * Copyright 2012-2016, Bergware International.
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
// Wrapper functions
function parse_plugin_cfg($plugin, $sections=false) {
  $ram = "/usr/local/emhttp/plugins/$plugin/default.cfg";
  $rom = "/boot/config/plugins/$plugin/$plugin.cfg";
  $cfg = file_exists($ram) ? parse_ini_file($ram, $sections) : array();
  return file_exists($rom) ? array_replace_recursive($cfg, parse_ini_file($rom, $sections)) : $cfg;
}

function parse_cron_cfg($plugin, $job, $text = "") {
  $cron = "/boot/config/plugins/$plugin/$job.cron";
  if ($text) file_put_contents($cron, $text); else @unlink($cron);
  exec("/usr/local/sbin/update_cron");
}

function agent_fullname($agent, $state) {
  switch ($state) {
    case 'enabled' : return "/boot/config/plugins/dynamix/notifications/agents/$agent";
    case 'disabled': return "/boot/config/plugins/dynamix/notifications/agents-disabled/$agent";
    default        : return $agent;
  }
}

function get_plugin_attr($attr, $file) {
  exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin $attr $file", $result, $error);
  if ($error===0) return $result[0];
}

function plugin_update_available($plugin, $os=false) {
  $local  = get_plugin_attr('version', "/var/log/plugins/$plugin.plg");
  $remote = get_plugin_attr('version', "/tmp/plugins/$plugin.plg");
  if (strcmp($remote,$local)>0) {
    if ($os) return $remote;
    if (!$unraid = get_plugin_attr('unRAID', "/tmp/plugins/$plugin.plg")) return $remote;
    $server = get_plugin_attr('version', "/var/log/plugins/unRAIDServer.plg");
    if (version_compare($server, $unraid, '>=')) return $remote;
  }
}
?>
