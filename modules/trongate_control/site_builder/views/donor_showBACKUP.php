<h1>&lt;?= $headline ?&gt;</h1>
&lt;?= flashdata() ?&gt;
<div class="card">
    <div class="card-heading">
        <?= ucwords($record_name_singular) ?> Details
    </div>
    <div class="card-body">
        <div class="text-right mb-3">
            &lt;?= anchor($back_url, 'Back', array('class' => 'button alt')) ?&gt;
            &lt;?= anchor(BASE_URL.'<?= $module_folder_name ?>/create/'.$update_id, 'Edit', array('class' => 'button')) ?&gt;
            &lt;?= anchor('<?= $module_folder_name ?>/delete_conf/'.$update_id, 'Delete',  array('class' => 'button danger')) ?&gt;
        </div>
        
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">Task Title</div>
                <div class="detail-value">&lt;?= out($task_title) ?&gt;</div>
            </div>
            <div class="detail-block">
                <div class="detail-label">Task Description</div>
                <div class="detail-content">&lt;?= nl2br(out($task_description)) ?&gt;</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    &lt;?= out($complete_formatted) ?&gt;
                </div>
            </div>
        </div>
    </div>
</div>
