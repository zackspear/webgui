<?PHP
/* Copyright 2025-, Lime Technology
 * Copyright 2025-, Simon Fairweather.
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

#VFSETTINGS=0000:04:11.5|8086:1520|0|62:00:04:11:05:01 0000:04:10.5|8086:1520|1|62:00:04:10:05:01
#VFS=0000:04:00.1|8086:1521|3 0000:04:00.0|8086:1521|2

$docroot ??= ($_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');
require_once "$docroot/webGui/include/Secure.php";
require_once "$docroot/webGui/include/Wrappers.php";
require_once "$docroot/webGui/include/SriovHelpers.php";

function action_settings($pciid) {
  $sriov = json_decode(getSriovInfoJson(),true);  
  $sriov_devices_settings=parseVFSettings();
  $vfs = $sriov[$pciid]['vfs'];
  file_put_contents('/tmp/vfaction',"");
  foreach($vfs as $vf) {
    if (array_key_exists($vf['pci'],$sriov_devices_settings)) {
      $vfpci = $vf['pci'];
      $vfio = $sriov_devices_settings[$vfpci]['vfio'];
      $mac = $sriov_devices_settings[$vfpci]['mac'];
      $action_cmd = "/usr/local/sbin/sriov-vfsettings.sh ".escapeshellarg($vf['pci'])." ".escapeshellarg($vf['vd'])." ".escapeshellarg($vfio)." ".escapeshellarg($mac);
      file_put_contents('/tmp/vfaction',$action_cmd,FILE_APPEND);
      $action_result = shell_exec($action_cmd);

      #if ($action_result == "error") return $action_result;
    }
  }
}

$sriov = '/boot/config/sriov.cfg';
$sriovvfs = '/boot/config/sriovvfs.cfg';

$type = _var($_POST,'type'); 
$pciid = _var($_POST,'pciid');
$vd = _var($_POST,'vd');

if (isset($pciid) && isset($vd)) {
  $newelement_check = $pciid.'|'.$vd.'|';

  switch($type) {
      case "sriov":
        $old  = is_file($sriov) ? rtrim(file_get_contents($sriov)) : '';
        $newexplode = explode(" ",str_replace("VFS=","",$old));
        $new = $old;
        $numvfs= _var($_POST,'numvfs');
        $currentvfs = _var($_POST,'currentvfs');
        $newelement_change = $newelement_check.$numfs;
        $found = false;
        $filepath = "/sys/bus/pci/devices/$pciid/sriov_numvfs";
        if ($numvfs == 0) {
          file_put_contents($filepath,0);
        echo 1;
        return;
        }
        if ($numvfs != $currentvfs) {
          file_put_contents($filepath,0);
          file_put_contents($filepath,$numvfs);
          action_settings($pciid);
        echo 1;
        return;
        }
        file_put_contents($filepath,$numvfs);
        action_settings($pciid);
        echo 1;
        return;
        break;
      case "sriovsettings":
        $old  = is_file($sriovvfs) ? rtrim(file_get_contents($sriovvfs)) : '';
        $newexplode = explode(" ",str_replace("VFSETTINGS=","",$old));
        $mac= _var($_POST,'mac');
        $vfio= _var($_POST,'vfio');
        if ($vfio == "true") $vfio = 1; else $vfio = 0;
        $found = false;
        foreach($newexplode as $key => $newelement) {
          if (strpos($newelement,$newelement_check) !== false) {
             $found = true;
            if($mac == "" && $vfio == 0) {
              unset($newexplode[$key]) ;
              break;
            } else {
              $newexplode[$key] = $newelement_check.$vfio."|".$mac;
              break;
            }
          }
        }
          if (!$found) $newexplode[] = $newelement_check.$vfio."|".$mac;
          $new = "VFSETTINGS=".implode(" ",$newexplode);
          $file = $sriovvfs; 
          break;
  }
}


?>
