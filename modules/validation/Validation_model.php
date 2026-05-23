<?php
/**
 * Validation Model
 * 
 * Handles the actual logic for built-in validation rules,
 * including standard post data and file/image validation.
 */
class Validation_model extends Model {

    /**
     * Array of validation error messages loaded from language files
     * 
     * @var array<string, string>
     */
    private array $validation_error_messages = [];

    /**
     * Constructor for Validation_model
     * 
     * Initializes the model and loads the appropriate validation language.
     */
    public function __construct() {
        parent::__construct();
        $lang = $this->get_validation_language();
        $this->load_validation_language($lang);
    }

    /**
     * Main execution point for a single rule.
     * Checks if the field is a file or standard post data.
     * 
     * @param string $rule The validation rule to execute
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function execute_rule(string $rule, array $data, array $errors): array {
        // Handle File/Image Validation
        // Check BOTH $_FILES and the 'is_file' flag we set in the controller
        if (isset($_FILES[$data['key']]) || (isset($data['is_file']) && $data['is_file'] === true)) {
            return $this->run_file_rule($rule, $data, $errors);
        }

        // Handle Standard Post Validation
        if (method_exists($this, $rule)) {
            return $this->$rule($data, $errors);
        }

        return $errors;
    }

    /* --- File & Image Validation Rules --- */

