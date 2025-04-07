<?PHP
/* Copyright 2005-2024, Lime Technology
 * Copyright 2012-2024, Bergware International.
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
require_once "$docroot/plugins/dynamix/include/ThemeHelper.php";
$themeHelper = new ThemeHelper($display['theme'], $display['width']);
$theme   = $themeHelper->getThemeName(); // keep $theme, $themes1, $themes2 vars for plugin backwards compatibility for the time being
$themes1 = $themeHelper->isTopNavTheme();
$themes2 = $themeHelper->isSidebarTheme();
$themeHelper->updateDockerLogColor($docroot);

$display['font'] = filter_var($_COOKIE['fontSize'] ?? $display['font'] ?? '',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);

$header  = $display['header']; // keep $header, $backgnd vars for plugin backwards compatibility for the time being
$backgnd = $display['background'];

$config  = "/boot/config";
$entity  = $notify['entity'] & 1 == 1;
$alerts  = '/tmp/plugins/my_alerts.txt';
$wlan0   = file_exists('/sys/class/net/wlan0');

$nchan = ['webGui/nchan/notify_poller','webGui/nchan/session_check'];
if ($wlan0) $nchan[] = 'webGui/nchan/wlan0';
$safemode = _var($var,'safeMode')=='yes';
$banner = "$config/plugins/dynamix/banner.png";

$notes = '/var/tmp/unRAIDServer.txt';
if (!file_exists($notes)) file_put_contents($notes,shell_exec("$docroot/plugins/dynamix.plugin.manager/scripts/plugin changes $docroot/plugins/unRAIDServer/unRAIDServer.plg"));

$taskPages = find_pages('Tasks');
$buttonPages = find_pages('Buttons');

function annotate($text) {echo "\n<!--\n",str_repeat("#",strlen($text)),"\n$text\n",str_repeat("#",strlen($text)),"\n-->\n";}

function generateReloadScript($loadMinutes) {
    if ($loadMinutes <= 0) return '';
    $interval = $loadMinutes * 60000;
    return "\n<script>timers.reload = setInterval(function(){if (nchanPaused === false)location.reload();},{$interval});</script>\n";
}
?>
<!DOCTYPE html>
<html <?=$display['rtl']?>lang="<?=strtok($locale,'_')?:'en'?>" class="<?= $themeHelper->getThemeHtmlClass() ?>">
<head>
<title><?=_var($var,'NAME')?>/<?=_var($myPage,'name')?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Security-Policy" content="block-all-mixed-content">
<meta name="format-detection" content="telephone=no">
<meta name="viewport" content="width=1300">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="same-origin">
<link type="image/png" rel="shortcut icon" href="/webGui/images/<?=_var($var,'mdColor','red-on')?>.png">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-fonts.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-cases.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/font-awesome.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/context.standalone.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/jquery.sweetalert.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/jquery.ui.css")?>">

<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-color-palette.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-base.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-dynamix.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/plugins/dynamix/styles/dynamix-jquery-ui.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/themes/{$theme}.css")?>">

<style>
:root {
  --customer-header-background-image: url(<?= file_exists($banner) ? autov($banner) : autov('/webGui/images/banner.png') ?>);
  <?if ($header):?>
    --customer-header-text-color: #<?=$header?>;
  <?endif;?>
  <?if ($backgnd):?>
    --customer-header-background-color: #<?=$backgnd?>;
  <?endif;?>
  <?if ($display['font']):?>
    --custom-font-size: <?=$display['font']?>%;
  <?endif;?>
}

<?
// Generate sidebar icon CSS if using sidebar theme
if ($themeHelper->isSidebarTheme()) {
  echo generate_sidebar_icon_css($taskPages, $buttonPages);
}
?>
</style>

<noscript>
<div class="upgrade_notice"><?=_("Your browser has JavaScript disabled")?></div>
</noscript>

<script src="<?autov('/webGui/javascript/dynamix.js')?>"></script>
<script src="<?autov('/webGui/javascript/translate.'.($locale?:'en_US').'.js')?>"></script>

<? require_once "$docroot/plugins/dynamix/include/DefaultPageLayout/HeadInlineJS.php"; ?>

<?
foreach ($buttonPages as $button) {
  annotate($button['file']);
  // include page specific stylesheets (if existing)
  $css = "/{$button['root']}/sheets/{$button['name']}";
  $css_stock = "$css.css";
  $css_theme = "$css-$theme.css"; // @todo add syslog for deprecation notice
  if (is_file($docroot.$css_stock)) echo '<link type="text/css" rel="stylesheet" href="',autov($css_stock),'">',"\n";
  if (is_file($docroot.$css_theme)) echo '<link type="text/css" rel="stylesheet" href="',autov($css_theme),'">',"\n";
  // create page content
  eval('?>'.parse_text($button['text']));
}

// Reload page every X minutes during extended viewing?
if (isset($myPage['Load'])) {
    echo generateReloadScript($myPage['Load']);
}
?>

<?include "$docroot/plugins/dynamix.my.servers/include/myservers1.php"?>
</head>
<body>
 <div id="displaybox">
  <div class="upgrade_notice" style="display:none"></div>
  <div id="header" class="<?=$display['banner']?>">
    <div class="logo">
      <a href="https://unraid.net" target="_blank"><?readfile("$docroot/webGui/images/UN-logotype-gradient.svg")?></a>
      <unraid-i18n-host><unraid-header-os-version></unraid-header-os-version></unraid-i18n-host>
    </div>
    <?include "$docroot/plugins/dynamix.my.servers/include/myservers2.php"?>
  </div>
  <a href="#" class="move_to_end" title="<?=_('Move To End')?>"><i class="fa fa-arrow-circle-down"></i></a>
  <a href="#" class="back_to_top" title="<?=_('Back To Top')?>"><i class="fa fa-arrow-circle-up"></i></a>
<?
// Build page menus
echo "<div id='menu'>";
if ($themeHelper->isSidebarTheme()) echo "<div id='nav-block'>";
echo "<div class='nav-tile'>";
foreach ($taskPages as $button) {
  $page = $button['name'];
  $play = $task==$page ? " active" : "";
  echo "<div class='nav-item{$play}'>";
  echo "<a href=\"/$page\" onclick=\"initab('/$page')\">"._(_var($button,'Name',$page))."</a></div>";
  // create list of nchan scripts to be started
  if (isset($button['Nchan'])) nchan_merge($button['root'], $button['Nchan']);
}
unset($taskPages);
echo "</div>";
echo "<div class='nav-tile right'>";
if (isset($myPage['Lock'])) {
  $title = $themeHelper->isSidebarTheme() ?  "" : _('Unlock sortable items');
  echo "<div class='nav-item LockButton util'><a href='#' class='hand' onclick='LockButton();return false;' title=\"$title\"><b class='icon-u-lock system green-text'></b><span>"._('Unlock sortable items')."</span></a></div>";
}
if ($display['usage']) my_usage();

foreach ($buttonPages as $button) {
  if (empty($button['Link'])) {
    $icon = $button['Icon'];
    if (substr($icon,-4)=='.png') {
      $icon = "<img src='/{$button['root']}/icons/$icon' class='system'>";
    } elseif (substr($icon,0,5)=='icon-') {
      $icon = "<b class='$icon system'></b>";
    } else {
      if (substr($icon,0,3)!='fa-') $icon = "fa-$icon";
      $icon = "<b class='fa $icon system'></b>";
    }
    $title = $themeHelper->isSidebarTheme() ? "" : " title=\""._($button['Title'])."\"";
    echo "<div class='nav-item {$button['name']} util'><a href='"._var($button,'Href','#')."' onclick='{$button['name']}();return false;'{$title}>$icon<span>"._($button['Title'])."</span></a></div>";
  } else {
    echo "<div class='{$button['Link']}'></div>";
  }
  // create list of nchan scripts to be started
  if (isset($button['Nchan'])) nchan_merge($button['root'], $button['Nchan']);
}

echo "<div class='nav-user show'><a id='board' href='#' class='hand'><b id='bell' class='icon-u-bell system'></b></a></div>";

if ($themeHelper->isSidebarTheme()) echo "</div>";
echo "</div></div>";

unset($buttonPages,$button);

// Build page content
echo "<div class='tabs'>";
$tab = 1;
$pages = [];
if (!empty($myPage['text'])) $pages[$myPage['name']] = $myPage;
if (_var($myPage,'Type')=='xmenu') $pages = array_merge($pages, find_pages($myPage['name']));
if (isset($myPage['Tabs'])) $display['tabs'] = strtolower($myPage['Tabs'])=='true' ? 0 : 1;
$tabbed = $display['tabs']==0 && count($pages)>1;

foreach ($pages as $page) {
  $close = false;
  if (isset($page['Title'])) {
    eval("\$title=\"".htmlspecialchars($page['Title'])."\";");
    if ($tabbed) {
      echo "<div class='tab'><input type='radio' id='tab{$tab}' name='tabs' onclick='settab(this.id)'><label for='tab{$tab}'>";
      echo tab_title($title,$page['root'],_var($page,'Tag',false));
      echo "</label><div class='content'>";
      $close = true;
    } else {
      if ($tab==1) echo "<div class='tab'><input type='radio' id='tab{$tab}' name='tabs'><div class='content shift'>";
      echo "<div class='title'><span class='left'>";
      echo tab_title($title,$page['root'],_var($page,'Tag',false));
      echo "</span></div>";
    }
    $tab++;
  }
  if (isset($page['Type']) && $page['Type']=='menu') {
    $pgs = find_pages($page['name']);
    foreach ($pgs as $pg) {
      @eval("\$title=\"".htmlspecialchars($pg['Title'])."\";");
      $icon = _var($pg,'Icon',"<i class='icon-app PanelIcon'></i>");
      if (substr($icon,-4)=='.png') {
        $root = $pg['root'];
        if (file_exists("$docroot/$root/images/$icon")) {
          $icon = "<img src='/$root/images/$icon' class='PanelImg'>";
        } elseif (file_exists("$docroot/$root/$icon")) {
          $icon = "<img src='/$root/$icon' class='PanelImg'>";
        } else {
          $icon = "<i class='icon-app PanelIcon'></i>";
        }
      } elseif (substr($icon,0,5)=='icon-') {
        $icon = "<i class='$icon PanelIcon'></i>";
      } elseif ($icon[0]!='<') {
        if (substr($icon,0,3)!='fa-') $icon = "fa-$icon";
        $icon = "<i class='fa $icon PanelIcon'></i>";
      }
      echo "<div class=\"Panel\"><a href=\"/$path/{$pg['name']}\" onclick=\"$.cookie('one','tab1')\"><span>$icon</span><div class=\"PanelText\">"._($title)."</div></a></div>";
    }
  }
  // create list of nchan scripts to be started
  if (isset($page['Nchan'])) nchan_merge($page['root'], $page['Nchan']);
  annotate($page['file']);
  // include page specific stylesheets (if existing)
  $css = "/{$page['root']}/sheets/{$page['name']}";
  $css_stock = "$css.css";
  $css_theme = "$css-$theme.css";
  if (is_file($docroot.$css_stock)) echo '<link type="text/css" rel="stylesheet" href="',autov($css_stock),'">',"\n";
  if (is_file($docroot.$css_theme)) echo '<link type="text/css" rel="stylesheet" href="',autov($css_theme),'">',"\n";
  // create page content
  empty($page['Markdown']) || $page['Markdown']=='true' ? eval('?>'.Markdown(parse_text($page['text']))) : eval('?>'.parse_text($page['text']));
  if ($close) echo "</div></div>";
}
if (count($pages)) {
  $running = file_exists($nchan_pid) ? file($nchan_pid,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) : [];
  $start   = array_diff($nchan, $running);  // returns any new scripts to be started
  $stop    = array_diff($running, $nchan);  // returns any old scripts to be stopped
  $running = array_merge($start, $running); // update list of current running nchan scripts
  // start nchan scripts which are new
  foreach ($start as $row) {
    $script = explode(':',$row)[0];
    exec("$docroot/$script &>/dev/null &");
  }
  // stop nchan scripts with the :stop option
  foreach ($stop as $row) {
    [$script,$opt] = my_explode(':',$row);
    if ($opt == 'stop') {
      exec("pkill -f $docroot/$script &>/dev/null &");
      array_splice($running,array_search($row,$running),1);
    }
  }
  if (count($running)) file_put_contents($nchan_pid,implode("\n",$running)."\n"); else @unlink($nchan_pid);
}
unset($pages,$page,$pgs,$pg,$icon,$nchan,$running,$start,$stop,$row,$script,$opt,$nchan_run);
?>
</div></div>
<div class="spinner fixed"></div>
<form name="rebootNow" method="POST" action="/webGui/include/Boot.php"><input type="hidden" name="cmd" value="reboot"></form>
<iframe id="progressFrame" name="progressFrame" frameborder="0"></iframe>

<? require_once "$docroot/webGui/include/DefaultPageLayout/Footer.php"; ?>

<script>
// Firefox specific workaround, not needed anymore in firefox version 100 and higher
//if (typeof InstallTrigger!=='undefined') $('#nav-block').addClass('mozilla');

function parseINI(msg) {
  var regex = {
    section: /^\s*\[\s*\"*([^\]]*)\s*\"*\]\s*$/,
    param: /^\s*([^=]+?)\s*=\s*\"*(.*?)\s*\"*$/,
    comment: /^\s*;.*$/
  };
  var value = {};
  var lines = msg.split(/[\r\n]+/);
  var section = null;
  lines.forEach(function(line) {
    if (regex.comment.test(line)) {
      return;
    } else if (regex.param.test(line)) {
      var match = line.match(regex.param);
      if (section) {
        value[section][match[1]] = match[2];
      } else {
        value[match[1]] = match[2];
      }
    } else if (regex.section.test(line)) {
      var match = line.match(regex.section);
      value[match[1]] = {};
      section = match[1];
    } else if (line.length==0 && section) {
      section = null;
    };
  });
  return value;
}
// unraid animated logo
var unraid_logo = '<?readfile("$docroot/webGui/images/animated-logo.svg")?>';

var defaultPage = new NchanSubscriber('/sub/session,var<?=$entity?",notify":""?>',{subscriber:'websocket', reconnectTimeout:5000});
defaultPage.on('message', function(msg,meta) {
  switch (meta.id.channel()) {
  case 0:
    // stale session, force login
    if (csrf_token != msg) location.replace('/');
    break;
  case 1:
    // message field in footer
    var ini = parseINI(msg);
    switch (ini['fsState']) {
      case 'Stopped'   : var status = "<span class='red strong'><i class='fa fa-stop-circle'></i> <?=_('Array Stopped')?></span>"; break;
      case 'Started'   : var status = "<span class='green strong'><i class='fa fa-play-circle'></i> <?=_('Array Started')?></span>"; break;
      case 'Formatting': var status = "<span class='green strong'><i class='fa fa-play-circle'></i> <?=_('Array Started')?></span>&bullet;<span class='orange strong tour'><?=_('Formatting device(s)')?></span>"; break;
      default          : var status = "<span class='orange strong'><i class='fa fa-pause-circle'></i> "+_('Array '+ini['fsState'])+"</span>";
    }
    if (ini['mdResyncPos'] > 0) {
      var resync = ini['mdResyncAction'].split(/\s+/);
      switch (resync[0]) {
        case 'recon': var action = resync[1]=='P' ? "<?=_('Parity-Sync')?>" : "<?=_('Data-Rebuild')?>"; break;
        case 'check': var action = resync.length>1 ? "<?=_('Parity-Check')?>" : "<?=_('Read-Check')?>"; break;
        case 'clear': var action = "<?=_('Disk-Clear')?>"; break;
        default     : var action = '';
      }
      action += " "+(ini['mdResyncPos']/(ini['mdResyncSize']/100+1)).toFixed(1)+" %";
      status += "&bullet;<span class='orange strong tour'>"+action.replace('.','<?=_var($display,'number','.,')[0]?>');
      if (ini['mdResyncDt']==0) status += " &bullet; <?=_('Paused')?>";
      status += "</span>";
    }
    if (ini['fsProgress']) status += "&bullet;<span class='blue strong tour'>"+_(ini['fsProgress'])+"</span>";
    $('#statusbar').html(status);
    break;
  case 2:
    // notifications
    var bell1 = 0, bell2 = 0, bell3 = 0;
    $.each($.parseJSON(msg), function(i, notify){
      switch (notify.importance) {
        case 'alert'  : bell1++; break;
        case 'warning': bell2++; break;
        case 'normal' : bell3++; break;
      }
<?if ($notify['display']==0):?>
      if (notify.show) {
        $.jGrowl(notify.subject+'<br>'+notify.description,{
          group: notify.importance,
          header: notify.event+': '+notify.timestamp,
          theme: notify.file,
          beforeOpen: function(e,m,o){if ($('div.jGrowl-notification').hasClass(notify.file)) return(false);},
          afterOpen: function(e,m,o){if (notify.link) $(e).css('cursor','pointer');},
          click: function(e,m,o){if (notify.link) location.replace(notify.link);},
          close: function(e,m,o){$.post('/webGui/include/Notify.php',{cmd:'hide',file:"<?=$notify['path'].'/unread/'?>"+notify.file,csrf_token:csrf_token}<?if ($notify['life']==0):?>,function(){$.post('/webGui/include/Notify.php',{cmd:'archive',file:notify.file,csrf_token:csrf_token});}<?endif;?>);}
        });
      }
<?endif;?>
    });
    $('#bell').removeClass('red-orb yellow-orb green-orb').prop('title',"<?=_('Alerts')?> ["+bell1+']\n'+"<?=_('Warnings')?> ["+bell2+']\n'+"<?=_('Notices')?> ["+bell3+']');
    if (bell1) $('#bell').addClass('red-orb'); else
    if (bell2) $('#bell').addClass('yellow-orb'); else
    if (bell3) $('#bell').addClass('green-orb');
    break;
  }
});

<?if ($wlan0):?>
function wlanSettings() {
  $.cookie('one','tab<?=count(glob("$docroot/webGui/Eth*.page"))?>');
  window.location = '/Settings/NetworkSettings';
}

var nchan_wlan0 = new NchanSubscriber('/sub/wlan0',{subscriber:'websocket', reconnectTimeout:5000});
nchan_wlan0.on('message', function(msg) {
  var wlan = JSON.parse(msg);
  $('#wlan0').removeClass().addClass(wlan.color).attr('title',wlan.title);
});
nchan_wlan0.start();
<?endif;?>

var nchan_plugins = new NchanSubscriber('/sub/plugins',{subscriber:'websocket', reconnectTimeout:5000});
nchan_plugins.on('message', function(data) {
  if (!data || openDone(data)) return;
  var box = $('pre#swaltext');
  const text = box.html().split('<br>');
  if (data.slice(-1) == '\r') {
    text[text.length-1] = data.slice(0,-1);
  } else {
    text.push(data.slice(0,-1));
  }
  box.html(text.join('<br>')).scrollTop(box[0].scrollHeight);
});

var nchan_docker = new NchanSubscriber('/sub/docker',{subscriber:'websocket', reconnectTimeout:5000});
nchan_docker.on('message', function(data) {
  if (!data || openDone(data)) return;
  var box = $('pre#swaltext');
  data = data.split('\0');
  switch (data[0]) {
  case 'addLog':
    var rows = document.getElementsByClassName('logLine');
    if (rows.length) {
      var row = rows[rows.length-1];
      row.innerHTML += data[1]+'<br>';
    }
    break;
  case 'progress':
    var rows = document.getElementsByClassName('progress-'+data[1]);
    if (rows.length) {
      rows[rows.length-1].textContent = data[2];
    }
    break;
  case 'addToID':
    var rows = document.getElementById(data[1]);
    if (rows === null) {
      rows = document.getElementsByClassName('logLine');
      if (rows.length) {
        var row = rows[rows.length-1];
        row.innerHTML += '<span id="'+data[1]+'">IMAGE ID ['+data[1]+']: <span class="content">'+data[2]+'</span><span class="progress-'+data[1]+'"></span>.</span><br>';
      }
    } else {
      var rows_content = rows.getElementsByClassName('content');
      if (!rows_content.length || rows_content[rows_content.length-1].textContent != data[2]) {
        rows.innerHTML += '<span class="content">'+data[2]+'</span><span class="progress-'+data[1]+'"></span>.';
      }
    }
    break;
  case 'show_Wait':
    progress_span[data[1]] = document.getElementById('wait-'+data[1]);
    progress_dots[data[1]] = setInterval(function(){if (((progress_span[data[1]].innerHTML += '.').match(/\./g)||[]).length > 9) progress_span[data[1]].innerHTML = progress_span[data[1]].innerHTML.replace(/\.+$/,'');},500);
    break;
  case 'stop_Wait':
    clearInterval(progress_dots[data[1]]);
    progress_span[data[1]].innerHTML = '';
    break;
  default:
    box.html(box.html()+data[0]);
    break;
  }
  box.scrollTop(box[0].scrollHeight);
});

var nchan_vmaction = new NchanSubscriber('/sub/vmaction',{subscriber:'websocket', reconnectTimeout:5000});
nchan_vmaction.on('message', function(data) {
  if (!data || openDone(data) || openError(data)) return;
  var box = $('pre#swaltext');
  data = data.split('\0');
  switch (data[0]) {
  case 'addLog':
    var rows = document.getElementsByClassName('logLine');
    if (rows.length) {
      var row = rows[rows.length-1];
      row.innerHTML += data[1]+'<br>';
    }
    break;
  case 'progress':
    var rows = document.getElementsByClassName('progress-'+data[1]);
    if (rows.length) {
      rows[rows.length-1].textContent = data[2];
    }
    break;
  case 'addToID':
    var rows = document.getElementById(data[1]);
    if (rows === null) {
      rows = document.getElementsByClassName('logLine');
      if (rows.length) {
        var row = rows[rows.length-1];
        row.innerHTML += '<span id="'+data[1]+'">'+data[1]+': <span class="content">'+data[2]+'</span><span class="progress-'+data[1]+'"></span>.</span><br>';
      }
    } else {
      var rows_content = rows.getElementsByClassName('content');
      if (!rows_content.length || rows_content[rows_content.length-1].textContent != data[2]) {
        rows.innerHTML += '<span class="content">'+data[2]+'</span><span class="progress-'+data[1]+'"></span>.';
      }
    }
    break;
  case 'show_Wait':
    progress_span[data[1]] = document.getElementById('wait-'+data[1]);
    progress_dots[data[1]] = setInterval(function(){if (((progress_span[data[1]].innerHTML += '.').match(/\./g)||[]).length > 9) progress_span[data[1]].innerHTML = progress_span[data[1]].innerHTML.replace(/\.+$/,'');},500);
    break;
  case 'stop_Wait':
    clearInterval(progress_dots[data[1]]);
    progress_span[data[1]].innerHTML = '';
    break;
  default:
    box.html(box.html()+data[0]);
    break;
  }
  box.scrollTop(box[0].scrollHeight);
});

const scrollDuration = 500;
$(window).scroll(function() {
  if ($(this).scrollTop() > 0) {
    $('.back_to_top').fadeIn(scrollDuration);
  } else {
    $('.back_to_top').fadeOut(scrollDuration);
  }
<?if ($themeHelper->isTopNavTheme()):?>
  var top = $('div#header').height()-1; // header height has 1 extra pixel to cover overlap
  $('div#menu').css($(this).scrollTop() > top ? {position:'fixed',top:'0'} : {position:'absolute',top:top+'px'});
  // banner
  $('div.upgrade_notice').css($(this).scrollTop() > 24 ? {position:'fixed',top:'0'} : {position:'absolute',top:'24px'});
<?endif;?>
});

$('.move_to_end').click(function(event) {
  event.preventDefault();
  $('html,body').animate({scrollTop:$(document).height()},scrollDuration);
  return false;
});

$('.back_to_top').click(function(event) {
  event.preventDefault();
  $('html,body').animate({scrollTop:0},scrollDuration);
  return false;
});

<?if ($entity):?>
$.post('/webGui/include/Notify.php',{cmd:'init',csrf_token:csrf_token});
<?endif;?>
$(function() {
  defaultPage.start();
  $('div.spinner.fixed').html(unraid_logo);
  setTimeout(function(){$('div.spinner').not('.fixed').each(function(){$(this).html(unraid_logo);});},500); // display animation if page loading takes longer than 0.5s
  shortcut.add('F1',function(){HelpButton();});
<?if (_var($var,'regTy')=='unregistered'):?>
  $('#licensetype').addClass('orange-text');
<?elseif (!in_array(_var($var,'regTy'),['Trial','Basic','Plus','Pro'])):?>
  $('#licensetype').addClass('red-text');
<?endif;?>
  $('input[value="<?=_("Apply")?>"],input[value="Apply"],input[name="cmdEditShare"],input[name="cmdUserEdit"]').prop('disabled',true);
  $('form').find('select,input[type=text],input[type=number],input[type=password],input[type=checkbox],input[type=radio],input[type=file],textarea').not('.lock').each(function(){$(this).on('input change',function() {
    var form = $(this).parentsUntil('form').parent();
    form.find('input[value="<?=_("Apply")?>"],input[value="Apply"],input[name="cmdEditShare"],input[name="cmdUserEdit"]').not('input.lock').prop('disabled',false);
    form.find('input[value="<?=_("Done")?>"],input[value="Done"]').not('input.lock').val("<?=_('Reset')?>").prop('onclick',null).off('click').click(function(){formHasUnsavedChanges=false;refresh(form.offset().top);});
  });});
  // add leave confirmation when form has changed without applying (opt-in function)
  if ($('form.js-confirm-leave').length>0) {
    $('form.js-confirm-leave').on('change',function(e){formHasUnsavedChanges=true;}).on('submit',function(e){formHasUnsavedChanges=false;});
    $(window).on('beforeunload',function(e){if (formHasUnsavedChanges) return '';}); // note: the browser creates its own popup window and warning message
  }
  // form parser: add escapeQuotes protection
  $('form').each(function(){
    var action = $(this).prop('action').actionName();
    if (action=='update.htm' || action=='update.php') {
      var onsubmit = $(this).attr('onsubmit')||'';
      $(this).attr('onsubmit','clearTimeout(timers.flashReport);escapeQuotes(this);'+onsubmit);
    }
  });
  var top = ($.cookie('top')||0) - $('.tabs').offset().top - 75;
  if (top>0) {$('html,body').scrollTop(top);}
  $.removeCookie('top');
  if ($.cookie('addAlert') != null) bannerAlert(addAlert.text,addAlert.cmd,addAlert.plg,addAlert.func);
<?if ($safemode):?>
  showNotice("<?=_('System running in')?> <b><?=('safe mode')?></b>");
<?else:?>
<?if (!_var($notify,'system')):?>
  addBannerWarning("<?=_('System notifications are')?> <b><?=_('disabled')?></b>. <?=_('Click')?> <a href='/Settings/Notifications'><?=_('here')?></a> <?=_('to change notification settings')?>.",true,true);
<?endif;?>
<?endif;?>
  var opts = [];
  context.settings({above:false});
  opts.push({header:"<?=_('Notifications')?>"});
  opts.push({text:"<?=_('View')?>",icon:'fa-folder-open-o',action:function(e){e.preventDefault();openNotifier();}});
  opts.push({text:"<?=_('History')?>",icon:'fa-file-text-o',action:function(e){e.preventDefault();viewHistory();}});
  opts.push({text:"<?=_('Acknowledge')?>",icon:'fa-check-square-o',action:function(e){e.preventDefault();closeNotifier();}});
  context.attach('#board',opts);
  if (location.pathname.search(/\/(AddVM|UpdateVM|AddContainer|UpdateContainer)/)==-1) {
    $('blockquote.inline_help').each(function(i) {
      $(this).attr('id','helpinfo'+i);
      var pin = $(this).prev();
      if (!pin.prop('nodeName')) pin = $(this).parent().prev();
      while (pin.prop('nodeName') && pin.prop('nodeName').search(/(table|dl)/i)==-1) pin = pin.prev();
      pin.find('tr:first,dt:last').each(function() {
        var node = $(this);
        var name = node.prop('nodeName').toLowerCase();
        if (name=='dt') {
          while (!node.html() || node.html().search(/(<input|<select|nbsp;)/i)>=0 || name!='dt') {
            if (name=='dt' && node.is(':first-of-type')) break;
            node = node.prev();
            name = node.prop('nodeName').toLowerCase();
          }
          node.css('cursor','help').click(function(){$('#helpinfo'+i).toggle('slow');});
        } else {
          if (node.html() && (name!='tr' || node.children('td:first').html())) node.css('cursor','help').click(function(){$('#helpinfo'+i).toggle('slow');});
        }
      });
    });
  }
  $('form').append($('<input>').attr({type:'hidden', name:'csrf_token', value:csrf_token}));
  setInterval(function(){if ($(document).height() > $(window).height()) $('.move_to_end').fadeIn(scrollDuration); else $('.move_to_end').fadeOut(scrollDuration);},250);
});

var gui_pages_available = [];
<?
  $gui_pages = glob("/usr/local/emhttp/plugins/*/*.page");
  array_walk($gui_pages,function($value,$key){ ?>
    gui_pages_available.push('<?=basename($value,".page")?>'); <?
  });
