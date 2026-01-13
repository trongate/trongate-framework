<?php
/**
 * Model Base Class
 *
 * Provides automatic loading and method delegation to module-specific model files.
 * Also serves as the data access layer, providing database connections for both
 * the default database and alternative database groups.
 * 
 * In Trongate v2, models can also load and use other modules via $this->module().
 */
class Model {

    // Cache for loaded model instances
    private array $loaded_models = [];

    // Cache for Db instances (default and alternative database groups)
    private array $db_instances = [];

    // Cache for loaded module instances
    private array $loaded_modules = [];

    // The module that instantiated this Model instance
    private ?string $current_module = null;

    /**
     * Constructor for Model class.
     *
     * @param string|null $module_name The name of the module calling this model.
     */
    public function __construct(?string $module_name = null) {
        $this->current_module = $module_name;
    }

    /**
     * Magic method to provide access to database connections and loaded modules.
     *
     * This method handles:
     * 1. Primary database access via $this->db (always available)
     * 2. Alternative database groups via $this->groupname (e.g., $this->analytics)
     * 3. Previously loaded modules via $this->module_name (if $this->module() was called)
     *
     * The decision logic:
     * - If 'db' → Return Db instance for default database
     * - If it's a configured database group → Return Db instance for that group
     * - If it's a loaded module → Return that module instance
     * - Otherwise → Throw helpful error
     *
     * @param string $key The property name (e.g., 'db', 'analytics', 'tax', etc.).
     * @return mixed The Db instance or module instance for the requested key.
     * @throws Exception If the property is not valid.
     */
    public function __get(string $key) {
        // Check if it's a cached Db instance (database connection)
        if (isset($this->db_instances[$key])) {
            return $this->db_instances[$key];
        }

        // Handle primary database (always accessible)
        if ($key === 'db') {
            return $this->db_instances[$key] = new Db($this->current_module);
        }

        // Handle alternative database groups
        // Check if this key corresponds to a configured database group
        if ($this->is_database_group($key)) {
            return $this->db_instances[$key] = new Db($this->current_module, $key);
        }

        // Check if it's a previously loaded module
        if (isset($this->loaded_modules[$key])) {
            return $this->loaded_modules[$key];
        }

        // Not found anywhere - throw helpful error
        $error_msg = "Undefined property: Model::\${$key}. ";
        
        if ($this->is_potential_module($key)) {
            $error_msg .= "If '{$key}' is a module, call \$this->module('{$key}') before using it. ";
        }
        
        if ($this->is_potential_database_group($key)) {
            $error_msg .= "If '{$key}' is meant to be a database group, ensure it is configured in /config/database.php";
        }
        
        throw new Exception($error_msg);
    }

