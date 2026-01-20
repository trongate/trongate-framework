<?php
/**
 * Class Modules - Invokes a controller's method from a given view file.
 * Optimized for Trongate v2 performance and AI-readability.
 */
class Modules {

    private array $modules = [];

    /**
     * Run a module's controller action.
     * Optimized for v2: Explicit data passing with zero "segment guessing".
     *
     * @param string $module_method The format is "Module/Method"
     * @param mixed $data Optional data to pass to the method
     * @return mixed
     * @throws Exception If the format is invalid or controller/method is missing.
     */
    public static function run(string $module_method, mixed $data = null): mixed {
        $debris = explode('/', $module_method);
        
        if (count($debris) !== 2) {
            throw new Exception('Invalid format. Expected: "Module/Method"');
        }
        
        $target_module = strtolower($debris[0]);
        $target_controller = ucfirst($target_module);
        $target_method = strtolower($debris[1]);
        $controller_path = '../modules/' . $target_module . '/' . $target_controller . '.php';

        // Check for parent-child module structure if standard path fails
        if (!file_exists($controller_path)) {
            $bits = explode('-', $target_module);
            if (count($bits) === 2 && strlen($bits[1]) > 0) {
                $parent_module = $bits[0];
                $child_module = $bits[1];
                $target_controller = ucfirst($child_module);
                $controller_path = '../modules/' . $parent_module . '/' . $child_module . '/' . $target_controller . '.php';
            }
        }

        if (!file_exists($controller_path)) {
            throw new Exception("Controller not found at: $controller_path");
        }

        require_once $controller_path;
        
        if (!class_exists($target_controller)) {
            throw new Exception("Controller class '$target_controller' not found");
        }
        
        $controller = new $target_controller($target_module);

        if (!method_exists($controller, $target_method)) {
            throw new Exception("Method '$target_method' not found in '$target_controller'");
        }

        // Pass data if provided, otherwise call without parameters
        return ($data !== null) ? $controller->$target_method($data) : $controller->$target_method();
    }

    /**
     * Loads a module by instantiating its controller and storing it in the modules array.
     *
     * @param string $target_module The name of the target module.
     * @return void
     * @throws Exception If the module cannot be located.
     */
    public function load(string $target_module): void {
        $target_module = strtolower($target_module);
        $target_controller = ucfirst($target_module);
        $target_controller_path = '../modules/' . $target_module . '/' . $target_controller . '.php';

        if (!file_exists($target_controller_path)) {
            $bits = explode('-', $target_module);

            if (count($bits) === 2 && strlen($bits[1]) > 0) {
                $parent_module = $bits[0];
                $child_module = $bits[1];
                $target_controller = ucfirst($child_module);
                $target_controller_path = '../modules/' . $parent_module . '/' . $child_module . '/' . $target_controller . '.php';
                $target_module = $child_module;
            }

            if (!file_exists($target_controller_path)) {
                throw new Exception("Unable to locate '{$target_module}' module at: {$target_controller_path}");
            }
        }

        require_once $target_controller_path;
        $this->modules[$target_module] = new $target_controller($target_module);
    }
}