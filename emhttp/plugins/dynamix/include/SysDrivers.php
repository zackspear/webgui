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

switch ($_POST['table']) {
case 't1':
  $option = $_POST['option'] ;
  $select = $_POST['select'] ;
  #$procmodules = file_get_contents("/proc/modules") ;
  #$procmodules = shell_exec('find /lib/modules/$(uname -r) -type f -not -path "/lib/modules/$(uname -r)/source/*" -not -path "/lib/modules/$(uname -r)/build/*" -name "*ko*" ') ;
  $procmodules = shell_exec('find /lib/modules/$(uname -r)/kernel/drivers/ -type f -not -path "/lib/modules/$(uname -r)/source/*" -not -path "/lib/modules/$(uname -r)/build/*" -name "*ko*" ') ;
  $procmodules = explode(PHP_EOL,$procmodules) ;
  $option = $_POST['option'] ;
  
  $kernel = shell_exec("uname -r") ;
  $kernel = trim($kernel,"\n") ;
  foreach($procmodules as $line) {
      if ($line == "") continue ;
      $modprobe = "" ;
      $name = $line ;
      $modname = trim(shell_exec("modinfo  $name"),"\n") ;
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
                  $file = trim($data[1]) ;
                  break ;
              case "description":
                  $desc = trim($data[1]) ;
                  break ;            
              case "parm":
                  $parms[] = trim(str_replace("parm:","",$outline)) ;
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
  $state = "Enabled" ;
  if (is_file("/boot/config/modprobe.d/$modname.conf")) {
      $modprobe = explode(PHP_EOL,file_get_contents("/boot/config/modprobe.d/$modname.conf")) ;
      $state = array_search("blacklist",$modprobe);
      if($state) {$state = "Disabled" ;} 
      else $state="Custom" ;
      } else if($option == "conf") continue ;
  
  $type = pathinfo($file) ;
  $dir =  $type['dirname'] ;
  $dir = str_replace("/lib/modules/$kernel/kernel/drivers/", "" ,$dir) ;
  $dir = str_replace("/lib/modules/$kernel/kernel/", "" ,$dir) ;
  $arrModules[$modname] = [
              'modname' => $modname,
              'dependacy' => $depends, 
              'parms' => $parms,
              'file' =>  $file,
              'modprobe' => $modprobe,
              'state' => $state,
              'type' => $dir,
              'description' => substr($desc , 0 ,60) ,
                ] ;
  }
  echo "<tr><td>"._("Module/Driver")."</td><td>"._("Description")."</td><td>"._("State")."</td><td>"._("Type")."</td><td>"._("Modeprobe.d config file")."</td></tr>";

  if (is_array($arrModules)) ksort($arrModules) ;
  foreach($arrModules as $modname => $module)
  {
  echo "<tr><td>$modname</td><td>{$module['description']}</td><td>{$module['state']}</td><td>{$module['type']}</td>";
  if (is_array($module["modprobe"])) {
    $i = 0 ;
      foreach($module["modprobe"] as $line) {
        if ($i) echo "<tr><td></td><td></td><td></td><td></td><td>$line</td></tr>" ; else echo "<td>$line</td></tr>" ;
        $i++ ;
      }
  }

  }   
  break;

}
?>
