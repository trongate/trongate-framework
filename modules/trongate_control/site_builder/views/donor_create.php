<h1>&lt;?= $headline ?&gt;</h1>
&lt;?= validation_errors() ?&gt;
<div class="card">
    <div class="card-heading">
        <?= ucwords($record_name_singular) ?> Details
    </div>
    <div class="card-body">
        &lt;?php
        echo form_open($form_location);<?= PHP_EOL.PHP_EOL ?>
<?= $dynamic_form_fields ?>
        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', ['class' => 'button alt']);
        echo form_submit('submit', 'Submit');
        echo '</div>';
        <?= PHP_EOL ?>
        echo form_close();
        ?&gt;
    </div>
</div>
<?= $dynamic_date_constraint_js ?>
