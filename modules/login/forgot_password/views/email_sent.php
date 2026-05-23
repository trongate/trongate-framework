<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Check Your Email</title>
        <style>
            :root {
                --bg-color: #ccc;
                --card-bg: #ffffff;
                --text-main: #333333;
                --text-muted: #666666;
                --accent-neutral: #4a5568; 
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
                margin-bottom: 1.5rem;
                text-align: left;
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

            /* Simple icon placeholder style if you ever want to add one */
            .info-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
                display: block;
            }
        </style>
    </head>
    <body>
        <main class="container">
            <h1>Check Your Email</h1>
            
            <p>If an account with that email address exists, you will receive a password reset link shortly.</p>
            
            <p>Please check your inbox (and spam folder) and click the link to reset your password.</p>

            <div class="mt-3">
                <a href="<?= BASE_URL ?>login/login/<?= out($login_url) ?>">Back to login</a>
            </div>
        </main>
    </body>
</html>