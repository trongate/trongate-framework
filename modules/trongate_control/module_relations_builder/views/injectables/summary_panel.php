<?php
/**
 * Injection template: show-view summary panel call (appended after the
 * details card). Contains a literal PHP short tag in the OUTPUT — written
 * as entities here and decoded by the controller after rendering (the
 * site_builder prep_file_contents pattern).
 *
 * Data: alt_module, runtime_route.
 */
?>
&lt;?= Modules::run('<?= $runtime_route ?>/draw_summary_panel', '<?= $alt_module ?>') ?&gt;
