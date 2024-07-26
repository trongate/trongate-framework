<?php

/**
 * Manages loading and displaying of content within HTML templates.
 */
class Template {

    /**
     * Retrieves the view module from the current URL.
     *
     * @return string The name of the view module.
     */
    public static function get_view_module(): string {
        // Attempt to get view_module from URL
        $url = str_replace(BASE_URL, '', current_url());
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url_bits = explode('/', $url);

        if (isset($url_bits[0])) {
            $view_module = $url_bits[0];
            $view_module = str_replace('-', '/', $view_module);
        } else {
            $view_module = DEFAULT_MODULE;
        }

        return $view_module;
    }

    /**
     * Displays the view file for the specified module.
     *
     * @param array|null $data Data to be passed to the view file.
     * @return void
     */
    public static function display(?array $data = null): void {
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
     * Includes a partial view file from the templates directory.
     *
     * @param string $file_name The name of the partial view file to include.
     * @param array|null $data Data to be passed to the partial view file.
     * @return void
     */
    public static function partial(string $file_name, ?array $data = null): void {
        $file_path = APPPATH . 'templates/views/' . $file_name . '.php';
        self::attempt_include($file_path, $data);
    }

    /**
     * Attempts to include a view file, extracting data variables if provided.
     * If the file does not exist, it terminates the script with an error message.
     *
     * @param string $file_path The path to the view file to include.
     * @param array|null $data Data to be extracted for use in the view file.
     * @return void
     */
    private static function attempt_include(string $file_path, ?array $data = null): void {
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