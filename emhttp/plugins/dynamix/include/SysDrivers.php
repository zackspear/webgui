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
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
// add translations
$_SERVER['REQUEST_URI'] = 'tools';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/webGui/include/Helpers.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php"; 

$kernel = shell_exec("uname -r") ;
$kernel = trim($kernel,"\n") ;
$lsmod = shell_exec("lsmod") ;
$supportpage = true;
$modtoplgfile = "/tmp/modulestoplg.json" ;
$sysdrvfile = "/tmp/sysdrivers.json" ;
if (!is_file($modtoplgfile) || !is_file($sysdrvfile)) { modtoplg() ; createlist() ;}
$arrModtoPlg = json_decode(file_get_contents("/tmp/modulestoplg.json") ,TRUE) ;

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
    #echo $line ;
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
    if ($state == "Inuse")  $state= "(builtin) - Inuse"; else $state="(builtin)" ;
}
if ($desc != null) $description = substr($desc , 0 ,60) ; else  $description = null ;
$arrModules[$modname] = [
            'modname' => $modname,
            'dependacy' => $depends, 
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
    global $modtoplgfile ;
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/boot/config/plugins'));
    $files = array(); 

    /** @var SplFileInfo $file */
    foreach ($rii as $file) {
        if ($file->isDir()){ 
            continue;
        }
        if ($file->getExtension() != "tgz" && $file->getExtension() != "txz")     continue ;
        $files[] = $file->getPathname();        
    }

    $list = array() ;
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
    global $modtoplgfile, $sysdrvfile, $lsmod, $kernel,$arrModules ;
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

switch ($_POST['table']) {
  
    case 't1create':      
        modtoplg() ;
        createlist() ;  
        /*$arrModtoPlg = json_decode(file_get_contents($modtoplgfile) ,TRUE) ;
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
        file_put_contents($sysdrvfile,json_encode($arrModules,JSON_PRETTY_PRINT)) ;   */
              
        break;

    case 't1load':
        $list = file_get_contents($sysdrvfile) ;
        $arrModules = json_decode($list,TRUE) ; 
        #echo "<thead><tr><th><b>"._("Actions")."</th><th><b>"._("Driver")."</th><th><b>"._("Description")."</th><th data-value='Inuse|Custom|Disabled'><b>"._("State")."</th><th><b>"._("Type")."</th><th><b>"._("Modeprobe.d config file")."</th></tr></thead>";
        echo "<thead><tr><th><b>"._("Driver")."</th><th><b>"._("Description")."</th><th data-value='Inuse|Custom|Disabled'><b>"._("State")."</th><th><b>"._("Type")."</th><th><b>"._("Modeprobe.d config file")."</th></tr></thead>";
        echo "<tbody>" ;
     
        if (is_array($arrModules)) ksort($arrModules) ;
        foreach($arrModules as $modname => $module) {
            if ($modname == "") continue ;

            if (is_file("/boot/config/modprobe.d/$modname.conf")) {
                $modprobe = file_get_contents("/boot/config/modprobe.d/$modname.conf") ;
                $state = strpos($modprobe, "blacklist");
                $modprobe = explode(PHP_EOL,$modprobe) ;
                if($state !== false) {$state = "Disabled" ;} else $state="Custom" ;
                $module['state'] = $state ;
                $module['modprobe'] = $modprobe ;
                } 
         
            echo "<tr id='row$modname'>" ;
            #echo "<td></td>" ;
            if ($supportpage) {
                if ($module['support'] == false) {
                        #$supporthtml = "<span id='link$modname'><i title='"._("No support page avaialable")."' class='fa fa-phone-square'></i></span>" ;
                        $supporthtml = "" ;
                    } else {
                        $supporturl = $module['supporturl'] ;
                        $pluginname = $module['plugin'] ;
                        $supporthtml = "<span id='link$modname'><a href='$supporturl' target='_blank'><i title='"._("Support page $pluginname")."' class='fa fa-phone-square'></i></a></span>" ;
                    } 
            }  
            echo "<td>$modname$supporthtml</td>" ;
            echo "<td>{$module['description']}</td><td id=\"status$modname\">{$module['state']}</td><td>{$module['type']}</td>";
    
            $text = "" ;
            if (is_array($module["modprobe"])) {
                    $text = implode("\n",$module["modprobe"]) ;
                    echo "<td><span><a class='info' href=\"#\"><i title='"._("Edit Modprobe config")."' onclick=\"textedit('".$modname."')\" id=\"icon'.$modname.'\" class='fa fa-edit'></i></a><span><textarea id=\"text".$modname."\" rows=3 disabled>$text</textarea><span id=\"save$modname\" hidden onclick=\"textsave('".$modname."')\" ><a  class='info' href=\"#\"><i  title='"._("Save Modprobe config")."' class='fa fa-save' ></i></a></span></td></tr>";
                } else echo "<td><span><a class='info' href=\"#\"><i title='"._("Edit Modprobe config")."' onclick=\"textedit('".$modname."')\" id=\"icon'.$modname.'\" class='fa fa-edit'></i></a><span><textarea id=\"text".$modname."\" rows=1 hidden disabled >$text</textarea><span id=\"save$modname\" hidden onclick=\"textsave('".$modname."')\" ><a class='info' href=\"#\"><i  title='"._("Save Modprobe config")."' class='fa fa-save' ></i></a></span></td></tr>"; 
    
            } 
        echo "</tbody>" ;
        break;
  
case "update":
    $conf = $_POST['conf'] ;
    $module = $_POST['module'] ;
    if ($conf == "") $error = unlink("/boot/config/modprobe.d/$module.conf") ; else $error = file_put_contents("/boot/config/modprobe.d/$module.conf",$conf) ;
    getmodules($module) ;
    $return = $arrModules[$module] ;
    $return['supportpage'] = $supportpage ;
    if (is_array($return["modprobe"]))$return["modprobe"] = implode("\n",$return["modprobe"]) ;
    if ($error !== false) $return["error"] = false ; else $return["error"] = true ;
    echo json_encode($return) ;
    break ;   
}             
?>
