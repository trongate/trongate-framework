<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Your Password</title>
        <style>
            :root {
                --bg-color: #ccc;
                --card-bg: #ffffff;
                --text-main: #333333;
                --text-muted: #666666;
                --border-color: #dddddd;
                --accent-neutral: #4a5568; 
                --accent-hover: #2d3748;
                --error-red: #c53030;
                --error-bg: #fff5f5;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-main);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }

            .container {
                width: 100%;
                max-width: 420px;
                background: var(--card-bg);
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                text-align: center;
            }

            h1 {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 12px;
                color: var(--text-main);
            }

            .instruction-text {
                font-size: 0.95rem;
                color: var(--text-muted);
                line-height: 1.5;
                margin-bottom: 24px;
            }

            /* Validation Error Box */
            .validation-errors {
                background-color: var(--error-bg);
                color: var(--error-red);
                padding: 12px;
                border-radius: 6px;
                border: 1px solid #feb2b2;
                margin-bottom: 20px;
                font-size: 0.9rem;
                text-align: left;
            }

            /* Form Styling */
            .form-group {
                text-align: left;
                margin-bottom: 20px;
            }

            label {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                margin-bottom: 8px;
                color: var(--text-main);
            }

            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                font-size: 1rem;
                transition: border-color 0.2s, box-shadow 0.2s;
                outline: none;
            }

            input[type="password"]:focus {
                border-color: var(--accent-neutral);
                box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
            }

            button, input[type="submit"] {
                width: 100%;
                padding: 12px;
                background-color: var(--accent-neutral);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            button:hover, input[type="submit"]:hover {
                background-color: var(--accent-hover);
            }

            .mt-3 {
                margin-top: 24px;
                border-top: 1px solid #eee;
                padding-top: 20px;
            }

            a {
                color: var(--accent-neutral);
                text-decoration: none;
                font-size: 0.9rem;
                font-weight: 500;
            }

            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <main class="container">

            <?php if (!empty($error_message) && !empty($token)): ?>
                <div class="validation-errors">
                    <p><?= out($error_message) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($token)): ?>

                <h1>Reset Your Password</h1>
                <p class="instruction-text">Enter your new password below.</p>

                <?php
                echo form_open($form_location);
                echo form_hidden('token', $token);

                echo '<div class="form-group">';
                echo form_label('New Password');
                echo form_password('password', '', [
                    'id' => 'password',
                    'placeholder' => 'Min. 8 characters',
                    'autocomplete' => 'new-password',
                    'required' => true
                ]);
                echo '</div>';

                echo '<div class="form-group">';
                echo form_label('Confirm New Password');
                echo form_password('confirm_password', '', [
                    'id' => 'confirm_password',
                    'placeholder' => 'Confirm new password',
                    'autocomplete' => 'new-password',
                    'required' => true
                ]);
                echo '</div>';

                echo form_submit('reset', 'Reset Password');
                echo form_close();
                ?>

            <?php else: ?>

                <h1>Invalid or Expired Link</h1>
                <p class="instruction-text"><?= out($error_message ?? 'This password reset link is invalid or has expired.') ?></p>
                <div class="mt-3">
                    <a href="<?= BASE_URL ?>login/forgot_password">Request a new reset link</a>
                </div>

            <?php endif; ?>

        </main>
    </body>
</html>