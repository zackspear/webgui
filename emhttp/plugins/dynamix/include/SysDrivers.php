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

function getmodules($kernel,$line) {
    global $arrModules,$lsmod ;
    $modprobe = "" ;
    $name = $line ;
    #echo $line ;
    $modname = trim(shell_exec("modinfo  $name > /dev/null"),"\n") ;
    $output=null ;
    exec("modinfo $name",$output,$error) ;
    $parms = array() ;
    foreach($output as $outline) {
    $data = explode(":",$outline) ;
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
if (strpos($lsmod, $modname,0)) $state = "Inuse" ; else $state = "Available";
if (is_file("/boot/config/modprobe.d/$modname.conf")) {
    $modprobe = explode(PHP_EOL,file_get_contents("/boot/config/modprobe.d/$modname.conf")) ;
    $state = array_search("blacklist",$modprobe);
    if($state) {$state = "Disabled" ;} 
    else $state="Custom" ;
    } else if($option == "conf") return ;

if ($filename != "(builtin)") {
$type = pathinfo($filename) ;
$dir =  $type['dirname'] ;
$dir = str_replace("/lib/modules/$kernel/kernel/drivers/", "" ,$dir) ;
$dir = str_replace("/lib/modules/$kernel/kernel/", "" ,$dir) ;
} else {
    $dir = $file ;
    $dir = str_replace("drivers/", "" ,$dir) ;
    if ($state == "Inuse")  $state= "(builtin) - Inuse"; else $state="(builtin)" ;
}
$arrModules[$modname] = [
            'modname' => $modname,
            'dependacy' => $depends, 
            'parms' => $parms,
            'file' =>  $file,
            'modprobe' => $modprobe,
            'state' => $state,
            'type' => $dir,
            'description' => substr($desc , 0 ,60)  ,
] ;
}

switch ($_POST['table']) {
case 't1':
  $option = $_POST['option'] ;
  $select = $_POST['select'] ;
  #$procmodules = file_get_contents("/proc/modules") ;
  $lsmod = shell_exec("lsmod") ;
  $kernel = shell_exec("uname -r") ;
  $kernel = trim($kernel,"\n") ;
  #$procmodules = shell_exec('find /lib/modules/$(uname -r) -type f -not -path "/lib/modules/$(uname -r)/source/*" -not -path "/lib/modules/$(uname -r)/build/*" -name "*ko*" ') ;
  $procmodules = shell_exec('find /lib/modules/$(uname -r)/kernel/drivers/ -type f -not -path "/lib/modules/$(uname -r)/source/*" -not -path "/lib/modules/$(uname -r)/build/*" -name "*ko*" ') ;
  $procmodules = explode(PHP_EOL,$procmodules) ;
  $builtinmodules = file_get_contents("/lib/modules/$kernel/modules.builtin") ;
  $builtinmodules = explode(PHP_EOL,$builtinmodules) ;
  $option = $_POST['option'] ;
  $arrModules = array() ;

  foreach($builtinmodules as $bultin)
  {
    if ($bultin == "") continue ;
    getmodules($kernel,pathinfo($bultin)["filename"]) ;
  }

foreach($procmodules as $line) {
    if ($line == "") continue ;
    getmodules($kernel,$line) ;
}


  echo "<tr><td><b>"._("Module/Driver")."</td><td><b>"._("Description")."</td><td><b>"._("State")."</td><td><b>"._("Type")."</td><td><b>"._("Modeprobe.d config file")."</td></tr>";
 
  if (is_array($arrModules)) ksort($arrModules) ;
  foreach($arrModules as $modname => $module)
  {

    switch ($_POST['option']){
        case "inuse":
            
        if ($module['state'] == "Available" || $module['state'] == "(builtin)") continue(2) ;  
        break ;
        case "confonly":
         if ($module['modprobe'] == "" ) continue(2) ;  
            break ;
        case "all":
            break ;
    }
  if (substr($module['state'],0,9) == "(builtin)") $disable = "disabled" ; else $disable = "" ;
  $disable = "disabled" ;
  echo "<tr><td><span $disable onclick=\"textedit('".$modname."')\" ><a><i $disable title='"._("Edit Modprobe config")."' id=\"icon'.$modname.'\" class='fa fa-edit' ></i></a></span> $modname</td><td>{$module['description']}</td><td>{$module['state']}</td><td>{$module['type']}</td>";
  $text = "" ;
  if (is_array($module["modprobe"])) {
          $text = implode("\n",$module["modprobe"]) ;
    echo "<td><textarea id=\"text".$modname."\" rows=3 disabled>$text</textarea><span id=\"save$modname\" hidden onclick=\"textsave('".$modname."')\" ><a><i  title='"._("Save Modprobe config")."' class='fa fa-save' ></i></a></span></td></tr>";
  } else echo "<td><textarea id=\"text".$modname."\" rows=1 hidden disabled >$text</textarea><span id=\"save$modname\" hidden onclick=\"textsave('".$modname."')\" ><a><i  title='"._("Save Modprobe config")."' class='fa fa-save' ></i></a></span></td></tr>"; 

  }   
  break;
case "update":
    $conf = $_POST['conf'] ;
    $module = $_POST['module'] ;
    if ($conf == "") $error = unlink("/boot/config/modprobe.d/$module.conf") ;
    else $error = file_put_contents("/boot/config/modprobe.d/$module.conf",$conf) ;
    echo $error ;
}
?>
