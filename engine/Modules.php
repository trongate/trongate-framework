<?php
/**
 * Class Modules - Handles loading and running modules
 */
class Modules {

    private $modules = [];

    /**
     * Statically run a controller action.
     * Format: "Module/Controller/Action"
     *
     * @param string $module_controller_action
     * @param mixed  $first_value
     * @param mixed  $second_value
     * @param mixed  $third_value
     * @return mixed
     */
    public static function run($module_controller_action, $first_value = null, $second_value = null, $third_value = null) {
        $debris = explode('/', $module_controller_action);
        $target_module = $debris[0];
        $target_controller = ucfirst($target_module);
        $target_method = $debris[1];

        // Try normal module path first
        $controller_path = '../modules/' . $target_module . '/controllers/' . $target_controller . '.php';

        if (!is_file($controller_path)) {
            // Try parent-child path
            $parts = self::split_parent_child($target_module);
            if ($parts) {
                $controller_path = self::child_controller_path($parts['parent'], $parts['child']);
                $target_controller = ucfirst($parts['child']);
            }
        }

        require_once $controller_path;
        $controller = new $target_controller($target_module);
        return $controller->$target_method($first_value, $second_value, $third_value);
    }

    /**
     * Load a module and keep its instance.
     *
     * @param string $target_module
     * @return void
     */
    public function load($target_module) {
        $target_controller = ucfirst($target_module);
        $target_controller_path = '../modules/' . $target_module . '/controllers/' . $target_controller . '.php';

        if (!is_file($target_controller_path)) {
            // Check for parent-child
            $parts = self::split_parent_child($target_module);
            if ($parts) {
                $child = $parts['child'];
                $target_controller_path = self::child_controller_path($parts['parent'], $child);
                $target_module = $child;
            }
        }

        require_once $target_controller_path;
        $this->modules[$target_module] = new $target_controller($target_module);
    }

    /**
     * Loads a module and returns the instance directly.
     *
     * @param string $target_module The name of the target module.
     * @return object The loaded module instance.
     * @throws Exception If the module cannot be loaded.
     */
    public function load_and_return(string $target_module): object {
        $path_info = Module_path::resolve($target_module);
        
        if ($path_info['type'] === 'not_found') {
            throw new Exception("Module controller not found: {$target_module}");
        }
        
        $access_key = $path_info['access_key'];
        
        // Return existing instance if already loaded
        if (isset($this->modules[$access_key])) {
            return $this->modules[$access_key];
        }
        
        require_once $path_info['controller_path'];
        
        if (!class_exists($path_info['controller_class'])) {
            throw new Exception("Module class not found: {$path_info['controller_class']}");
        }
        
        $instance = new $path_info['controller_class']($target_module);
        $this->modules[$access_key] = $instance;
        
        return $instance;
    }

    /**
     * List all existing modules.
     *
     * @param bool $recursive
     * @return array
     */
    public function list($recursive = false) {
        $file = new File;
        return $file->list_directory(APPPATH . 'modules', $recursive);
    }

    /**
     * Split a parent-child module token into components.
     * 
     * @param string $module_token The module identifier (e.g., 'dealfinder-page_watcher').
     * @return array|false Array with 'parent' and 'child' keys, or false if not a child module format.
     */
    private static function split_parent_child($module_token) {
        if (strpos($module_token, '-') === false) {
            return false;
        }
        
        $parts = explode('-', $module_token);
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            return false;
        }
        
        return [
            'parent' => strtolower($parts[0]),
            'child' => strtolower($parts[1])
        ];
    }

    /**
     * Get the controller path for a child module.
     * 
     * @param string $parent The parent module name.
     * @param string $child The child module name.
     * @return string The path to the child controller file.
     */
    private static function child_controller_path($parent, $child) {
        $child_controller = ucfirst($child);
        return '../modules/' . $parent . '/' . $child . '/controllers/' . $child_controller . '.php';
    }

}