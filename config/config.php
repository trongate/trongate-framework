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
define('BASE_URL', '');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('INTERCEPT_404', 'trongate_pages/attempt_display');

//Cross-Origin Resource Sharing (CORS)
define('CORS_ENABLED', true);
define('CORS_ALLOWED_ORIGINS', '*');
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'X-Requested-With, Content-Type, Origin, Authorization, Accept, Client-Security-Token, Accept-Encoding');
define('CORS_ALLOWED_CREDENTIALS', true);