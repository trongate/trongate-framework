<?php
/**
 * File_validation Class (Child Module)
 *
 * This class provides file validation services for the Trongate framework and is 
 * specifically designed to be invoked by the parent Validation module. It handles
 * various file validation criteria such as file type, size, dimensions, and security scanning.
 * 
 * The class is not intended for direct use by framework users. It should only be accessed
 * through the parent Validation module, which automatically loads and uses this child module
 * when file validation is required.
 *
 * Location: modules/validation/file_validation/File_validation.php
 *
 * Usage:
 * This class is automatically invoked by the Validation module when $_FILES data is detected.
 * Developers should never need to load or interact with this module directly.
 */

class File_validation extends Trongate {

    /**
     * Class constructor.
     *
     * Prevents direct URL access to the file validation module while allowing
     * internal validation operations via the parent Validation module.
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url($this->module_name);
    }

    /**
     * Validates an uploaded file based on provided rules.
     * 
     * This is the main entry point for file validation. It performs security checks
     * and then validates the file against all specified rules.
     *
     * @param string $key The key in $_FILES array (form field name).
     * @param string $label The human-readable label for the file field.
     * @param string $rules The validation rules separated by '|' (e.g., 'allowed_types[jpg,png]|max_size[2000]').
     * @param array $file The file array from $_FILES.
     * @return array An array of error messages (empty if validation passes).
     */
    public function validate(string $key, string $label, string $rules, array $file): array {
        $errors = [];

        // First, run security validation
        try {
            $this->validate_file_content($file, $key);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            return $errors; // Stop validation if security threat detected
        }

        // Parse the rules string into individual validation checks
        $validation_rules = $this->parse_rules($rules);

        // Run each validation check
        foreach ($validation_rules as $rule_name => $rule_value) {
            $error = $this->run_validation_check($rule_name, $rule_value, $file, $label);
            if ($error !== '') {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Parses the validation rules string into an associative array.
     * 
     * Converts a string like 'allowed_types[jpg,png]|max_size[2000]|max_width[1200]'
     * into ['allowed_types' => 'jpg,png', 'max_size' => '2000', 'max_width' => '1200']
     *
     * @param string $rules The validation rules string.
     * @return array An associative array of rule names and their parameters.
     */
    private function parse_rules(string $rules): array {
        $parsed_rules = [];
        $rules_array = explode('|', $rules);

        foreach ($rules_array as $rule) {
            if (strpos($rule, '[') !== false) {
                // Rule has parameters in brackets
                $rule_name = substr($rule, 0, strpos($rule, '['));
                $rule_value = $this->extract_content($rule, '[', ']');
                $parsed_rules[$rule_name] = $rule_value;
            } else {
                // Rule without parameters (e.g., 'square')
                $parsed_rules[$rule] = '';
            }
        }

        return $parsed_rules;
    }

    /**
     * Runs a specific validation check based on the rule name.
     *
     * @param string $rule_name The name of the validation rule (e.g., 'allowed_types', 'max_size').
     * @param string $rule_value The parameter value for the rule.
     * @param array $file The file array from $_FILES.
     * @param string $label The human-readable label for the file field.
     * @return string An error message if validation fails, empty string if passes.
     */
    private function run_validation_check(string $rule_name, string $rule_value, array $file, string $label): string {
        switch ($rule_name) {
            case 'allowed_types':
                return $this->check_allowed_types($file['name'], $rule_value, $label);
            
            case 'max_size':
                return $this->check_file_size($file['size'], $rule_value, $label);
            
            case 'max_width':
            case 'max_height':
            case 'min_width':
            case 'min_height':
            case 'square':
                return $this->check_image_dimensions($file['tmp_name'], $rule_name, $rule_value, $label);
            
            default:
                // Unknown rule - ignore it
                return '';
        }
    }

    /**
     * Validates the file type against allowed extensions.
     *
     * @param string $filename The name of the uploaded file.
     * @param string $allowed_types Comma-separated list of allowed extensions (e.g., 'jpg,png,gif').
     * @param string $label The human-readable label for the file field.
     * @return string Error message if validation fails, empty string if passes.
     */
    private function check_allowed_types(string $filename, string $allowed_types, string $label): string {
        $allowed_extensions = explode(',', strtolower($allowed_types));
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            return 'The ' . $label . ' must be one of the following types: ' . implode(', ', $allowed_extensions) . '.';
        }

        return '';
    }

    /**
     * Validates the file size against the maximum allowed size.
     *
     * @param int $file_size The size of the file in bytes.
     * @param string $max_size_kb The maximum allowed size in kilobytes.
     * @param string $label The human-readable label for the file field.
     * @return string Error message if validation fails, empty string if passes.
     */
    private function check_file_size(int $file_size, string $max_size_kb, string $label): string {
        $file_size_kb = $file_size / 1024; // Convert bytes to KB
        $max_size = (float) $max_size_kb;

        if ($file_size_kb > $max_size) {
            return 'The ' . $label . ' exceeds the maximum allowed size of ' . $max_size . ' KB.';
        }

        return '';
    }

    /**
     * Validates image dimensions according to the specified rule.
     *
     * @param string $tmp_file_path The temporary file path of the uploaded file.
     * @param string $rule_name The dimension rule name (max_width, max_height, min_width, min_height, square).
     * @param string $rule_value The dimension value in pixels.
     * @param string $label The human-readable label for the file field.
     * @return string Error message if validation fails, empty string if passes.
     */
    private function check_image_dimensions(string $tmp_file_path, string $rule_name, string $rule_value, string $label): string {
        // First, verify it's an image file
        $dimensions = @getimagesize($tmp_file_path);
        
        if ($dimensions === false) {
            return 'The ' . $label . ' must be a valid image file.';
        }

        $width = $dimensions[0];
        $height = $dimensions[1];
        $value = (int) $rule_value;

        switch ($rule_name) {
            case 'max_width':
                if ($width > $value) {
                    return 'The ' . $label . ' width cannot exceed ' . $value . ' pixels.';
                }
                break;

            case 'max_height':
                if ($height > $value) {
                    return 'The ' . $label . ' height cannot exceed ' . $value . ' pixels.';
                }
                break;

            case 'min_width':
                if ($width < $value) {
                    return 'The ' . $label . ' width must be at least ' . $value . ' pixels.';
                }
                break;

            case 'min_height':
                if ($height < $value) {
                    return 'The ' . $label . ' height must be at least ' . $value . ' pixels.';
                }
                break;

            case 'square':
                if ($width !== $height) {
                    return 'The ' . $label . ' must be square (width must equal height).';
                }
                break;
        }

        return '';
    }

    /**
     * Validates the content of an uploaded file to detect potential security threats.
     *
     * This method reads the first 256 bytes of the file and scans for dangerous patterns,
     * such as PHP code, CDATA sections, or dangerous functions like `eval` and `exec`.
     *
     * @param array $file The file array from $_FILES.
     * @param string $field_name The form field name (for error reporting).
     * @return void
     * @throws RuntimeException If a security threat is detected or file cannot be opened.
     */
    private function validate_file_content(array $file, string $field_name): void {
        if (($file_handle = @fopen($file['tmp_name'], 'rb')) === false) {
            throw new RuntimeException('Unable to open file for security scanning.');
        }

        $content = fread($file_handle, 256); // Read first 256 bytes
        fclose($file_handle);

        // Define dangerous patterns that indicate potential security threats
        $dangerous_patterns = [
            '/<\?php/i',           // PHP opening tag
            '/\<\!\[CDATA\[/i',    // CDATA section
            '/\<\!DOCTYPE/i',      // DOCTYPE declaration
            '/\<\!ENTITY/i',       // XML entity declaration
            '/\beval\s*\(/i',      // eval() function
            '/\bexec\s*\(/i',      // exec() function
            '/\bshell_exec\s*\(/i' // shell_exec() function
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new RuntimeException('Potential security threat detected in file.');
            }
        }
    }

    /**
     * Extracts content between specified start and end delimiters.
     *
     * @param string $string The string to search within.
     * @param string $start_delim The starting delimiter.
     * @param string $end_delim The ending delimiter.
     * @return string The content between delimiters, or empty string if not found.
     */
    private function extract_content(string $string, string $start_delim, string $end_delim): string {
        if (($start_pos = strpos($string, $start_delim)) !== false) {
            $start_pos += strlen($start_delim);
            if (($end_pos = strpos($string, $end_delim, $start_pos)) !== false) {
                return substr($string, $start_pos, $end_pos - $start_pos);
            }
        }
        return '';
    }

}