<?php
/**
 * Class Modules - Handles serving controller content from a given view file.
 */
class Modules {

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
        $controller_path = '../modules/'.$target_module.'/controllers/'.$target_controller.'.php';

        if (file_exists($controller_path)) {
            require_once($controller_path);
        } else {

            //attempt to find child module 
            $bits = explode('-', $target_module);

            if (count($bits)==2) {
                if (strlen($bits[1])>0) {
                    $parent_module = $bits[0];
                    $target_module = $bits[1];
                    $target_controller = ucfirst($target_module);
                    $controller_path = '../modules/'.$parent_module.'/'.$target_module.'/controllers/'.$target_controller.'.php';
                }
            }

        }

        require_once $controller_path;
        $controller = new $target_controller($target_module);
        return $controller->$target_method($first_value, $second_value, $third_value);
    }

}