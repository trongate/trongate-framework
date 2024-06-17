<?php

/**
 * Manages loading modules and rendering of HTML templates.
 * Also contains methods for assisting with file uploads.
 */
class Trongate {

    use Dynamic_properties;

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
    }

    /**
     * Renders a specific template view by calling a corresponding method in the Templates controller class.
     *
     * @param string $template_name The name of the template method to be called.
     * @param array $data An associative array containing data to be passed to the template method.
     * @return void
     */
    protected function template(string $template_name, array $data = []): void {
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
     * Loads a module using the Modules class.
     *
     * This method serves as an alternative way of invoking the load method from the Modules class.
     * It simply instantiates a Modules object and calls its load method with the provided target module name.
     *
     * @param string $target_module The name of the target module.
     * @return void
     */
    protected function module(string $target_module): void {
        $modules = new Modules;
        $modules->load($target_module);
    }

    /**
     * Upload a picture file using the upload method from the Image class.
     *
     * This method serves as an alternative way of invoking the upload method from the Image class.
     * It simply instantiates an Image object and calls its upload method with the provided configuration data.
     *
     * @param array $config The configuration data for handling the upload.
     * @return array|null The information of the uploaded file.
     */
    protected function upload_picture(array $config): ?array {
        $image = new Image;
        return $image->upload($config);
    }

    /**
     * Upload a file using the upload method from the File class.
     *
     * This method serves as an alternative way of invoking the upload method from the File class.
     * It simply instantiates a File object and calls its upload method with the provided configuration data.
     *
     * @param array $config The configuration data for handling the upload.
     * @return array|null The information of the uploaded file.
     */
    protected function upload_file(array $config): ?array {
        $file = new File;
        return $file->upload($config);
    }

    /**
     * Renders a view file with optional data.
     *
     * This method can either display the view on the browser or return the generated contents as a string.
     *
     * @param string $view The name of the view file to render.
     * @param array $data Optional. An associative array of data to pass to the view. Default is an empty array.
     * @param bool|null $return_as_str Optional. Whether to return the rendered view as a string. Default is null.
     *                                If set to true, the view content will be returned as a string; if set to false or null,
     *                                the view will be displayed on the browser. Default is null, which means the view will be displayed.
     * @return string|null If $return_as_str is true, the rendered view as a string; otherwise, null.
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
     * @param string|null $module_name Module name to which the view belongs.
     *
     * @return string The path of the view file.
     * @throws \Exception If the view file does not exist.
     */
    private function get_view_path(string $view, ?string $module_name): string {
        try {
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
        } catch (Exception $e) {
            // Attempt to derive module name from URL segment
            $segment_one = segment(1);
            if (strpos($segment_one, '-') !== false && substr_count($segment_one, '-') == 1) {
                $module_name_from_segment = str_replace('-', '/', $segment_one);
                $view_path_from_segment = APPPATH . "modules/$module_name_from_segment/views/$view.php";
                if (file_exists($view_path_from_segment)) {
                    return $view_path_from_segment;
                } else {
                    throw new Exception("View '$view_path_from_segment' does not exist (derived from segment)");
                }
            } else {
                throw $e; // Re-throw the original exception if unable to find view using segment
            }
        }
    }

}