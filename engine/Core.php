<?php

declare(strict_types=1);

class Core
{
    protected $current_module = DEFAULT_MODULE;

    protected $current_controller = DEFAULT_CONTROLLER;

    protected $current_method = DEFAULT_METHOD;

    protected $current_value = '';

    public function __construct()
    {
        if (strpos(ASSUMED_URL, '/vendor/')) {
            $this->serve_vendor_asset();
        } elseif (strpos(ASSUMED_URL, MODULE_ASSETS_TRIGGER) === false) {
            $this->serve_controller();
        } else {
            $this->serve_module_asset();
        }
    }

    private function serve_vendor_asset(): void
    {
        $vendor_file_path = explode('/vendor/', ASSUMED_URL)[1];
        $vendor_file_path = '../vendor/'.$vendor_file_path;
        if (file_exists($vendor_file_path)) {
            if (strpos($vendor_file_path, '.css')) {
                $content_type = 'text/css';
            } else {
                $content_type = 'text/plain';
            }

            header('Content-type: '.$content_type);
            $contents = file_get_contents($vendor_file_path);
            echo $contents;
            exit;
        }
        exit('Vendor file not found.');
    }

    private function serve_module_asset(): void
    {
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

                $asset_path = '../modules/'.strtolower($target_module).'/assets/'.$target_dir.'/'.$file_name;

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
                    if (is_numeric(strpos($content_type, 'php')) || ($file_name === 'api.json')) {
                        http_response_code(422);
                        exit;
                    }

                    header('Content-type: '.$content_type);
                    $contents = file_get_contents($asset_path);
                    echo $contents;
                    exit;
                }
                $this->serve_child_module_asset($asset_path, $file_name);
            }
        }
    }

    private function serve_child_module_asset($asset_path, $file_name): void
    {
        $start = '/modules/';
        $end = '/assets/';

        $pos = stripos($asset_path, $start);
        $str = substr($asset_path, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        $target_str = trim($str_three);

        $bits = explode('-', $target_str);

        if (count($bits) === 2) {
            if (strlen($bits[1]) > 0) {
                $parent_module = $bits[0];
                $child_module = $bits[1];

                $asset_path = str_replace($target_str, $parent_module.'/'.$child_module, $asset_path);
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

                    header('Content-type: '.$content_type);
                    $contents = file_get_contents($asset_path);
                    echo $contents;
                    exit;
                }
            }
        }
    }

    private function attempt_sql_transfer($controller_path): void
    {
        $ditch = 'controllers/'.$this->current_controller.'.php';
        $dir_path = str_replace($ditch, '', $controller_path);

        $files = [];
        foreach (glob($dir_path.'*.sql') as $file) {
            $file = str_replace($controller_path, '', $file);
            $files[] = $file;
        }

        if (count($files) > 0) {
            require_once 'tg_transferer/index.php';
            exit;
        }
    }

    private function serve_controller(): void
    {
        $segments = SEGMENTS;

        if (isset($segments[1])) {
            $module_with_no_params = $segments[1];
            $module_with_no_params = explode('?', $segments[1])[0];
            $this->current_module = strtolower($module_with_no_params);
            $this->current_controller = ucfirst($this->current_module);

            if (defined('TRONGATE_PAGES_TRIGGER')) {
                if ($segments[1] === TRONGATE_PAGES_TRIGGER) {
                    $this->current_module = 'trongate_pages';
                    $this->current_controller = 'Trongate_pages';
                }
            }
        }

        if (isset($segments[2])) {
            $method_with_no_params = $segments[2];
            $method_with_no_params = explode('?', $segments[2])[0];
            $this->current_method = strtolower($method_with_no_params);
            //make sure not private
            $str = substr($this->current_method, 0, 1);
            if ($str === '_') {
                $this->draw_error_page();
            }
        }

        if (isset($segments[3])) {
            $value_with_no_params = $segments[3];
            $value_with_no_params = explode('?', $segments[3])[0];
            $this->current_value = $value_with_no_params;
        }

        $controller_path = '../modules/'.$this->current_module.'/controllers/'.$this->current_controller.'.php';

        if ($controller_path === '../modules/api/controllers/Api.php') {
            //API intercept, since segment(1) is 'api/'
            $controller_path = '../engine/Api.php';
            require_once $controller_path;
            $target_method = $this->current_method;
            $this->current_controller = new $this->current_controller($this->current_module);
            if (method_exists($this->current_controller, $this->current_method)) {
                $this->current_controller->$target_method($this->current_value);

                return;
            }
            $this->draw_error_page();
        }

        if (! file_exists($controller_path)) {
            $controller_path = $this->attempt_init_child_controller($controller_path);
        }

        require_once $controller_path;

        if (strtolower(ENV) === 'dev') {
            $this->attempt_sql_transfer($controller_path);
        }

        if (method_exists($this->current_controller, $this->current_method)) {
            $target_method = $this->current_method;
            $this->current_controller = new $this->current_controller($this->current_module);
            $this->current_controller->$target_method($this->current_value);
        } else {
            //method does not exist - attempt invoke standard endpoint
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
    }

    private function attempt_init_child_controller($controller_path)
    {
        $bits = explode('-', $this->current_controller);

        if (count($bits) === 2) {
            if (strlen($bits[1]) > 0) {
                $parent_module = strtolower($bits[0]);
                $child_module = strtolower($bits[1]);
                $this->current_controller = ucfirst($bits[1]);
                $controller_path = '../modules/'.$parent_module.'/'.$child_module.'/controllers/'.ucfirst($bits[1]).'.php';

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
            $controller_path = '../modules/'.$this->current_module.'/controllers/'.$this->current_controller.'.php';
            if (file_exists($controller_path)) {
                return $controller_path;
            }
        }

        $this->draw_error_page();
    }

    private function draw_error_page(): void
    {
        load('error_404');
        exit; //end of the line (all possible scenarios tried)
    }
}
