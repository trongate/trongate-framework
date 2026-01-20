<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Update Password
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo form_label('New Password');
        echo form_password('password', '', ["placeholder" => "Enter New Password", "autocomplete" => "off"]);
        echo form_label('Confirm Password');
        echo form_password('confirm_password', '', ["placeholder" => "Confirm New Password", "autocomplete" => "off"]);
        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', ['class' => 'button alt']);
        echo form_submit('submit', 'Update Password');
        echo form_close();
        echo '</div>';
        ?>
    </div>
</div>