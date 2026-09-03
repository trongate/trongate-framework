<?php
/**
 * Injection template: release_{plural}_links() private method (1:N parent
 * delete hook). Data: plural, module, alt_module, runtime_route.
 */
?>
    /**
     * Delete hook: releases every child before this parent record is
     * deleted (1:N — children must never point at a deleted record).
     * Delegates to the module_relations runtime.
     *
     * @param int $update_id The record being deleted.
     * @return void
     */
    private function release_<?= $plural ?>_links(int $update_id): void {
        Modules::run('<?= $runtime_route ?>/release_children', [
            'calling_module' => '<?= $module ?>',
            'alt_module' => '<?= $alt_module ?>',
            'update_id' => $update_id
        ]);
    }
