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
  return ($dir && dirname($dir)!='/' && dirname($dir)!='/mnt' && dirname($dir)!='/mnt/user')
  ? "<a href=\"".htmlspecialchars("/$path?dir=".urlencode_path(dirname($dir)))."\">Parent Directory...</a>" : "";
}
function trim_slash($url){
  return preg_replace('/\/\/+/','/',$url);
}
extract(parse_plugin_cfg('dynamix',true));
$disks = parse_ini_file('state/disks.ini',true);
$dir = $_GET['dir'];
$path = $_GET['path'];
$user = $_GET['user'];
$list = [];
$all = $docroot.preg_replace('/([\'" &()[\]\\\\])/','\\\\$1',$dir).'/*';
$fix = explode('/',trim_slash($dir))[2];

exec("shopt -s dotglob; stat -L -c'%F|%n|%s|%Y' $all 2>/dev/null",$file);
if ($user) {
  exec("shopt -s dotglob; getfattr --no-dereference --absolute-names --only-values -n system.LOCATIONS $all 2>/dev/null",$set);
  $set = explode("\n",preg_replace("/(disk|cache)/","\n$1",$set[0])); $i = 0;
}

echo "<thead><tr><th>Type</th><th>Name</th><th>Size</th><th>Last Modified</th><th>Location</th></tr></thead>";
if ($link = parent_link()) echo "<tbody class='tablesorter-infoOnly'><tr><td><div class='icon-dirup'></div></td><td>$link</td><td colspan='3'></td></tr></tbody>";

foreach ($file as $row) {
  $attr = explode('|',$row);
  $info = pathinfo($attr[1]);
  $disk = $user ? $set[++$i] : $fix;
  $rows = explode(',',$disk);
  $tag = preg_replace('/\d+/','',$rows[0]);
  $show = false;
  $luks = '';
  foreach ($rows as $row) $show |= strpos($disks[$tag.str_replace($tag,'',$row)]['fsType'],'luks:')!==false;
  if ($show) foreach ($rows as $row) {
    switch ($disks[$tag.str_replace($tag,'',$row)]['luksState']) {
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
    'disk' => my_disk($disk).$luks
  ];
}
$sort = [];
foreach ($list as $row) $sort[] = $row['type'];
array_multisort($sort,$list);

echo "<tbody>";
$dirs=0; $files=0; $total=0;
foreach ($list as $row) {
  if ($row['type']=='directory') {
    echo "<tr>";
    echo "<td data=''><div class='icon-folder'></div></td>";
    echo "<td><a href=\"".htmlspecialchars("/$path?dir=".urlencode_path(trim_slash($dir.'/'.$row['name'])))."\">".htmlspecialchars($row['name'])."</a></td>";
    echo "<td data='0'>&lt;DIR&gt;</td>";
    echo "<td data='{$row['time']}'>".my_time($row['time'],"%F {$display['time']}")."</td>";
    echo "<td>{$row['disk']}</td>";
    echo "</tr>";
    $dirs++;
  } else {
    if ($files==0 && $dirs>0) echo "</tbody><tbody>";
    $tag = strpos($row['disk'],',')===false ? '' : 'warning';
    echo "<tr>";
    echo "<td data='{$row['fext']}'><div class='icon-file icon-{$row['fext']}'></div></td>";
    echo "<td><a href=\"".htmlspecialchars(urlencode_path(trim_slash($dir.'/'.$row['name'])))."\" class=\"".($tag?:'none')."\">".htmlspecialchars($row['name'])."</a></td>";
    echo "<td data='{$row['size']}' class='$tag'>".my_scale($row['size'],$unit)." $unit</td>";
    echo "<td data='{$row['time']}' class='$tag'>".my_time($row['time'],"%F {$display['time']}")."</td>";
    echo "<td class='$tag'>{$row['disk']}</td>";
    echo "</tr>";
    $files++;
    $total+=$row['size'];
  }
}
echo "</tbody>";
$objs = $dirs+$files;
$objtext = "$objs object".($objs==1?'':'s');
$dirtext = "$dirs director".($dirs==1?'y':'ies');
$filetext = "$files file".($files==1?'':'s');
$totaltext = $files==0 ? '':'('.my_scale($total,$unit).' '.$unit.' total)';
echo "<tfoot><tr><td></td><td colspan='4'>$objtext: $dirtext, $filetext $totaltext</td></tr></tfoot>";
