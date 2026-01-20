<h1><?= $headline ?></h1>
<?= flashdata() ?>
<div class="card">
    <div class="card-heading">
        Record Details
    </div>
    <div class="card-body">
        <div class="text-right mb-3">
            <?php
            echo anchor($back_url, 'Back', array('class' => 'button alt'));
            echo anchor(BASE_URL.'trongate_administrators/create/'.$update_id, 'Edit', array('class' => 'button'));

            if ($is_own_account === false) {
                echo anchor('trongate_administrators/delete_conf/'.$update_id, 'Delete', array('class' => 'button danger'));
            }
            ?>
        </div>
        
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">Username</div>
                <div class="detail-value"><?= out($username) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trongate User ID</div>
                <div class="detail-value"><?= out($trongate_user_id) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?= out($active_formatted) ?>
                </div>
            </div>
        </div>
    </div>
</div>