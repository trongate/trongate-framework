<?php
session_start();
require_once '../config/config.php';
require_once '../config/custom_routing.php';
require_once '../config/database.php';
require_once '../config/site_owner.php';
require_once '../config/themes.php';
require_once 'get_segments.php';

spl_autoload_register(function($class_name) {

    if (strpos($class_name, '_helper')) {
        $class_name = 'tg_helpers/'.$class_name;
    }

    require_once $class_name . '.php';
});

function load($template_file, $data=NULL) {
    //load template view file
    if (isset(THEMES[$template_file])) {
        $theme_dir = THEMES[$template_file]['dir'];
        $template = THEMES[$template_file]['template'];
        $file_path = APPPATH.'public/themes/'.$theme_dir.'/'.$template;
        define('THEME_DIR', BASE_URL.'themes/'.$theme_dir.'/');
    } else {
        $file_path = APPPATH.'templates/views/'.$template_file.'.php';
    }

    if (file_exists($file_path)) {

        if (isset($data)) {
            extract($data);
        }

        require_once($file_path);

    } else {
        die('<br><b>ERROR:</b> View file does not exist at: '.$file_path);
    }
}

$tg_helpers = ['form_helper', 'flashdata_helper', 'url', 'validation_helper'];
define('TRONGATE_HELPERS', $tg_helpers);