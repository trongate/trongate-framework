<div class="center-stage cloak">
    <div class="mt-1"><?= out($heading) ?></div>
    <div class="mt-1">
        <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('mod-options-list').innerHTML">Select Option...</button>
    </div>
    <div id="mod-options-list" style="display:none">
        <ul class="options-selector">
            <?php if (!empty($modules)): ?>
                <?php foreach ($modules as $mod): ?>
                    <?php $mx_vals = json_encode(['selected' => $mod], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>
                    <li mx-post="trongate_control-module_relations_builder/<?= out($submit_method) ?>" mx-target="main" mx-after-swap="TrongateCodeGenerator.focusOnInput" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out(ucwords(str_replace('_', ' ', $mod))) ?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
