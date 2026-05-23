<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset Complete</title>
        <style>
            :root {
                --bg-color: #ccc;
                --card-bg: #ffffff;
                --text-main: #333333;
                --text-muted: #666666;
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
                margin-bottom: 20px;
                color: var(--text-main);
            }

            p {
                font-size: 0.95rem;
                color: var(--text-muted);
                line-height: 1.6;
                margin-bottom: 2rem;
            }

            /* Styling the 'Back to Login' link as a primary button */
            .btn-login {
                display: inline-block;
                width: 100%;
                padding: 12px;
                background-color: var(--accent-neutral);
                color: white;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 0.2s, transform 0.1s;
            }

            .btn-login:hover {
                background-color: var(--accent-hover);
                text-decoration: none;
            }

            .btn-login:active {
                transform: scale(0.98);
            }

            .mt-3 {
                margin-top: 1rem;
            }
        </style>
    </head>
    <body>
        <main class="container">
            <h1>Password Reset Complete</h1>
            
            <p>Your password has been updated successfully. You can now log in using your new password.</p>
            
            <div class="mt-3">
                <a href="<?= BASE_URL ?>login" class="btn-login">Log In</a>
            </div>
        </main>
    </body>
</html>