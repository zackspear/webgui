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
$_SERVER['REQUEST_URI'] = '';
require_once "$docroot/webGui/include/Translations.php";

require_once "$docroot/webGui/include/Helpers.php";

function write(&$rows) {
  if ($size = count($rows)) echo '<tbody>',implode(array_map(function($row){echo gzinflate($row);},$rows)),'</tbody>';
  $rows = $size;
}
function add($number, $name, $single='', $plural='s') {
  return $number.' '._($name.($number==1 ? $single : $plural));
}
function parent_link() {
  global $dir,$path;
  $parent = dirname($dir);
  return $parent=='/' ? false : '<a href="/'.$path.'?dir='.rawurlencode(htmlspecialchars($parent)).'">'._('Parent Directory').'</a>';
}
function my_devs(&$devs) {
  global $disks,$lock;
  $text = []; $i = 0;
  foreach ($devs as $dev) {
    if ($lock=='---') {
      $text[$i] = '<a class="info" onclick="return false"><i class="lock fa fa-fw fa-hdd-o grey-text"></i></a>&nbsp;---';
    } else {
      switch ($disks[$dev]['luksState']??0) {
        case 0: $text[$i] = "<a class='info' onclick='return false'><i class='lock fa fa-fw fa-unlock-alt grey-text'></i><span>"._('Not encrypted')."</span></a>"; break;
        case 1: $text[$i] = "<a class='info' onclick='return false'><i class='lock fa fa-fw fa-unlock-alt green-text'></i><span>"._('Encrypted and unlocked')."</span></a>"; break;
        case 2: $text[$i] = "<a class='info' onclick='return false'><i class='lock fa fa-fw fa-lock red-text'></i><span>"._('Locked: missing encryption key')."</span></a>"; break;
        case 3: $text[$i] = "<a class='info' onclick='return false'><i class='lock fa fa-fw fa-lock red-text'></i><span>"._('Locked: wrong encryption key')."</span></a>"; break;
       default: $text[$i] = "<a class='info' onclick='return false'><i class='lock fa fa-fw fa-lock red-text'></i><span>"._('Locked: unknown error')."</span></a>"; break;
      }
      $text[$i] .= compress($dev,12,0);
    }
    $i++;
  }
  return implode($text);
}
extract(parse_plugin_cfg('dynamix',true));
$disks  = parse_ini_file('state/disks.ini',true);
$shares = parse_ini_file('state/shares.ini',true);
$dir    = realpath(htmlspecialchars_decode(rawurldecode($_GET['dir'])));
$path   = unscript($_GET['path']);
$fmt    = "%F {$display['time']}";
$dirs   = $files = [];
$total  = $objs = 0;
[$null,$root,$main,$rest] = my_explode('/',$dir,4);
$user   = $root=='mnt' && in_array($main,['user','user0']);
$lock   = $root=='mnt' ? ($main ?: '---') : ($root=='boot' ? _('flash') : '---');

if ($user) {
  exec("shopt -s dotglob; getfattr --no-dereference --absolute-names -n system.LOCATIONS ".escapeshellarg($dir)."/* 2>/dev/null",$tmp);
  for ($i = 0; $i < count($tmp); $i+=3) $set[basename($tmp[$i])] = explode('"',$tmp[$i+1])[1];
  unset($tmp);
}
$stat = popen("shopt -s dotglob; stat -L -c'%F|%s|%Y|%n' ".escapeshellarg($dir)."/* 2>/dev/null",'r');
while (($row = fgets($stat))!==false) {
  [$type,$size,$time,$name] = explode('|',rtrim($row,"\n"),4);
  $dev  = explode('/',$name,5);
  $devs = explode(',',$user ? $set[basename($name)]??$shares[$dev[3]]['cachePool']??'' : $lock);
  $objs++;
  $text = [];
  if ($type[0]=='d') {
    $text[] = "<tr>";
    $text[] = "<td data=''><div class='icon-dir'></div></td>";
    $text[] = "<td><a href=\"/$path?dir=".rawurlencode(htmlspecialchars($name))."\">".htmlspecialchars(basename($name))."</a></td>";
    $text[] = "<td data='0'>&lt;"._('FOLDER')."&gt;</td>";
    $text[] = "<td data='$time'>".my_time($time,$fmt)."</td>";
    $text[] = "<td class='loc'>".my_devs($devs)."</td>";
    $text[] = "</tr>";
    $dirs[] = gzdeflate(implode($text));
  } else {
    $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
    $tag = count($devs)>1 ? 'warning' : '';
    $text[] = "<tr>";
    $text[] = "<td data='$ext'><div class='icon-file icon-$ext'></div></td>";
    $text[] = "<td><a href=\"".htmlspecialchars($name)."\" download target=\"_blank\" class=\"".($tag?:'none')."\">".htmlspecialchars(basename($name))."</a></td>";
    $text[] = "<td data='$size' class='$tag'>".my_scale($size,$unit)." $unit</td>";
    $text[] = "<td data='$time' class='$tag'>".my_time($time,$fmt)."</td>";
    $text[] = "<td class='loc $tag'>".my_devs($devs)."</td>";
    $text[] = "</tr>";
    $files[] = gzdeflate(implode($text));
    $total += $size;
  }
}
pclose($stat);
if ($link = parent_link()) echo "<tbody class='tablesorter-infoOnly'><tr><td><div><img src='/webGui/icons/folderup.png'></div></td><td>$link</td><td colspan='3'></td></tr></tbody>";
echo write($dirs),write($files),'<tfoot><tr><td></td><td colspan="4">',add($objs,'object'),': ',add($dirs,'director','y','ies'),', ',add($files,'file'),' (',my_scale($total,$unit),' ',$unit,' ',_('total'),')</td></tr></tfoot>';
?>