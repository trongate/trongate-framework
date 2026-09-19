<?php
/**
 * Image Uploader — picture panel live region (runtime).
 *
 * Rendered by Image_uploader::render_panel_body() into the card shell's
 * unique-ID container (the mx-get target), and re-rendered by
 * remove_picture() as the out-of-band swap response for the same region.
 *
 * The body is a single panel-scoped region:
 *
 *   #iu-{panel_id}-body — the picture state: placeholder + upload action
 *                          when no picture is set, or the preview +
 *                          replace/remove actions when one is.
 *
 * The remove button posts to image_uploader/remove_picture with
 * mx-target="none" and mx-select-oob pointing at this region, so the
 * response swaps ONLY the picture state — never the whole card. The
 * response body for a successful remove is this same view: MX picks the
 * region out of it and swaps it into the live page.
 *
 * Upload/replace are plain links to the dedicated upload_form page — never
 * MX file transfers, so a large upload is an ordinary browser submission.
 *
 * All behaviour is Trongate MX attributes — no custom JavaScript.
 */
$oob_swaps = json_encode([
    ['select' => '#iu-' . $panel_id . '-body', 'target' => '#iu-' . $panel_id . '-body']
]);
?>

<div id="iu-<?= out($panel_id) ?>-body">
    <?php if ($file_name === ''): ?>
        <p class="picture-panel__empty">No picture has been uploaded yet.</p>
        <a href="<?= out($upload_url) ?>" class="button">Upload Picture</a>
    <?php else: ?>
        <div class="picture-panel__preview">
            <?php if ($thumbnails): ?>
                <img src="<?= out($thumbnail_url) ?>" alt="Picture preview" class="picture-panel__img">
            <?php else: ?>
                <img src="<?= out($picture_url) ?>" alt="Picture preview" class="picture-panel__img">
            <?php endif; ?>
            <div class="picture-panel__filename sm"><?= out($file_name) ?></div>
        </div>
        <div class="picture-panel__actions">
            <a href="<?= out($upload_url) ?>" class="button alt">Replace Picture</a>
            <button type="button"
                    class="button danger"
                    aria-label="Remove picture"
                    mx-post="image_uploader/remove_picture"
                    mx-vals='<?= out(json_encode([
                        'module'    => $module,
                        'update_id' => (int) $update_id,
                        'panel_id'  => $panel_id,
                        'csrf_token' => $csrf_token
                    ])) ?>'
                    mx-headers='{"X-Requested-With": "XMLHttpRequest"}'
                    mx-target="none"
                    mx-select-oob='<?= out($oob_swaps) ?>'
                    mx-on-error="#iu-<?= out($panel_id) ?>">Remove</button>
        </div>
    <?php endif; ?>
</div>
