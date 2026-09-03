<?php
/**
 * Injection template: create() options-fetch line (before view_file).
 * Data: fk, options_var, alt_singular, marker, module, alt_module, runtime_route.
 */
?>
        // <?= $marker ?>options fetch
        $data['<?= $options_var ?>'] = $this->get_<?= $alt_singular ?>_options((int) ($data['<?= $fk ?>'] ?? 0));
