<?php
class Modules {

    public static function run($moduleControllerAction, $first_value=NULL, $second_value=NULL, $third_value=NULL) {
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