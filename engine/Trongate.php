<?php
class Trongate {

    use Dynamic_properties;

    protected Modules $modules;
    private ?Model $model;
    protected ?string $module_name;
    protected string $parent_module = '';
    protected string $child_module = '';

    public function __construct(?string $module_name = null) {
        $this->module_name = $module_name;
        $this->modules = new Modules;
    }

    /**
     * Load a helper class dynamically and instantiate it.
     *
     * @param string $helper The name of the helper class to load.
     * 
     * @return void
     * 
     * @throws Exception If the helper class file cannot be found or the class cannot be instantiated.
     */
    public function load(string $helper): void {
        require_once 'tg_helpers/' . $helper . '.php';
        $this->$helper = new $helper;
    }

    /**
     * Loads a template controller file, instantiates the corresponding object, and calls
     * the specified template method with the given data.
     *
     * @param string $template_name The name of the template method to call.
     * @param array $data The data to pass to the template method.
     *
     * @return void
     *
     * @throws Exception If the template controller file cannot be found or the template method does not exist.
     */
    public function template(string $template_name, array $data = []): void {
        $template_controller_path = '../templates/controllers/Templates.php';
        require_once $template_controller_path;

        $templates = new Templates;

        if (method_exists($templates, $template_name)) {

            if (!isset($data['view_file'])) {
                $data['view_file'] = DEFAULT_METHOD;
            }

            $templates->$template_name($data);
        } else {
            $template_controller_path = str_replace('../', APPPATH, $template_controller_path);
            die('ERROR: Unable to find ' . $template_name . ' method in ' . $template_controller_path . '.');
        }
    }

    /**
     * Load a module's controller dynamically and instantiate it.
     *
     * @param class-string $target_module The name of the target module to load.
     *
     * @return void
     *
     * @throws ReflectionException If the target controller file cannot be found or the controller class cannot be instantiated.
     */
    public function module(string $target_module): void {
        $target_controller = ucfirst($target_module);
        $target_controller_path = '../modules/' . $target_module . '/controllers/' . $target_controller . '.php';

        if (!file_exists($target_controller_path)) {
            $child_module = $this->get_child_module($target_module);
            $target_controller_path = '../modules/' . $target_module . '/' . $child_module . '/controllers/' . ucfirst($child_module) . '.php';
            $ditch = '-' . $child_module . '/' . $child_module . '/controllers';
            $replace = '/' . $child_module . '/controllers';
            $target_controller_path = str_replace($ditch, $replace, $target_controller_path);
            $target_module = $child_module;
        }

        require_once $target_controller_path;
        $this->$target_module = new $target_module($target_module);
    }

    /**
     * Get the child module name from the target module name.
     *
     * @param string $target_module The name of the target module.
     *
     * @return string|null The name of the child module, or null if not found.
     *
     * @throws ReflectionException If the target module is not found.
     */
    private function get_child_module(string $target_module): ?string {
        $child_module_path = false;
        $bits = explode('-', $target_module);

        if (count($bits) == 2) {
            if (strlen($bits[1]) > 0) {
                $child_module = $bits[1];
            }
        }

        if (!isset($child_module)) {
            http_response_code(404);
            echo 'ERROR: Unable to locate ' . $target_module . ' module!';
            die();
        }

        return $child_module;
    }

    /**
     * Render a view.
     *
     * @param string $view           The name of the view to render.
     * @param array  $data           The data to pass to the view.
     * @param bool   $return_as_str  Whether to return the view as a string or output it.
     *
     * @return string|null The rendered view as a string, or null if not rendered.
     *
     * @throws ReflectionException If a reflection error occurs while loading the view.
     */
    protected function view(string $view, array $data = [], bool $return_as_str = false): ?string {
        if ($this->parent_module !== '' && $this->child_module !== '') {
            // Load view from child module
            $output = $this->load_view_file($view, $data, $return_as_str);
            if ($return_as_str) {
                return $output;
            }
        } else {
            // Normal view loading process
            $module_name = $data['view_module'] ?? $this->module_name;
            extract($data);
            $view_path = APPPATH . 'modules/' . $module_name . '/views/' . $view . '.php';
            if (!file_exists($view_path)) {
                $view = str_replace('/', '/views/', $view);
                $view_path = APPPATH . 'modules/' . $view . '.php';
            }
            $output = $this->load_view_file($view_path, $data, $return_as_str);
            if ($return_as_str) {
                return $output;
            }
        }
        return null;
    }

    /**
     * Load a view file and optionally return it as a string.
     *
     * @param string $view_path      The path to the view file.
     * @param array  $data           The data to pass to the view.
     * @param bool   $return_as_str  Whether to return the view as a string or output it.
     *
     * @return string|null  The rendered view as a string, or null if not rendered
     *                      and $return_as_str is true, or false if not rendered
     *                      and $return_as_str is false.
     *
     * @throws Exception If the view file does not exist.
     */
    protected function load_view_file(string $view_path, array $data, bool $return_as_str): ?string {
        if (!file_exists($view_path)) {
            throw new Exception('View ' . $view_path . ' does not exist');
        }
        extract($data);
        if ($return_as_str) {
            ob_start();
            require $view_path;
            return ob_get_clean() ?: null;
        } else {
            require $view_path;
            return false;
        }
    }

    /**
     * Load a child view file.
     *
     * @param string $view  The name of the view file.
     * @param array  $data  The data to pass to the view.
     *
     * @return void
     *
     * @throws Exception If the view file does not exist.
     */
    private function load_child_view(string $view, array $data): void {
        extract($data);
        $view_path = APPPATH . 'modules/' . $this->parent_module . '/' . $this->child_module . '/views/' . $view . '.php';

        // Check for view file
        if (file_exists($view_path)) {
            // Require view file
            require_once $view_path;
        } else {
            // No view exists
            throw new Exception('view ' . $view_path . ' does not exist');
        }
    }

    /**
     * Upload a picture file.
     *
     * @param array $data The data for the uploaded file.
     *
     * @return array|null The information of the uploaded file.
     */
    public function upload_picture(array $data): ?array {
        $uploaded_file_info = $this->img_helper->upload($data);
        return $uploaded_file_info;
    }

    /**
     * Upload a file.
     *
     * @param array $data The data for the uploaded file.
     *
     * @return array|null The information of the uploaded file.
     */
    public function upload_file(array $data): ?array {
        $uploaded_file_info = $this->file_helper->upload($data);
        return $uploaded_file_info;
    }
}
