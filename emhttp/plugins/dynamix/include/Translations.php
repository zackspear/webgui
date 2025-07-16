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
require_once "$docroot/webGui/include/MarkdownExtra.inc.php";

exec("mkdir -p /usr/local/emhttp/locale/en_US/LC_MESSAGES");


// start new session as required
if (!headers_sent() && session_status()==PHP_SESSION_NONE && !isset($login_locale)) {
  session_start();
  session_write_close();
}
// remove empty and temporary session files
$session = '/var/lib/php/sess_'.session_id();
if (file_exists($session)) {
  if (filesize($session)===0 || (count($_SESSION??[])==1 && isset($_SESSION['locale']))) unlink($session);
}

function _($text, $do=-1,$context="") {
  // PHP translation function _
  global $language;

  // sanitize context
  if ( $context && ! is_string($context) ) {
    $context = "";
  }

  $text = trim($text);
  if (!$text) return '';
  if ( !is_numeric($do) && is_string($do) ) {
    $context = $do;
    $do = -1;
  }
  if ($context) {
    $text = $context . "\004" . $text;
  }

  switch ($do) {
  case 0: // date translation
    static $language_date = ['Monday'=>_('Monday'),'Tuesday'=>_('Tuesday'),'Wednesday'=>_('Wednesday'),'Thursday'=>_('Thursday'),'Friday'=>_('Friday'),'Saturday'=>_('Saturday'),'Sunday'=>_('Sunday'),'Mon'=>_('Mon'),'Tue'=>_('Tue'),'Wed'=>_('Wed'),'Thu'=>_('Thu'),'Fri'=>_('Fri'),'Sat'=>_('Sat'),'Sun'=>_('Sun'),'January'=>_('January'),'February'=>_('February'),'March'=>_('March'),'April'=>_('April'),'May'=>_('May'),'June'=>_('June'),'July'=>_('July'),'August'=>_('August'),'September'=>_('September'),'October'=>_('October'),'November'=>_('November'),'December'=>_('December'),'Jan'=>_('Jan'),'Feb'=>_('Feb'),'Mar'=>_('Mar'),'Apr'=>_('Apr'),'May'=>_('May'),'Jun'=>_('Jun'),'Jul'=>_('Jul'),'Aug'=>_('Aug'),'Sep'=>_('Sep'),'Oct'=>_('Oct'),'Nov'=>_('Nov'),'Dec'=>_('Dec')];
    foreach ($language_date as $word => $that) if (strpos($text,$word)!==false) {$text = str_replace($word,$that,$text); break;}
    return $text;
  case 1: // number translation
    static $language_numbers = ['thirty'=>_('thirty'),'twenty-nine'=>_('twenty-nine'),'twenty-eight'=>_('twenty-eight'),'twenty-seven'=>_('twenty-seven'),'twenty-six'=>_('twenty-six'),'twenty-five'=>_('twenty-five'),'twenty-four'=>_('twenty-four'),'twenty-three'=>_('twenty-three'),'twenty-two'=>_('twenty-two'),'twenty-one'=>_('twenty-one'),'twenty'=>_('twenty'),'nineteen'=>_('nineteen'),'eighteen'=>_('eighteen'),'seventeen'=>_('seventeen'),'sixteen'=>_('sixteen'),'fifteen'=>_('fifteen'),'fourteen'=>_('fourteen'),'thirteen'=>_('thirteen'),'twelve'=>_('twelve'),'eleven'=>_('eleven'),'ten'=>_('ten'),'nine'=>_('nine'),'eight'=>_('eight'),'seven'=>_('seven'),'six'=>_('six'),'five'=>_('five'),'four'=>_('four'),'three'=>_('three'),'two'=>_('two'),'one'=>_('one'),'zero'=>_('zero')];
    return $language_numbers[$text] ?? $text;
  case 2: // time translation
    static $language_time = ['days'=>_('days'),'hours'=>_('hours'),'minutes'=>_('minutes'),'seconds'=>_('seconds'),'day'=>_('day'),'hour'=>_('hour'),'minute'=>_('minute'),'second'=>_('second')];
    foreach ($language_time as $word => $that) if (strpos($text,$word)!==false) {$text = str_replace($word,$that,$text); break;}
    return $text;
  case 3: // device translation
    [$p1,$p2] = array_pad(preg_split('/(?<=[a-z])(?= ?[0-9]+)/i',$text),2,'');
    return _($p1).$p2;
  default: // regular translation
    return preg_replace(['/\*\*(.+?)\*\*/','/\*(.+?)\*/',"/'/"],['<b>$1</b>','<i>$1</i>','&apos;'],gettext($text));
  }
}

function parse_help_file($file) {
  // parser for help text files, includes some trickery to handle PHP quirks.
  $text = array_tags((array)parse_ini_string(preg_replace(['/^$/m','/^([^:;].+)$/m','/^:(.+_help(_\d{8})?):$/m','/^:end$/m'],['>','>$1','_$1="','"'],escapeQuotes(file_get_contents($file)))));
  return array_help($text);
}