?>

function isValidURL(url) {
  try {
    var ret = new URL(url);
    return ret;
  } catch (err) {
    return false;
  }
}

$('body').on('click','a,.ca_href', function(e) {
  if ($(this).hasClass('ca_href')) {
    var ca_href = true;
    var href=$(this).attr('data-href');
    var target=$(this).attr('data-target');
  } else {
    var ca_href = false;
    var href = $(this).attr('href');
    var target = $(this).attr('target');
  }
  if (href) {
    href = href.trim();
    // Sanitize href to prevent XSS
    href = href.replace(/[<>"]/g, '');
    if (href.match('https?://[^\.]*.(my)?unraid.net/') || href.indexOf('https://unraid.net/') == 0 || href == 'https://unraid.net' || href.indexOf('http://lime-technology.com') == 0) {
      if (ca_href) window.open(href,target);
      return;
    }
    if (href !== '#' && href.indexOf('javascript') !== 0) {
      var dom = isValidURL(href);
      if (dom == false) {
        if (href.indexOf('/') == 0) return;  // all internal links start with "/"
      var baseURLpage = href.split('/');
        if (gui_pages_available.includes(baseURLpage[0])) return;
      }
      if ($(this).hasClass('localURL')) return;
      try {
        var domainsAllowed = JSON.parse($.cookie('allowedDomains'));
      } catch(e) {
        var domainsAllowed = new Object();
      }
      $.cookie('allowedDomains',JSON.stringify(domainsAllowed),{expires:3650}); // rewrite cookie to further extend expiration by 400 days
      if (domainsAllowed[dom.hostname]) return;
      e.preventDefault();
      swal({
        title: "<?=_('External Link')?>",
        text: "<span title='"+href+"'><?=_('Clicking OK will take you to a 3rd party website not associated with Lime Technology')?><br><br><b>"+href+"<br><br><input id='Link_Always_Allow' type='checkbox'></input><?=_('Always Allow')?> "+dom.hostname+"</span>",
        html: true,
        animation: 'none',
        type: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        cancelButtonText: "<?=_('Cancel')?>",
        confirmButtonText: "<?=_('OK')?>"
      },function(isConfirm) {
        if (isConfirm) {
          if ($('#Link_Always_Allow').is(':checked')) {
            domainsAllowed[dom.hostname] = true;
            $.cookie('allowedDomains',JSON.stringify(domainsAllowed),{expires:3650});
          }
          var popupOpen = window.open(href,target);
          if (!popupOpen || popupOpen.closed || typeof popupOpen == 'undefined') {
            var popupWarning = addBannerWarning("<?=_('Popup Blocked');?>");
            setTimeout(function(){removeBannerWarning(popupWarning);},10000);
          }
        }
      });
    }
  }
});

// Start & stop live updates when window loses focus
var nchanPaused = false;
var blurTimer = false;

$(window).focus(function() {
  nchanFocusStart();
});

// Stop nchan on loss of focus
<? if ( $display['liveUpdate'] == "no" ):?>
$(window).blur(function() {
  blurTimer = setTimeout(function(){
    nchanFocusStop();
  },30000);
});
<?endif;?>

document.addEventListener("visibilitychange", (event) => {
  <? if ( $display['liveUpdate'] == "no" ):?>
  if (document.hidden) {
    nchanFocusStop();
  }
<?else:?>
  if (document.hidden) {
    nchanFocusStop();
  } else {
    nchanFocusStart();
  }
<?endif;?>
});

function nchanFocusStart() {
  if ( blurTimer !== false ) {
    clearTimeout(blurTimer);
    blurTimer = false;
  }

  if (nchanPaused !== false ) {
    removeBannerWarning(nchanPaused);
    nchanPaused = false;

    try {
      pageFocusFunction();
    } catch(error) {}

    subscribers.forEach(function(e) {
      e.start();
    });
  }
}

function nchanFocusStop(banner=true) {
  if ( subscribers.length ) {
    if ( nchanPaused === false ) {
      var newsub = subscribers;
      subscribers.forEach(function(e) {
        try {
          e.stop();
        } catch(err) {
          newsub.splice(newsub.indexOf(e,1));
        }
      });
      subscribers = newsub;
      if ( banner && subscribers.length ) {
        nchanPaused = addBannerWarning("<?=_('Live Updates Paused');?>",false,true );
      }
    }
  }
}
</script>
</body>
</html>
