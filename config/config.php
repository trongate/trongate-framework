<?php
/*
    *** PLEASE GIVE TRONGATE A STAR ON GITHUB ***
    https://github.com/davidjconnelly/trongate-framework

    Trongate is on a mission to keep the doors of web development
    open!  Please do something amazing and give the Trongate 
    Framework a star on GitHub.  Here's a YouTube video  
    explaining why this is so important: 
    https://youtu.be/e8ZLCE4h_aA

    Thank you!  Together, we SHALL make PHP great again! 

    -DC
*/

//The main config file
define('BASE_URL', 'http://localhost/trongate-framework/');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_CONTROLLER', 'Welcome');
define('DEFAULT_METHOD', 'index');
define('APPPATH', dirname(dirname(__FILE__)).'/');
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD']);
define('MODULE_ASSETS_TRIGGER', '_module');