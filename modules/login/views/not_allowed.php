<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="<?= BASE_URL ?>">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Temporarily Blocked</title>
        <link rel="stylesheet" href="css/trongate.css">
    </head>
    <body>
        <main class="page">
            <h1>Access Temporarily Blocked</h1>
            <p>
                Too many failed login attempts have been detected for this account or IP address.
                Please wait <?= (int) $block_duration ?> minutes and try again.
            </p>
            <p>
                <a href="<?= BASE_URL ?>login/login/<?= out($login_url) ?>">Return to login page</a>
            </p>
        </main>
    </body>
</html>
