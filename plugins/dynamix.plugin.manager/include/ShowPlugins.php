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
require_once "$docroot/webGui/include/Markdown.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php";

$current = parse_ini_file('/etc/unraid-version');
$release = $_GET['release'] ?? false;
$system  = $_GET['system'] ?? false;
$empty   = true;
$nofetch = false;
$builtin = ['unRAIDServer','dynamix'];
$https   = ['stable' => 'https://raw.github.com/limetech/\&name;/master/\&name;.plg',
            'next'   => 'https://s3.amazonaws.com/dnld.lime-technology.com/\&category;/\&name;.plg'];

foreach (glob("/var/log/plugins/*.plg",GLOB_NOSORT) as $plugin_link) {
//only consider symlinks
  $plugin_file = @readlink($plugin_link);
  if ($plugin_file === false) continue;
//plugin name
  $name = plugin('name',$plugin_file) ?: basename($plugin_file,".plg");
  $custom = in_array($name,$builtin);
//switch between system and custom plugins
  if (($system && !$custom) || (!$system && $custom)) continue;
//forced plugin check
  $checked = check_plugin("$name.plg");
//OS update?
  $os = $system && $name==$builtin[0];
  $toggle = false;
//toggle stable/next release?
  if ($os && $release) {
    $toggle = plugin('version',$plugin_file);
    $cat = strpos($toggle,'rc')!==false ? 'stable' : 'next';
    $tmp_plg = "$name-.plg";
    $tmp_file = "/var/tmp/$name.plg";
    copy($plugin_file,$tmp_file);
    exec("sed -ri 's|^(<!ENTITY category).*|\\1 \"{$cat}\">|' $tmp_file");
    exec("sed -ri 's|^(<!ENTITY pluginURL).*|\\1 \"{$https[$release]}\">|' $tmp_file");
    symlink($tmp_file,"/var/log/plugins/$tmp_plg");
    if (check_plugin($tmp_plg)) {
      copy("/tmp/plugins/$tmp_plg",$tmp_file);
      $plugin_file = $tmp_file;
    }
  }
//link/icon
  $icon = icon($name);
  if ($launch = plugin('launch',$plugin_file))
    $link = "<a href='/$launch'><img src='/$icon' class='list'></a>";
  else
    $link = "<img src='/$icon' class='list'>";
//description
  $readme = "plugins/{$name}/README.md";
  if (file_exists($readme))
    $desc = Markdown(file_get_contents($readme));
  else
    $desc = Markdown("**{$name}**");
//author
  $author = plugin('author',$plugin_file) ?: "anonymous";
//version
  $version = plugin('version',$plugin_file) ?: "unknown";
//category
  $cat = strpos($version,'rc')!==false ? 'next' : 'stable';
//status
  $status = 'unknown';
  $changes_file = $plugin_file;
  $URL = plugin('pluginURL',$plugin_file);
  if ($URL !== false) {
    $filename = "/tmp/plugins/".(($os && $release) ? $tmp_plg : basename($URL));
    if ($checked && file_exists($filename)) {
      if ($toggle && $toggle != $version) {
        $status = make_link('install',$plugin_file,'forced');
      } else {
        $latest = plugin('version',$filename);
        if (strcmp($latest,$version) > 0) {
          $unRAID = plugin('unRAID',$filename);
          if ($unRAID === false || version_compare($current['version'],$unRAID,'>=')) {
            $version .= "<br><span class='red-text'>{$latest}</span>";
            $status = make_link("update",basename($plugin_file));
            $changes_file = $filename;
          } else {
            $status = "up-to-date";
          }
        } else {
          $status = "up-to-date";
        }
      }
    } else $nofetch = true;
  }
  $changes = plugin('changes',$changes_file);
  if ($changes !== false) {
    $txtfile = "/tmp/plugins/".basename($plugin_file,'.plg').".txt";
    file_put_contents($txtfile,$changes);
    $version .= "&nbsp;<a href='#' title='View Release Notes' onclick=\"openBox('/plugins/dynamix.plugin.manager/include/ShowChanges.php?file=".urlencode($txtfile)."','Release Notes',600,900); return false\"><img src='/webGui/images/information.png' class='icon'></a>";
  }
//write plugin information
  $empty = false;
  echo "<tr>";
  echo "<td style='vertical-align:top;width:64px'><p style='text-align:center'>{$link}</p></td>";
  echo "<td><span class='desc_readmore' style='display:block'>{$desc}</span></td>";
  echo "<td>{$author}</td>";
  echo "<td>{$version}</td>";
  echo "<td>{$status}</td>";
  echo "<td>";
  if ($system) {
    if ($os) {
      echo "<select id='change_release' class='auto' onchange='change_release(this.value)'>";
      echo mk_option($cat,'stable');
      echo mk_option($cat,'next');
      echo "</select>";
    }
  } else {
    echo make_link('remove',basename($plugin_file));
  }
  echo "</td>";
  echo "</tr>";
//remove temporary symlink
  @unlink("/var/log/plugins/$tmp_plg");
}
if ($empty) echo "<tr><td colspan='6' style='text-align:center;padding-top:12px'><i class='fa fa-check-square-o icon'></i> No plugins installed</td><tr>";
elseif ($nofetch) echo "<tr><td colspan='4'></td><td><input type='button' value='Retry' onclick='change_release()'></td><td></td><tr>";
?>
