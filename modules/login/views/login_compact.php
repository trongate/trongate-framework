<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>
    <style>
        /* =========================================
           RESET & BASE STYLES
           ========================================= */
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --bg-glass: #ffffff;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --input-border: #d1d5db;
            --input-focus: #4f46e5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            /* CHANGED: Using min-height instead of height ensures content fits on small screens */
            min-height: 100vh; 
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f3f4f6; 
            background: linear-gradient(135deg, #252427 0.000%, #262528 5.000%, #27252b 10.000%, #29272e 15.000%, #2c2833 20.000%, #2f2a39 25.000%, #322d40 30.000%, #362f47 35.000%, #3b334e 40.000%, #403656 45.000%, #453a5d 50.000%, #4b3e64 55.000%, #51426a 60.000%, #584770 65.000%, #5f4d74 70.000%, #675277 75.000%, #6f5879 80.000%, #785e7a 85.000%, #816579 90.000%, #8a6c77 95.000%, #947373 100.000%);
            color: var(--text-color);
            padding: 20px; /* Added padding to prevent edges touching on very small screens */
        }

        /* =========================================
           CARD LAYOUT
           ========================================= */
        .card {
            background: var(--bg-glass);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(0,0,0,0.05);
            /* Margin is now handled by body padding to keep it centered */
        }

        .card-heading h2 {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-color);
            letter-spacing: -0.025em;
        }

        /* =========================================
           FORM ELEMENTS
           ========================================= */
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            outline: none;
            background-color: #f9fafb;
        }

        input:focus {
            border-color: var(--input-focus);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.875rem;
            margin-top: 1rem;
            background-color: var(--primary-color);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        button[type="submit"]:hover {
            background-color: var(--primary-hover);
        }

        /* =========================================
           VALIDATION ERRORS
           ========================================= */
        .validation-errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            color: #991b1b;
            font-size: 0.875rem;
        }

        .validation-errors p {
            margin-bottom: 0.25rem;
        }

        .validation-errors p:last-child {
            margin-bottom: 0;
        }

        .validation-errors li {
          margin-left: 1.2em;  /* Adjust this value to reduce/increase gap */
          padding-left: 0;      /* Remove any default padding */
        }

        /* =========================================
           LINKS (INSIDE CARD)
           ========================================= */
        .mt-1 {
            margin-top: 1rem;
        }

        .text-center {
            text-align: center;
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

    </style>
</head>
<body>

    <main>
        <?= form_open($form_location, ['class' => 'card', 'id' => 'loginForm']) ?>

            <div class="card-heading">
                <h2>Log In</h2>
            </div>

            <div class="card-body">

                <?= validation_errors() ?>

                <div class="form-group">
                    <label for="identifier"><?= out($identifier_label) ?></label>
                    <?= form_input('identifier', '', [
                        'placeholder'  => $identifier_label,
                        'autocomplete' => 'username',
                        'required'     => true
                    ]) ?>
                </div>

                <div class="form-group">
                    <label for="password"><?= out($fields['password']['label']) ?></label>
                    <?= form_password('password', '', [
                        'placeholder'  => $fields['password']['label'],
                        'autocomplete' => 'current-password',
                        'required'     => true
                    ]) ?>
                </div>

                <?= form_submit('login', 'Log In') ?>

                <?php if (isset($forgot_password_url)): ?>
                <p class="mt-1 text-center">
                    <a href="<?= BASE_URL . out($forgot_password_url) ?>">Forgot password?</a>
                </p>
                <?php endif; ?>
            </div>

        <?= form_close() ?>
    </main>

    <!-- Password toggle feature removed -->
</body>
</html>
