<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Log In</title>
        <link rel="stylesheet" href="trongate_administrators_module/css/login.css">
    </head>
    <body>
        <main class="page">
            
            <?php
            echo form_open($form_location, array('class' => 'login-form'));
            echo validation_errors('<div class="validation-errors">', '</div>');
            echo '<div class="form-group">';
            echo form_label('Username');
            $username_attr = [
                'id' => 'username',
                'placeholder' => 'Username',
                'autocomplete' => 'off',
                'required' => true
            ];
            echo form_input('username', '', $username_attr);
            echo '</div>';

            echo '<div class="form-group">';
            echo form_label('Password');
            $password_attr = [
                'id' => 'password',
                'placeholder' => 'Password',
                'autocomplete' => 'current-password',
                'required' => true
            ];
            echo form_password('password', '', $password_attr);
            echo '</div>';
 
            echo '<label>';
            echo form_checkbox('remember', 1);
            echo ' Remember me';
            echo '</label>';
           
            echo form_submit('login', 'Log In');
            echo form_close();
            ?>

        </main>
    </body>
</html>