function array_tags($array) {
  // filter outdated help tags
  return array_filter($array,function($v,$k){
    $tag = explode('_',$k);
    $tag = end($tag);
    return ($tag=='help' ? true : $tag <= $_SESSION['buildDate']) && strlen($v);
  },ARRAY_FILTER_USE_BOTH);
}
function array_help(&$array) {
  // select latest applicable help
  foreach ($array as $key => $val) {
    $tag = explode('_',$key);
    if (end($tag)=='help') continue;
    $array[implode('_',array_slice($tag,0,-1))] = $array[$key];
    unset($array[$key]);
  }
  return $array;
}
// internal helper functions
function parse_array($text,&$array) {
  // multi keyword parser
  parse_str(str_replace([' ',':'],['&','='],$text),$array);
}
function array_safe($array) {
  // remove potential dangerous tags
  return array_filter($array,function($v,$k){
    return strlen($v) && !preg_match('#<(script|iframe)(.*?)>(.+?)</(script|iframe)>|<(link|meta)\s(.+?)/?>#is',html_entity_decode($v));
  },ARRAY_FILTER_USE_BOTH);
}
function parse_lang_file($file) {
  // parser for translation files, includes some trickery to handle PHP quirks.
  return array_safe((array)parse_ini_string(preg_replace(['/^\s*?(null|yes|no|true|false|on|off|none)\s*?=/mi','/^\s*?([^>].*?)\s*?=\s*?(.*)\s*?$/m','/^:(.+_(help|plug)):$/m','/^:end$/m'],['$1.=','$1="$2"','_$1="','"'],escapeQuotes(file_get_contents($file)))));
}
function parse_text($text) {
  // inline text parser
  return preg_replace_callback('/_\((.+?)\)_/m',function($m){return _($m[1]);},preg_replace(["/^:(.+_help):$/m","/^:(.+_plug):$/m","/^:end$/m"],["<?translate(\"_$1\");?>","<?if (translate(\"_$1\")):?>","<?endif;?>"],$text));
}
function escapeQuotes($text) {
  // escape double quotes
  return str_replace(["\"\n",'"'],["\" \n",'\"'],$text);
}
function translate($key) {
  // replaces multi-line sections
  global $language,$netpool,$netpool6,$netport,$nginx;
  if ($plug = isset($language[$key])) eval('?>'.Markdown($language[$key]));
  return !$plug;
}

// main
$language = [];
$locale   = $_SESSION['locale'] ?? $login_locale ?? '';
$return   = "function _(t){return t;}";
$jscript  = "$docroot/webGui/javascript/translate.en_US.js";
$root     = "$docroot/locale/en_US/LC_MESSAGES/en_US-helptext.txt";
$help     = "$docroot/locale/en_US/LC_MESSAGES/en_US-helptext.dot";
$languageDir = "$docroot/locale/en_US/LC_MESSAGES";

putenv("LC_ALL=en_US.UTF-8");
setlocale(LC_ALL, 'en_US.UTF-8');

$domain = $locale ?: 'en_US';
if ( !is_file("$languageDir/$locale.mo")) {
  $locale = 'en_US';
}

bindtextdomain($domain, '/usr/local/emhttp/locale');
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

if ($locale && $locale != 'en_US') {
  $text = "$languageDir/$locale-helptext.txt";
  if (file_exists($text)) {
    $store = "$languageDir/$locale-helptext.dot";
    // global translations
    if (!file_exists($store)) file_put_contents($store,serialize(parse_lang_file($text)));
    $language = unserialize(file_get_contents($store));
  }
  if (file_exists("$languageDir/$locale-helptext.txt")) {
    $root = "$languageDir/$locale-helptext.txt";
    $help = "$languageDir/$locale-helptext.dot";
  }
  $jscript = "$docroot/webGui/javascript/translate.$locale.js";
  if (!file_exists($jscript)) {
    // create javascript file with translations
    $source = [];
    $files = glob("$languageDir/$locale-javascript*.txt",GLOB_NOSORT);
    foreach ($files as $js) $source = array_merge($source,parse_lang_file($js));
    if (count($source)) {
      $script = ['function _(t){var l=[];'];
      foreach ($source as $key => $value) $script[] = "l[\"$key\"]=\"$value\";";
      $script[] = "return l[t.replace(/\&amp;|[\?\{\}\|\&\~\!\[\]\(\)\/\\:\*^\.\"']|<.+?\/?>/g,'').replace(/  +/g,' ')]||t;}";
      file_put_contents($jscript,implode($script));
    } else {
      file_put_contents($jscript,$return);
    }
  }
}
// split URI into translation levels
if (($_SERVER['REQUEST_URI'][0]??'')=='/') {
  if (!file_exists($help)) file_put_contents($help,serialize(parse_help_file($root)));
  $language = array_merge($language,unserialize(file_get_contents($help)));
}
unset($return,$jscript,$root,$help,$store,$uri,$more,$text);
?>
