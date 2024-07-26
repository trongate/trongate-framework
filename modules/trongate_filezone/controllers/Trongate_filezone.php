<?php

/**
 * Trongate Filezone is a multi-file uploader, giving users the ability to drag and drop files.
 * Trongate Filezone comes with built-in authentication via usage of Trongate's token system.
 */
class Trongate_filezone extends Trongate {

    /**
     * Renders a page that displays the uploader view.
     *
     * @return void
     */
    public function uploader(): void {
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
            $picture_directory_path = APPPATH . 'modules/' . $target_module . '/assets/' . $destination . '/' . $update_id;
            $dir = str_replace('\\', '/', $picture_directory_path);
            $module_assets_dir = BASE_URL . $target_module . MODULE_ASSETS_TRIGGER;
            $target_dir = $module_assets_dir . '/' . $destination . '/' . $update_id;
        } else {
            $dir = $destination . '/' . $update_id;
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
                        $row_data['overlay_id'] = $this->get_overlay_id($file);
                        $previously_uploaded_files[] = $row_data;
                    }
                }
                closedir($dh);
            }
        }

        $additional_includes_top[] = BASE_URL . 'trongate_filezone_module/css/trongate-filezone.css';
        $data['additional_includes_top'] = $additional_includes_top;
        $additional_includes_btm[] = BASE_URL . 'trongate_filezone_module/js/trongate-filezone.js';
        $data['additional_includes_btm'] = $additional_includes_btm;
        $data['target_module'] = $settings['targetModule'];
        $target_module_desc = str_replace("_", " ", $data['target_module']);
        $data['target_module_desc'] = ucwords($target_module_desc);
        $data['previous_url'] = BASE_URL . $target_module . '/show/' . $update_id;
        $data['update_id'] = $update_id;
        $data['headline'] = 'Upload Pictures';
        $data['upload_url'] = BASE_URL . 'trongate_filezone/upload/' . $target_module . '/' . $update_id;
        $data['delete_url'] = BASE_URL . 'trongate_filezone/ditch';
        $data['previously_uploaded_files'] = $previously_uploaded_files;
        $data['view_file'] = 'uploader';
        $this->template('admin', $data);
    }

    /**
     * Handles the upload functionality including picture removal.
     * This authorizes the API request then manages either picture deletion or uploading based on the request type.
     *
     * @return void
     */
    public function upload(): void {
        api_auth();

        $request_type = $_SERVER['REQUEST_METHOD'];
        $target_module = segment(3);
        $update_id = segment(4);

        if ($request_type == 'DELETE') {
            $this->remove_picture($target_module, $update_id);
        } else {
            $this->do_upload($update_id, $target_module);
        }
    }

    /**
     * Handles the deletion of a specific image file based on the posted data.
     * Authorizes the API request, retrieves the necessary data, and removes the designated image file.
     *
     * @return void
     */
    public function ditch(): void {
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
        $last_bit = '-' . $bits[count($bits) - 1];
        $last_bit_len = strlen($last_bit);
        $target_len = strlen($element_id) - $last_bit_len;
        $first_chunk = $this->get_str_chunk($element_id, $target_len, true);
        $correct_last_bit = str_replace('-', '.', $last_bit);
        $target_image_name = $first_chunk . $correct_last_bit;

        if ($settings['upload_to_module'] == true) {
            $target_dir = APPPATH . 'modules/' . $target_module . '/assets/' . $destination . '/' . $update_id . '/';
            $target_dir = str_replace('\\', '/', $target_dir);
        } else {
            $target_dir = $destination . '/' . $update_id . '/';
        }

        $target_file = $target_dir . $target_image_name;

        if (file_exists($target_file)) {
            unlink($target_file);
            http_response_code(200);
            echo $element_id;
        }
    }

    /**
     * Renders the summary panel for a given update ID and Filezone settings.
     *
     * @param int $update_id The ID of the update.
     * @param array $filezone_settings Settings related to the Filezone.
     * @return void
     */
    public function _draw_summary_panel(int $update_id, array $filezone_settings): void {
        $this->module('trongate_security');
        $data['token'] = $this->trongate_security->_make_sure_allowed();
        $this->make_sure_got_sub_folder($update_id, $filezone_settings);
        $data['update_id'] = $update_id;
        $data['target_module'] = $filezone_settings['targetModule'];
        $data['uploader_url'] = 'trongate_filezone/uploader/' . $data['target_module'] . '/' . $update_id;
        $data['pictures'] = $this->fetch_pictures($update_id, $filezone_settings);
        $data['target_directory'] = BASE_URL . $data['target_module'] . '_pictures/' . $update_id . '/';

        if (!isset($filezone_settings['upload_to_module'])) {
            $filezone_settings['upload_to_module'] = false;
        }

        if ($filezone_settings['upload_to_module'] == true) {
            $module_assets_dir = BASE_URL . segment(1) . MODULE_ASSETS_TRIGGER;
            $data['target_directory'] = $module_assets_dir . '/' . $filezone_settings['destination'] . '/' . $update_id;
        } else {
            $data['target_directory'] = BASE_URL . $data['target_module'] . '_pictures/' . $update_id . '/';
        }

        $this->view('multi_summary_panel', $data);
    }

    /**
     * Ensures the existence of a subfolder for a given update ID and Filezone settings.
     *
     * @param int $update_id The ID of the update.
     * @param array $filezone_settings Settings related to the Filezone.
     * @param string|null $target_module (Optional) The target module. Defaults to null.
     * @return void
     */
    private function make_sure_got_sub_folder(int $update_id, array $filezone_settings, ?string $target_module = null): void {
        $destination = $filezone_settings['destination'];

        if (!isset($target_module)) {
            $target_module = segment(1);
        }

        if (!isset($filezone_settings['upload_to_module'])) {
            $filezone_settings['upload_to_module'] = false;
        }

        if ($filezone_settings['upload_to_module'] == true) {
            $target_dir = APPPATH . 'modules/' . $target_module . '/assets/' . $destination . '/' . $update_id;
        } else {
            $target_dir = APPPATH . 'public/' . $destination . '/' . $update_id;
        }

        $target_dir = str_replace('\\', '/', $target_dir);

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
    }

    /**
     * Fetches pictures related to a specific update ID and Filezone settings.
     *
     * @param int $update_id The ID of the update.
     * @param array $filezone_settings Settings related to the Filezone.
     * @return array Array containing the fetched pictures.
     */
    private function fetch_pictures(int $update_id, array $filezone_settings): array {
        $data = [];
        $pictures_directory = $this->get_pictures_directory($filezone_settings);

        if ($filezone_settings['upload_to_module'] == true) {
            $target_module = (isset($filezone_settings['targetModule']) ? $filezone_settings['targetModule'] : segment(1));
            $module_assets_dir = APPPATH . 'modules/' . $target_module . '/assets';
            $picture_directory_path = $module_assets_dir . '/' . $pictures_directory . '/' . $update_id;
        } else {
            $picture_directory_path = APPPATH . 'public/' . $pictures_directory . '/' . $update_id;
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

    /**
     * Retrieves the directory for pictures based on Filezone settings.
     *
     * @param array $filezone_settings Settings related to the Filezone.
     * @return string The directory for pictures.
     */
    private function get_pictures_directory(array $filezone_settings): string {
        $target_module = $filezone_settings['targetModule'];
        $directory = $target_module . '_pictures';
        return $directory;
    }

    /**
     * Retrieves the overlay ID that corresponds with the provided filename.
     *
     * @param string $filename The input filename.
     * @return string The extracted overlay ID.
     */
    private function get_overlay_id(string $filename): string {
        $bits = explode('.', $filename);
        $last_bit = $bits[count($bits) - 1];
        $ditch = '.' . $last_bit;
        $replace = '-' . $last_bit;
        $overlay_id = str_replace($ditch, $replace, $filename);
        return $overlay_id;
    }

    /**
     * Retrieves a specified portion of a string.
     *
     * @param string $str The input string.
     * @param int $target_length The length of the desired string portion.
     * @param bool|null $from_start Determines if the portion is retrieved from the start or the end of the string.
     * @return string The extracted string portion.
     */
    private function get_str_chunk(string $str, int $target_length, ?bool $from_start = null): string {
        $strlen = strlen($str);
        $start_pos = $strlen - $target_length;

        if (isset($from_start)) {
            $start_pos = 0;
        }

        $str_chunk = substr($str, $start_pos, $target_length);
        return $str_chunk;
    }

    /**
     * Removes a picture based on the provided target module and update ID.
     * Handles the deletion of the specified picture path.
     *
     * @param string $target_module The target module for the picture.
     * @param int $update_id The ID related to the update of the picture.
     * @return void
     */
    private function remove_picture(string $target_module, int $update_id): void {
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);
        $picture_path = file_get_contents("php://input");
        $picture_path = str_replace(BASE_URL, '', $picture_path);

        $this->module($target_module);
        $filezone_settings = $this->$target_module->_init_filezone_settings();

        if ($filezone_settings['upload_to_module'] == true) {
            $target_module = (isset($filezone_settings['targetModule']) ? $filezone_settings['targetModule'] : segment(1));
            $ditch = $target_module . MODULE_ASSETS_TRIGGER . '/';
            $replace = '';
            $picture_path = str_replace($ditch, $replace, $picture_path);
            $picture_path = APPPATH . 'modules/' . $target_module . '/assets/' . $picture_path;
        } else {
            $picture_path = APPPATH . 'public/' . $picture_path;
        }

        $picture_path = str_replace('\\', '/', $picture_path);

        if (file_exists($picture_path)) {
            //delete the picture
            unlink($picture_path);
            $this->fetch();
        } else {
            echo 'file does not exist at ' . $picture_path;
            die();
            http_response_code(422);
            echo $picture_path;
        }
    }

    /**
     * Fetches pictures based on the provided update ID and Filezone settings.
     * Outputs the fetched pictures as a JSON response.
     *
     * @return void
     */
    private function fetch(): void {
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
        $pictures = $this->fetch_pictures($update_id, $filezone_settings);
        http_response_code(200);
        echo json_encode($pictures);
    }

    /**
     * Prepares a safe file name by altering the provided file name.
     *
     * @param string $file_name The original file name to be processed.
     * @return string The safe file name after alterations.
     */
    private function prep_file_name(string $file_name): string {
        $bits = explode('.', $file_name);
        $last_bit = '.' . $bits[count($bits) - 1];

        //remove last_bit from the file_name
        $file_name = str_replace($last_bit, '', $file_name);
        $safe_file_name = $file_name;
        $safe_file_name = url_title($file_name);

        //get the first 8 chars
        $safe_file_name = substr($safe_file_name, 0, 8);
        $safe_file_name .= make_rand_str(4);
        $safe_file_name .= $last_bit;
        return $safe_file_name;
    }

    /**
     * Ensures that the provided argument is an image.
     *
     * @param array $value The value to be checked if it represents an image.
     * @return void
     */
    private function make_sure_image(array $value): void {
        $target_str = 'image/';
        $first_six = substr($value['type'], 0, 6);

        if ($first_six !== $target_str) {
            http_response_code(403);
            echo 'Not an image!';
            die();
        }
    }

    /**
     * Handles the file upload process and sets configurations.
     *
     * @param int    $update_id      The ID used to identify the update.
     * @param string $target_module  The module to target for file upload.
     * @return void
     */
    private function do_upload(int $update_id, string $target_module): void {

        foreach ($_FILES as $key => $value) {
            $this->make_sure_image($value);
            $file_name = $value['name'];
            $new_file_name = $this->prep_file_name($file_name);
            $_FILES[$key]['name'] = $new_file_name;
        }

        //get picture settings
        $this->module($target_module);
        $filezone_settings = $this->$target_module->_init_filezone_settings();
        $this->make_sure_got_sub_folder($update_id, $filezone_settings, $target_module);

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