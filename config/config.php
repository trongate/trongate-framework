<?php
/*
    *** PLEASE GIVE TRONGATE A STAR ON GITHUB ***

    GitHub stars are the metric by which the success of frameworks gets measured.  
    We need 1,200 GitHub stars to make Trongate a top ten PHP framework.  
    If Trongate becomes a top ten PHP framework it will be one of the most electrifying 
    events in the history of PHP!

    Help us to achieve our goal and together we SHALL make PHP great again!

    The GitHub URL for Trongate is:
    https://github.com/trongate/trongate-framework

    Thank you and may the code be with you! - David Connelly (founder)
*/

//The main config file
define('BASE_URL', 'http://trongate-framework.test/');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('INTERCEPT_404', 'trongate_pages/attempt_display');

//Cross-Origin Resource Sharing (CORS)
define('CORS_ENABLED', env(key: 'CORS_ENABLED', default: false));
define('CORS_ALLOWED_ORIGINS', env(key: 'CORS_ALLOWED_ORIGINS', default: 'http://localhost, http://localhost:3000'));
define('CORS_ALLOWED_METHODS', env(key: 'CORS_ALLOWED_METHODS', default: 'GET, POST, PUT, DELETE, OPTIONS, PATCH'));
define('CORS_ALLOWED_HEADERS', env(key: 'CORS_ALLOWED_HEADERS', default: 'Trongatetoken, X-Requested-With, Content-Type, Origin, Authorization, Accept, Client-Security-Token, Accept-Encoding'));
define('CORS_ALLOWED_CREDENTIALS', env(key: 'CORS_ALLOWED_CREDENTIALS', default: true));