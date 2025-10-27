<?php

/**
 * Core Framework Dispatcher
 * 
 * The main request router that handles three types of requests:
 * 1. Vendor assets (/vendor/library/file.css) - serves third-party library files
 * 2. Module assets (module_module/js/script.js) - serves assets from module directories
 * 3. Controller requests - standard MVC routing to controllers and methods
 * 
 * Supports complex module structures including parent/child modules (e.g., cars-accessories).
 * Instantiated on every request immediately after framework bootstrap.
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
     * Serve controller class.
     *
     * @return void
     */
    private function serve_controller(): void {
        $segments = SEGMENTS;

        // Parse module from segments
        if (isset($segments[1])) {
            $module_with_no_params = explode('?', $segments[1])[0];
            $this->current_module = !empty($module_with_no_params) ? strtolower($module_with_no_params) : $this->current_module;
            $this->current_controller = ucfirst($this->current_module);
        }

        // Parse method from segments  
        if (isset($segments[2])) {
            $method_with_no_params = explode('?', $segments[2])[0];
            $this->current_method = !empty($method_with_no_params) ? strtolower($method_with_no_params) : $this->current_method;

            // Block access to private methods (starting with _)
            if (substr($this->current_method, 0, 1) === '_') {
                $this->draw_error_page();
            }
        }

        // Get optional parameter value
        $this->current_value = $segments[3] ?? '';

        // Build controller path and load controller
        $controller_path = $this->get_controller_path();
        require_once $controller_path;

        // Dev environment: check for SQL transfers
        if (strtolower(ENV) === 'dev') {
            $this->attempt_sql_transfer($controller_path);
        }

        $this->invoke_controller_method();
    }

    /**
     * Get the correct controller path, handling child modules and 404 fallbacks.
     *
     * @return string The path to the controller file
     */
    private function get_controller_path(): string {
        $controller_path = '../modules/' . $this->current_module . '/controllers/' . $this->current_controller . '.php';

        if (file_exists($controller_path)) {
            return $controller_path;
        }

        // Try child controller
        $child_path = $this->try_child_controller();
        if ($child_path !== null) {
            return $child_path;
        }

        // Try custom 404 intercept
        $intercept_path = $this->try_404_intercept();
        if ($intercept_path !== null) {
            return $intercept_path;
        }

        // All options exhausted
        $this->draw_error_page();
    }

    /**
     * Attempt to find a child controller.
     *
     * @return string|null The controller path if found, null otherwise
     */
    private function try_child_controller(): ?string {
        $path_info = Module_path::resolve($this->current_module);
        
        if ($path_info['type'] === 'child') {
            $this->current_controller = $path_info['controller_class'];
            return $path_info['controller_path'];
        }
        
        return null;
    }

    /**
     * Attempt to use custom 404 intercept.
     *
     * @return string|null The controller path if found, null otherwise
     */
    private function try_404_intercept(): ?string {
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

        return null;
    }

    /**
     * Invoke the appropriate controller method.
     *
     * @return void
     */
    private function invoke_controller_method(): void {
        $controller_class = $this->current_controller;
        $controller_instance = new $controller_class($this->current_module);

        if (method_exists($controller_instance, $this->current_method)) {
            $controller_instance->{$this->current_method}($this->current_value);
        } else {
            $this->draw_error_page();
        }

    }

    /**
     * Attempt SQL transfer for Module Import Wizard.
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
                    
                    if (is_file($asset_path)) {
                        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
                            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= filemtime($asset_path)) {
                                header('Last-Modified: '.gmdate('D, d M Y H:i:s',  filemtime($asset_path)).' GMT', true, 304);
                                die;
                        }
                        
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
                            http_response_code(403);
                            die();
                        }

                        header('Content-type: ' . $content_type);
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($asset_path)) . ' GMT');
                        readfile($asset_path);
                        die;
                    } 
                } catch (Exception $e) {
                    die($e->getMessage());
                }
            }
        }
    }

    /**
     * Sanitize file paths to prevent directory traversal.
     *
     * @param string $path The path to sanitize.
     * @param string $base_dir The base directory to compare against.
     * @param bool $is_child_module True if attempting to sanitize the path for a child module asset
     * @return string The sanitized path.
     * @throws Exception if the path is invalid.
     */
    private function sanitize_file_path(string $path, string $base_dir, bool $is_child_module = false): string {
        $real_base_dir = realpath($base_dir);
        $real_path = realpath($path);

        if ( (!$real_path || strpos($real_path, $real_base_dir) !== 0) && !$is_child_module ) {

            $real_path = $this->sanitize_file_path($path, $base_dir, true);

        } else if ($is_child_module) {

            $path_bits = explode('/',$path);
            $path_bits[2] = str_replace('-','/',$path_bits[2]); // split target module into parent/child
            $real_path = realpath(implode('/',$path_bits));

            if (!$real_path || strpos($real_path, $real_base_dir) !== 0) {
                http_response_code(404);
                throw new Exception('Invalid file path.');
            }
        }

        return $real_path;
    }

    /**
     * Draw an error page.
     *
     * @return void
     */
    private function draw_error_page(): void {
        // Load the Templates controller
        $template_controller_path = '../templates/Templates.php';

        if (!file_exists($template_controller_path)) {
            // Fallback if Templates.php doesn't exist
            die('404 - Page Not Found');
        }

        require_once $template_controller_path;
        $templates = new Templates();

        // Call the error_404 method without any arguments
        $templates->error_404();
        die();
    }

}
