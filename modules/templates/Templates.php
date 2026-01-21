<?php
/**
 * Template management class for rendering application templates.
 * Handles theme displays with support for additional CSS/JS includes.
 * Provides secure template rendering with URL invocation blocking and error handling.
 */
class Templates extends Trongate {

    /**
     * Class constructor.
     *
     * Prevents direct URL access to the templates module while allowing
     * internal template rendering via application code.
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url($this->module_name);
    }

    /**
     * Display admin theme template with provided data.
     *
     * @param array $data The data to pass to the template view
     * @return void
     */
    public function admin(array $data): void {
        $data['theme'] = (isset($data['theme'])) ? $data['theme'] : 'default';
        $data['additional_includes_top'] = $this->build_additional_includes($data['additional_includes_top'] ?? []);
        $data['additional_includes_btm'] = $this->build_additional_includes($data['additional_includes_btm'] ?? []);
        $this->display('admin', $data);
    }

    /**
     * Display public theme template with provided data.
     * Loads the public template with optional theme variation support.
     *
     * @param array $data The data to pass to the template view
     * @return void
     */
    public function public(array $data): void {
        $data['additional_includes_top'] = $this->build_additional_includes($data['additional_includes_top'] ?? []);
        $data['additional_includes_btm'] = $this->build_additional_includes($data['additional_includes_btm'] ?? []);
        $this->display('public', $data);
    }

    /**
     * Display 404 error page.
     *
     * @return void
     */
    public function error_404(): void {
        $this->display('error_404');
    }
    
    /**
     * Display a template file from this module.
     *
     * @param string $template_name The name of the template file (without .php extension)
     * @param array $data Associative array of data to extract into template scope
     * @return void
     * @throws Exception If template file is not found
     */
    private function display(string $template_name, array $data = []): void {
        $template_path = __DIR__ . "/views/{$template_name}.php";
        
        if (!file_exists($template_path)) {
            throw new Exception("Template '{$template_name}' not found at {$template_path}");
        }

        extract($data);
        require $template_path;
    }

    /**
     * Builds HTML code for additional includes based on file types.
     *
     * @param array $files Array of file names.
     * @return string HTML code for additional includes.
     */
    private function build_additional_includes(array|string|null $files): string {
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
                'js' => $this->build_js_include_code($file), // Add JS separately without a newline
                'css' => $this->build_css_include_code($file) . PHP_EOL, // Add a newline for CSS files
                default => $file . PHP_EOL, // Add a newline for other file types
            };
        }

        return trim($html) . PHP_EOL;
    }

    /**
     * Builds JavaScript include code for the given file.
     *
     * @param string $file File path for JavaScript include.
     * @return string JavaScript include code.
     */
    private function build_js_include_code(string $file): string {
        $code = '<script src="' . $file . '"></script>';
        $code = str_replace('""></script>', '"></script>', $code);
        return $code;
    }

    /**
     * Builds CSS include code for the given file.
     *
     * @param string $file File path for CSS include.
     * @return string CSS include code.
     */
    private function build_css_include_code(string $file): string {
        $code = '<link rel="stylesheet" href="' . $file . '">';
        $code = str_replace('"">', '">', $code);
        return $code;
    }

}