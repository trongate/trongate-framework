<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login</title>
</head>
<body>

    <div class="center-stage">
        <div class="image-panel"></div>
        <div class="form-panel">
            <h1>Account Login</h1>

            <?= validation_errors() ?>

            <?= form_open($form_location) ?>

            <div class="form-group">
                <?= form_label($identifier_label, ['for' => 'identifier']) ?>
                <?= form_input('identifier', '', [
                    'id'           => 'identifier',
                    'placeholder'  => $identifier_label,
                    'autocomplete' => 'username',
                    'required'     => true
                ]) ?>
            </div>

            <div class="form-group">
                <?= form_label($fields['password']['label'], ['for' => 'password']) ?>
                <?= form_password('password', '', [
                    'id'           => 'password',
                    'placeholder'  => $fields['password']['label'],
                    'autocomplete' => 'current-password',
                    'required'     => true
                ]) ?>
            </div>

            <?= form_submit('login', 'Sign In') ?>

            <?php if (isset($forgot_password_url)): ?>
            <p class="link-row">
                <a href="<?= BASE_URL . $forgot_password_url ?>">Forgot password?</a>
            </p>
            <?php endif; ?>

            <?= form_close() ?>
        </div>
    </div>

<style>
body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    font-family: verdana;
    color: #999;
    margin: 0;
    padding: 1rem;
    box-sizing: border-box;
    background-image: url('login_module/images/surfgirl.webp');
    background-position: center bottom;
    background-size: cover;
}


.center-stage {
    background-color: #fff;
    width: 100%;
    max-width: max-content;
    display: flex;
    box-shadow: 0 4px 10px 0 rgba(0, 0, 0, 0.2), 0 4px 20px 0 rgba(0, 0, 0, 0.19);
}

.center-stage .form-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
    box-sizing: border-box;
}

h1 {
    text-transform: uppercase;
    font-size: 1.33em;
    font-weight: normal;
    margin: 0 0 1.5rem;
    color: #555;
}

/* Form styles */
.form-group {
    width: 100%;
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
    color: #777;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-group input {
    width: 100%;
    padding: 0.7rem 0.9rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.95rem;
    color: #555;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-group input:focus {
    border-color: #888;
    outline: none;
}

button[type="submit"] {
    width: 100%;
    padding: 0.75rem;
    background: #555;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 0.5rem;
}

button[type="submit"]:hover {
    background: #777;
}

.link-row {
    text-align: center;
    margin-top: 1.25rem;
    font-size: 0.85rem;
}

a {
    color: #999;
    text-decoration: none;
}

a:hover {
    color: #555;
    text-decoration: underline;
}

/* Validation errors */
.validation-errors {
    width: 100%;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 0.75rem;
    margin-bottom: 1.25rem;
    color: #991b1b;
    font-size: 0.85rem;
    box-sizing: border-box;
}

.validation-errors p {
    margin: 0 0 0.25rem;
}

.validation-errors li {
  margin-left: 1.2em;  /* Adjust this value to reduce/increase gap */
  padding-left: 0;      /* Remove any default padding */
}

.validation-errors p:last-child {
    margin-bottom: 0;
}

.center-stage .form-panel {
    width: 100%;
}

@media screen and (min-width: 845px) {

    body {
        background-color: #ccc;
        background-image: none;
    }

    .center-stage {
        flex-direction: row;
        align-items: stretch;
        justify-content: space-between;
        max-width: 860px;
    }

    .center-stage .image-panel {
        width: 50%;
        min-height: 70vh;
        background-image: url('login_module/images/surfgirl.webp');
        background-position: center bottom;
        background-size: cover;
    }

    .center-stage .form-panel {
        width: 50%;
    }

}
</style>
</body>
</html>
