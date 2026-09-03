<?php
/**
 * Injection template: submit_delete() junction-row release hook (before
 * delete). No data — the marker is fixed (a junction relation has no FK
 * column of its own; the release is symmetric across both sides).
 */
?>
            // flo_relation: junction delete hook
            $this->release_junction_links($update_id);
