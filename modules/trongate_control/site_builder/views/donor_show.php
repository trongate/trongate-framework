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
<?= $dynamic_details ?>
    </div>
</div>
