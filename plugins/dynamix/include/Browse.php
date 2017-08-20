<?PHP
/* Copyright 2005-2017, Lime Technology
 * Copyright 2012-2017, Bergware International.
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
$docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "$docroot/webGui/include/Helpers.php";

function parent_link() {
  global $dir,$path;
  if ($dir=='/' || $dir=='/boot' || dirname($dir)=='/mnt' || dirname($dir)=='/mnt/user') return "";
  $parent = urlencode_path(dirname($dir));
  return "<a href=\"".htmlspecialchars("/$path?dir=$parent")."\">Parent Directory...</a>";
}

extract(parse_plugin_cfg('dynamix',true));
$disks = parse_ini_file('state/disks.ini',true);
$dir = $_GET['dir'];
$path = $_GET['path'];
$user = $_GET['user'];
$list = [];
$all = $docroot.preg_replace('/([\'" &()[\]\\\\])/','\\\\$1',$dir).'/*';
$set = explode('/',str_replace('//','/',$dir))[2];

exec("shopt -s dotglob; stat -L -c'%F|%n|%s|%Y' $all 2>/dev/null",$item);
if ($user) {
  exec("shopt -s dotglob; getfattr --no-dereference --absolute-names --only-values -n system.LOCATIONS $all 2>/dev/null",$loc);
  $loc = explode("\n",preg_replace("/(disk|cache)/","\n$1",$loc[0])); $i = 0;
}

echo "<thead><tr><th>Type</th><th>Name</th><th>Size</th><th>Last Modified</th><th>Location</th></tr></thead>";
if ($link = parent_link()) echo "<tbody><tr><td><div class='icon-dirup'></div></td><td>$link</td><td colspan='3'></td></tr></tbody>";
echo "<tbody>";
foreach ($item as $entry) {
  $attr = explode('|',$entry);
  $info = pathinfo($attr[1]);
  $disk = $user ? $loc[++$i] : $set;
  $devs = explode(',',$disk);
  $type = preg_replace('/\d+/','',$devs[0]);
  $show = false;
  $luks = '';
  foreach ($devs as $dev)
    $show |= strpos($disks[$type.str_replace($type,'',$dev)]['fsType'],'luks:')!==false;
  if ($show) foreach ($devs as $dev) {
    switch ($disks[$type.str_replace($type,'',$dev)]['luksState']) {
    case 0: $luks .= "<i class='padlock grey-text fa fa-unlock-alt' title='Not encrypted'></i>"; break;
    case 1: $luks .= "<i class='padlock green-text fa fa-lock' title='Encrypted'></i>"; break;
    case 2: $luks .= "<i class='padlock red-text fa fa-unlock' title='Missing encryption key'></i>"; break;
    case 3: $luks .= "<i class='padlock red-text fa fa-unlock' title='Wrong encryption key'></i>"; break;
   default: $luks .= "<i class='padlock red-text fa fa-unlock' title='Unknown error'></i>"; break;}
  }
  $list[] = [
  'type' => $attr[0],
  'name' => $info['basename'],
  'fext' => strtolower($info['extension']),
  'size' => $attr[2],
  'time' => $attr[3],
  'disk' => my_disk($disk).$luks];
}
$dirs=0; $files=0; $total=0;
foreach ($list as $entry) {
  echo "<tr>";
  if ($entry['type']=='directory') {
    $dirs++;
    echo "<td sort='D' data=''><div class='icon-folder'></div></td>";
    echo "<td sort='D'><a href=\"".htmlspecialchars("/$path?dir=".urlencode_path($dir.'/'.$entry['name']))."\">".htmlspecialchars($entry['name'])."</a></td>";
    echo "<td sort='D' data='0'>&lt;DIR&gt;</td>";
    echo "<td sort='D' data='{$entry['time']}'>".my_time($entry['time'],"%F {$display['time']}")."</td>";
    echo "<td sort='D'>{$entry['disk']}</td>";
  } else {
    $files++;
    $total+=$entry['size'];
    $type = strpos($entry['disk'],',')===false ? '' : 'warning';
    echo "<td sort='F' data='{$entry['fext']}'><div class='icon-file icon-".strtolower($entry['fext'])."'></div></td>";
    echo "<td sort='F'><a href=\"".htmlspecialchars(urlencode_path($dir.'/'.$entry['name']))."\" class=\"".($type?:'none')."\">".htmlspecialchars($entry['name'])."</a></td>";
    echo "<td sort='F' data='{$entry['size']}' class='$type'>".my_scale($entry['size'],$unit)." $unit</td>";
    echo "<td sort='F' data='{$entry['time']}' class='$type'>".my_time($entry['time'],"%F {$display['time']}")."</td>";
    echo "<td sort='F' class='$type'>{$entry['disk']}</td>";
  }
  echo "</tr>";
}
echo "</tbody>";
$objs = $dirs+$files;
$objtext = "$objs object".($objs==1?'':'s');
$dirtext = "$dirs director".($dirs==1?'y':'ies');
$filetext = "$files file".($files==1?'':'s');
$totaltext = $files==0 ? '':'('.my_scale($total,$unit).' '.$unit.' total)';
echo "<tfoot><tr><td></td><td colspan='4'>$objtext: $dirtext, $filetext $totaltext</td></tr></tfoot>";
