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

//TrongateLocalization (i18n)
define('FALLBACK_LOCALE', getenv('FALLBACK_LOCALE') ?: 'en');
define('LOCALE_MAPPINGS', getenv('LOCALE_MAPPINGS') ?: 'da:da_DK,en:en_US,es:es_ES,fr:fr_FR,de:de_DE,it:it_IT,pt:pt_PT,ru:ru_RU,zh:zh_CN,ja:ja_JP,ko:ko_KR,ar:ar_AE,hi:hi_IN,vi:vi_VN,th:th_TH,ms:ms_MY,fil:fil_PH');
define('LOCALIZATION_DRIVER', getenv('LOCALIZATION_DRIVER') ?: 'Filesystem_driver');