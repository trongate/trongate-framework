<?php
/**
 * Injection template: submit_delete() junction-row release hook (before
 * delete). Data: method, alt_module.
 *
 * Both the marker and the method name are qualified with the other module:
 * a module may sit in several junction relations, each needing its own hook
 * and its own release method in the same file.
 */
?>
            // flo_relation: junction delete hook (<?= $alt_module ?>)
            $this-><?= $method ?>($update_id);
