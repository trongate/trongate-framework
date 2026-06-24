<div class="mt-1">Choose Default Order By</div>
<div class="mt-1">
    <button class="selector-btn" onclick="document.querySelector('main').innerHTML=document.getElementById('ob-options-list').innerHTML">Select Option...</button>
    <p>Submit empty option if not required</p>
</div>
<div id="ob-options-list" style="display:none">
    <ul class="options-selector">
        <li mx-post="trongate_control-evo/submit_order_by" mx-target="main" mx-target-loading="cloak" mx-vals='{"selected":""}'></li>
        <?php
        $order_by_options = [
            ['propertyName' => 'id', 'column' => 'id'],
            ['propertyName' => 'id DESC', 'column' => 'id DESC']
        ];
        if (!empty($properties)):
            foreach ($properties as $prop):
                $prop_name = $prop['propertyName'] ?? '';
                if ($prop_name !== ''):
                    $column = str_replace('-', '_', url_title($prop_name));
                    $order_by_options[] = ['propertyName' => $prop_name, 'column' => $column];
                    $order_by_options[] = ['propertyName' => $prop_name . ' DESC', 'column' => $column . ' DESC'];
                endif;
            endforeach;
        endif;
        foreach ($order_by_options as $opt):
            $opt_name = $opt['propertyName'];
            $opt_column = $opt['column'];
            $mx_vals = json_encode(['selected' => $opt_column], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
        ?>
            <li mx-post="trongate_control-evo/submit_order_by" mx-target="main" mx-target-loading="cloak" mx-vals='<?= $mx_vals ?>'><?= out($opt_name) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

