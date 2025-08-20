<?php

/**
 * Trongate Base Controller Class
 * 
 * The foundation class that all application controllers extend.
 * Provides core functionality for templates, views, modules, and file uploads.
 */
class Trongate {

    private array $instances = [];
    private array $attributes = [];
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
    }

    /**
     * Magic getter for framework classes and loaded modules.
     *
     * @param string $key The property name.
     * @return object The class instance.
     * @throws Exception If the property is not supported.
     */
    public function __get(string $key): object {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if (in_array($key, ['model', 'validation', 'file', 'image', 'template'])) {
            return $this->instances[$key] ??= match($key) {
                'model' => new Model($this->module_name),
                'validation' => new Validation(),
                'file' => new File(),
                'image' => new Image(),
                'template' => new Template(),
            };
        }

        $class_name = ucfirst($key);
        $module_path = '../modules/' . $key . '/controllers/' . $class_name . '.php';
        
        if (file_exists($module_path)) {
            require_once $module_path;
            $this->attributes[$key] = new $class_name($key);
            return $this->attributes[$key];
        }

        throw new Exception("Undefined property: " . get_class($this) . "::$key");
    }

    /**
     * Magic setter for dynamic properties.
     *
     * @param string $key The property key.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $key, $value): void {
        $this->attributes[$key] = $value;
    }

    /**
     * Renders a specific template view by calling a corresponding method in the Templates controller class.
     *
     * @param string $template_name The name of the template method to be called.
     * @param array $data An associative array containing data to be passed to the template method.
     * @return void
     * @throws Exception If template controller or method is not found.
     */
    protected function template(string $template_name, array $data = []): void {
        $template_controller_path = '../templates/controllers/Templates.php';
        
        if (!file_exists($template_controller_path)) {
            $template_controller_path = str_replace('../', APPPATH, $template_controller_path);
            throw new Exception('ERROR: Unable to find Templates controller at ' . $template_controller_path . '.');
        }
        
        require_once $template_controller_path;
        $templates = new Templates;

        if (!method_exists($templates, $template_name)) {
            $template_controller_path = str_replace('../', APPPATH, $template_controller_path);
            throw new Exception('ERROR: Unable to find ' . $template_name . ' method in ' . $template_controller_path . '.');
        }

        if (!isset($data['view_file'])) {
            $data['view_file'] = DEFAULT_METHOD;
        }

        $templates->$template_name($data);
    }

    /**
     * Loads a module using the Modules class.
     *
     * @param string $target_module The name of the target module.
     * @return void
     */
    protected function module(string $target_module): void {
        $access_key = Module_path::get_access_key($target_module);
        
        if (isset($this->attributes[$access_key])) {
            return;
        }

        $modules = new Modules;
        $this->attributes[$access_key] = $modules->load_and_return($target_module);
    }

    /**
     * Upload a picture file using the upload method from the Image class.
     *
     * @param array $config The configuration data for handling the upload.
     * @return array|null The information of the uploaded file.
     */
    protected function upload_picture(array $config): ?array {
        return $this->image->upload($config);
    }

    /**
     * Upload a file using the upload method from the File class.
     *
     * @param array $config The configuration data for handling the upload.
     * @return array|null The information of the uploaded file.
     */
    protected function upload_file(array $config): ?array {
        return $this->file->upload($config);
    }

    /**
     * Renders a view file with optional data.
     *
     * @param string $view The name of the view file to render.
     * @param array $data Optional. An associative array of data to pass to the view. Default is an empty array.
     * @param bool|null $return_as_str Optional. Whether to return the rendered view as a string. Default is null.
     * @return string|null If $return_as_str is true, the rendered view as a string; otherwise, null.
     * @throws Exception If the view file is not found.
     */
    protected function view(string $view, array $data = [], ?bool $return_as_str = null): ?string {
        $return_as_str = $return_as_str ?? false;

        if (isset($data['view_module'])) {
            $module_name = $data['view_module'];
        } else {
            $module_name = strtolower(get_class($this));
        }

        $view_path = $this->get_view_path($view, $module_name);
        extract($data);

        if ($return_as_str) {
            ob_start();
            require $view_path;
            return ob_get_clean();
        } else {
            require $view_path;
            return null;
        }
    }

    /**
     * Get the path of a view file with optimized fallback logic.
     *
     * @param string $view The name of the view file.
     * @param string|null $module_name Module name to which the view belongs.
     * @return string The path of the view file.
     * @throws Exception If the view file does not exist.
     */
    private function get_view_path(string $view, ?string $module_name): string {
        $possible_paths = [];

        if ($this->parent_module !== '' && $this->child_module !== '') {
            $possible_paths[] = APPPATH . "modules/{$this->parent_module}/{$this->child_module}/views/{$view}.php";
        }

        $possible_paths[] = APPPATH . "modules/{$module_name}/views/{$view}.php";

        $segment_one = segment(1);
        if (strpos($segment_one, '-') !== false && substr_count($segment_one, '-') === 1) {
            $module_name_from_segment = str_replace('-', '/', $segment_one);
            $possible_paths[] = APPPATH . "modules/{$module_name_from_segment}/views/{$view}.php";
        }

        foreach ($possible_paths as $view_path) {
            if (file_exists($view_path)) {
                return $view_path;
            }
        }

        $attempted_paths = implode("\n- ", $possible_paths);
        throw new Exception("View '{$view}' not found. Attempted paths:\n- {$attempted_paths}");
    }

}