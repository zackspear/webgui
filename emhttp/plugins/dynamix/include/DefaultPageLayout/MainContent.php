<?php
/**
 * Main content delegator for the Unraid web interface.
 * Includes the correct template based on tabbed state.
 * 
 * Even if DisplaySettings is not enabled for tabs, pages with Tabs="true" will use tabs
 * and pages with Tabs="false" will not use tabs.
 */
$display['tabs'] = isset($myPage['Tabs'])
    ? (strtolower($myPage['Tabs']) == 'true' ? 0 : 1)
    : $display['tabs'];
$tabbed = $display['tabs'] == 0 && count($pages) > 1;
$contentInclude = $tabbed ? 'MainContentTabbed.php' : 'MainContentNotab.php';

$defaultIcon = "<i class=\"icon-app PanelIcon\"></i>";
function process_icon($icon, $docroot, $root) {
    global $defaultIcon;
    if (substr($icon, -4) == '.png') {
        if (file_exists("$docroot/$root/images/$icon")) {
            return "<img src=\"/$root/images/$icon\" class=\"PanelImg\">";
        } elseif (file_exists("$docroot/$root/$icon")) {
            return "<img src=\"/$root/$icon\" class=\"PanelImg\">";
        }
        return $defaultIcon;
    } elseif (substr($icon, 0, 5) == 'icon-') {
        return "<i class=\"$icon PanelIcon\"></i>";
    } elseif ($icon[0] != '<') {
        if (substr($icon, 0, 3) != 'fa-') {
            $icon = "fa-$icon";
        }
        return "<i class=\"fa $icon PanelIcon\"></i>";
    }
    return $icon;
}
?>

<?php require_once __DIR__ . "/$contentInclude"; ?>