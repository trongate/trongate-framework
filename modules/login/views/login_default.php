<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Log In</title>
        <link rel="stylesheet" href="css/trongate.css">
        <link rel="stylesheet" href="login_module/css/login_default.css">
    </head>
    <body>
        <div class="card">
            <div class="card-body">
                <h1>Ahoy!</h1>
                <p class="text-center">Please sign in to access your account.</p>

                <?= validation_errors() ?>

                <?= form_open($form_location) ?>

                <?php echo form_label($identifier_label, ['id' => 'identifier']) ?>

                <?php echo form_input('identifier', '', [
                    'placeholder'  => $identifier_label,
                    'autocomplete' => 'username',
                    'required'     => true
                ]) ?>

                <?php echo form_label($fields['password']['label'], ['id' => 'password']) ?>

                <?php echo form_password('password', '', [
                    'placeholder'  => $fields['password']['label'],
                    'autocomplete' => 'current-password',
                    'required'     => true
                ]) ?>

                <?php if ($allow_remember === 1): ?>
                    <div>
                        <label class="mb-2">
                            <?= form_checkbox('remember', 1) ?>
                            Remember me
                        </label>
                    </div>
                <?php endif; ?>

                <div class="mt-1">
                    <?= form_submit('login', 'Sign In') ?>
                    <?= anchor(BASE_URL, 'Cancel', ['class' => 'button alt']) ?>
                </div>

                <?= form_close() ?>

                <?php if (isset($forgot_password_url)): ?>
                <p class="sm text-center">
                    <a href="<?= BASE_URL . $forgot_password_url ?>">
                        Forgot your password?
                    </a>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </body>
</html>
