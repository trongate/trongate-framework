<?php

/**
 * Class Modules - Handles serving controller content from a given view file.
 */
class Modules {

    private $modules = [];

    /**
     * Run a module's controller action.
     *
     * @param string $moduleControllerAction The format is "Module/Controller/Action".
     * @param mixed $first_value (Optional) First parameter for the action.
     * @param mixed $second_value (Optional) Second parameter for the action.
     * @param mixed $third_value (Optional) Third parameter for the action.
     * 
     * @return mixed The result of the controller action.
     */
    public static function run(string $moduleControllerAction, $first_value = null, $second_value = null, $third_value = null) {
        $debris = explode('/', $moduleControllerAction);
        $target_module = $debris[0];
        $target_controller = ucfirst($target_module);
        $target_method = $debris[1];
        $controller_path = '../modules/' . $target_module . '/controllers/' . $target_controller . '.php';

        if (file_exists($controller_path)) {
            require_once($controller_path);
        } else {

            //attempt to find child module 
            $bits = explode('-', $target_module);

            if (count($bits) == 2) {
                if (strlen($bits[1]) > 0) {
                    $parent_module = $bits[0];
                    $target_module = $bits[1];
                    $target_controller = ucfirst($target_module);
                    $controller_path = '../modules/' . $parent_module . '/' . $target_module . '/controllers/' . $target_controller . '.php';
                }
            }
        }

        require_once $controller_path;
        $controller = new $target_controller($target_module);
        return $controller->$target_method($first_value, $second_value, $third_value);
    }

    /**
     * Loads a module by instantiating its controller.
     *
     * @param string $target_module The name of the target module.
     * @return void
     */
    public function load(string $target_module): void {
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
        $this->modules[$target_module] = new $target_module($target_module);
    }

    /**
     * Lists all existing modules.
     *
     * @param bool $recursive Determines whether the listing should be recursive. Default is false.
     * @return array Returns an array containing the list of existing modules.
     */
    public function list(bool $recursive = false): array {
        $target_path = APPPATH . 'modules';
        $file = new File;
        $existing_modules = $file->list_directory($target_path, $recursive);
        return $existing_modules;
    }

    /**
     * Retrieves the child module from the target module name.
     *
     * @param string $target_module The target module name.
     * @return ?string The child module name, or null if not found.
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
}
