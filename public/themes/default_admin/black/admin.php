<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/trongate-datetime.css">
    <link rel="stylesheet" href="<?= THEME_DIR ?>css/admin-theme.css">
    <?= $additional_includes_top ?>
    <title>Admin</title>
</head>

<body>
    <header>
        <nav class="hide-sm">
            <ul>
                <li><?= anchor(BASE_URL, 'Homepage', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/docs', 'Docs', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/help_bar', 'Help Bar', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/learning-zone', 'Learning Zone', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/news', 'News', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/module_requests/browse', 'Module Requests', array('target' => '_blank')) ?></li>
                <li><?= anchor('https://trongate.io/module-market', 'Module Market', array('target' => '_blank')) ?></li>
            </ul>
        </nav>
        <div id="hamburger" class="hide-lg" onclick="openSlideNav()">&#9776;</div>
        <div>
            <?php
            echo anchor('trongate_administrators/manage', '<i class="fa fa-gears"></i>');
            echo anchor('trongate_administrators/account', '<i class="fa fa-user"></i>');
            echo anchor('trongate_administrators/logout', '<i class="fa fa-sign-out"></i>');
            ?>
        </div>
    </header>
    <div class="wrapper">
        <div id="sidebar">
            <h3>Menu</h3>
            <nav id="left-nav">
                <?= Template::partial('partials/admin/dynamic_nav') ?>
            </nav>
        </div>
        <div>
            <main>
                <?= Template::display($data) ?></main>
            <footer>
                <div>Footer</div>
                <div>Powered by <?= anchor('https://trongate.io', 'Trongate') ?></div>
            </footer>
        </div>
    </div>
    <div id="slide-nav">
        <div id="close-btn" onclick="closeSlideNav()">&times;</div>
        <ul auto-populate="true"></ul>
    </div>
    <script src="<?= BASE_URL ?>js/admin.js"></script>
    <script src="<?= BASE_URL ?>js/trongate-datetime.js"></script>
    <?= $additional_includes_btm ?>
</body>

</html>