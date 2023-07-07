<?php
class Trongate {

    use Dynamic_properties;

    protected Modules $modules;
    private ?Model $model;
    protected ?string $module_name = '';
    protected string $parent_module = '';
    protected string $child_module = '';

    /**
     * Constructor for Trongate class.
     * 
     * @param string|null $module_name The name of the module to use, or null for default module.
     */
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
     * 
     * @see https://trongate.io/docs/information/what-are-templates
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
     * @param  string  $target_module The name of the target module.
     *
     * @return string|null The name of the child module, or null if not found.
     */
    private function get_child_module(string $target_module): ?string {
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
     * Renders a view and returns the output as a string, or to the browser.
     *
     * @param  string     $view The name of the view file to render.
     * @param  array      $data An array of data to pass to the view file.
     * @param  bool|null  $return_as_str If set to true, the output is returned as a string, otherwise to the browser.
     *
     * @return string|null If $return_as_str is true, returns the output as a string, otherwise returns null.
     * @throws \Exception
     * 
     * @see https://trongate.io/docs/information/understanding-view-files
     */
    protected function view(string $view, array $data = [], ?bool $return_as_str = null): ?string {
        $return_as_str = $return_as_str ?? false;
        $module_name = $data['view_module'] ?? $this->module_name;

        $view_path = $this->_get_view_path($view, $module_name);
        extract($data);

        if ($return_as_str) {
            // Output as string
            ob_start();
            require $view_path;
            return ob_get_clean();
        } else {
            // Output view file
            require $view_path;
            return null;
        }
    }

    /**
     * Get the path of a view file.
     *
     * @param string $view The name of the view file.
     * @param string $module_name Module name to which the view belongs.
     *
     * @return string The path of the view file.
     * @throws \Exception If the view file does not exist.
     */
    function _get_view_path(string $view, ?string $module_name): string {

        if ($this->parent_module !== '' && $this->child_module !== '') {
            // Load view from child module
            $view_path = APPPATH . "modules/$this->parent_module/$this->child_module/views/$view.php";
        } else {
            // Normal view loading process
            $view_path = APPPATH . "modules/$module_name/views/$view.php";
        }

        if (file_exists($view_path)) {
            return $view_path;
        } else {
            $error_message = $this->parent_module !== '' && $this->child_module !== '' ?
                "View '$view_path' does not exist for child view" :
                "View '$view_path' does not exist";
            throw new Exception($error_message);
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
        return $this->img_helper->upload($data);
    }

    /**
     * Upload a file.
     *
     * @param array $data The data for the uploaded file.
     *
     * @return array|null The information of the uploaded file.
     */
    public function upload_file(array $data): ?array {
        return $this->file_helper->upload($data);
    }
}
