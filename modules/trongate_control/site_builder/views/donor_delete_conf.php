<h1>&lt;?= $headline ?&gt;</h1>
<div class="card">
    <div class="card-heading">
        Confirmation Required
    </div>
    <div class="card-body">
        <p>Are you sure?</p>
        <p>You are about to delete a <?= strtolower($record_name_singular) ?> record. This cannot be undone. Do you really want to do this?</p>
        
        &lt;?php
        echo form_open($form_location);
        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_submit('submit', 'Yes - Delete Now', array('class' => 'danger'));
        echo form_close();
        echo '</div>';
        ?&gt;
    </div>
</div>
