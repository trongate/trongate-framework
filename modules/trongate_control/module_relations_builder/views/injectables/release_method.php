<?php
/**
 * Injection template: release_{alt_singular}_link() private method.
 * Data: alt_singular, module, alt_module, runtime_route.
 */
?>
    /**
     * Delete hook: zeroes the partner's back-FK before this record
     * is deleted (1:1 no-bridge — the partner must never point at a
     * deleted record). Delegates to the module_relations runtime.
     *
     * @param int $update_id The record being deleted.
     * @return void
     */
    private function release_<?= $alt_singular ?>_link(int $update_id): void {
        Modules::run('<?= $runtime_route ?>/release_create_link', [
            'calling_module' => '<?= $module ?>',
            'alt_module' => '<?= $alt_module ?>',
            'update_id' => $update_id
        ]);
    }
