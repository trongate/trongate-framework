<?php

/**
 * Class Core
 * Manages the serving of assets for the Trongate framework.
 */
class Core {

    protected $current_module = DEFAULT_MODULE;
    protected $current_controller = DEFAULT_CONTROLLER;
    protected $current_method = DEFAULT_METHOD;
    protected $current_value = '';

    /**
     * Constructor for the Core class.
     * Depending on the URL, serves either vendor assets, controller content, or module assets.
     */
    public function __construct() {
        if (strpos(ASSUMED_URL, '/vendor/')) {
            $this->serve_vendor_asset();
        } elseif (strpos(ASSUMED_URL, MODULE_ASSETS_TRIGGER) === false) {
            $this->serve_controller();
        } else {
            $this->serve_module_asset();
        }
    }

    /**
     * Serve vendor assets.
     *
     * @return void
     */
    private function serve_vendor_asset(): void {
        $vendor_file_path = explode('/vendor/', ASSUMED_URL)[1];
        $vendor_file_path = '../vendor/' . $vendor_file_path;
        
        try {
            $vendor_file_path = $this->sanitize_file_path($vendor_file_path, '../vendor/');
            
            if (file_exists($vendor_file_path)) {
                if (strpos($vendor_file_path, '.css')) {
                    $content_type = 'text/css';
                } else {
                    $content_type = 'text/plain';
                }

                header('Content-type: ' . $content_type);
                $contents = file_get_contents($vendor_file_path);
                echo $contents;
                die();
            } else {
                die('Vendor file not found.');
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Sanitize file paths to prevent directory traversal.
     *
     * @param string $path The path to sanitize.
     * @param string $base_dir The base directory to compare against.
     * @return string The sanitized path.
     * @throws Exception if the path is invalid.
     */
    private function sanitize_file_path(string $path, string $base_dir): string {
        $real_base_dir = realpath($base_dir);
        $real_path = realpath($path);

        if (!$real_path || strpos($real_path, $real_base_dir) !== 0) {
            throw new Exception('Invalid file path.');
        }

        return $real_path;
    }

    /**
     * Serve module assets.
     *
     * @return void
     */
    private function serve_module_asset(): void {
        $url_segments = SEGMENTS;

        foreach ($url_segments as $url_segment_key => $url_segment_value) {
            $pos = strpos($url_segment_value, MODULE_ASSETS_TRIGGER);

            if (is_numeric($pos)) {
                $target_module = str_replace(MODULE_ASSETS_TRIGGER, '', $url_segment_value);
                $file_name = $url_segments[count($url_segments) - 1];

                $target_dir = '';
                for ($i = $url_segment_key + 1; $i < count($url_segments) - 1; $i++) {
                    $target_dir .= $url_segments[$i];
                    if ($i < count($url_segments) - 2) {
                        $target_dir .= '/';
                    }
                }

                $asset_path = '../modules/' . strtolower($target_module) . '/assets/' . $target_dir . '/' . $file_name;
                
                try {
                    $asset_path = $this->sanitize_file_path($asset_path, '../modules/');
                    
                    if (file_exists($asset_path)) {
                        $content_type = mime_content_type($asset_path);

                        if ($content_type === 'text/plain' || $content_type === 'text/html') {
                            if (strpos($file_name, '.css') !== false) {
                                $content_type = 'text/css';
                            } elseif (strpos($file_name, '.js') !== false) {
                                $content_type = 'text/javascript';
                            }
                        }

                        if ($content_type === 'image/svg') {
                            $content_type .= '+xml';
                        }

                        // Make sure it's not a PHP file or api.json
                        if (strpos($content_type, 'php') !== false || $file_name === 'api.json') {
                            http_response_code(422);
                            die();
                        }

                        header('Content-type: ' . $content_type);
                        $contents = file_get_contents($asset_path);
                        echo $contents;
                        die();
                    } else {
                        $this->serve_child_module_asset($asset_path, $file_name);
                    }
                } catch (Exception $e) {
                    die($e->getMessage());
                }
            }
        }
    }

    /**
     * Serve child module assets.
     *
     * @param string $asset_path The path to the asset.
     * @param string $file_name The name of the file.
     * @return void
     */
    private function serve_child_module_asset(string $asset_path, string $file_name): void {
        $start = '/modules/';
        $end = '/assets/';

        $pos = stripos($asset_path, $start);
        $str = substr($asset_path, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        $target_str = trim($str_three);

        $bits = explode('-', $target_str);

        if (count($bits) == 2) {
            if (strlen($bits[1]) > 0) {
                $parent_module = $bits[0];
                $child_module = $bits[1];

                $asset_path = str_replace($target_str, $parent_module . '/' . $child_module, $asset_path);
                if (file_exists($asset_path)) {

                    $content_type = mime_content_type($asset_path);

                    if ($content_type === 'text/plain' || $content_type === 'text/html') {
                        $pos2 = strpos($file_name, '.css');
                        if (is_numeric($pos2)) {
                            $content_type = 'text/css';
                        }
                        $pos2 = strpos($file_name, '.js');
                        if (is_numeric($pos2)) {
                            $content_type = 'text/javascript';
                        }
                    }

                    header('Content-type: ' . $content_type);
                    $contents = file_get_contents($asset_path);
                    echo $contents;
                    die();
                }
            }
        }
    }

    /**
     * Attempt SQL transfer.
     *
     * @param string $controller_path The path to the controller.
     * @return void
     */
    private function attempt_sql_transfer(string $controller_path): void {
        $ditch = 'controllers/' . $this->current_controller . '.php';
        $dir_path = str_replace($ditch, '', $controller_path);

        $files = array();
        foreach (glob($dir_path . "*.sql") as $file) {
            $file = str_replace($controller_path, '', $file);
            $files[] = $file;
        }

        if (count($files) > 0) {
            require_once('tg_transferer/index.php');
            die();
        }
    }

    /**
     * Serve controller class.
     *
     * @return void
     */
    private function serve_controller(): void {
        $segments = SEGMENTS;

        if (isset($segments[1])) {
            $module_with_no_params = explode('?', $segments[1])[0];
            $this->current_module = !empty($module_with_no_params) ? strtolower($module_with_no_params) : $this->current_module;
            $this->current_controller = ucfirst($this->current_module);

            if (defined('TRONGATE_PAGES_TRIGGER') && $segments[1] === TRONGATE_PAGES_TRIGGER) {
                $this->current_module = 'trongate_pages';
                $this->current_controller = 'Trongate_pages';
            }
        }

        if (isset($segments[2])) {
            $method_with_no_params = explode('?', $segments[2])[0];
            $this->current_method = !empty($method_with_no_params) ? strtolower($method_with_no_params) : $this->current_method;

            if (substr($this->current_method, 0, 1) === '_') {
                $this->draw_error_page();
            }
        }

        $this->current_value = isset($segments[3]) ? $segments[3] : $this->current_value;

        $controller_path = '../modules/' . $this->current_module . '/controllers/' . $this->current_controller . '.php';

        if ($controller_path === '../modules/api/controllers/Api.php') {
            $controller_path = '../engine/Api.php';
            require_once $controller_path;

            $target_method = $this->current_method;
            $this->current_controller = new $this->current_controller($this->current_module);

            if (method_exists($this->current_controller, $this->current_method)) {
                $this->current_controller->$target_method($this->current_value);
                return;
            } else {
                $this->draw_error_page();
            }
        }

        switch (segment(1)) {
            case 'dateformat':
                $this->draw_date_format();
                break;

            case 'tgp_element_adder':
                $this->draw_element_adder();
                break;

            default:
                if (!file_exists($controller_path)) {
                    $controller_path = $this->attempt_init_child_controller($controller_path);
                }

                require_once $controller_path;

                if (strtolower(ENV) == 'dev') {
                    $this->attempt_sql_transfer($controller_path);
                }

                $this->invoke_controller_method();
                break;
        }
    }

    private function draw_date_format(): void {
        if (!defined('DEFAULT_DATE_FORMAT')) {
            get_default_date_format();
        }

        if (!defined('DEFAULT_LOCALE_STR')) {
            get_default_locale_str();
        }

        $date_prefs = [
            'default_date_format' => DEFAULT_DATE_FORMAT,
            'default_locale_str' => DEFAULT_LOCALE_STR,
        ];

        http_response_code(200);
        echo json_encode($date_prefs);
        die();
    }

    private function draw_element_adder(): void {
        http_response_code(200);
        $view_file_path = realpath(APPPATH . 'engine/views/element_adder.php');

        if ($view_file_path && file_exists($view_file_path)) {
            $file_content = file_get_contents($view_file_path);
            $file_content = str_replace('[BASE_URL]', BASE_URL, $file_content);
            echo $file_content;
            die();
        } else {
            http_response_code(404);
            echo 'Cannot find ' . $view_file_path;
            die();
        }
    }


    private function invoke_controller_method(): void {
        if (method_exists($this->current_controller, $this->current_method)) {
            $target_method = $this->current_method;
            $this->current_controller = new $this->current_controller($this->current_module);
            $this->current_controller->$target_method($this->current_value);
        } else {
            $this->handle_standard_endpoints();
        }
    }

    private function handle_standard_endpoints(): void {
        $this->current_controller = 'Standard_endpoints';
        $controller_path = '../engine/Standard_endpoints.php';
        require_once $controller_path;

        $se = new Standard_endpoints();
        $endpoint_index = $se->attempt_find_endpoint_index();

        if ($endpoint_index !== '') {
            $target_method = $this->current_method;
            if (is_numeric($target_method)) {
                $se->attempt_serve_standard_endpoint($endpoint_index);
            } else {
                $se->$target_method($this->current_value);
            }

            return;
        }

        $this->draw_error_page();
    }

    /**
     * Attempt initialization of child controller.
     *
     * @param string $controller_path The path to the controller.
     * @return string The path to the controller after initialization.
     */
    private function attempt_init_child_controller(string $controller_path): string {
        $bits = explode('-', $this->current_controller);

        if (count($bits) == 2) {
            if (strlen($bits[1]) > 0) {

                $parent_module = strtolower($bits[0]);
                $child_module = strtolower($bits[1]);
                $this->current_controller = ucfirst($bits[1]);
                $controller_path = '../modules/' . $parent_module . '/' . $child_module . '/controllers/' . ucfirst($bits[1]) . '.php';

                if (file_exists($controller_path)) {
                    return $controller_path;
                }
            }
        }

        //do we have a custom 404 intercept declared?
        if (defined('INTERCEPT_404')) {
            $intercept_bits = explode('/', INTERCEPT_404);
            $this->current_module = $intercept_bits[0];
            $this->current_controller = ucfirst($intercept_bits[0]);
            $this->current_method = $intercept_bits[1];
            $controller_path = '../modules/' . $this->current_module . '/controllers/' . $this->current_controller . '.php';
            if (file_exists($controller_path)) {
                return $controller_path;
            }
        }

        $this->draw_error_page();
    }

    /**
     * Draw an error page.
     *
     * @return void
     */
    private function draw_error_page(): void {
        load('error_404');
        die(); //end of the line (all possible scenarios tried)
    }
}