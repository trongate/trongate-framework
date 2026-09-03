<div class="mt-1">Choose Relation Type</div>
<div class="mt-1">
    <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('rt-options-list').innerHTML">Select Option...</button>
</div>
<div id="rt-options-list" style="display:none">
    <ul class="options-selector">
        <?php
        // NOTE: literal spaced values — the controller validates against
        // these exact keys (in_array strict). Underscored variants
        // (one_to_one) are NOT valid and would fail the guard.
        $types = [
            'one to one' => 'One to One',
            'one to many' => 'One to Many',
            'many to many' => 'Many to Many'
        ];
        foreach ($types as $key => $label):
            $mx_vals = json_encode(['selected' => $key], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
        ?>
            <li mx-post="trongate_control-module_relations_builder/submit_relation_type" mx-target="main" mx-after-swap="TrongateCodeGenerator.focusOnInput" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out($label) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
