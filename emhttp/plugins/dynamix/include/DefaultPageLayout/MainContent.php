<?
/**
 * Main content template for the Unraid web interface.
 * Handles the rendering of tabs and page content.
 */

// Initialize variables
$tab = 1;
$display['tabs'] = isset($myPage['Tabs']) ? (strtolower($myPage['Tabs']) == 'true' ? 0 : 1) : 1;
$tabbed = $display['tabs'] == 0 && count($pages) > 1;

// Helper function to process icon
function process_icon($icon, $docroot, $root) {
    if (substr($icon, -4) == '.png') {
        if (file_exists("$docroot/$root/images/$icon")) {
            return "<img src=\"/$root/images/$icon\" class=\"PanelImg\">";
        } elseif (file_exists("$docroot/$root/$icon")) {
            return "<img src=\"/$root/$icon\" class=\"PanelImg\">";
        }
        return "<i class=\"icon-app PanelIcon\"></i>";
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
<div id="displaybox">
    <div class="tabs">
        <? foreach ($pages as $page):
            $close = false;
            if (isset($page['Title'])):
                eval("\$title=\"".htmlspecialchars($page['Title'])."\";");
                if ($tabbed): ?>
                    <div class="tab">
                        <input type="radio" id="tab<?= $tab ?>" name="tabs" onclick="settab(this.id)">
                        <label for="tab<?= $tab ?>">
                            <?= tab_title($title, $page['root'], _var($page, 'Tag', false)) ?>
                        </label>
                        <div class="content">
                    <? $close = true;
                else:
                    if ($tab == 1): ?>
                        <div class="tab">
                            <input type="radio" id="tab<?= $tab ?>" name="tabs">
                            <div class="content shift">
                    <? endif; ?>
                        <div class="title">
                            <span class="left">
                                <?= tab_title($title, $page['root'], _var($page, 'Tag', false)) ?>
                            </span>
                        </div>
                <? endif;
                $tab++;
            endif;

            // Handle menu type pages
            if (isset($page['Type']) && $page['Type'] == 'menu'):
                $pgs = find_pages($page['name']);
                foreach ($pgs as $pg):
                    @eval("\$title=\"".htmlspecialchars($pg['Title'])."\";");
                    $icon = _var($pg, 'Icon', "<i class=\"icon-app PanelIcon\"></i>");
                    $icon = process_icon($icon, $docroot, $pg['root']); ?>
                    <div class="Panel">
                        <a href="/<?= $path ?>/<?= $pg['name'] ?>" onclick="$.cookie('one','tab1')">
                            <span><?= $icon ?></span>
                            <div class="PanelText"><?= _($title) ?></div>
                        </a>
                    </div>
                <? endforeach;
            endif;

            // Annotate and create page content
            annotate($page['file']);

            // Create page content
            if (empty($page['Markdown']) || $page['Markdown'] == 'true'):
                eval('?>'.Markdown(parse_text($page['text'])));
            else:
                eval('?>'.parse_text($page['text']));
            endif;

            if ($close): ?>
                </div>
            </div>
            <? endif;
        endforeach; ?>
    </div>
</div>
<?
// Clean up variables
unset($pages, $page, $pgs, $pg, $icon, $nchan, $running, $start, $stop, $row, $script, $opt, $nchan_run);
?>