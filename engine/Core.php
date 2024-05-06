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
     *
     * This constructor checks the requested URL and serves the appropriate asset based on the URL path.
     * If the URL contains '/vendor/', it serves a vendor asset.
     * If the URL does not contain the module assets trigger, it serves a controller asset.
     * Otherwise, it serves a module asset.
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
     * Serves a vendor asset.
     *
     * This function serves a vendor asset based on the URL path.
     * It extracts the vendor file path from the URL and constructs the absolute path to the vendor file.
     * If the vendor file exists, it determines the content type based on the file extension (CSS or plain text).
     * It sends the appropriate content type header and echoes the contents of the vendor file.
     * If the vendor file does not exist, it terminates execution with an error message.
     *
     * @return void
     */
    private function serve_vendor_asset(): void {
        $vendor_file_path = explode('/vendor/', ASSUMED_URL)[1];
        $vendor_file_path = '../vendor/' . $vendor_file_path;
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
    }

    /**
     * Serves a module asset.
     *
     * This function serves a module asset based on the URL path.
     * It parses the URL segments to extract the target module, directory, and file name of the asset.
     * It constructs the absolute path to the module asset and checks if the asset file exists.
     * If the asset file exists, it determines the content type based on the file extension and MIME type.
     * It sends the appropriate content type header and echoes the contents of the asset file.
     * If the asset file does not exist, it attempts to serve a child module asset or terminates execution.
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

                    if ($content_type === 'image/svg') {
                        $content_type .= '+xml';
                    }

                    //make sure not a PHP file or api.json
                    if ((is_numeric(strpos($content_type, 'php'))) || ($file_name === 'api.json')) {
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
            }
        }
    }

    /**
     * Serves a child module asset if available.
     *
     * This function attempts to serve a child module asset when the requested module asset is not found.
     * It extracts the parent and child module names from the asset path and constructs the path to the child module asset.
     * If the child module asset exists, it determines the content type based on the file extension and MIME type.
     * It sends the appropriate content type header and echoes the contents of the child module asset.
     *
     * @param string $asset_path The absolute path to the module asset.
     * @param string $file_name The name of the asset file.
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
     * Attempts SQL transfer.
     *
     * This function attempts to transfer SQL files associated with the current controller.
     * It extracts the directory path based on the provided controller path and searches for SQL files within that directory.
     * If SQL files are found, it includes the transferer script to initiate the transfer process.
     *
     * @param string $controller_path The path to the current controller file.
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
     * Serves the controller.
     *
     * This function handles the serving of the controller based on the URL segments.
     * It determines the current module, controller, and method from the URL segments.
     * If the controller is for the API module, it loads the appropriate API controller.
     * Otherwise, it checks if the controller file exists and loads it.
     * If in development mode, it attempts to transfer SQL files associated with the controller.
     * It then invokes the controller method based on the URL segments.
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

    /**
     * Draws the date format preferences.
     *
     * This function retrieves the default date format and locale string if not already defined.
     * It then constructs an array containing the default date format and locale string.
     * Finally, it outputs the date preferences as JSON and terminates the script.
     *
     * @return void
     */
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

    /**
     * Draws the element adder view.
     *
     * This function sets the HTTP response code to 200 (OK) and retrieves the path to the
     * element adder view file. If the view file exists, its content is read, and any placeholders
     * are replaced with actual values. Finally, the content of the view file is echoed, and the
     * script terminates. If the view file is not found, it sets the HTTP response code to 404 (Not Found)
     * and outputs an error message before terminating the script.
     *
     * @return void
     */
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


    /**
     * Invokes the controller method.
     *
     * This function checks if the current controller class has the specified method.
     * If the method exists, it instantiates the controller class and calls the method
     * with the provided value as an argument. If the method does not exist, it falls back
     * to handling standard endpoints.
     *
     * @return void
     */
    private function invoke_controller_method(): void {
        if (method_exists($this->current_controller, $this->current_method)) {
            $target_method = $this->current_method;
            $this->current_controller = new $this->current_controller($this->current_module);
            $this->current_controller->$target_method($this->current_value);
        } else {
            $this->handle_standard_endpoints();
        }
    }

    /**
     * Handles standard endpoints.
     *
     * This function sets the current controller to 'Standard_endpoints' and includes
     * the corresponding file. It then attempts to find the index of the endpoint being
     * accessed. If found, it calls the corresponding method in the Standard_endpoints class
     * based on the endpoint index. If not found, it draws the error page.
     *
     * @return void
     */
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
     * Attempts to initialize a child controller based on the current controller name.
     *
     * This function checks if the current controller name consists of two parts separated by a hyphen.
     * If it does, it assumes it's a child controller and attempts to find and initialize the corresponding
     * controller file. If found, it returns the path to the controller file. If not found, it proceeds
     * to check if a custom 404 intercept is declared. If so, it sets the current module, controller, and
     * method based on the intercept and attempts to find and initialize the corresponding controller file.
     * If found, it returns the path to the controller file. If none of the above conditions are met,
     * it draws the error page.
     *
     * @param string $controller_path The path to the controller file to be initialized.
     * @return string The path to the initialized controller file.
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
     * Draws the error page.
     *
     * This function loads the 'error_404' view and terminates the script execution, indicating the end
     * of all possible scenarios being tried.
     */
    private function draw_error_page(): void {
        load('error_404');
        die(); //end of the line (all possible scenarios tried)
    }
}
