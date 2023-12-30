<div class="card record-details">
    <div class="card-heading">
        Picture Gallery
    </div>
    <div class="card-body">
        <p class="text-center">
            <?= anchor($uploader_url, '<i class="fa fa-image"></i> Upload Pictures', array("class" => "button alt")) ?>
        </p>

        <?php
        if (count($pictures) == 0) {
        ?>
            <div id="gallery-pics" style="border-bottom: 0; grid-template-columns: repeat(1, 1fr);">
                <p class="text-center">There are currently no gallery pictures for this record.</p>
            </div>
        <?php
        } else {
        ?>
            <div id="gallery-pics">
                <?php
                foreach ($pictures as $picture) {
                    $el_id = str_replace('.', '-', $picture);
                    $picture_path = $target_directory . '/' . $picture;
                    echo '<div id="gallery-preview-' . $el_id . '" onclick="openPicPreview(\'preview-pic-modal\', \'' . $picture_path . '\')">';
                    echo '<img src="' . $picture_path . '" alt="<?= $picture ?>"></div>';
                }
                ?>
            </div>
        <?php
        }
        ?>
    </div>
</div>

<div class="modal" id="preview-pic-modal" style="display: none;">
    <div class="modal-heading"><i class="fa fa-image"></i> Picture Preview</div>
    <div class="modal-body">
        <p id="preview-pic"></p>
        <?php
        $attr_close = array(
            "class" => "alt",
            "onclick" => "closeModal()"
        );
        echo '<p>' . form_button('close', 'Cancel', $attr_close);

        $attr_ditch_pic = array(
            "class" => "danger",
            "id" => "ditch-pic-btn",
            "onclick" => "ditchPreviewPic()"
        );
        echo form_button('delete_pic', 'DELETE THIS PICTURE', $attr_ditch_pic) . '</p>';
        ?>
    </div>
</div>