<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <script src="js/trongate-mx.min.js"></script>
    <title>Module Details</title>
</head>
<body>
    <header>
        <span class="close-btn" onclick="window.parent.postMessage('reload_iframe:<?= $after_close_url ?>|<?= $after_close_width ?>|<?= $after_close_height ?>', '*')">&times;</span>
    </header>
    <main><?= $view_content ?></main>
    <style>
        body { background-color: #cae5fe; min-height: 100vh; display: flex; flex-direction: column; color: #2d2d2d; }
        header { background-color: #ff744a; color: #eee; text-align: right; font-weight: bold; font-size: 18px; padding: 6px 12px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        main { margin-top: 48px; }
        h1 { text-align: center; margin-top: 0; font-size: 33px; }
        p { text-align: center; }
        table { background-color: #fff; }
        tr > td { vertical-align: top; }
        .close-btn { cursor: pointer; }
        .validation-error-report > div { font-size: .9em; }
    </style>
    <script>
        window.trongateValidationErrors = undefined;
        <?php foreach ($local_storage_items as $key => $value): ?>
        localStorage.setItem('<?= $key ?>', <?= json_encode($value, JSON_UNESCAPED_UNICODE) ?>);
        <?php endforeach; ?>
    </script>
</body>
</html>
