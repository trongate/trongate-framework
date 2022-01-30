<?php
class Trongate_filezone extends Trongate {

    function _draw_summary_panel($update_id, $filezone_settings) {
        $this->module('trongate_security');
        $data['token'] = $this->trongate_security->_make_sure_allowed();
        $this->_make_sure_got_sub_folder($update_id, $filezone_settings);
        $data['update_id'] = $update_id;
        $data['target_module'] = $filezone_settings['targetModule'];
        $data['uploader_url'] = 'trongate_filezone/uploader/'.$data['target_module'].'/'.$update_id;
        $data['pictures'] = $this->_fetch_pictures($update_id, $filezone_settings);
        $data['target_directory'] = BASE_URL.$data['target_module'].'_pictures/'.$update_id.'/';

        if (!isset($filezone_settings['upload_to_module'])) {
            $filezone_settings['upload_to_module'] = false;
        }

        if ($filezone_settings['upload_to_module'] == true) {
            $module_assets_dir = BASE_URL.segment(1).MODULE_ASSETS_TRIGGER;
            $data['target_directory'] = $module_assets_dir.'/'.$filezone_settings['destination'].'/'.$update_id;
        } else {
            $data['target_directory'] = BASE_URL.$data['target_module'].'_pictures/'.$update_id.'/';
        }

        $this->view('multi_summary_panel', $data);
    }

    function _make_sure_got_sub_folder($update_id, $filezone_settings, $target_module=null) {
        $destination = $filezone_settings['destination'];

        if (!isset($target_module)) {
            $target_module = segment(1);
        }

        if (!isset($filezone_settings['upload_to_module'])) {
            $filezone_settings['upload_to_module'] = false;
        }

        if ($filezone_settings['upload_to_module'] == true) {
            $target_dir = APPPATH.'modules/'.$target_module.'/assets/'.$destination.'/'.$update_id;
        } else {
            $target_dir = APPPATH.'public/'.$destination.'/'.$update_id;
        }

        $target_dir = str_replace('\\', '/', $target_dir);

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
    }

    function _fetch_pictures($update_id, $filezone_settings) {
        $data = [];
        $pictures_directory = $this->_get_pictures_directory($filezone_settings);

        if ($filezone_settings['upload_to_module'] == true) {
            $target_module = (isset($filezone_settings['targetModule']) ? $filezone_settings['targetModule'] : segment(1));
            $module_assets_dir = APPPATH.'modules/'.$target_module.'/assets';
            $picture_directory_path = $module_assets_dir.'/'.$pictures_directory.'/'.$update_id;
        } else {
            $picture_directory_path = APPPATH.'public/'.$pictures_directory.'/'.$update_id;
        }

        $picture_directory_path = str_replace('\\', '/', $picture_directory_path);

        if (is_dir($picture_directory_path)) {
            $pictures = scandir($picture_directory_path);
            foreach ($pictures as $key => $value) {
                if (($value !== '.') && ($value !== '..') && ($value !== '.DS_Store')) {
                    $data[] = $value;
                }
            }

        }

        return $data;
    }

    function _get_pictures_directory($filezone_settings) {
        $target_module = $filezone_settings['targetModule'];
        $directory = $target_module . '_pictures';
        return $directory;
    }

    function _remove_flashdata() {
        if (isset($_SESSION['flashdata'])) {
            unset($_SESSION['flashdata']);
        }
    }

    function _get_previously_uploaded_files($code) {
        $data = [];
        $pictures_directory = BASE_URL.'module_resources/'.$code.'/picture_gallery';
        $picture_directory_path = str_replace(BASE_URL, './', $pictures_directory);

        if (is_dir($picture_directory_path)) {
            $pictures = scandir($picture_directory_path);
            foreach ($pictures as $key => $value) {
                if (($value !== '.') && ($value !== '..') && ($value !== '.DS_Store')) {
                    $data[] = $value;
                }
            }
        }
        return $data;
    }

    function _make_sure_got_dir($target_dir) {
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
    }

