<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Forgot Password</title>
        <style>
            :root {
                --bg-color: #ccc; /* Updated per your request */
                --card-bg: #ffffff;
                --text-main: #333333;
                --text-muted: #666666;
                --border-color: #dddddd;
                --accent-neutral: #4a5568; 
                --accent-hover: #2d3748;
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

            /* Trongate Flashdata Styling */
            .alert, [style*="color: red"], [style*="background-color: #f8d7da"] {
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 0.9rem;
                list-style: none;
                background-color: #fff5f5;
                color: #c53030;
                border: 1px solid #feb2b2;
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

            input[type="text"] {
                width: 100%;
                padding: 12px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                font-size: 1rem;
                transition: border-color 0.2s, box-shadow 0.2s;
                outline: none;
            }

            input[type="text"]:focus {
                border-color: var(--accent-neutral);
                box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
            }

            /* Trongate Form Submit Button Styling */
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

            .mt-2 {
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
            <h1>Forgot Your Password?</h1>
            <?php
            $lower_label = strtolower($identifier_label);
            $instruction = (strpos($lower_label, 'email') !== false)
                ? "Enter your {$lower_label} and we will send you a reset link."
                : "Enter your {$lower_label} or email address and we will send you a reset link.";
            ?>
            <p class="instruction-text"><?= $instruction ?></p>

            <?php
            echo flashdata();
            echo form_open($form_location);

            echo '<div class="form-group">';
            echo form_label($identifier_label);
            echo form_input('identifier', '', [
                'id' => 'identifier',
                'placeholder' => $identifier_label . ' or Email',
                'autocomplete' => 'email',
                'required' => true
            ]);
            echo '</div>';

            echo form_submit('send_link', 'Send Reset Link');
            echo form_close();
            ?>

            <p class="mt-2">
                <a href="<?= BASE_URL ?>login/login/<?= out($login_url) ?>">Back to login</a>
            </p>
        </main>
    </body>
</html>