<div class="center-stage cloak">
    <div class="mt-1">Would you like to create a bridging table for this relationship?</div>
    <div class="mt-1">
        <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('br-options-list').innerHTML">Select Option...</button>
    </div>
    <div id="br-options-list" style="display:none">
        <ul class="options-selector">
            <?php
            $options = ['Yes', 'No'];
            foreach ($options as $option):
                $mx_vals = json_encode(['selected' => $option], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
            ?>
                <li mx-post="trongate_control-module_relations_builder/submit_bridging_option" mx-target="main" mx-after-swap="TrongateCodeGenerator.focusOnInput" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out($option) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
