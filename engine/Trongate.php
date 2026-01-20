<?php
/**
 * Trongate Base Controller Class
 * 
 * The foundation class that all application controllers extend.
 * Provides core functionality for views, modules, and file uploads.
 */
class Trongate {

    // Instance cache for lazy loading
    private array $instances = [];
    
    // Loaded modules cache
    private array $loaded_modules = [];
    
    // Core properties
    protected ?string $module_name = '';
    protected string $parent_module = '';
    protected string $child_module = '';

    /**
     * Constructor for Trongate class.
     * 
     * Initializes the module name by using the provided module name parameter
     * or automatically detecting it from the child class name if not provided.
     *
     * @param string|null $module_name The name of the module to use, 
     *                                or null to auto-detect from class name.
     */
    public function __construct(?string $module_name = null) {
        $this->module_name = $module_name ?? strtolower(get_class($this));
    }

    /**
     * Magic getter for framework classes and loaded modules.
     *
     * @param string $key The property name.
     * @return object The class instance.
     * @throws Exception If the property is not supported.
     */
    public function __get(string $key): object {
        // Check if it's a loaded module first
        if (isset($this->loaded_modules[$key])) {
            return $this->loaded_modules[$key];
        }

        // Handle core framework classes with lazy loading
        $core_instance = match($key) {
            'model' => new Model($this->module_name),
            default => null
        };

        if ($core_instance !== null) {
            return $this->instances[$key] ??= $core_instance;
        }

        // If not a core class, try to load it as a module
        $this->module($key);
        return $this->loaded_modules[$key];
    }

    /**
     * Loads a module and makes it available as a property.
     * Handles both Trongate-extending modules and standalone utility classes.
     *
     * @param string $target_module The name of the target module.
     * @return void
     * @throws Exception If the module cannot be found or loaded.
     */
    protected function module(string $target_module): void {

        // Don't reload if already loaded
        if (isset($this->loaded_modules[$target_module])) {
            return;
        }

        // Build the controller path and class name
        $controller_class = ucfirst($target_module);
        $controller_path = '../modules/' . $target_module . '/' . $controller_class . '.php';
        $is_child_module = false;

        // If standard path doesn't exist, try child module
        if (!file_exists($controller_path)) {
            $child_module_info = $this->try_child_module_path($target_module);
            $controller_path = $child_module_info['path'];
            $controller_class = $child_module_info['class'];
            $is_child_module = true;
        }
        
        // Load the module file
        require_once $controller_path;
        
        if (!class_exists($controller_class)) {
            throw new Exception("Module class not found: {$controller_class}");
        }
        
        // Determine how to instantiate based on class inheritance
        if (is_subclass_of($controller_class, 'Trongate') || $controller_class === 'Trongate') {
            // Trongate-extending module - pass module name to constructor
            $module_instance = new $controller_class($target_module);
        } else {
            // Standalone utility class (e.g., Image, Calculator) - no framework dependencies
            $module_instance = new $controller_class();
        }
        
        // Store the module instance using the original target_module as key
        $this->loaded_modules[$target_module] = $module_instance;
        
        // For child modules, also store under the child module name for easy access
        if ($is_child_module) {
            $bits = explode('-', $target_module);
            if (count($bits) === 2) {
                $child_module_name = strtolower($bits[1]);
                $this->loaded_modules[$child_module_name] = $module_instance;
            }
        }
    }

    /**
     * Try to find a child module controller.
     *
     * @param string $target_module The target module name.
     * @return array An array containing 'path' and 'class' keys.
     * @throws Exception If the controller cannot be found.
     */
    private function try_child_module_path(string $target_module): array {
        $bits = explode('-', $target_module);

        if (count($bits) === 2 && strlen($bits[1]) > 0) {
            $parent_module = strtolower($bits[0]);
            $child_module = strtolower($bits[1]);
            $controller_class = ucfirst($child_module);

            $controller_path = '../modules/' . $parent_module . '/' . $child_module . '/' . $controller_class . '.php';

            if (file_exists($controller_path)) {
                return [
                    'path' => $controller_path,
                    'class' => $controller_class
                ];
            }
        }

        throw new Exception("Module controller not found: {$target_module}");
    }

    /**
     * Renders a view file with optional data.
     *
     * This method can either display the view on the browser or return the generated contents as a string.
     *
     * @param string $view The name of the view file to render.
     * @param array $data Optional. An associative array of data to pass to the view.
     * @param bool|null $return_as_str Optional. Whether to return the rendered view as a string.
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
     * Get the path of a view file with optimized fallback logic.
     *
     * @param string $view The name of the view file.
     * @param string|null $module_name Module name to which the view belongs.
     * @return string The path of the view file.
     * @throws Exception If the view file does not exist.
     */
    private function get_view_path(string $view, ?string $module_name): string {
        $possible_paths = [];

        // Priority 1: Child module path (if parent/child modules are set)
        if ($this->parent_module !== '' && $this->child_module !== '') {
            $possible_paths[] = APPPATH . "modules/{$this->parent_module}/{$this->child_module}/views/{$view}.php";
        }

        // Priority 2: Standard module path
        $possible_paths[] = APPPATH . "modules/{$module_name}/views/{$view}.php";

        // Priority 3: Derive module name from URL segment (for parent-child modules)
        $segment_one = segment(1);
        if (strpos($segment_one, '-') !== false && substr_count($segment_one, '-') === 1) {
            $module_name_from_segment = str_replace('-', '/', $segment_one);
            $possible_paths[] = APPPATH . "modules/{$module_name_from_segment}/views/{$view}.php";
        }

        // Check each path in order of priority
        foreach ($possible_paths as $view_path) {
            if (file_exists($view_path)) {
                return $view_path;
            }
        }

        // No view found - throw exception with helpful error message
        $attempted_paths = implode("\n- ", $possible_paths);
        throw new Exception("View '{$view}' not found. Attempted paths:\n- {$attempted_paths}");
    }

    /**
     * Reads a manifest file from a specified path.
     *
     * @param string $path The path to the directory containing the manifest file.
     * @return array|false The manifest data as an associative array, or false if not found.
     */
    protected function read_manifest(string $path): array|false {
        $manifest_file = APPPATH . $path . '/manifest.php';
        
        if (!file_exists($manifest_file)) {
            return false;
        }
        
        return include($manifest_file);
    }

}