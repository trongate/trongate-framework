<h1><?= $headline ?></h1>
<div class="card">
    <div class="card-heading">
        Confirmation Required
    </div>
    <div class="card-body">
        <h3 class="text-center mt-0">Are you sure?</h3>
        <p class="text-center">You are about to delete an administrator record. This cannot be undone. Do you really want to do this?</p>
        
        <?php
        echo form_open($form_location);
        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_submit('submit', 'Yes - Delete Now', array('class' => 'danger'));
        echo form_close();
        echo '</div>';
        ?>
    </div>
</div>
