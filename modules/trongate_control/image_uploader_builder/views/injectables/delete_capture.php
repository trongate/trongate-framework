<?php
/**
 * Injection template: submit_delete() picture capture.
 *
 * Inserted immediately BEFORE the row-deletion call in the target
 * controller's submit_delete(), so the picture's file name is known before
 * the row (and with it the only reference to those files) is gone.
 *
 * Relies on the scaffold contract: `$record` has already been loaded —
 * `$record = $this->model->find_by_id($update_id);` — and validated by the
 * `if ($record === false)` guard above the injection point. The builder's
 * preflight verifies that line exists exactly once before writing.
 *
 * Data:
 *   column — the uploader's picture column name.
 */
?>
            // Image uploader delete hook: capture the picture file name
            // BEFORE the row is deleted (the runtime needs it to clean up
            // the files afterwards).
            $picture_file_name = $record-><?= $column ?> ?? '';
