        <div class="detail-grid">
<?php
foreach($properties as $property) {
    if ($property->property_type === 'textarea') { ?>
            <div class="detail-block">
                <div class="detail-label"><?= $property->property_name ?></div>
                <div class="detail-content">&lt;?= nl2br(out($<?= $property->form_field_name ?>)) ?&gt;</div>
            </div><?php
    } else { ?>
            <div class="detail-row">
                <div class="detail-label"><?= $property->property_name ?></div>
                <div class="detail-value">&lt;?= out($<?= $property->form_field_name ?>) ?&gt;</div>
            </div><?php
    }
    echo PHP_EOL;
}
?>
        </div><?= PHP_EOL ?>