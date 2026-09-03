<?php
/**
 * Injection template: submit() sync after CREATE.
 * Data: alt_singular, fk.
 */
?>
                    // flo_relation: <?= $fk ?> sync on save
                    $this->sync_with_<?= $alt_singular ?>((int) $update_id, (int) $data['<?= $fk ?>'], 0);
