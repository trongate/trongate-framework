<h1><?= $headline ?></h1>
<p>Please use this page to upload images. When you are finished, click 'Go Back'.</p>
<p><?php
    $btn_attr['class'] = 'button';
    echo anchor($previous_url, '<i class="fa fa-arrow-left"></i> Go Back', $btn_attr) ?></p>
<div class="drop-zone" id="drop-zone">
    <div bp="grid 4@sm 3@md 2@lg container" id="thumbnail-grid"><?php
                                                                $num_previously_uploaded_files = count($previously_uploaded_files);
                                                                foreach ($previously_uploaded_files as $previously_uploaded_file) {
                                                                    $file_path = $previously_uploaded_file['directory'] . '/' . $previously_uploaded_file['filename'];
                                                                ?>
            <div class="drop-zone__thumb" data-label="<?= $previously_uploaded_file['filename'] ?>" id="vWVnX" style="background-image: url('<?= $file_path ?>');">
                <div class="thumboverlay thumboverlay-green" id="<?= $previously_uploaded_file['overlay_id'] ?>">
                    <div class="ditch-cross" onclick="deleteImg('<?= $previously_uploaded_file['overlay_id'] ?>')">✘</div>
                </div>
            </div>
        <?php
                                                                }
        ?>
    </div>
    <div id="controls">
        <span class="drop-zone__prompt">
            Drag &amp; Drop your files here or click '<span class="browse" onclick="initBrowse()">Browse</span>'
        </span>
        <form id="multi-form" enctype="multipart/form-data" style="display: none;">
            <input type="file" id="files" name="files" multiple onchange="activateFiles()">
        </form>
    </div>
</div>
<script>
    const targetModule = '<?= $target_module ?>';
    const updateId = <?= $update_id ?>;
    const uploadUrl = '<?= $upload_url ?>';
    const deleteUrl = '<?= $delete_url  ?>';
    const token = '<?= $token ?>';
</script>