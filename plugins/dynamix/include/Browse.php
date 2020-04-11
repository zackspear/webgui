<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2012-2020, Bergware International.
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
$_SERVER['REQUEST_URI'] = '';
require_once "$docroot/webGui/include/Translations.php";

require_once "$docroot/webGui/include/Helpers.php";

function parent_link() {
  global $dir,$path;
  return ($dir && dirname($dir)!='/' && dirname($dir)!='/mnt' && dirname($dir)!='/mnt/user')
  ? "<a href=\"/$path?dir=".htmlspecialchars(urlencode_path(dirname($dir)))."\">Parent Directory</a>" : "";
}
function trim_slash($url){
  return preg_replace('/\/\/+/','/',$url);
}
function my_name($name) {
  $names = [];
  foreach (array_map('my_disk',explode(',',$name)) as $fancy) $names[] = my_lang($fancy,3);
  return implode(', ',$names);
}
extract(parse_plugin_cfg('dynamix',true));
$disks = parse_ini_file('state/disks.ini',true);
$dir = urldecode($_GET['dir']);
$path = $_GET['path'];
$user = $_GET['user'];
$list = [];
$all = $docroot.preg_replace('/([\'" &()[\]\\\\])/','\\\\$1',$dir).'/*';
$fix = substr($dir,0,4)=='/mnt' ? explode('/',trim_slash($dir))[2] : 'flash';
$cache = implode('|',pools_filter($disks)) ?: 'cache';

$dirs = exec("find \"$dir\" -mindepth 1 -maxdepth 1 -type d|wc -l");
$files = exec("find \"$dir\" -mindepth 1 -maxdepth 1 -type f|wc -l");
 
exec("shopt -s dotglob; stat -L -c'%F|%n|%s|%Y' $all 2>/dev/null",$file);
if ($user) {
  exec("shopt -s dotglob; getfattr --no-dereference --absolute-names --only-values -n system.LOCATIONS $all 2>/dev/null",$set);
  $set = explode("\n",str_replace(",\n",",",preg_replace("/($cache|disk)/","\n$1",$set[0]))); $i = 0;
}

echo "<thead><tr><th>"._('Type')."</th><th class='sorter-text'>"._('Name')."</th><th>"._('Size')."</th><th>"._('Last Modified')."</th><th>"._('Location')."</th></tr></thead>";
if ($link = parent_link()) echo "<tbody class='tablesorter-infoOnly'><tr><td><div><img src='/webGui/icons/folderup.png'></div></td><td>$link</td><td colspan='3'></td></tr></tbody>";

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
    case 0: $luks .= "<a class='info' onclick='return false'><i class='lock fa fa-unlock grey-text'></i><span>"._('Not encrypted')."</span></a>"; break;
    case 1: $luks .= "<a class='info' onclick='return false'><i class='lock fa fa-unlock-alt green-text'></i><span>"._('Encrypted and unlocked')."</span></a>"; break;
    case 2: $luks .= "<a class='info' onclick='return false'><i class='lock fa fa-lock red-text'></i><span>"._('Locked: missing encryption key')."</span></a>"; break;
    case 3: $luks .= "<a class='info' onclick='return false'><i class='lock fa fa-lock red-text'></i><span>"._('Locked: wrong encryption key')."</span></a>"; break;
    default: $luks .= "<a class='info' onclick='return false'><i class='lock fa fa-lock red-text'></i><span>"._('Locked: unknown error')."</span></a>"; break;}
  }
  $list[] = ['type' => $attr[0], 'name' => $info['basename'], 'fext' => strtolower($info['extension']), 'size' => $attr[2], 'time' => $attr[3], 'disk' => my_name($disk).$luks];
}
array_multisort(array_column($list,'type'),$list);

echo "<tbody>";
$total=0; $first = true;
foreach ($list as $row) {
  if ($row['type']=='directory') {
    echo "<tr>";
    echo "<td data=''><div class='icon-dir'></div></td>";
    echo "<td><a href=\"/$path?dir=".htmlspecialchars(urlencode_path(trim_slash($dir.'/'.$row['name'])))."\">".htmlspecialchars($row['name'])."</a></td>";
    echo "<td data='0'>&lt;"._('FOLDER')."&gt;</td>";
    echo "<td data='{$row['time']}'>".my_time($row['time'],"%F {$display['time']}")."</td>";
    echo "<td class='loc'>{$row['disk']}</td>";
    echo "</tr>";
  } else {
    if ($first && $dirs>0) echo "</tbody><tbody>";
    $tag = strpos($row['disk'],',')===false ? '' : 'warning';
    echo "<tr>";
    echo "<td data='{$row['fext']}'><div class='icon-file icon-{$row['fext']}'></div></td>";
    echo "<td><a href=\"".htmlspecialchars(trim_slash($dir.'/'.$row['name']))."\" download target=\"_blank\" class=\"".($tag?:'none')."\">".htmlspecialchars($row['name'])."</a></td>";
    echo "<td data='{$row['size']}' class='$tag'>".my_scale($row['size'],$unit)." $unit</td>";
    echo "<td data='{$row['time']}' class='$tag'>".my_time($row['time'],"%F {$display['time']}")."</td>";
    echo "<td class='loc $tag'>{$row['disk']}</td>";
    echo "</tr>";
    $total+=$row['size'];
    $first = false;
  }
}
echo "</tbody>";
$objs = $dirs+$files;
$totaltext = $files==0 ? '' : '('.my_scale($total,$unit).' '.$unit.' '._('total').')';
if ($first && $files) echo "<tbody><tr><td colspan='5' style='text-align:center'>"._('No listing: Too many files')."</td></tr></tbody>";
echo "<tfoot><tr><td></td><td colspan='4'>$objs "._('object'.($objs==1?'':'s')).": $dirs "._('director'.($dirs==1?'y':'ies')).", $files "._('file'.($files==1?'':'s'))." $totaltext</td></tr></tfoot>";
