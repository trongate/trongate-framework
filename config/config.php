<?php
//The main config file
define('BASE_URL', $_ENV['BASE_URL'] ?? '');
define('ENV', $_ENV['ENV'] ?? 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('INTERCEPT_404', 'trongate_pages/attempt_display');