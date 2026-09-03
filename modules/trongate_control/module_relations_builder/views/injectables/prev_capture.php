<?php
/**
 * Injection template: submit() pre-edit FK capture (before UPDATE).
 * Data: fk.
 */
?>
                    // flo_relation: <?= $fk ?> prev capture
                    $prev_<?= $fk ?> = 0;
                    $existing_<?= $fk ?>_row = $this->model->find_by_id($update_id);
                    if ($existing_<?= $fk ?>_row !== false) {
                        $prev_<?= $fk ?> = (int) ($existing_<?= $fk ?>_row-><?= $fk ?> ?? 0);
                    }
