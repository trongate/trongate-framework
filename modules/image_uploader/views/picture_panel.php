<?php
/**
 * Image Uploader — show-page "Picture" card shell (runtime).
 *
 * Rendered by Image_uploader::draw_panel() into the target module's show
 * page (Modules::run, page load). This is deliberately a shell — the same
 * division of labour as Module Relations' summary panel: the shell seeds
 * context and lets the browser fetch the live region via
 * render_panel_body(), so the body always reflects the current database
 * state and can be refreshed in place after MX mutations.
 *
 * Every id on this panel is prefixed 'iu-' so it starts with a letter and
 * never needs CSS-escaped selectors (even when make_rand_str() emits a
 * leading digit).
 */
?>

<div class="card">
    <div class="card-heading">Picture</div>
    <div class="card-body">
        <div class="spinner mx-indicator indicator-iu-<?= out($panel_id) ?>"></div>

        <div id="iu-<?= out($panel_id) ?>"
            mx-get="image_uploader/render_panel_body"
            mx-headers='<?= json_encode([
                'X-Module'    => $module,
                'X-Update-ID' => (int) $update_id,
                'X-Panel-ID'  => $panel_id
            ]) ?>'
            mx-trigger="load"
            mx-indicator=".indicator-iu-<?= out($panel_id) ?>"></div>
    </div>
</div>
