<?php
/**
 * Injection template: submit() sync after UPDATE (with previous FK).
 * Data: alt_singular, fk.
 */
?>
                    // flo_relation: <?= $fk ?> sync on save
                    $this->sync_with_<?= $alt_singular ?>((int) $update_id, (int) $data['<?= $fk ?>'], $prev_<?= $fk ?>);
