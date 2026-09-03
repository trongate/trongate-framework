<?php
/**
 * Injection template: create-view dropdown row (replaces any existing FK row).
 * Data: fk, options_var, label, marker.
 */
?>
        // <?= $marker ?>dropdown row
        echo form_label('<?= $label ?>');
        echo form_dropdown('<?= $fk ?>', $<?= $options_var ?>, $<?= $fk ?>);