    /**
     * Handles File and Image specific validation logic.
     * 
     * @param string $rule The validation rule to execute
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    private function run_file_rule(string $rule, array $data, array $errors): array {
        // Ensure we are grabbing the file data from the right place
        $file = $_FILES[$data['key']] ?? $data['posted_value'];

        // Skip validation for empty files unless it's the 'required' rule
        if ($file['error'] === UPLOAD_ERR_NO_FILE && $rule !== 'required') {
            return $errors;
        }

        // SECURITY SCAN FOR ALL FILES - ESSENTIAL!
        if ($file['error'] === UPLOAD_ERR_OK) {
            if (!$this->scan_file_content($file['tmp_name'])) {
                $errors[$data['key']][] = $this->get_error_message('security_threat', $data);
                return $errors; // Stop processing immediately for security threats
            }
        }

        // For image-specific rules, we need to use the Image module
        $image_rules = ['is_image', 'max_width', 'max_height', 'min_width', 'min_height', 
                        'exact_width', 'exact_height', 'square'];
        
        if (in_array($rule, $image_rules)) {
            // Pass the correct $file array to run_image_rule
            return $this->run_image_rule($rule, $data, $file, $errors);
        }

        // Handle standard file rules
        switch ($rule) {
            case 'required':
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[$data['key']][] = $this->get_error_message('required', $data);
                }
                break;

            case 'allowed_types':
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_array = explode(',', strtolower($data['param']));
                    if (!in_array($ext, $allowed_array)) {
                        $errors[$data['key']][] = $this->get_error_message('allowed_types', $data);
                    }
                }
                break;

            case 'max_size':
                if ($file['error'] === UPLOAD_ERR_OK) {
                    if (($file['size'] / 1024) > (int)$data['param']) {
                        $errors[$data['key']][] = $this->get_error_message('max_size', $data);
                    }
                }
                break;

            case 'min_size':
                if ($file['error'] === UPLOAD_ERR_OK) {
                    if (($file['size'] / 1024) < (int)$data['param']) {
                        $errors[$data['key']][] = $this->get_error_message('min_size', $data);
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * Handles image-specific validation rules using the Image module.
     * 
     * @param string $rule The image validation rule to execute
     * @param array<string, mixed> $data The validation data array
     * @param array<string, mixed> $file The file data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    private function run_image_rule(string $rule, array $data, array $file, array $errors): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $errors;
        }

        try {
            // Direct instantiation (clean and reliable)
            $image_check = new Image();
            $image_check->load($file['tmp_name']);
            
            // Get dimensions if needed for other rules
            $width = $image_check->get_width();
            $height = $image_check->get_height();
            
            $image_check->destroy();

        } catch (Exception $e) {
            // Not a valid image
            $errors[$data['key']][] = $this->get_error_message('is_image', $data);
            return $errors;
        }

        // Handle dimension rules if present
        switch ($rule) {
            case 'max_width':
                if ($width > (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('max_width', $data);
                }
                break;
            case 'max_height':
                if ($height > (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('max_height', $data);
                }
                break;
            case 'min_width':
                if ($width < (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('min_width', $data);
                }
                break;
            case 'min_height':
                if ($height < (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('min_height', $data);
                }
                break;
            case 'exact_width':
                if ($width !== (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('exact_width', $data);
                }
                break;
            case 'exact_height':
                if ($height !== (int)$data['param']) {
                    $errors[$data['key']][] = $this->get_error_message('exact_height', $data);
                }
                break;
            case 'square':
                if ($width !== $height) {
                    $errors[$data['key']][] = $this->get_error_message('square', $data);
                }
                break;
            // 'is_image' already succeeded, so no action needed
        }

        return $errors;
    }

    /**
     * Scans file content for malicious patterns.
     * Essential for security - scans ALL files, not just images.
     * 
     * @param string $file_path The path to the temporary uploaded file
     * @return bool True if file is safe, false if malicious patterns detected
     */
    private function scan_file_content(string $file_path): bool {
        // Read first 4096 bytes for better detection
        $handle = fopen($file_path, 'r');
        $content = fread($handle, 4096);
        fclose($handle);
        
        // Dangerous patterns to detect
        $dangerous = [
            '/<\?php/i',           // PHP opening tag
            '/<\?=/i',             // PHP short echo tag
            '/<\?/i',              // PHP short open tag
            '/<script[^>]*>/i',    // Script tags
            '/eval\s*\(/i',        // eval() function
            '/exec\s*\(/i',        // exec() function
            '/shell_exec\s*\(/i',  // shell_exec function
            '/system\s*\(/i',      // system() function
            '/passthru\s*\(/i',    // passthru() function
            '/popen\s*\(/i',       // popen() function
            '/proc_open\s*\(/i',   // proc_open() function
            '/<iframe/i',          // iframe tags
            '/<object/i',          // object tags
            '/<embed/i',           // embed tags
            '/<!DOCTYPE/i',        // DOCTYPE declarations (can indicate polyglot)
            '/<!\[CDATA\[/i'       // CDATA sections
        ];
        
        foreach ($dangerous as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }

    /* --- Core Post Rules --- */

    /**
     * Validates that a field is required (not empty).
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function required(array $data, array $errors): array {
        $value = $data['posted_value'] ?? '';
        
        // Handle file upload required case (already handled in run_file_rule)
        if (isset($_FILES[$data['key']])) {
            return $errors;
        }
        
        if (trim($value) === '') {
            $errors[$data['key']][] = $this->get_error_message('required', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains a numeric value.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function numeric(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !is_numeric($data['posted_value'])) {
            $errors[$data['key']][] = $this->get_error_message('numeric', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains an integer value.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function integer(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !filter_var($data['posted_value'], FILTER_VALIDATE_INT)) {
            $errors[$data['key']][] = $this->get_error_message('integer', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains a decimal value.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function decimal(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !is_numeric($data['posted_value'])) {
            $errors[$data['key']][] = $this->get_error_message('decimal', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid email address.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_email(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !filter_var($data['posted_value'], FILTER_VALIDATE_EMAIL)) {
            $errors[$data['key']][] = $this->get_error_message('valid_email', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid URL.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_url(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !filter_var($data['posted_value'], FILTER_VALIDATE_URL)) {
            $errors[$data['key']][] = $this->get_error_message('valid_url', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid IP address.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_ip(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && !filter_var($data['posted_value'], FILTER_VALIDATE_IP)) {
            $errors[$data['key']][] = $this->get_error_message('valid_ip', $data);
        }
        return $errors;
    }

    /* --- Length Rules --- */

    /**
     * Validates that a field has a minimum length.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function min_length(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && strlen($data['posted_value']) < (int)$data['param']) {
            $errors[$data['key']][] = $this->get_error_message('min_length', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field has a maximum length.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function max_length(array $data, array $errors): array {
        if (strlen($data['posted_value'] ?? '') > (int)$data['param']) {
            $errors[$data['key']][] = $this->get_error_message('max_length', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field has an exact length.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function exact_length(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && strlen($data['posted_value']) !== (int)$data['param']) {
            $errors[$data['key']][] = $this->get_error_message('exact_length', $data);
        }
        return $errors;
    }

    /* --- Comparison Rules --- */

    /**
     * Validates that a field matches another field's value.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function matches(array $data, array $errors): array {
        $other_field_value = post($data['param'], true);
        if (($data['posted_value'] ?? '') !== $other_field_value) {
            $errors[$data['key']][] = $this->get_error_message('matches', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field value is greater than a specified number.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function greater_than(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && (float)$data['posted_value'] <= (float)$data['param']) {
            $errors[$data['key']][] = $this->get_error_message('greater_than', $data);
        }
        return $errors;
    }

    /**
     * Validates that a field value is less than a specified number.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function less_than(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '' && (float)$data['posted_value'] >= (float)$data['param']) {
            $errors[$data['key']][] = $this->get_error_message('less_than', $data);
        }
        return $errors;
    }

    /* --- Date/Time Rules --- */

    /**
     * Validates that a field contains a valid date in YYYY-MM-DD format.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_date(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $data['posted_value']);
            if (!($d && $d->format('Y-m-d') === $data['posted_value'])) {
                $errors[$data['key']][] = $this->get_error_message('valid_date', $data);
            }
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid time in HH:MM or HH:MM:SS format.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_time(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '') {
            // Accept HH:MM or HH:MM:SS
            $format = (strlen($data['posted_value']) > 5) ? 'H:i:s' : 'H:i';
            $d = DateTime::createFromFormat($format, $data['posted_value']);
            if (!($d && $d->format($format) === $data['posted_value'])) {
                $errors[$data['key']][] = $this->get_error_message('valid_time', $data);
            }
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid datetime-local in YYYY-MM-DDTHH:MM format.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_datetime_local(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '') {
            $d = DateTime::createFromFormat('Y-m-d\TH:i', $data['posted_value']);
            if (!($d && $d->format('Y-m-d\TH:i') === $data['posted_value'])) {
                $errors[$data['key']][] = $this->get_error_message('valid_datetime_local', $data);
            }
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid month in YYYY-MM format.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_month(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '') {
            $d = DateTime::createFromFormat('Y-m', $data['posted_value']);
            if (!($d && $d->format('Y-m') === $data['posted_value'])) {
                $errors[$data['key']][] = $this->get_error_message('valid_month', $data);
            }
        }
        return $errors;
    }

    /**
     * Validates that a field contains a valid week in YYYY-Www format.
     * 
     * @param array<string, mixed> $data The validation data array
     * @param array<string, array<string>> $errors The current errors array
     * @return array<string, array<string>> The updated errors array
     */
    public function valid_week(array $data, array $errors): array {
        if (($data['posted_value'] ?? '') !== '') {
            if (!preg_match('/^\d{4}-W\d{2}$/', $data['posted_value'])) {
                $errors[$data['key']][] = $this->get_error_message('valid_week', $data);
            }
        }
        return $errors;
    }

    /* --- Internal Logic Helpers --- */

    /**
     * Determines the current validation language.
     * Priority: Session > Constant > Default (en)
     * 
     * @return string The language code (e.g., 'en', 'fr', 'es')
     */
    private function get_validation_language(): string {
        $this->module('language');
        return $this->language->get_language();
    }

    /**
     * Loads validation error messages from language files.
     * 
     * @param string $lang The language code to load
     * @return void
     */
    public function load_validation_language(string $lang): void {
        $path = APPPATH . 'modules/validation/language/' . $lang . '/validation_errors.php';
        
        if (!file_exists($path)) {
            $path = APPPATH . 'modules/validation/language/en/validation_errors.php';
        }

        if (file_exists($path)) {
            require $path;
            $this->validation_error_messages = $validation_errors ?? [];
        }
    }

    /**
     * Gets the error message for a validation rule.
     * 
     * Maps rule names to language keys and replaces placeholders with actual values.
     * 
     * @param string $rule The validation rule name
     * @param array<string, mixed> $data The validation data array
     * @return string The formatted error message
     */
    private function get_error_message(string $rule, array $data): string {
        // Map rule names to language keys
        $key_map = [
            'required' => 'required_error',
            'numeric' => 'numeric_error',
            'integer' => 'integer_error',
            'decimal' => 'decimal_error',
            'valid_email' => 'valid_email_error',
            'valid_url' => 'valid_url_error',
            'valid_ip' => 'valid_ip_error',
            'min_length' => 'min_length_error',
            'max_length' => 'max_length_error',
            'exact_length' => 'exact_length_error',
            'matches' => 'matches_error',
            'greater_than' => 'greater_than_error',
            'less_than' => 'less_than_error',
            'valid_date' => 'valid_date_error',
            'valid_time' => 'valid_time_error',
            'valid_datetime_local' => 'valid_datetime_local_error',
            'valid_month' => 'valid_month_error',
            'valid_week' => 'valid_week_error',
            'allowed_types' => 'allowed_types_error',
            'max_size' => 'max_size_error',
            'min_size' => 'min_size_error',
            'is_image' => 'is_image_error',
            'max_width' => 'max_width_error',
            'min_width' => 'min_width_error',
            'max_height' => 'max_height_error',
            'min_height' => 'min_height_error',
            'exact_width' => 'exact_width_error',
            'exact_height' => 'exact_height_error',
            'square' => 'square_error',
            'security_threat' => 'security_threat_error'
        ];

        $key = $key_map[$rule] ?? $rule . '_error';
        $message = $this->validation_error_messages[$key] ?? "The {label} field failed validation.";
        
        $find = ['{label}', '[label]', '{param}', '[param]'];
        $replace = [$data['label'], $data['label'], $data['param'] ?? '', $data['param'] ?? ''];

        return str_replace($find, $replace, $message);
    }

    /**
     * Resolves a custom error message from a callback.
     * Checks the loaded language array for a matching key.
     * 
     * @param string $result The result string from a callback validation
     * @param array<string, mixed> $data The validation data array
     * @return string The resolved error message with placeholders replaced
     */
    public function resolve_error_message(string $result, array $data): string {
        // Look for the exact string returned by the callback in our language array
        if (isset($this->validation_error_messages[$result])) {
            $message = $this->validation_error_messages[$result];
        } else {
            // Not a key in the language file, so treat the string as the message
            $message = $result;
        }

        $find = ['{label}', '[label]', '{param}', '[param]'];
        $replace = [$data['label'], $data['label'], $data['param'] ?? '', $data['param'] ?? ''];

        return str_replace($find, $replace, $message);
    }

}