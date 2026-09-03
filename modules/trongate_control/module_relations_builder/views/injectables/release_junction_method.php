<?php
/**
 * Injection template: release_junction_links() private method (junction
 * delete hook — 1:1 bridge / many to many). Data: module, alt_module,
 * runtime_route.
 */
?>
    /**
     * Delete hook: removes every junction row that references this
     * record before it is deleted (junction relations — a deleted
     * record must not leave orphaned links behind). Delegates to the
     * module_relations runtime.
     *
     * @param int $update_id The record being deleted.
     * @return void
     */
    private function release_junction_links(int $update_id): void {
        Modules::run('<?= $runtime_route ?>/release_junction_links', [
            'calling_module' => '<?= $module ?>',
            'alt_module' => '<?= $alt_module ?>',
            'update_id' => $update_id
        ]);
    }
