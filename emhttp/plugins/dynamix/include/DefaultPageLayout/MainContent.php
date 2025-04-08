<?
// Build page content
echo "<div class='tabs'>";
$tab = 1;
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
unset($pages,$page,$pgs,$pg,$icon,$nchan,$running,$start,$stop,$row,$script,$opt,$nchan_run);
?>
</div></div>