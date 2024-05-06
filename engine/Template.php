<?php

/**
 * Template Class
 *
 * This class provides methods for managing views and partials within a PHP application.
 */
class Template {


    /**
     * Get View Module
     *
     * Attempts to extract the view module from the URL.
     *
     * @return string The extracted view module.
     */
    static public function get_view_module() {
        //attempt to get view_module from URL

        $url = str_replace(BASE_URL, '', current_url());
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url_bits = explode('/', $url);

        if (isset($url_bits[0])) {
            $view_module = $url_bits[0];
        } else {
            $view_module = DEFAULT_MODULE;
        }

        return $view_module;
    }


    /**
     * Display
     *
     * Displays the specified view file.
     *
     * @param array|null $data Optional data to pass to the view file.
     */
    static public function display($data = null) {

        if (!isset($data['view_module'])) {
            $data['view_module'] = self::get_view_module();
        }

        if (!isset($data['view_file'])) {
            $data['view_file'] = 'index';
        }

        $file_path = APPPATH . 'modules/' . $data['view_module'] . '/views/' . $data['view_file'] . '.php';
        self::attempt_include($file_path, $data);
    }


    /**
     * Partial
     *
     * Loads a partial view file.
     *
     * @param string $file_name The name of the partial view file.
     * @param array|null $data Optional data to pass to the partial view file.
     */
    static public function partial($file_name, $data = null) {
        $file_path = APPPATH . 'templates/views/' . $file_name . '.php';
        self::attempt_include($file_path, $data);
    }


    /**
     * Attempt Include
     *
     * Attempts to include a file and extract data if provided.
     *
     * @param string $file_path The path to the file to include.
     * @param array|null $data Optional data to extract and pass to the included file.
     */
    static private function attempt_include($file_path, $data = null) {

        if (file_exists($file_path)) {

            if (isset($data)) {
                extract($data);
            }

            require_once($file_path);
        } else {
            die('<br><b>ERROR:</b> View file does not exist at: ' . $file_path);
        }
    }
}
