<?php
/**
 * Injection template: submit_delete() file-removal hook.
 *
 * Inserted immediately AFTER the row-deletion call in the target
 * controller's submit_delete(): the row goes first, then the files it
 * referenced are removed — never a surviving row pointing at files that
 * have gone, never orphaned files belonging to no row.
 *
 * The file name comes from the capture block injected just above the
 * deletion; an empty string (no picture) short-circuits the whole call.
 *
 * Data:
 *   module — the target module name.
 */
?>
            // Image uploader delete hook: remove the recorded files AFTER
            // the row deletion has succeeded (no orphans, no
            // lost-file-with-surviving-row).
            if ($picture_file_name !== '') {
                Modules::run('image_uploader/delete_files', [
                    'module_name' => '<?= $module ?>',
                    'update_id'   => $update_id,
                    'file_name'   => $picture_file_name
                ]);
            }
