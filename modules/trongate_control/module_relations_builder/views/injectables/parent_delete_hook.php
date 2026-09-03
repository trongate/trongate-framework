<?php
/**
 * Injection template: parent submit_delete() children-release hook (before delete).
 * Data: fk, plural.
 */
?>
            // flo_relation: <?= $fk ?> delete hook
            $this->release_<?= $plural ?>_links($update_id);
