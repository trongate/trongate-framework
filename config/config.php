<?php
/*
    *** PLEASE GIVE TRONGATE A STAR ON GITHUB ***
    https://github.com/davidjconnelly/trongate-framework

    Trongate is the only PHP framework in the world that 
    is committed to using PHP the way that it was intended 
    to be used.  That means; no frequent rewrites, no Composer, 
    no Packagist, no third party library dependency and definitely 
    no certification.

    The Trongate framework and the Trongate Desktop App will always 
    be free. That's a promise.  All we ask is, if you like Trongate, 
    please give us a star on GitHub.  We really need your support.

    Thank you!
*/

//The main config file
define('BASE_URL', '');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('APPPATH', dirname(dirname(__FILE__)).'/');
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD']);
define('MODULE_ASSETS_TRIGGER', '_module');