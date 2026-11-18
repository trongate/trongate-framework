<h1 class="text-center">Create Admin Account</h1>
<?php
$form_attr = [
    'class' => 'highlight-errors',
    'mx-post' => 'setup/submit_admin_account',
    'mx-target' => '.setup-container',
    'mx-indicator' => '.spinner',
    'mx-target-loading' => 'cloak',
    'mx-animate-success' => 'true',
    'mx-animate-error' => 'true'
];
echo form_open('#', $form_attr);
?>

<p class="text-center">Set up your administrator account.</p>

<?= Modules::run('setup/draw_steps_indicator', 3) ?>

<?php
echo form_label('Username', ['for' => 'username']);
echo validation_errors('username');
echo form_input('username', $username, ['id' => 'username', 'placeholder' => 'Enter username here', 'autocomplete' => 'username', 'required' => 'required']);

echo form_label('Email Address', ['for' => 'email']);
echo validation_errors('email');
echo form_email('email', $email, ['id' => 'email', 'placeholder' => 'Enter email address here', 'autocomplete' => 'email', 'required' => 'required']);

echo form_label('Password (min. 8 characters)', ['for' => 'password']);
echo validation_errors('password');
echo form_password('password', '', ['id' => 'password', 'placeholder' => 'Create a strong password', 'autocomplete' => 'new-password', 'required' => 'required']);

echo form_label('Confirm Password', ['for' => 'confirm_password']);
echo validation_errors('confirm_password');
echo form_password('confirm_password', '', ['id' => 'confirm_password', 'placeholder' => 'Repeat your password', 'autocomplete' => 'new-password', 'required' => 'required']);

echo form_submit('submit', 'Complete Setup', ['class' => 'btn btn-primary']);
echo form_close();
?>
<?= Modules::run('setup/draw_help_link') ?>