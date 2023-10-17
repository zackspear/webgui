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


function getplugin($in) {
    $plugins = "/var/log/plugins/";
    $plugin_link = $plugins.$in ;
    $plugin_file = @readlink($plugin_link);
    $support = plugin('support',$plugin_file) ?: "";
    return($support) ;
}

function getmodules($line) {
    global $arrModules,$lsmod,$kernel,$arrModtoPlg,$modplugins ;
    $modprobe = "" ;
    $desc = $file = $pluginfile = $option = $filename = $depends = $support = $supporturl = $dir = $state =  null ;
    $name = $line ;
    $modname = shell_exec("modinfo  $name > /dev/null") ;
    if ($modname != null) $modname = trim($modname,"\n") ;
    $output=null ;
    exec("modinfo $name",$output,$error) ;
    $parms = array() ;
    foreach($output as $outline) {
    $data = explode(":",$outline) ;
    $support = false ; $supporturl = null ;
    switch ($data[0])
    {
        case "name":
            $modname = trim($data[1]) ;
            break ;
        case "depends":
            $depends = trim($data[1]) ;
            break ;
        case "filename":
            $filename = trim($data[1]) ;
            break ;
        case "description":
            $desc = trim($data[1]) ;
            break ;            
        case "parm":
            $parms[] = trim(str_replace("parm:","",$outline)) ;
            break ;
        case "file":
            $file = trim(str_replace("file:","",$outline)) ;
            break ;
        case "version":
            $version = trim(str_replace("version:","",$data[1])) ;
            break ;
        case "alias":
        case "author":
        case "firmware":
        case "intree":
        case "vermagic":
        case "retpoline":
        case "import_ns":
        case "license":
            break ;
        default:
            $parms[] = trim($outline) ;
            break ;
    }
}
if ($modname != null) {
    if (strpos($lsmod, $modname,0)) $state = "Inuse" ; else $state = "Available";
    if (isset($arrModtoPlg[$modname])) { $support = true ; $supporturl = plugin("support", $modplugins[$arrModtoPlg[$modname]]) ; $pluginfile = "Plugin name: {$arrModtoPlg[$modname]}" ; } else { $support = false ; $supporturl = null ; }
    }
if (is_file("/boot/config/modprobe.d/$modname.conf")) {
    $modprobe = file_get_contents("/boot/config/modprobe.d/$modname.conf") ;
    $state = strpos($modprobe, "blacklist");
    $modprobe = explode(PHP_EOL,$modprobe) ;
    if($state !== false) {$state = "Disabled" ;} 
    else $state="Custom" ;
    } else {
        if (is_file("/etc/modprobe.d/$modname.conf")) {
            $modprobe = file_get_contents("/etc/modprobe.d/$modname.conf") ;
            $state = strpos($modprobe, "blacklist");
            $modprobe = explode(PHP_EOL,$modprobe) ;
            if($state !== false) {$state = "Disabled" ;} else $state="System" ;
            $module['state'] = $state ;
            $module['modprobe'] = $modprobe ;
            }
        }

if ($filename != "(builtin)") {
if ($filename != null) {
$type = pathinfo($filename) ;
$dir =  $type['dirname'] ;

$dir = str_replace("/lib/modules/$kernel/kernel/drivers/", "" ,$dir) ;
$dir = str_replace("/lib/modules/$kernel/kernel/", "" ,$dir) ;
}
} else {
    $dir = $file ;
    $dir = str_replace("drivers/", "" ,$dir) ;
    if ($state == "Inuse")  $state= "Kernel - Inuse"; else $state="Kernel" ;
}
if ($desc != null) $description = substr($desc , 0 ,60) ; else  $description = null ;
$arrModules[$modname] = [
            'modname' => $modname,
            'dependacy' => $depends,
            'version' => $version, 
            'parms' => $parms,
            'file' =>  $file,
            'modprobe' => $modprobe,
            'plugin' => $pluginfile ,
            'state' => $state,
            'type' => $dir,
            'support' => $support,
            'supporturl' => $supporturl,
            'description' => $description  ,
] ;
}

function modtoplg() {
    global $modtoplgfile,$kernel ;

    $files = array(); 
    $kernelsplit = explode('-',$kernel) ;
    $kernelvers = trim($kernelsplit[0],"\n") ;

    $list = array() ;
    $files = glob('/boot/config/plugins/*/packages/' . $kernelvers . '/*.{txz,tgz}', GLOB_BRACE);
    foreach ($files as $f) {
            $plugin = str_replace("/boot/config/plugins/", "", $f) ;
            $plugin = substr($plugin,0,strpos($plugin,'/') ) ;
            $tar = [] ;
            exec("tar -tf $f | grep -E '.ko.xz|.ko' ",$tar) ;
            foreach ($tar as $t) {
                $p = pathinfo($t) ;
                $filename = str_replace(".ko","",$p["filename"]) ;
                $list[$filename] = $plugin ;
            }
        }

    file_put_contents($modtoplgfile,json_encode($list,JSON_PRETTY_PRINT)) ;
   
}

function createlist() {
    global $modtoplgfile, $sysdrvfile, $lsmod, $kernel,$arrModules, $modplugins,$arrModtoPlg ;
    $arrModtoPlg = json_decode(file_get_contents($modtoplgfile) ,TRUE) ;
    $builtinmodules = file_get_contents("/lib/modules/$kernel/modules.builtin") ;
    $builtinmodules = explode(PHP_EOL,$builtinmodules) ;
    $procmodules =file_get_contents("/lib/modules/$kernel/modules.order") ;
    $procmodules = explode(PHP_EOL,$procmodules) ; 
    $arrModules = array() ;

    $list = scandir('/var/log/plugins/') ;
    foreach($list as $f) $modplugins[plugin("name" , @readlink("/var/log/plugins/$f"))] = @readlink("/var/log/plugins/$f") ;
  
    foreach($builtinmodules as $bultin)
    {
      if ($bultin == "") continue ;
      getmodules(pathinfo($bultin)["filename"]) ;
    }
  
    foreach($procmodules as $line) {
      if ($line == "") continue ;
      getmodules(pathinfo($line)["filename"]) ;
    } 

    $lsmod2 = explode(PHP_EOL,$lsmod) ; 
    foreach($lsmod2 as $line) {
            if ($line == "") continue ;
            $line2 = explode(" ",$line) ;
         getmodules($line2['0']) ;
      } 

    unset($arrModules['null']);  
    file_put_contents($sysdrvfile,json_encode($arrModules,JSON_PRETTY_PRINT)) ;   
}

?>