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
session_start();
session_write_close();

function _($text) {
  global $language;
  if (!$text) return '';
  $data = $language[preg_replace(['/\&amp;|[\?\{\}\|\&\~\!\[\]\(\)\/\\:\*^\.\"\']|<.+?\/?>/','/^(null|yes|no|true|false|on|off|none)$/i','/  +/'],['','$1.',' '],$text)] ?? $text;
  return strpos($data,'*')===false ? $data : preg_replace(['/\*\*(.+?)\*\*/','/\*(.+?)\*/'],['<b>$1</b>','<i>$1</i>'],$data);
}
function parse_lang_file($file) {
  return array_filter(parse_ini_string(preg_replace(['/"/m','/^(null|yes|no|true|false|on|off|none)=/mi','/^([^>].*)=([^"\'`].*)$/m','/^:((help|plug)\d*)$/m','/^:end$/m'],['\'','$1.=','$1="$2"',"_$1_=\"",'"'],str_replace("=\n","=''\n",file_get_contents($file)))),'strlen');
}
function rewrite($text) {
  return preg_replace_callback('/_\((.+?)\)_/m',function($m){return _($m[1]);},preg_replace(["/^:((help|plug)\d*)$/m","/^:end$/m"],["<?if (translate(\"_$1_\")):?>","<?endif;?>"],$text));
}
function my_lang($text,$do=0) {
  global $language;
  switch ($do) {
  case 0: // date translation
    $months = isset($language['Months_array']) ? explode(' ',$language['Months_array']) : [];
    $days = isset($language['Days_array']) ? explode(' ',$language['Days_array']) : [];
    foreach ($months as $month) {
      [$word,$that] = explode(':',$month);
      if (strpos($text,$word)!==false) {$text = str_replace($word,$that,$text); break;}
    }
    foreach ($days as $day) {
      [$word,$that] = explode(':',$day);
      if (strpos($text,$word)!==false) {$text = str_replace($word,$that,$text); break;}
    }
    $keys = ['today','yesterday','day ago','days ago','week ago','weeks ago','month ago','months ago'];
    foreach ($keys as $key) if (isset($language[$key])) $text = str_replace($key,$language[$key],$text);
    break;
  case 1: // number translation
    $numbers = isset($language['Numbers_array']) ? explode(' ',$language['Numbers_array']) : [];
    foreach ($numbers as $number) {
      [$word,$that] = explode(':',$number);
      if (strpos($text,$word)!==false) {$text = str_replace($word,$that,$text); break;}
    }
    break;
  case 2: // time translation
    $time1 = ['days'=>$language['days']??'','hours'=>$language['hours']??'','minutes'=>$language['minutes']??'','seconds'=>$language['seconds']??''];
    $time2 = ['day'=>$language['day']??'','hour'=>$language['hour']??'','minute'=>$language['minute']??'','second'=>$language['second']??''];
    foreach ($time1 as $word => $that) {
      if ($that && strpos($text,$word)!==false) {
        $text = str_replace($word,$that,$text);
      } else {
        $one = substr($word,0,-1);
        if ($time2[$one]) $text = str_replace($one,$time2[$one],$text);
      }
    }
    if (isset($language['Average speed'])) $text = str_replace('Average speed',$language['Average speed'],$text);
    break;
  case 3: // device translation
    [$p1,$p2] = explode(' ',$text);
    $text = rtrim(_($p1)." $p2");
  }
  return $text;
}
function translate($key) {
  global $language;
  if ($plug = isset($language[$key])) eval('?>'.Markdown($language[$key]));
  return !$plug;
}
$language = [];
$locale   = $_SESSION['locale'];
$return   = 'function _(t){return t;}';
$jscript  = "$docroot/webGui/javascript/translate.en.js";

if ($locale) {
  $text = "$docroot/languages/$locale/translations.txt";
  if (file_exists($text)) {
    $basis = "$docroot/languages/$locale/translations.dot";
    // global translations
    if (!file_exists($basis)) file_put_contents($basis,serialize(parse_lang_file($text)));
    $language = unserialize(file_get_contents($basis));
  }
  $jscript = "$docroot/webGui/javascript/translate.$locale.js";
  if (!file_exists($jscript)) {
    // create javascript file with translations
    $source = [];
    $files = glob("$docroot/languages/$locale/javascript*.txt",GLOB_NOSORT);
    foreach ($files as $js) $source = array_merge($source,parse_lang_file($js));
    if (count($source)) {
      $script = ['function _(t){var l={};'];
      foreach ($source as $key => $value) $script[] = "l[\"$key\"]=\"$value\";";
      $script[] ="return l[t.replace(/\&amp;|[\?\{\}\|\&\~\!\[\]\(\)\/\\:\*^\.\"']|<.+?\/?>/g,'').replace(/  +/g,' ')]||t;}";
      file_put_contents($jscript,implode('',$script));
    } else {
      file_put_contents($jscript,$return);
    }
  }
  // split URI into translation levels
  $uri = array_filter(explode('/',strtok($_SERVER['REQUEST_URI'],'?')));
  foreach($uri as $more) {
    $more = strtolower($more);
    $text = "$docroot/languages/$locale/$more.txt";
    if (file_exists($text)) {
      // additional translations
      $other = "$docroot/languages/$locale/$more.dot";
      if (!file_exists($other)) file_put_contents($other,serialize(parse_lang_file($text)));
      $language = array_merge($language,unserialize(file_get_contents($other)));
    }
  }
} elseif (!file_exists($jscript)) {
  file_put_contents($jscript,$return);
}
?>
