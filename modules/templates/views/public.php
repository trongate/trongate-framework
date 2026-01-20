<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
    <?= $additional_includes_top ?? '' ?>
    <title>Welcome to Trongate</title>
</head>
<body>
    <div class="container">
        <div class="text-center"><?= display($data) ?></div>
    </div>
<?= $additional_includes_btm ?? '' ?>
</body>
</html>