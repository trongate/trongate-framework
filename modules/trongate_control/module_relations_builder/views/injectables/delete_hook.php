<?php
/**
 * Injection template: submit_delete() release hook (before delete).
 * Data: alt_singular, fk.
 */
?>
            // flo_relation: <?= $fk ?> delete hook
            $this->release_<?= $alt_singular ?>_link($update_id);
