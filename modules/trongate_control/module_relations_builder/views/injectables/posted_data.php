<?php
/**
 * Injection template: model get_data_from_post() FK line (defaults to 0).
 * Data: fk, marker.
 */
?>
        // <?= $marker ?>posted data
        '<?= $fk ?>' => ((int) post('<?= $fk ?>', true) > 0) ? (int) post('<?= $fk ?>', true) : 0,
