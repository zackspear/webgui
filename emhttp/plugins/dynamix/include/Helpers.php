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
$docroot ??= ($_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');
require_once "$docroot/webGui/include/Wrappers.php";
require_once "$docroot/webGui/include/Secure.php";

// Helper functions
function my_scale($value, &$unit, $decimals=NULL, $scale=NULL, $kilo=1000) {
  global $display,$language;
  $scale = $scale ?? $display['scale'];
  $number = _var($display,'number','.,');
  $units = explode(' ', ' '.($kilo==1000 ? ($language['prefix_SI'] ?? 'K M G T P E Z Y') : ($language['prefix_IEC'] ?? 'Ki Mi Gi Ti Pi Ei Zi Yi')));
  $size = count($units);
  if ($scale==0 && ($decimals===NULL || $decimals<0)) {
    $decimals = 0;
    $unit = '';
  } else {
    $base = $value ? intval(floor(log($value, $kilo))) : 0;
    if ($scale>0 && $base>$scale) $base = $scale;
    if ($base>$size) $base = $size-1;
    $value /= pow($kilo, $base);
    if ($decimals===NULL) $decimals = $value>=100 ? 0 : ($value>=10 ? 1 : (round($value*100)%100===0 ? 0 : 2));
    elseif ($decimals<0) $decimals = $value>=100||round($value*10)%10===0 ? 0 : abs($decimals);
    if ($scale<0 && round($value,-1)==1000) {$value = 1; $base++;}
    $unit = $units[$base]._('B');
  }
  return number_format($value, $decimals, $number[0], $value>9999 ? $number[1] : '');
}
function my_number($value) {
  global $display;
  $number = _var($display,'number','.,');
  return number_format($value, 0, $number[0], ($value>=10000 ? $number[1] : ''));
}
function my_time($time, $fmt=NULL) {
  global $display;
  if (!$fmt) $fmt = _var($display,'date').(_var($display,'date')!='%c' ? ", "._var($display,'time') : "");
  return $time ? my_date($fmt, $time) : _('unknown');
}
function my_temp($value) {
  global $display;
  $unit = _var($display,'unit','C');
  $number = _var($display,'number','.,');
  return is_numeric($value) ? (($unit=='F' ? fahrenheit($value) : str_replace('.', $number[0], $value)).'&#8201;&#176;'.$unit) : $value;
}
function my_disk($name, $raw=false) {
  global $display;
  return _var($display,'raw')||$raw ? $name : ucfirst(preg_replace('/(\d+)$/',' $1',$name));
}
function my_disks($disk) {
  return strpos(_var($disk,'status'),'_NP')===false;
}
function my_hyperlink($text, $link) {
  return str_replace(['[',']'],["<a href=\"$link\">","</a>"],$text);
}
function main_only($disk) {
  return _var($disk,'type')=='Parity' || _var($disk,'type')=='Data';
}
function parity_only($disk) {
  return _var($disk,'type')=='Parity';
}
function data_only($disk) {
  return _var($disk,'type')=='Data';
}
function cache_only($disk) {
  return _var($disk,'type')=='Cache';
}
function main_filter($disks) {
  return array_filter($disks,'main_only');
}
function parity_filter($disks) {
  return array_filter($disks,'parity_only');
}
function data_filter($disks) {
  return array_filter($disks,'data_only');
}
function cache_filter($disks) {
  return array_filter($disks,'cache_only');
}
function pools_filter($disks) {
  return array_unique(array_map('prefix',array_keys(cache_filter($disks))));
}
function my_id($id) {
  global $display;
  $len = strlen($id);
  $wwn = substr($id,-18);
  return (_var($display,'wwn') || substr($wwn,0,2)!='_3' || preg_match('/.[_-]/',$wwn)) ? $id : substr($id,0,$len-18);
}
function my_word($num) {
  $words = ['zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty','twenty-one','twenty-two','twenty-three','twenty-four','twenty-five','twenty-six','twenty-seven','twenty-eight','twenty-nine','thirty'];
  return $num<count($words) ? _($words[$num],1) : $num;
}
function my_usage() {
  global $disks,$var,$display;
  $arraysize=0;
  $arrayfree=0;
  foreach ($disks as $disk) {
    if (strpos(_var($disk,'name'),'disk')!==false) {
      $arraysize += _var($disk,'sizeSb',0);
      $arrayfree += _var($disk,'fsFree',0);
    }
  }
  if (_var($var,'fsNumMounted',0)>0) {
    $used = $arraysize ? 100-round(100*$arrayfree/$arraysize) : 0;
    echo "<div class='usage-bar' title='" . _('Array utilization') . " – {$used}%'><span style='width:{$used}%' class='".usage_color($display,$used,false)."'>{$used}%</span></div>";
  } else {
    echo "<div class='usage-bar'><span style='text-align:center'>".($var['fsState']=='Started'?'Maintenance':'off-line')."</span></div>";
  }
}
function usage_color(&$disk, $limit, $free) {
  global $display;
  if (_var($display,'text',0)==1 || intval(_var($display,'text',0)/10)==1) return '';
  $critical = _var($disk,'critical')>=0 ? $disk['critical'] : (_var($display,'critical')>=0 ? $display['critical'] : 0);
  $warning = _var($disk,'warning')>=0 ? $disk['warning'] : (_var($display,'warning')>=0 ? $display['warning'] : 0);
  if (!$free) {
    if ($critical>0 && $limit>=$critical) return 'redbar';
    if ($warning>0 && $limit>=$warning) return 'orangebar';
    return 'greenbar';
  } else {
    if ($critical>0 && $limit<=100-$critical) return 'redbar';
    if ($warning>0 && $limit<=100-$warning) return 'orangebar';
    return 'greenbar';
  }
}
function my_check($time, $speed) {
  if (!$time) return _('unavailable (no parity-check entries logged)');
  $days = floor($time/86400);
  $hmss = $time-$days*86400;
  $hour = floor($hmss/3600);
  $mins = floor($hmss/60)%60;
  $secs = $hmss%60;
  return plus($days,'day',($hour|$mins|$secs)==0).plus($hour,'hour',($mins|$secs)==0).plus($mins,'minute',$secs==0).plus($secs,'second',true).". "._('Average speed').": ".(is_numeric($speed) ? my_scale($speed,$unit,1)." $unit/s" : $speed);
}
function my_error($code) {
  switch ($code) {
  case -4:
    return "<em>"._('aborted')."</em>";
  default:
    return "<strong>$code</strong>";
  }
}
function mk_option($select, $value, $text, $extra="") {
  return "<option value='$value'".($value==$select ? " selected" : "").(strlen($extra) ? " $extra" : "").">$text</option>";
}
function mk_option_check($name, $value, $text="") {
  if ($text) {
    $checked = in_array($value,explode(',',$name)) ? " selected" : "";
    return "<option value='$value'$checked>$text</option>";
  }
  if (strpos($name,'disk')!==false) {
    $checked = in_array($name,explode(',',$value)) ? " selected" : "";
    return "<option value='$name'$checked>".my_disk($name)."</option>";
  }
}
function mk_option_luks($name, $value, $luks) {
  if (strpos($name,'disk')!==false) {
    $checked = in_array($name,explode(',',$value)) ? " selected" : "";
    return "<option luks='$luks' value='$name'$checked>".my_disk($name)."</option>";
  }
}
function day_count($time) {
  global $var;
  if (!$time) return;
  $datetz = new DateTimeZone($var['timeZone']);
  $date = new DateTime("now", $datetz);
  $offset = $datetz->getOffset($date);
  $now  = new DateTime("@".intval((time()+$offset)/86400)*86400);
  $last = new DateTime("@".intval(($time+$offset)/86400)*86400);
  $days = date_diff($last,$now)->format('%a');
  switch (true) {
  case ($days<0):
    return;
  case ($days==0):
    return " <span class='green-text'>("._('today').")</span>";
  case ($days==1):
    return " <span class='green-text'>("._('yesterday').")</span>";
  case ($days<=31):
    return " <span class='green-text'>(".sprintf(_('%s days ago'),my_word($days)).")</span>";
  case ($days<=61):
    return " <span class='orange-text'>(".sprintf(_('%s days ago'),$days).")</span>";
  case ($days>61):
    return " <span class='red-text'>(".sprintf(_('%s days ago'),$days).")</span>";
  }
}
function plus($val, $word, $last) {
  return $val>0 ? (($val || $last) ? ($val.' '._($word.($val!=1?'s':'')).($last ?'':', ')) : '') : '';
}
function compress($name, $size=18, $end=6) {
  return mb_strlen($name)<=$size ? $name : mb_substr($name,0,$size-($end?$end+3:0)).'...'.($end?mb_substr($name,-$end):'');
}
function escapestring($name) {
  return "\"$name\"";
}
function tail($file, $rows=1) {
  $file = new SplFileObject($file);
  $file->seek(PHP_INT_MAX);
  $file->seek($file->key()-$rows);
  $echo = [];
  while (!$file->eof()) {
    $echo[] = $file->current();
    $file->next();
  }
  return implode($echo);
}

/* Get the last parity check from the parity history. */
function last_parity_log() {
	$log = '/boot/config/parity-checks.log';

	if (file_exists($log)) {
		list($date, $duration, $speed, $status, $error, $action, $size) = my_explode('|', tail($log), 7);
	} else {
		list($date, $duration, $speed, $status, $error, $action, $size) = array_fill(0, 7, 0);
	}

	if ($date) {
		list($y, $m, $d, $t) = my_preg_split('/ +/', $date, 4);
		$date = strtotime("$d-$m-$y $t");
	}

	return [$date, $duration, $speed, $status, $error, $action, $size];
}

/* Get the last parity check from Unraid. */
function last_parity_check() {
	global $var;

	/* Files for the latest parity check. */
	$stamps	= '/var/tmp/stamps.ini';
	$resync	= '/var/tmp/resync.ini';

	/* Get the latest parity information from Unraid. */
	$synced		= file_exists($stamps) ? explode(',',file_get_contents($stamps)) : [];
	$sbSynced	= array_shift($synced) ?: _var($var,'sbSynced',0);
	$idle		= [];
	while (count($synced) > 1) {
		$idle[] = array_pop($synced) - array_pop($synced);
	}
	$action		= _var($var, 'mdResyncAction');
	$size		= _var($var, 'mdResyncSize', 0);
	if (file_exists($resync)) {
		list($action, $size) = my_explode(',', file_get_contents($resync));
	}
	$duration	= $var['sbSynced2']-$sbSynced-array_sum($idle);
	$status		= _var($var,'sbSyncExit');
	$speed		= $status==0 ? round($size*1024/$duration) : 0;
	$error		= _var($var,'sbSyncErrs',0);

	return [$duration, $speed, $status, $error, $action, $size];
}

function urlencode_path($path) {
  return str_replace("%2F", "/", urlencode($path));
}
function pgrep($process_name, $escape_arg=true) {
  $pid = exec("pgrep ".($escape_arg?escapeshellarg($process_name):$process_name), $output, $retval);
  return $retval==0 ? $pid : false;
}
function is_block($path) {
  return (@filetype(realpath($path))=='block');
}
function autov($file,$ret=false) {
  global $docroot;
  $path = $docroot.$file;
  clearstatcache(true, $path);
  $time = file_exists($path) ? filemtime($path) : 'autov_fileDoesntExist';
  $newFile = "$file?v=".$time;
  if ($ret)
    return $newFile;
  else
    echo $newFile;
}
function transpose_user_path($path) {
  if (strpos($path,'/mnt/user/')===0 && file_exists($path)) {
    $realdisk = trim(shell_exec("getfattr --absolute-names --only-values -n system.LOCATION ".escapeshellarg($path)." 2>/dev/null"));
    if (!empty($realdisk))
      $path = str_replace('/mnt/user/', "/mnt/$realdisk/", $path);
  }
  return $path;
}
// custom parse_ini_file/string functions to deal with '#' comment lines
function my_parse_ini_string($text, $sections=false, $scanner=INI_SCANNER_NORMAL) {
  return parse_ini_string(preg_replace('/^#/m',';',$text),$sections,$scanner);
}
function my_parse_ini_file($file, $sections=false, $scanner=INI_SCANNER_NORMAL) {
  return my_parse_ini_string(file_get_contents($file),$sections,$scanner);
}
function cpu_list() {
  exec('cat /sys/devices/system/cpu/*/topology/thread_siblings_list|sort -nu', $cpus);
  return $cpus;
}
function my_explode($split, $text, $count=2) {
  return array_pad(explode($split,$text,$count),$count,'');
}
function my_preg_split($split, $text, $count=2) {
  return array_pad(preg_split($split,$text,$count),$count,'');
}
function delete_file(...$file) {
  array_map('unlink',array_filter($file,'file_exists'));
}
function my_mkdir($dirname,$permissions = 0777,$recursive = false,$own = "nobody",$grp = "users") {
  if (is_dir($dirname)) return(false);
  $parent = $dirname;
  while (!is_dir($parent)){
    if (!$recursive) return(false);
    $pathinfo2 = pathinfo($parent);
    $parent = $pathinfo2["dirname"];
  }
  if (strpos($dirname,'/mnt/user/')===0) {
    $realdisk = trim(shell_exec("getfattr --absolute-names --only-values -n system.LOCATION ".escapeshellarg($parent)." 2>/dev/null"));
    if (!empty($realdisk)) {
      $dirname = str_replace('/mnt/user/', "/mnt/$realdisk/", $dirname);
      $parent = str_replace('/mnt/user/', "/mnt/$realdisk/", $parent);
    }
  }
	$fstype = trim(shell_exec(" stat -f -c '%T' $parent"));
  $rtncode = false;
  switch ($fstype) {
    case "zfs":
      $zfsdataset = trim(shell_exec("zfs list -H -o name  $parent")) ;
      $zfsdataset .= str_replace($parent,"",$dirname);
      if ($recursive) $rtncode=exec("zfs create -p \"$zfsdataset\"");else $rtncode=exec("zfs create \"$zfsdataset\"");
      if (!$rtncode) mkdir($dirname, $permissions, $recursive); else chmod($zfsdataset,$permissions);
      break;
    case "btrfs":
      if ($recursive) $rtncode=exec("btrfs subvolume create --parents \"$dirname\""); else $rtncode=exec("btrfs subvolume create \"$dirname\"");
      if (!$rtncode) mkdir($dirname, $permissions, $recursive); else chmod($dirname,$permissions);
      break;
    default:
      mkdir($dirname, $permissions, $recursive);
      break;
  }
  chown($dirname, $own);
  chgrp($dirname, $grp);
  return($rtncode);
}
function get_realvolume($path) {
  if (strpos($path,"/mnt/user/",0) === 0) 
    $reallocation = trim(shell_exec("getfattr --absolute-names --only-values -n system.LOCATION ".escapeshellarg($path)." 2>/dev/null")); 
  else {
    $realexplode = explode("/",str_replace("/mnt/","",$path));
    $reallocation = $realexplode[0];
  }
  return $reallocation;
}
?>
