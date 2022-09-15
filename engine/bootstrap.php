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

define('APPPATH', str_replace("\\", "/", dirname(dirname(__FILE__)).'/'));
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD']);

define('FALLBACK_LOCALE', 'en_US');

// Set the app locale from the requested locale or fallback
$locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?: FALLBACK_LOCALE;
Locale::setDefault($locale);
define('APP_LOCALE', $locale);

// Use PHPs built-in number formatter to retrieve the currency code for the app locale
$numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
define('APP_CURRENCY', $numberFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE));

// Not sure if using a DB table or a php file returning an array or a JSON file is more ideal.
// Leaning towards JSON because it's useful for client apps.
// Also using the VSCode ext "Localise/i18n Ally" with a JSON file seems fairly straight forwards aswell.
$loadTranslations = function (string $locale) {
  $translations = file_get_contents(APPPATH . 'lang' . DIRECTORY_SEPARATOR . $locale . '.json');  

  return json_decode($translations, true);
};

define('APP_TRANSLATIONS', $loadTranslations(APP_LOCALE) ?: $loadTranslations(FALLBACK_LOCALE));

function t(string $key, mixed $default = null) {
  if (isset(APP_TRANSLATIONS[$key])) {
    return APP_TRANSLATIONS[$key];
  }

  return $default ?: $key;
}

$tg_helpers = ['form_helper', 'flashdata_helper', 'url', 'validation_helper'];
define('TRONGATE_HELPERS', $tg_helpers);