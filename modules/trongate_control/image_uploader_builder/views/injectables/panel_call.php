<?php
/**
 * Injection template: the show page's "Picture" panel call.
 *
 * Appended after the details card (the final </div>) of a target module's
 * show view — the same placement and shape as the hand-wired actors
 * reference. The literal short-open tag is written as entities so it is
 * OUTPUT here rather than executed; the builder decodes it after rendering
 * (prep_file_contents, the site_builder pattern).
 *
 * Data:
 *   module — the target module name.
 */
?>
&lt;?= Modules::run('image_uploader/draw_panel', '<?= $module ?>') ?&gt;
