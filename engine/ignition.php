<?php
session_start();
require_once '../config/config.php';
require_once '../config/custom_routing.php';
require_once '../config/database.php';
require_once '../config/site_owner.php';
require_once '../config/themes.php';

spl_autoload_register(function ($class_name) {

    $class_name = str_replace('alidation_helper', 'alidation', $class_name);
    $target_filename = realpath(__DIR__ . '/' . $class_name . '.php');

    if (file_exists($target_filename)) {
        return require_once($target_filename);
    }

    return false;
});

/**
 * Retrieves the URL segments after optionally ignoring custom routes.
 *
 * @param bool|null $ignore_custom_routes Flag to determine whether to ignore custom routes.
 * @return array Returns an associative array with 'assumed_url' and 'segments'.
 */
function get_segments(?bool $ignore_custom_routes = null): array {

    // Figure out how many segments need to be ditched
    $pseudo_url = str_replace('://', '', BASE_URL);
    $pseudo_url = rtrim($pseudo_url, '/');
    $bits = explode('/', $pseudo_url);
    $num_bits = count($bits);

    if ($num_bits > 1) {
        $num_segments_to_ditch = $num_bits - 1;
    } else {
        $num_segments_to_ditch = 0;
    }

    $assumed_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if (!isset($ignore_custom_routes)) {
        $assumed_url = attempt_add_custom_routes($assumed_url);
    }

    $data['assumed_url'] = $assumed_url;

    $assumed_url = str_replace('://', '', $assumed_url);
    $assumed_url = rtrim($assumed_url, '/');

    $segments = explode('/', $assumed_url);

    for ($i = 0; $i < $num_segments_to_ditch; $i++) {
        unset($segments[$i]);
    }

    $data['segments'] = array_values($segments);
    return $data;
}

/**
 * Attempts to replace the target URL with a custom route if a match is found in the custom routes configuration.
 *
 * @param string $target_url The original target URL to potentially replace.
 * @return string Returns the updated URL if a custom route match is found, otherwise returns the original URL.
 */
function attempt_add_custom_routes(string $target_url): string {
    $target_url = rtrim($target_url, '/');
    $target_segments_str = str_replace(BASE_URL, '', $target_url);
    $target_segments = explode('/', $target_segments_str);

    foreach (CUSTOM_ROUTES as $custom_route => $custom_route_destination) {
        $custom_route_segments = explode('/', $custom_route);
        if (count($target_segments) == count($custom_route_segments)) {
            if ($custom_route == $target_segments_str) { // Perfect match; return immediately
                $target_url = str_replace($custom_route, $custom_route_destination, $target_url);
                break;
            }
            $abort_route_check = false;
            $correction_counter = 0;
            $new_custom_url = rtrim(BASE_URL . $custom_route_destination, '/');
            for ($i = 0; $i < count($target_segments); $i++) {
                if ($custom_route_segments[$i] == $target_segments[$i]) {
                    continue;
                } else if ($custom_route_segments[$i] == "(:num)" && is_numeric($target_segments[$i])) {
                    $correction_counter++;
                    $new_custom_url = str_replace('$' . $correction_counter, $target_segments[$i], $new_custom_url);
                } else if ($custom_route_segments[$i] == "(:any)") {
                    $correction_counter++;
                    $new_custom_url = str_replace('$' . $correction_counter, $target_segments[$i], $new_custom_url);
                } else {
                    $abort_route_check = true;
                    break;
                }
            }
            if (!$abort_route_check) {
                $target_url = $new_custom_url;
            }
        }
    }
    return $target_url;
}

define('APPPATH', str_replace("\\", "/", dirname(dirname(__FILE__)) . '/'));
define('REQUEST_TYPE', $_SERVER['REQUEST_METHOD']);
$tg_helpers = ['form_helper', 'flashdata_helper', 'string_helper', 'timedate_helper', 'url_helper', 'utilities_helper'];
define('TRONGATE_HELPERS', $tg_helpers);
$data = get_segments();
define('SEGMENTS', $data['segments']);
define('ASSUMED_URL', $data['assumed_url']);

//load the helper classes
foreach (TRONGATE_HELPERS as $tg_helper) {
    require_once 'tg_helpers/' . $tg_helper . '.php';
}
