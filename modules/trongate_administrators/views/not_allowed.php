<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Temporarily Blocked</title>
    <link rel="stylesheet" href="trongate_administrators_module/css/login.css">
</head>
<body>
    <main class="blocked-page">
        <h1>Access Temporarily Blocked</h1>
        <?= flashdata() ?>
        <p>Due to multiple unsuccessful login attempts, your account has been temporarily locked.</p>
        <p>Please wait 15 minutes before trying again.</p>
        <p>If you believe this is a mistake, contact the system administrator.</p>
        <?php
        if (isset($login_url)) {
            echo anchor($login_url, 'Back to Login');
        }
        ?>
    </main>
    <style>
        /* Optional inline styling for emphasis */
        .blocked-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            background-color: #f8f8f8;
        }
        .blocked-page h1 {
            font-size: 2rem;
            color: #d9534f;
        }
        .blocked-page p {
            margin-top: 10px;
            font-size: 1rem;
            color: #333;
        }
        .blocked-page a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #fff;
            background-color: #0275d8;
            padding: 10px 20px;
            border-radius: 4px;
        }
        .blocked-page a:hover {
            background-color: #025aa5;
        }

        .blocked-page h1, .blocked-page p, .blocked-page a {
            top: -3em;
            position: relative;
        }
    </style>
</body>
</html>
