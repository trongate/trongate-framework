<h1 class="text-center">Database Connection Test</h1>
<?php
$form_attr = [
    'class' => 'highlight-errors',
    'mx-post' => $form_location,
    'mx-target' => '.setup-container',
    'mx-indicator' => '.spinner',
    'mx-target-loading' => 'cloak',
    'mx-animate-success' => 'true',
    'mx-animate-error' => 'true'
];
echo form_open('#', $form_attr);
?>
<p class="text-center">Please fill out then submit the form below.</p>

<?= Modules::run('setup/draw_steps_indicator', 2) ?>

<?= flashdata() ?>

<?php
echo form_label('Host', ['for' => 'host']);
echo validation_errors('host');
echo form_input('host', $host, ['id' => 'host', 'placeholder' => 'e.g. 127.0.0.1', 'required' => 'required']);

echo form_label('Port', ['for' => 'port']);
echo validation_errors('port');
echo form_input('port', $port, ['id' => 'port', 'placeholder' => 'e.g. 3306']);

echo form_label('Username', ['for' => 'user']);
echo validation_errors('user');
echo form_input('user', $user, ['id' => 'user', 'autocomplete' => 'off', 'required' => 'required']);

echo form_label('Password', ['for' => 'password']);
echo validation_errors('password');
echo form_password('password', $password, ['id' => 'password', 'autocomplete' => 'off']);

echo form_label('Database Name', ['for' => 'database']);
echo validation_errors('database');
echo form_input('database', $database, ['id' => 'database', 'placeholder' => 'e.g. my_app', 'required' => 'required']);

echo '<p class="mt-1 long-path"><strong>Note:</strong> Your database will be created automatically.</p>';

echo form_submit('submit', 'Test Connection & Continue', ['class' => 'btn btn-primary']);
echo form_close();
?>
<?= Modules::run('setup/draw_help_link') ?>
