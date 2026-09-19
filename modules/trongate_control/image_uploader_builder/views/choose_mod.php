<div class="center-stage cloak">
    <div class="mt-1">Choose a Module for Your Image Uploader</div>

    <div class="mt-1">
        <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('iu-mod-options-list').innerHTML">Select Module...</button>
    </div>

    <div id="iu-mod-options-list" style="display:none">
        <ul class="options-selector">
            <?php foreach ($modules as $mod): ?>
                <?php if ($mod['ready']): ?>
                    <?php $mx_vals = json_encode(['selected' => $mod['module']], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>
                    <li mx-post="trongate_control-image_uploader_builder/submit_mod" mx-target="main" mx-after-swap="TrongateCodeGenerator.focusOnInput" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out($mod['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php
    // Modules failing preflight are listed with the specific reason and
    // cannot be selected.
    $blocked = array_filter($modules, fn($m) => !$m['ready']);
    if (count($blocked) > 0):
    ?>
        <div class="mt-2">
            <div class="mt-1"><strong>Not ready for an image uploader</strong></div>
            <ul class="iu-blocked-list">
                <?php foreach ($blocked as $mod): ?>
                    <li>
                        <strong><?= out($mod['label']) ?></strong>
                        — <?= out(implode('; ', $mod['reasons'])) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
