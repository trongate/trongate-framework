<?php
class Trongate {

    use Dynamic_properties;

    protected $modules;
    protected $model;
    protected $url;
    protected $module_name;
    protected $parent_module = '';
    protected $child_module = '';

    public function __construct($module_name=NULL) {
        $this->module_name = $module_name;
        $this->modules = new Modules;

        //load the model class
        require_once 'Model.php';
        $this->model = new Model($module_name);
    }

    public function load($helper) {
        require_once 'tg_helpers/'.$helper.'.php';
        $this->$helper = new $helper;
    }

    public function template($template_name, $data=NULL) {
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
            die('ERROR: Unable to find '.$template_name.' method in '.$template_controller_path.'.');
        }
    }

    public function module($target_module) {
        $target_controller = ucfirst($target_module);
        $target_controller_path = '../modules/'.$target_module.'/controllers/'.$target_controller.'.php';

        if (!file_exists($target_controller_path)) {
            $child_module = $this->get_child_module($target_module);
            $target_controller_path = '../modules/'.$target_module.'/'.$child_module.'/controllers/'.ucfirst($child_module).'.php';
            $ditch = '-'.$child_module.'/'.$child_module.'/controllers';
            $replace = '/'.$child_module.'/controllers';
            $target_controller_path = str_replace($ditch, $replace, $target_controller_path);
            $target_module = $child_module;
        }

        require_once $target_controller_path;
        $this->$target_module = new $target_module($target_module);
    }

    private function get_child_module($target_module) {
        $child_module_path = false;
        $bits = explode('-', $target_module);

        if (count($bits)==2) {
            if (strlen($bits[1])>0) {
                $child_module = $bits[1];
            }
        }

        if (!isset($child_module)) {
            http_response_code(404);
            echo 'ERROR: Unable to locate '.$target_module.' module!';
            die();
        }

        return $child_module;
    }

    protected function view($view, $data = [], $return_as_str=NULL) {

        if ((isset($return_as_str)) || (gettype($data) == 'boolean')) {
            $return_as_str = true;
        } else {
            $return_as_str = false;
        }

        if (($this->parent_module !== '') && ($this->child_module !== '')) {
            //load view from child module
            if ($return_as_str == true) {
                // Return output as string
                ob_start();
                $this->load_child_view($view, $data);
                $output = ob_get_clean();
                return $output;
            } else {
                // Require child file
                $this->load_child_view($view, $data);
            }

        } else {
            //normal view loading process
            if (isset($data['view_module'])) {
                $module_name = $data['view_module'];
            } else {
                $module_name = $this->module_name;
            }

            extract($data);

            $view_path = APPPATH.'modules/'.$module_name.'/views/'.$view.'.php';

            // Check for view file
            if(file_exists($view_path)){
                
                if ($return_as_str == true) {
                    // Return output as string
                    ob_start();
                    require $view_path;
                    $output = ob_get_clean();
                    return $output;
                } else {
                    // Require view file
                    require $view_path;
                }
                
            } else {
                // No view exists
                $view = str_replace('/', '/views/', $view);
                $view_path = APPPATH.'modules/'.$view.'.php';
            
                if(file_exists($view_path)){

                    if ($return_as_str == true) {
                        // Return output as string
                        ob_start();
                        require $view_path;
                        $output = ob_get_clean();
                        return $output;
                    } else {
                        // Require view file
                        require $view_path;
                    }

                } else {
                    throw new exception('view '.$view_path.' does not exist');
                }
            }
        }
    }

    private function load_child_view($view, $data) {
        extract($data);
        $view_path = APPPATH.'modules/'.$this->parent_module.'/'.$this->child_module.'/views/'.$view.'.php';

        // Check for view file
        if(file_exists($view_path)){
            // Require view file
            require_once $view_path;
        } else {
            // No view exists
            throw new exception('view '.$view_path.' does not exist');
        }
    }

    public function upload_picture($data) {
        $uploaded_file_info = $this->img_helper->upload($data);
        return $uploaded_file_info;
    }

    public function upload_file($data) {
        $uploaded_file_info = $this->file_helper->upload($data);
        return $uploaded_file_info;
    }

}