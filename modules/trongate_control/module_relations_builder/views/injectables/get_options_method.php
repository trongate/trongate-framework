<?php
/**
 * Injection template: get_{alt_singular}_options() private method.
 * Data: alt_singular, label, module, alt_module, runtime_route.
 */
?>
    /**
     * Options for the <?= $label ?> dropdown on the create form.
     *
     * Delegates to the module_relations runtime (the single SQL
     * owner) — generated modules never duplicate query logic.
     *
     * @param int $selected_key The currently-linked record id (edit form).
     * @return array<int|string,string> Dropdown options (key => display).
     */
    private function get_<?= $alt_singular ?>_options(int $selected_key = 0): array {
        $options = Modules::run('<?= $runtime_route ?>/fetch_create_options', [
            'calling_module' => '<?= $module ?>',
            'alt_module' => '<?= $alt_module ?>',
            'selected_key' => $selected_key
        ]);
        return is_array($options) ? $options : [];
    }
