<?php

declare(strict_types=1);

$admin_theme = [
    'dir' => 'default_admin/blue',
    'template' => 'admin.php',
];

$themes['admin'] = $admin_theme;
define('THEMES', $themes);
