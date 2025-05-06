<?
/**
 * Main content template for the Unraid web interface.
 * Handles the rendering of tabs and page content.
 * @todo - tabs and content need to be split from each other. And not nested any longer.
 */

$defaultIcon = "<i class=\"icon-app PanelIcon\"></i>";
// Helper function to process icon
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

$tab = 1;
$content = 1;
?>
<div id="displaybox">
    <? if ($tabbed): ?>
        <div class="tabs">
            <div class="tabs-inner">
                <? foreach ($pages as $page): ?>
                    <? if (isset($page['Title']) && $title = htmlspecialchars($page['Title']) ?? ''): ?>
                        <div class="tab">
                            <input type="radio" id="tab<?= $tab ?>" name="tabs" onclick="settab(this.id)">
                            <label for="tab<?= $tab ?>">
                                <?= tab_title($title, $page['root'], _var($page, 'Tag', false)) ?>
                            </label>
                        </div>

                        <? $tab++; ?>
                    <? endif; ?>
                <? endforeach; ?>
            </div>
        </div> <!-- /.tabs -->
    <? endif; ?>

    <? foreach ($pages as $page): ?>
        <div id="content-<?= $content ?>" class="content">
            <? if (!$tabbed && isset($page['Title']) && $title = htmlspecialchars($page['Title']) ?? ''): ?>
                <div class="title">
                    <span class="left">
                        <?= tab_title($title, $page['root'], _var($page, 'Tag', false)) ?>
                    </span>
                </div>
            <? endif; ?>

            <? if (isset($page['Type']) && $page['Type'] == 'menu'): ?>
                <? foreach (find_pages($page['name']) as $pg): ?>
                    <? $title = htmlspecialchars($pg['Title']); ?>
                    <? $icon = _var($pg, 'Icon', $defaultIcon); ?>
                    <? $icon = process_icon($icon, $docroot, $pg['root']); ?>
                    <div class="Panel">
                        <a href="/<?= $path ?>/<?= $pg['name'] ?>" onclick="$.cookie('one','tab1')">
                            <span><?= $icon ?></span>
                            <div class="PanelText"><?= _($title) ?></div>
                        </a>
                    </div>
                <? endforeach; ?>
            <? endif; ?>

            <? // Create page content ?>
            <? annotate($page['file']); ?>
            <? if (empty($page['Markdown']) || $page['Markdown'] == 'true'): ?>
                <? eval('?>'.Markdown(parse_text($page['text']))); ?>
            <? else: ?>
                <? eval('?>'.parse_text($page['text'])); ?>
            <? endif; ?>

            <? $content++; ?>
        </div><!-- /.content -->
    <? endforeach; ?>
</div><!-- /#displaybox -->
<?
// Clean up variables
unset($pages, $page, $pgs, $pg, $icon, $nchan, $running, $start, $stop, $row, $script, $opt, $nchan_run);
?>
