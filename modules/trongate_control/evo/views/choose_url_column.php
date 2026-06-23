<div class="mt-1">Choose URL Column</div>
<div class="mt-1">
    <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('uc-options-list').innerHTML">Select Option...</button>
    <p>Submit empty option if not required</p>
</div>
<div id="uc-options-list" style="display:none">
    <ul class="options-selector">
        <li mx-post="trongate_control-evo/submit_url_col" mx-target="main" mx-target-loading="cloak" mx-vals='{"selected":""}'></li>
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $prop): ?>
                <?php $prop_name = $prop['propertyName'] ?? ''; ?>
                <?php $mx_vals = json_encode(['selected' => $prop_name], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>
                <li mx-post="trongate_control-evo/submit_url_col" mx-target="main" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out($prop_name) ?></li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
