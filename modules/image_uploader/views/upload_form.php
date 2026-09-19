<?php
/**
 * Image Uploader — dedicated upload page (runtime).
 *
 * Rendered by Image_uploader::render_upload_form() inside the admin
 * template: a full page for one record, so a large upload is an ordinary
 * multipart browser POST to submit_upload() rather than an MX transfer.
 *
 * Expects: $headline, $module, $update_id, $current_file_name,
 *          $current_picture_url, $form_location, $cancel_url.
 */
?>

<h1><?= out($headline) ?></h1>
<?= flashdata() ?>
<?= validation_errors() ?>

<div class="card">
    <div class="card-heading">
        Picture
    </div>
    <div class="card-body">
        <?php if ($current_file_name !== ''): ?>
            <div class="picture-upload__current">
                <img src="<?= out($current_picture_url) ?>" alt="Current picture" class="picture-upload__current-img">
                <p>Current picture: <?= out($current_file_name) ?></p>
                <p>Uploading a new picture replaces the current one.</p>
            </div>
        <?php endif; ?>

        <?php
        echo form_open_upload($form_location);

        echo form_hidden('module', $module);
        echo form_hidden('update_id', (int) $update_id);

        echo form_label('Picture');
        echo '<input type="file" name="picture" accept="image/jpeg,image/png,image/gif,image/webp" required>';

        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', ['class' => 'button alt']);
        echo form_submit('submit', 'Upload Picture');
        echo '</div>';

        echo form_close();
        ?>
    </div>
</div>
