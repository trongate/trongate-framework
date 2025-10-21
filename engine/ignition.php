<?php
/**
 * Trongate Ignition
 * Single entry-point for every HTTP request.
 * Responsibilities:
 *   1. Load configuration
 *   2. Register autoloader
 *   3. Load core helpers
 *   4. Run user interceptors (early hooks)
 *   5. Bootstrap routing helpers
 */
session_start();

/* --------------------------------------------------------------
 * 1.  Path constants (earliest possible)
 * -------------------------------------------------------------- */
define('APPPATH', str_replace('\\', '/', dirname(__DIR__) . '/'));
define('ENGPATH', __DIR__ . '/');
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD'] ?? 'CLI');

/* --------------------------------------------------------------
 * 2.  Configuration files
 * -------------------------------------------------------------- */
$config_files = [
    APPPATH . 'config/config.php',
    APPPATH . 'config/custom_routing.php',
    APPPATH . 'config/database.php',
    APPPATH . 'config/site_owner.php',
    APPPATH . 'config/themes.php'
];

foreach ($config_files as $file) {
    if (!is_file($file)) {
        throw new RuntimeException('Missing config file: ' . $file);
    }
    require_once $file;
}

/* --------------------------------------------------------------
 * 3.  Autoloader for core/engine classes
 * -------------------------------------------------------------- */
spl_autoload_register(function ($class_name) {
    $file = ENGPATH . $class_name . '.php';
    if (is_file($file)) {
        require_once $file;
        return true;
    }
    return false;
});

/* --------------------------------------------------------------
 * 3a.  Core helper functions
 * -------------------------------------------------------------- */
require_once ENGPATH . 'Module_path.php';

/* --------------------------------------------------------------
 * 4.  Interceptor execution (early hooks)
 * -------------------------------------------------------------- */
if (defined('INTERCEPTORS') && is_array(INTERCEPTORS)) {
    foreach (INTERCEPTORS as $module => $method) {
        $controller_path = APPPATH . "modules/{$module}/controllers/" . ucfirst($module) . '.php';

        if (!is_file($controller_path)) {
            throw new RuntimeException("Interceptor controller not found: {$controller_path}");
        }

        require_once $controller_path;

        $class = ucfirst($module);
        if (!class_exists($class, false)) {
            throw new RuntimeException("Interceptor class {$class} not defined");
        }

        $instance = new $class($module);

        if (!is_callable([$instance, $method])) {
            throw new RuntimeException("Interceptor method {$class}::{$method} is not callable");
        }

        $instance->{$method}();
    }
}

/* --------------------------------------------------------------
 * 5.  URL & routing helpers
 * -------------------------------------------------------------- */

/**
 * Returns cleaned URL and segments after custom routing
 * @return array ['assumed_url' => string, 'segments' => array]
 */
function get_segments() {
    $base = rtrim(str_replace('://', '', BASE_URL), '/');
    $discard = max(0, substr_count($base, '/'));

    $scheme = (($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http';
    $assumed = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    $assumed = attempt_custom_routing($assumed);

    $clean = trim(str_replace('://', '', $assumed), '/');
    $segments = explode('/', $clean);

    return [
        'assumed_url' => $assumed,
        'segments' => array_slice($segments, $discard)
    ];
}

/**
 * Cached custom-route matching
 */
function attempt_custom_routing($url) {
    static $routes = [];

    if (empty($routes)) {
        if (!defined('CUSTOM_ROUTES') || empty(CUSTOM_ROUTES)) {
            return $url;
        }

        foreach (CUSTOM_ROUTES as $pattern => $dest) {
            $regex = '#^' . strtr($pattern, [
                '/'      => '\/',
                '(:num)' => '(\d+)',
                '(:any)' => '([^\/]+)',
            ]) . '$#';
            $routes[] = [$regex, $dest];
        }
    }

    $path = ltrim(parse_url($url, PHP_URL_PATH) ?: '/', '/');
    $base_path = ltrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '/', '/');

    if ($base_path !== '' && strpos($path, $base_path) === 0) {
        $path = substr($path, strlen($base_path));
    }

    foreach ($routes as [$regex, $dest]) {
        if (preg_match($regex, $path, $matches)) {
            $match_count = count($matches);
            for ($i = 1; $i < $match_count; $i++) {
                $dest = str_replace('$' . $i, $matches[$i], $dest);
            }
            return rtrim(BASE_URL . $dest, '/');
        }
    }

    return $url;
}

/* --------------------------------------------------------------
 * 6.  Final constants & helpers
 * -------------------------------------------------------------- */
$segments = get_segments();
define('SEGMENTS', $segments['segments']);
define('ASSUMED_URL', $segments['assumed_url']);

$helpers = [
    'tg_helpers/flashdata_helper.php',
    'tg_helpers/form_helper.php',
    'tg_helpers/string_helper.php',
    'tg_helpers/timedate_helper.php',
    'tg_helpers/url_helper.php',
    'tg_helpers/utilities_helper.php'
];

foreach ($helpers as $helper) {
    require_once ENGPATH . $helper;
}