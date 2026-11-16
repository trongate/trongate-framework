<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Administrator Details
    </div>
    <div class="card-body">
        <?php
        if ($update_id > 0) {
            echo '<p class="text-right">'.anchor($update_password_url, 'Update Password', ['class' => 'button alt']).'</p>';
        }

        echo form_open($form_location);

        echo form_label('Username');
        $username_attr = [
            'placeholder' => 'Enter Username',
            'required'  => true,
            'minlength' => 2,
            'maxlength' => 50,
            'autocomplete' => 'off'
        ];
        echo form_input('username', $username, $username_attr);

        echo form_label('Email Address');
        $email_attr = [
            'placeholder' => 'Enter Email Address',
            'maxlength' => 255,
            'autocomplete' => 'off'
        ];
        echo form_email('email', $email, $email_attr);

        echo '<label>';
        echo form_checkbox('active', 1, $active);
        echo ' Active';
        echo '</label>';

        echo '<div class="text-center">';
        echo anchor($cancel_url, 'Cancel', ['class' => 'button alt']);
        echo form_submit('submit', 'Submit');
        echo '</div>';

        echo form_close();
        ?>
    </div>
</div>
