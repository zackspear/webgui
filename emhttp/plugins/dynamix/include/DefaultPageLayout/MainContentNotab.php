<?php
/**
 * Non-tabbed content template for the Unraid web interface.
 * Renders all pages in sequence without tabs, using original per-page logic.
 */
?>
<div id="displaybox">
    <div class="content">
        <? foreach ($pages as $page): ?>
            <? annotate($page['file']); ?>
            <? includePageStylesheets($page); ?>

            <? if (isset($page['Title'])): ?>
                <div class="title">
                    <?= tab_title($page['Title'], $page['root'], _var($page, 'Tag', false)) ?>
                </div>
            <? endif; ?>

            <? if (isset($page['Type']) && $page['Type'] == 'menu'): ?>
                <? $pgs = find_pages($page['name']); ?>
                <? foreach ($pgs as $pg): ?>
                    <?
                        @$panelTitle = htmlspecialchars($pg['Title']);
                        $icon = _var($pg, 'Icon', $defaultIcon);
                        $icon = process_icon($icon, $docroot, $pg['root']);
                    ?>
                    <div class="Panel">
                        <a href="/<?= $path ?>/<?= $pg['name'] ?>" onclick="$.cookie('one','tab1')">
                            <span><?= $icon ?></span>
                            <div class="PanelText"><?= _($panelTitle) ?></div>
                        </a>
                    </div>
                <? endforeach; ?>
            <? endif; ?>

            <? if (empty($page['Markdown']) || $page['Markdown'] == 'true'): ?>
                <? eval('?>'.Markdown(parse_text($page['text']))); ?>
            <? else: ?>
                <? eval('?>'.parse_text($page['text'])); ?>
            <? endif; ?>
        <? endforeach; ?>
    </div>
</div>