    /**
     * Loads a module and makes it available as a property in model files.
     * 
     * This enables models to use other modules:
     * $this->module('email_sender');
     * $this->email_sender->send($to, $subject, $body);
     *
     * @param string $target_module The name of the target module to load.
     * @return void
     * @throws Exception If the module cannot be found.
     */
    public function module(string $target_module): void {
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
        
        // Load and instantiate the module
        require_once $controller_path;
        
        if (!class_exists($controller_class)) {
            throw new Exception("Module class not found: {$controller_class}");
        }
        
        // Create the module instance
        $module_instance = new $controller_class($target_module);
        
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
     * Magic method to handle calls to module-specific model methods.
     * Automatically loads the appropriate <Module>_model.php file and forwards the method call.
     *
     * @param string $method The name of the method being called.
     * @param array $arguments The arguments passed to the method.
     * @return mixed The result of the method call.
     * @throws Exception If the model file or method cannot be found.
     */
    public function __call(string $method, array $arguments) {
        // Get the calling module from the current_module property
        if (!isset($this->current_module)) {
            throw new Exception("Model class cannot determine the calling module. Please ensure the module name is set.");
        }

        $module_name = $this->current_module;

        // Load the model if not already loaded
        if (!isset($this->loaded_models[$module_name])) {
            $this->load_model($module_name);
        }

        // Get the model instance
        $model_instance = $this->loaded_models[$module_name];

        // Check if the method exists in the model
        if (!method_exists($model_instance, $method)) {
            $model_class = ucfirst($module_name) . '_model';
            throw new Exception("Method '{$method}' not found in {$model_class} class.");
        }

        // Call the method and return the result
        return call_user_func_array([$model_instance, $method], $arguments);
    }

    /**
     * Check if a property key corresponds to a configured database group.
     * This checks the global $databases array defined in /config/database.php
     *
     * @param string $key The property name to check.
     * @return bool True if it's a valid database group, false otherwise.
     */
    private function is_database_group(string $key): bool {
        return isset($GLOBALS['databases'][$key]);
    }

    /**
     * Check if a key might be a module (for better error messages).
     * This is a heuristic check - if a module directory exists with this name.
     *
     * @param string $key The property name to check.
     * @return bool True if it looks like a module might exist, false otherwise.
     */
    private function is_potential_module(string $key): bool {
        $module_path = '../modules/' . strtolower($key) . '/' . ucfirst($key) . '.php';
        return file_exists($module_path);
    }

    /**
     * Check if a key might be intended as a database group (for better error messages).
     *
     * @param string $key The property name to check.
     * @return bool True if it's not a module and could be a db group name, false otherwise.
     */
    private function is_potential_database_group(string $key): bool {
        // If it's lowercase and not a module, it might be intended as a db group
        return strtolower($key) === $key && !$this->is_potential_module($key);
    }

    /**
     * Loads a module-specific model file.
     *
     * @param string $module_name The name of the module whose model should be loaded.
     * @return void
     * @throws Exception If the model file or class cannot be found.
     */
    private function load_model(string $module_name): void {
        // Build the model class name and file path
        // Handle child modules (format: parent-child)
        if (strpos($module_name, '-') !== false) {
            $bits = explode('-', $module_name);
            if (count($bits) === 2) {
                $child_module = $bits[1];
                $model_class = ucfirst($child_module) . '_model';
            } else {
                $model_class = ucfirst($module_name) . '_model';
            }
        } else {
            $model_class = ucfirst($module_name) . '_model';
        }
        
        $model_path = $this->get_model_path($module_name, $model_class);

        // Require the model file
        require_once $model_path;

        // Check if the class exists
        if (!class_exists($model_class)) {
            throw new Exception("Model class '{$model_class}' not found in {$model_path}");
        }

        // Instantiate the model and cache it
        $this->loaded_models[$module_name] = new $model_class($module_name);
    }

    /**
     * Get the path to a module's model file, handling both standard and child modules.
     *
     * @param string $module_name The name of the module.
     * @param string $model_class The name of the model class.
     * @return string The path to the model file.
     * @throws Exception If the model file cannot be found.
     */
    private function get_model_path(string $module_name, string $model_class): string {
        $possible_paths = [];

        // Priority 1: Standard module path
        $possible_paths[] = '../modules/' . $module_name . '/' . $model_class . '.php';

        // Priority 2: Child module path (for parent-child module structure)
        if (strpos($module_name, '-') !== false) {
            $bits = explode('-', $module_name);

            if (count($bits) === 2 && strlen($bits[1]) > 0) {
                $parent_module = strtolower($bits[0]);
                $child_module = strtolower($bits[1]);
                $model_class_name = ucfirst($child_module) . '_model';
                $possible_paths[] = '../modules/' . $parent_module . '/' . $child_module . '/' . $model_class_name . '.php';
            }
        }

        // Check each possible path
        foreach ($possible_paths as $model_path) {
            if (file_exists($model_path)) {
                return $model_path;
            }
        }

        // Model file not found
        $attempted_paths = implode("\n- ", $possible_paths);
        throw new Exception("Model file '{$model_class}.php' not found for module '{$module_name}'. Attempted paths:\n- {$attempted_paths}");
    }

}