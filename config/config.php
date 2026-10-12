<?php
//The main config file
define('BASE_URL', '****');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'setup');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('ERROR_404', 'templates/error_404');

// Secure flag on the trongatetoken login cookie: null = automatic (on over HTTPS,
// including behind a proxy that sends X-Forwarded-Proto), true/false = force it
define('TOKEN_COOKIE_SECURE', null);
