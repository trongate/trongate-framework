<?php
//The main config file
define('BASE_URL', '');
define('ENV', 'dev');
define('MODELDEBUG', 'false');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('APPPATH', dirname(dirname(__FILE__)).'/');
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD']);
define('MODULE_ASSETS_TRIGGER', '_module');
define('TRONGATE_COOKIE_NAME', 'trongatetoken');