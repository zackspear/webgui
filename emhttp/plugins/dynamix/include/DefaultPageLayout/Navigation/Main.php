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
}

echo "<div class='nav-user show'><a id='board' href='#' class='hand'><b id='bell' class='icon-u-bell system'></b></a></div>";

if ($themeHelper->isSidebarTheme()) echo "</div>";
echo "</div></div>";

unset($buttonPages,$button);
