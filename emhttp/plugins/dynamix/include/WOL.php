<?PHP
/* Copyright 2005-2023, Lime Technology
 * Copyright 2012-2023, Bergware International.
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
global $lv;
$docroot ??= ($_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');
require_once "$docroot/webGui/include/Helpers.php";
require_once "$docroot/webGui/include/SysDriversHelpers.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php";
require_once "$docroot/plugins/dynamix.vm.manager/include/libvirt_helpers.php";
// add translations
$_SERVER['REQUEST_URI'] = 'tools';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/plugins/dynamix.docker.manager/include/DockerClient.php";
require_once "/usr/local/emhttp/plugins/dynamix.vm.manager/include/libvirt.php";
$vms = $lv->get_domains();
sort($vms,SORT_NATURAL);
foreach($vms as $vm){
  $arry['VM'][$vm]['interfaces'] = $lv->get_nic_info($vm);
  $arry['VM'][$vm]['name'] = $vm;
}

$DockerClient    = new DockerClient();
$containers      = $DockerClient->getDockerJSON("/containers/json?all=1");
foreach($containers as $ct)
  $arry['Docker'][substr($ct["Names"][0],1)] = [
      'interfaces' => ['0 '=> ['mac' => $ct["NetworkSettings"]["Networks"]["bridge"]["MacAddress"]]],
      'name' => substr($ct["Names"][0],1),
  ];

$lxc = explode("\n",shell_exec("lxc-ls -1")) ;
$lxcpath = trim(shell_exec("lxc-config lxc.lxcpath"));
foreach ($lxc as $lxcname) {
  if ($lxcname == "") continue;
  $value = explode("=",shell_exec("cat $lxcpath/$lxcname/config  | grep 'hwaddr'"));
  $arry['LXC'][$lxcname]['interfaces'][0]['mac'] = trim($value[1]);
  $arry['LXC'][$lxcname]['name'] = $lxcname;
}

if (is_file("/tmp/wol.json")) $user_mac = json_decode(file_get_contents("/tmp/wol.json"),true); else $user_mac = [];

foreach($arry as $key => $data) {
  $type=$key;
  foreach($data as $data2){
    $name=$data2['name'];
    if (isset($user_mac[$type][$name])) {
        $name=$name;
      #var_dump($name);
      $arry[$type][$name]['enable'] = $user_mac[$type][$name]['enable']? "enable" : "disabled";
      $arry[$type][$name]['user_mac'] = strtolower($user_mac[$type][$name]['user_mac']);
    } else {
      $arry[$type][$name]['enable'] = 'enable';
      $arry[$type][$name]['user_mac'] = 'None Defined';
    }
  }
}

switch ($_POST['table']) {

case 't1load':
  $arrModules = $arry;
  $init = false;
  if (is_file($sysdrvinit)) $init = file_get_contents($sysdrvinit);
  $html =  "<thead><tr><th>"._('Service')."</th><th>"._('Name')."</th><th>"._('Mac Address')."</th><th>"._('Enabled')."</th><th>"._('User Mac Address')."</th></tr></thead>";
  $html .= "<tbody>";
  ksort($arrModules);
  foreach($arrModules as $modname => $m) {
    foreach($m as $module) {  
    if ($modname == "") continue;
    if (is_file("/boot/config/modprobe.d/$modname.conf")) {
      $modprobe = file_get_contents("/boot/config/modprobe.d/$modname.conf");
      $state = strpos($modprobe, "blacklist");
      $modprobe = explode(PHP_EOL,$modprobe);
      if($state !== false) {$state = "Disabled";} else $state="Custom";
      $module['state'] = $state;
      $module['modprobe'] = $modprobe;
    } else {
      if (is_file("/etc/modprobe.d/$modname.conf")) {
        $modprobe = file_get_contents("/etc/modprobe.d/$modname.conf");
        $state = strpos($modprobe, "blacklist");
        $modprobe = explode(PHP_EOL,$modprobe);
        if($state !== false) {$state = "Disabled";} else $state="System";
        $module['state'] = $state;
        $module['modprobe'] = $modprobe;
      }
    }
    $html .=  "<tr id='row$modname'>";
    if ($supportpage) {
      if ($module['support'] == false) {
        $supporthtml = "";
      } else {
        $supporturl = $module['supporturl'];
        $pluginname = $module['plugin'];
        $supporthtml = "<span id='link$modname'><a href='$supporturl' target='_blank'><i title='"._("Support page $pluginname")."' class='fa fa-phone-square'></i></a></span>";
      }
    }
   if (!empty($module["version"])) $version = " (".$module["version"].")"; else $version = "";
   $macs = "";
   foreach($module['interfaces'] as $intdetail)
   {
    $macs .= " {$intdetail['mac']}" ;
   }
    $html .= "<td>$modname</td>";
    $html .= "<td>{$module['name']}</td><td id=\"status$modname\">$macs</td><td>{$module['enable']}</td><td>{$module['user_mac']}</td></tr>";
    $text = "";
  }
}
  $html .=  "</tbody>";
  
  $rtn = array();
  $rtn['html'] = $html;
  if ($init !== false) {$init = true; unlink($sysdrvinit);}
  $rtn['init'] = $init;
  echo json_encode($rtn);
  break;

case "update":
  $conf = $_POST['conf'];
  $module = $_POST['module'];
  if ($conf == "") $error = unlink("/boot/config/modprobe.d/$module.conf"); else $error = file_put_contents("/boot/config/modprobe.d/$module.conf",$conf);
  getmodules($module);
  $return = $arrModules[$module];
  $return['supportpage'] = $supportpage;
  if (is_array($return["modprobe"]))$return["modprobe"] = implode("\n",$return["modprobe"]);
  if ($error !== false) $return["error"] = false; else $return["error"] = true;
  echo json_encode($return);
  break;
}
?>
