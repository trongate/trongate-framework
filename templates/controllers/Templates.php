<?php
class Templates extends Trongate {

    /**
     * Loads the 'public' view with provided data.
     *
     * @param mixed $data Data array to be passed to the view.
     * @return void
     */
    function public($data): void {
        load('public', $data);
    }

    /**
     * Loads the 'error_404' view with provided data.
     *
     * @param mixed $data Data array to be passed to the view.
     * @return void
     */
    function error_404($data): void {
        load('error_404', $data);
    }

    /**
     * Loads the 'admin' view with provided data and additional includes.
     *
     * @param array $data Data array to be passed to the view.
     * @return void
     */
    function admin(array $data): void {
        $data['additional_includes_top'] = $this->_build_additional_includes($data['additional_includes_top'] ?? []);
        $data['additional_includes_btm'] = $this->_build_additional_includes($data['additional_includes_btm'] ?? []);
        load('admin', $data);
    }

    /**
     * Builds CSS include code for the given file.
     *
     * @param string $file File path for CSS include.
     * @return string CSS include code.
     */
    function _build_css_include_code(string $file): string {
        $code = '<link rel="stylesheet" href="' . $file . '">';
        $code = str_replace('""></script>', '"></script>', $code);
        return $code;
    }

    /**
     * Builds JavaScript include code for the given file.
     *
     * @param string $file File path for JavaScript include.
     * @return string JavaScript include code.
     */
    function _build_js_include_code(string $file): string {
        $code = '<script src="' . $file . '"></script>';
        $code = str_replace('""></script>', '"></script>', $code);
        return $code;
    }

    /**
     * Builds HTML code for additional includes based on file types.
     *
     * @param array $files Array of file names.
     * @return string HTML code for additional includes.
     */
    function _build_additional_includes(array|string|null $files): string {
        if (!is_array($files)) {
            return ''; // Return an empty string if $files is not an array
        }

        $html = '';
        $tabs_str = '    '; // Assuming 4 spaces per tab

        foreach ($files as $index => $file) {
            $file_bits = explode('.', $file);
            $filename_extension = end($file_bits);

            if ($index > 0) {
                $html .= $tabs_str; // Add tabs for lines beyond the first
            }

            $html .= match ($filename_extension) {
                'js' => $this->_build_js_include_code($file), // Add JS separately without a newline
                'css' => $this->_build_css_include_code($file) . PHP_EOL, // Add a newline for CSS files
                default => $file . PHP_EOL, // Add a newline for other file types
            };
        }

        return trim($html) . PHP_EOL;
    }
}
