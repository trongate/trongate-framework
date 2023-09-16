<h1><?php

declare(strict_types=1);

echo $headline ?> <span class="smaller hide-sm">(Record ID: <?php echo $update_id ?>)</span></h1>
<?php echo flashdata() ?>
<div class="card">
    <div class="card-heading">
        Options
    </div>
    <div class="card-body">
        <?php
declare(strict_types=1);
echo anchor('trongate_pages/manage', 'View All Trongate Pages', ['class' => 'button alt']);
echo anchor('trongate_pages/create/'.$update_id, 'Update Details', ['class' => 'button']);
$attr_delete = [
    'class' => 'danger go-right',
    'id' => 'btn-delete-modal',
    'onclick' => "openModal('delete-modal')",
];
echo form_button('delete', 'Delete', $attr_delete);
?>
    </div>
</div>
<div class="two-col">
    <div class="card">
        <div class="card-heading">
            Trongate Page Details
        </div>
        <div class="card-body">
            <div class="record-details">
                <div class="row">
                    <div>Page Title</div>
                    <div><?php echo $page_title ?></div>
                </div>
                <div class="row">
                    <div class="full-width">
                        <div><b>Meta Keywords</b></div>
                        <div><?php echo nl2br($meta_keywords) ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="full-width">
                        <div><b>Meta Description</b></div>
                        <div><?php echo nl2br($meta_description) ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="full-width">
                        <div><b>Page Body</b></div>
                        <div><?php echo nl2br($page_body) ?></div>
                    </div>
                </div>
                <div class="row">
                    <div>Date Created</div>
                    <div><?php echo $date_created ?></div>
                </div>
                <div class="row">
                    <div>Last Updated</div>
                    <div><?php echo $last_updated ?></div>
                </div>
                <div class="row">
                    <div>Published</div>
                    <div><?php echo $published ?></div>
                </div>
                <div class="row">
                    <div>Created By</div>
                    <div><?php echo $created_by ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-heading">
            Comments
        </div>
        <div class="card-body">
            <div class="text-center">
                <p><button class="alt" onclick="openModal('comment-modal')">Add New Comment</button></p>
                <div id="comments-block"><table></table></div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="comment-modal" style="display: none;">
    <div class="modal-heading"><i class="fa fa-commenting-o"></i> Add New Comment</div>
    <div class="modal-body">
        <p><textarea placeholder="Enter comment here..."></textarea></p>
        <p><?php
    $attr_close = [
        'class' => 'alt',
        'onclick' => 'closeModal()',
    ];
echo form_button('close', 'Cancel', $attr_close);
echo form_button('submit', 'Submit Comment', ['onclick' => 'submitComment()']);
?>
        </p>
    </div>
</div>
<div class="modal" id="delete-modal" style="display: none;">
    <div class="modal-heading danger"><i class="fa fa-trash"></i> Delete Record</div>
    <div class="modal-body">
        <?php echo form_open('trongate_pages/submit_delete/'.$update_id) ?>
        <p>Are you sure?</p>
        <p>You are about to delete a Trongate Page record.  This cannot be undone.  Do you really want to do this?</p> 
        <?php
        echo '<p>'.form_button('close', 'Cancel', $attr_close);
echo form_submit('submit', 'Yes - Delete Now', ['class' => 'danger']).'</p>';
echo form_close();
?>
    </div>
</div>
<script>
var token = '<?php echo $token ?>';
var baseUrl = '<?php echo BASE_URL ?>';
var segment1 = '<?php echo segment(1) ?>';
var updateId = '<?php echo $update_id ?>';
var drawComments = true;
</script>