<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Record Details
    </div>
    <div class="card-body">
        <?php
        if (isset($id)) {
            echo '<p class="text-right">'.anchor($update_password_url, 'Update Password', array('class' => 'button alt mt-0')).'</p>';
        }
        
        echo form_open($form_location);
        echo form_label('Username');
        echo form_input('username', $username, ["placeholder" => "Enter Username", "autocomplete" => "off"]);

        echo '<label>';
        echo form_checkbox('active', 1, $active);
        echo ' Active';
        echo '</label>';

        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', ['class' => 'button alt']);
        echo form_submit('submit', 'Submit');
        echo form_close();
        echo '</div>';
        ?>
    </div>
</div>