<?php
/**
 * Injection template: parent submit_delete() children-release hook (before delete).
 * Data: fk, plural.
 *
 * The marker is qualified with the plural because a parent may have several
 * one-to-many children, each with its own hook in this same method.
 */
?>
            // flo_relation: <?= $fk ?> delete hook (<?= $plural ?>)
            $this->release_<?= $plural ?>_links($update_id);
