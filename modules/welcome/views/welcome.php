<!DOCTYPE html>
<html lang="<?= APP_LOCALE ?>">
<head>
    <meta charset="UTF-8">
    <title><?= t('hello world'); ?></title>
</head>
<body>
<section>
    <h1>Congratulations</h1>
    <h2>It Totally Works!</h2>
    <p>This page is being generated dynamically by the Trongate Framework.</p>
    <p>To edit this page, go to: modules/welcome/views/welcome.php</p>
    <p>To explore the documentation go to <a href="http://www.trongate.io/documentation">http://www.trongate.io/documentation</a></p>
    <p><?= money(100); ?></p>
</section>    
    <style>
        body {
            font-size: 2em;
            background: #636ec6;
            color: #ddd;
            text-align: center;
            font-family: "Lucida Console", Monaco, monospace;
        }

        h1 {
            margin-top: 2em;
        }

        h1, h2 {
            text-transform: uppercase;
        }

        a { color: white; }

    </style>
</body>
</html>