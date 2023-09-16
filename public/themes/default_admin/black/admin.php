<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php

declare(strict_types=1);

    echo BASE_URL ?>css/trongate.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>css/trongate-datetime.css">
    <link rel="stylesheet" href="<?php echo THEME_DIR ?>css/admin.css">
    <?php echo $additional_includes_top ?>
    <title>Admin</title>
</head>
<body>
    <header>    
        <nav class="hide-sm">
            <ul>
                <li><?php echo anchor(BASE_URL, 'Homepage', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/docs', 'Docs', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/help_bar', 'Help Bar', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/learning-zone', 'Learning Zone', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/news', 'News', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/module_requests/browse', 'Module Requests', ['target' => '_blank']) ?></li>
                <li><?php echo anchor('https://trongate.io/module-market', 'Module Market', ['target' => '_blank']) ?></li>
            </ul>        
        </nav>
        <div id="hamburger" class="hide-lg" onclick="openSlideNav()">&#9776;</div>
        <div>
            <?php
    declare(strict_types=1);
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
                <?php echo Template::partial('partials/admin/dynamic_nav') ?>
            </nav>       
        </div>
        <div>
            <main>
                <?php echo Template::display($data) ?></main>
            <footer>
                <div>Footer</div>
                <div>Powered by <?php echo anchor('https://trongate.io', 'Trongate') ?></div>
            </footer>
        </div>  
    </div>
    <div id="slide-nav">
        <div id="close-btn" onclick="closeSlideNav()">&times;</div>
        <ul auto-populate="true"></ul>
    </div>
<script src="<?php echo BASE_URL ?>js/admin.js"></script>
<script src="<?php echo BASE_URL ?>js/trongate-datetime.js"></script>
<?php echo $additional_includes_btm ?>
</body>
</html>