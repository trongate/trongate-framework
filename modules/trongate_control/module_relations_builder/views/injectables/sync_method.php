<?php
/**
 * Injection template: sync_with_{alt_singular}() private method.
 * Data: alt_singular, module, alt_module, runtime_route.
 */
?>
    /**
     * Bidirectional 1:1 integrity at create/edit — claims the chosen
     * partner on both sides and releases any previous partner
     * (no orphans). Delegates to the module_relations runtime.
     *
     * @param int $update_id The record being created/edited.
     * @param int $alt_id    The chosen partner record id.
     * @param int $prev_alt_id The previously-linked partner (edit path).
     * @return void
     */
    private function sync_with_<?= $alt_singular ?>(int $update_id, int $alt_id, int $prev_alt_id = 0): void {
        if ($update_id < 1) {
            return;
        }
        Modules::run('<?= $runtime_route ?>/sync_create_claim', [
            'calling_module' => '<?= $module ?>',
            'alt_module' => '<?= $alt_module ?>',
            'update_id' => $update_id,
            'value' => $alt_id,
            'prev_value' => $prev_alt_id
        ]);
    }