    function uploader() {
        $this->module('trongate_security');
        $data['token'] = $this->trongate_security->_make_sure_allowed();
        $target_module = segment(3);
        $update_id = segment(4);

        //get all of the uploaded files
        $this->module($target_module);
        $settings = $this->$target_module->_init_filezone_settings();

        if (!isset($settings['upload_to_module'])) {
            $settings['upload_to_module'] = false;
        }

        $destination = $settings['destination']; // e.g., a2s_pictures

        if ($settings['upload_to_module'] == true) {
            $target_module = (isset($settings['targetModule']) ? $settings['targetModule'] : segment(1));
            $picture_directory_path = APPPATH.'modules/'.$target_module.'/assets/'.$destination.'/'.$update_id;
            $dir = str_replace('\\', '/', $picture_directory_path);
            $module_assets_dir = BASE_URL.$target_module.MODULE_ASSETS_TRIGGER;
            $target_dir = $module_assets_dir.'/'.$destination.'/'.$update_id;
        } else {
            $dir = $destination.'/'.$update_id;
            $target_dir = $dir;
        }

        $previously_uploaded_files = [];
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $first_char = substr($file, 0, 1);

                    if ($first_char !== '.') {
                        $row_data['directory'] = $target_dir;
                        $row_data['filename'] = $file;
                        $row_data['overlay_id'] = $this->_get_overlay_id($file);
                        $previously_uploaded_files[] = $row_data;
                    }
                }
                closedir($dh);
            }
        }

        $additional_includes_top[] = BASE_URL.'trongate_filezone_module/css/trongate-filezone.css';
        $data['additional_includes_top'] = $additional_includes_top;
        $additional_includes_btm[] = BASE_URL.'trongate_filezone_module/js/trongate-filezone.js';
        $data['additional_includes_btm'] = $additional_includes_btm;
        $data['target_module'] = $settings['targetModule'];
        $target_module_desc = str_replace("_", " ", $data['target_module']);
        $data['target_module_desc'] = ucwords($target_module_desc);
        $data['previous_url'] = BASE_URL . $target_module . '/show/' . $update_id;
        $data['update_id'] = $update_id;
        $data['headline'] = 'Upload Pictures';
        $data['upload_url'] = BASE_URL.'trongate_filezone/upload/'.$target_module.'/'.$update_id;
        $data['delete_url'] = BASE_URL.'trongate_filezone/ditch';
        $data['previously_uploaded_files'] = $previously_uploaded_files;
        $data['view_file'] = 'uploader';
        $this->template('admin', $data);
    }

    function _get_overlay_id($filename) {
        $bits = explode('.', $filename);
        $last_bit = $bits[count($bits)-1];
        $ditch = '.'.$last_bit;
        $replace = '-'.$last_bit;
        $overlay_id = str_replace($ditch, $replace, $filename);
        return $overlay_id;
    }

    function alt() {
        $data['view_file'] = 'alt';
        $this->template('public_default', $data);
    }

    function _get_str_chuck($str, $target_length, $from_start=null) {
        $strlen = strlen($str);
        $start_pos = $strlen-$target_length;

        if (isset($from_start)) {
            $start_pos = 0;
        }

        $str_chunk = substr($str, $start_pos, $target_length);
        return $str_chunk;
    }

    function ditch() {
        api_auth();
        $post = file_get_contents('php://input');
        $posted_data = json_decode($post, true);

        $element_id = $posted_data['elId'];
        $update_id = $posted_data['update_id'];
        $target_module = $posted_data['target_module'];

        $this->module($target_module);
        $settings = $this->$target_module->_init_filezone_settings();
        $destination = $settings['destination'];

        $bits = explode('-', $element_id);
        $last_bit = '-'.$bits[count($bits)-1];
        $last_bit_len = strlen($last_bit);
        $target_len = strlen($element_id) - $last_bit_len;
        $first_chunk = $this->_get_str_chuck($element_id, $target_len, true);
        $correct_last_bit = str_replace('-', '.', $last_bit);
        $target_image_name = $first_chunk.$correct_last_bit;

        if ($settings['upload_to_module'] == true) { 
            $target_dir = APPPATH.'modules/'.$target_module.'/assets/'.$destination.'/'.$update_id.'/';
            $target_dir = str_replace('\\', '/', $target_dir);
        } else {
            $target_dir = $destination.'/'.$update_id.'/';
        }

        $target_file = $target_dir.$target_image_name;

        if (file_exists($target_file)) {
            unlink($target_file);
            http_response_code(200);
            echo $element_id;            
        }

    }

    function upload() {
        api_auth();

        $request_type = $_SERVER['REQUEST_METHOD'];
        $target_module = segment(3);
        $update_id = segment(4);

        if ($request_type == 'DELETE') {
            $this->_remove_picture($target_module, $update_id);
        } else {
            $this->_do_upload($update_id, $target_module);
        }

    }

    function _remove_picture($target_module, $update_id) {
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);
        $picture_path = file_get_contents("php://input");
        $picture_path = str_replace(BASE_URL, '', $picture_path);

        $this->module($target_module);
        $filezone_settings = $this->$target_module->_init_filezone_settings();

        if ($filezone_settings['upload_to_module'] == true) {
            $target_module = (isset($filezone_settings['targetModule']) ? $filezone_settings['targetModule'] : segment(1));
            $ditch = $target_module.MODULE_ASSETS_TRIGGER.'/';
            $replace = '';
            $picture_path = str_replace($ditch, $replace, $picture_path);
            $picture_path = APPPATH.'modules/'.$target_module.'/assets/'.$picture_path;
        } else {
            $picture_path = APPPATH.'public/'.$picture_path;
        }

        $picture_path = str_replace('\\', '/', $picture_path);

        if (file_exists($picture_path)) {
            //delete the picture
            unlink($picture_path);
            $this->_fetch();
        } else {
            echo 'file does not exist at '.$picture_path; die();
            http_response_code(422);
            echo $picture_path;
        }
    }

    function _fetch() {
        $target_module = segment(3);
        $update_id = segment(4);

        if (($target_module == '') || (!is_numeric($update_id))) {
            http_response_code(422);
            echo 'Invalid target module and/or update_id.';
            die();
        }

        //get the settings
        $this->module($target_module);
        $filezone_settings = $this->$target_module->_init_filezone_settings();
        $pictures = $this->_fetch_pictures($update_id, $filezone_settings);
        http_response_code(200);
        echo json_encode($pictures);
    }

    function _prep_file_name($file_name) {
        $bits = explode('.', $file_name);
        $last_bit = '.'.$bits[count($bits)-1];

        //remove last_bit from the file_name
        $file_name = str_replace($last_bit, '', $file_name);
        $safe_file_name = $file_name;
        $safe_file_name = url_title($file_name);

        //get the first 8 chars
        $safe_file_name = substr($safe_file_name, 0, 8);
        $safe_file_name.= make_rand_str(4);
        $safe_file_name.= $last_bit;
        return $safe_file_name;
    }

    function _make_sure_image($value) {
        $target_str = 'image/';
        $first_six = substr($value['type'], 0, 6);

        if ($first_six !== $target_str) {
            http_response_code(403);
            echo 'Not an image!';
            die();
        }

    }

    function _do_upload($update_id, $target_module) {

        foreach ($_FILES as $key => $value) {
            $this->_make_sure_image($value);
            $file_name = $value['name'];
            $new_file_name = $this->_prep_file_name($file_name);
            $_FILES[$key]['name'] = $new_file_name;
        }

        //get picture settings
        $this->module($target_module);
        $filezone_settings = $this->$target_module->_init_filezone_settings();
        $this->_make_sure_got_sub_folder($update_id, $filezone_settings, $target_module);

        $config['targetModule']     = $target_module;
        $config['maxFileSize']      = $filezone_settings['max_file_size'];
        $config['maxWidth']         = 1400;
        $config['maxHeight']        = 1400;
        $config['resizedMaxWidth']  = $filezone_settings['max_width'];
        $config['resizedMaxHeight'] = $filezone_settings['max_height'];
        $config['destination']      = $filezone_settings['destination'] . '/' . $update_id;
        $config['upload_to_module'] = (!isset($filezone_settings['upload_to_module']) ? false : $filezone_settings['upload_to_module']);

        $this->upload_picture($config);

        if (isset($_FILES['file1'])) {
            $picture_name = $_FILES['file1']['name'];
            $picture_name_ref = str_replace('.', '-', $picture_name);
            echo $picture_name_ref;
        }

        http_response_code(200);

    }

}