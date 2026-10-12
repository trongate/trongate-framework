<?php
//The main config file
define('BASE_URL', '****');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'setup');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('ERROR_404', 'templates/error_404');

// Lazy sessions: only start a PHP session once something is stored in $_SESSION
// (or the browser already has one). Set to false to start one on every request.
define('SESSION_LAZY', true);
