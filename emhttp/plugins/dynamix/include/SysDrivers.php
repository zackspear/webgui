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
require_once "$docroot/webGui/include/SysDriversHelpers.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php"; 

$kernel = shell_exec("uname -r") ;
$kernel = trim($kernel,"\n") ;
$lsmod = shell_exec("lsmod") ;
$supportpage = true;
$modtoplgfile = "/tmp/modulestoplg.json" ;
$sysdrvfile = "/tmp/sysdrivers.json" ;
if (!is_file($modtoplgfile) || !is_file($sysdrvfile)) { modtoplg() ; createlist() ;}
$arrModtoPlg = json_decode(file_get_contents("/tmp/modulestoplg.json") ,TRUE) ;

switch ($_POST['table']) {
  
    case 't1create':      
        modtoplg() ;
        createlist() ;  
             
        break;

    case 't1load':
        $list = file_get_contents($sysdrvfile) ;
        $arrModules = json_decode($list,TRUE) ; 
        echo "<thead><tr><th><b>"._("Driver")."</th><th><b>"._("Description")."</th><th data-value='Inuse|Custom|Disabled|\"Kernel - Inuse\"'><b>"._("State")."</th><th><b>"._("Type")."</th><th><b>"._("Modprobe.d config file")."</th></tr></thead>";
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
            if ($supportpage) {
                if ($module['support'] == false) {
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
