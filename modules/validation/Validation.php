<?php
/**
 * Form validation class for server-side input validation with built-in rules.
 * Provides comprehensive validation including file uploads, custom callbacks, and CSRF protection.
 */
class Validation extends Trongate {

    /** @var array Holds the form submission errors. */
    public array $form_submission_errors = [];

    /** @var array Holds the posted fields. */
    public array $posted_fields = [];

    /**
     * Class constructor.
     *
     * Prevents direct URL access to the validation module while allowing
     * internal validation operations via application code.
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url($this->module_name);
    }

    /**
     * Set rules for form field validation.
     *
     * @param string $key The form field name.
     * @param string $label The form field label.
     * @param string|array $rules The validation rules for the field.
     * @return void
     */
    public function set_rules(string $key, string $label, string $rules): void {
        $validation_data['key'] = $key;
        $validation_data['label'] = $label;

        if (isset($_FILES[$key])) {
            // File handling - delegate to child module
            $file = $_FILES[$key];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->handle_upload_error($key, $label, $file['error']);
                return;
            }

            // Load the file_validation child module explicitly
            $this->module('validation-file_validation');
            
            // Delegate all file validation to the file_validation child module
            $file_errors = $this->file_validation->validate($key, $label, $rules, $file);
            
            if (!empty($file_errors)) {
                foreach ($file_errors as $error) {
                    $this->form_submission_errors[$key][] = $error;
                }
            }

        } else {
            // Normal form field handling
            $validation_data['posted_value'] = post($key, true);
            $tests_to_run = $this->get_tests_to_run($rules);

            foreach ($tests_to_run as $test_to_run) {
                $this->posted_fields[$key] = $label;
                $validation_data['test_to_run'] = $test_to_run;
                $this->run_validation_test($validation_data, $rules);
            }
        }

        $_SESSION['form_submission_errors'] = $this->form_submission_errors;
    }

    /**
     * Handles file upload errors by mapping the error code to a user-friendly message
     * and storing the error in the form submission errors array.
     *
     * @param string $key The key associated with the file upload field.
     * @param string $label The label/name of the file upload field.
     * @param int $error_code The error code returned by the file upload process.
     * @return void
     */
    private function handle_upload_error(string $key, string $label, int $error_code): void {
        $error_message = match($error_code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'An unknown error occurred during file upload'
        };
        $this->form_submission_errors[$key][] = $error_message;
        $_SESSION['form_submission_errors'] = $this->form_submission_errors;
    }

    /**
     * Run a validation test based on the provided validation data and rules.
     *
     * @param array $validation_data An array containing validation data.
     * @param mixed|null $rules The rules for validation (default: null).
     * @return void
     */
    private function run_validation_test(array $validation_data, $rules = null): void {

        switch ($validation_data['test_to_run']) {
            case 'required':
                $this->check_for_required($validation_data);
                break;
            case 'numeric':
                $this->check_for_numeric($validation_data);
                break;
            case 'integer':
                $this->check_for_integer($validation_data);
                break;
            case 'decimal':
                $this->check_for_decimal($validation_data);
                break;
            case 'valid_email':
                $this->valid_email($validation_data);
                break;
            case 'valid_date':
                $this->valid_date($validation_data);
                break;
            case 'valid_time':
                $this->valid_time($validation_data);
                break;
            case 'valid_datetime_local':
                $this->valid_datetime_local($validation_data);
                break;
            case 'valid_month':
                $this->valid_month($validation_data);
                break;
            case 'valid_week':
                $this->valid_week($validation_data);
                break;
            default:
                $this->run_special_test($validation_data);
                break;
        }
    }

    /**
     * Run form validation checks.
     *
     * @param array|null $validation_array An array containing validation rules (default: null).
     * @return bool|null Returns a boolean value if validation completes, null if the script execution is potentially terminated.
     */
    public function run(?array $validation_array = null): ?bool {

        $this->csrf_protect();

        if (isset($_SESSION['form_submission_errors'])) {
            unset($_SESSION['form_submission_errors']);
        }

        if (isset($validation_array)) {
            $this->process_validation_array($validation_array);
        }

        if (count($this->form_submission_errors) > 0) {
            $_SESSION['form_submission_errors'] = $this->form_submission_errors;
            return false;
        } else {
            return true;
        }
    }

    /**
     * Invoke the required form validation tests.
     *
     * @param array $validation_array The array containing validation rules and data.
     * @return void
     */
    private function process_validation_array(array $validation_array): void {

        foreach ($validation_array as $key => $value) {
            if (isset($value['label'])) {
                $label = $value['label'];
            } else {
                $label = str_replace('_', ' ', $key);
            }

            $posted_value = post($key, true);
            $rules = $this->build_rules_str($value);
            $tests_to_run = $this->get_tests_to_run($rules);

            $validation_data['key'] = $key;
            $validation_data['label'] = $label;
            $validation_data['posted_value'] = $posted_value;

            foreach ($tests_to_run as $test_to_run) {
                $this->posted_fields[$key] = $label;
                $validation_data['test_to_run'] = $test_to_run;
                $this->run_validation_test($validation_data);
            }
        }
    }

    /**
     * Build rules string based on the provided validation rules.
     *
     * @param array $value An array representing validation rules.
     * @return string Returns a string containing rules generated from the validation rules.
     */
    private function build_rules_str(array $value): string {
        $rules_str = '';

        foreach ($value as $k => $v) {
            if ($k !== 'label') {
                $rules_str .= $k . (is_bool($v) ? '|' : '[' . $v . ']|');
            }
        }

        return rtrim($rules_str, '|'); // Remove trailing '|' if present
    }

    /**
     * Get tests to run based on provided rules.
     *
     * @param string $rules A string containing validation rules separated by '|'.
     * @return array An array containing individual tests to run based on rules.
     */
    private function get_tests_to_run(string $rules): array {
        $tests_to_run = explode('|', $rules);
        return $tests_to_run;
    }

    /**
     * Check for required fields in the validation data.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function check_for_required(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = trim($validation_data['posted_value']);

        if ($posted_value === '') {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field is required.';
        }
    }

    /**
     * Check if the value in validation data is numeric.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function check_for_numeric(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ((!is_numeric($posted_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be numeric.';
        }
    }

    /**
     * Check if the value in validation data is an integer.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function check_for_integer(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ((!filter_var($posted_value, FILTER_VALIDATE_INT)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be an integer.';
        }
    }

    /**
     * Check if the value in validation data is a decimal number.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function check_for_decimal(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ((!filter_var($posted_value, FILTER_VALIDATE_FLOAT)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a decimal number.';
        }
    }

    /**
     * Validate the email address format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_email(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ((!filter_var($posted_value, FILTER_VALIDATE_EMAIL)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid email address.';
        }
    }

    /**
     * Validate date in YYYY-MM-DD format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_date(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value === '') {
            return;
        }

        $date = DateTime::createFromFormat('Y-m-d', $posted_value);
        $is_valid = $date && $date->format('Y-m-d') === $posted_value;

        if (!$is_valid) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid date in YYYY-MM-DD format.';
        }
    }

    /**
     * Validate time in HH:MM or HH:MM:SS format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_time(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value === '') {
            return;
        }

        // Try HH:MM:SS format first
        $time = DateTime::createFromFormat('H:i:s', $posted_value);
        $is_valid = $time && $time->format('H:i:s') === $posted_value;

        // If not valid, try HH:MM format
        if (!$is_valid) {
            $time = DateTime::createFromFormat('H:i', $posted_value);
            $is_valid = $time && $time->format('H:i') === $posted_value;
        }

        if (!$is_valid) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid time in HH:MM or HH:MM:SS format.';
        }
    }

    /**
     * Validate datetime-local in YYYY-MM-DDTHH:MM format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_datetime_local(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value === '') {
            return;
        }

        $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $posted_value);
        $is_valid = $datetime && $datetime->format('Y-m-d\TH:i') === $posted_value;

        if (!$is_valid) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid datetime in YYYY-MM-DDTHH:MM format.';
        }
    }

    /**
     * Validate month in YYYY-MM format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_month(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value === '') {
            return;
        }

        $date = DateTime::createFromFormat('Y-m', $posted_value);
        $is_valid = $date && $date->format('Y-m') === $posted_value;

        if (!$is_valid) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid month in YYYY-MM format.';
        }
    }

    /**
     * Validate week in YYYY-W## format.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value'
     * @return void
     */
    private function valid_week(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value === '') {
            return;
        }

        // Validate format YYYY-W## (e.g., 2025-W52)
        if (!preg_match('/^\d{4}-W\d{2}$/', $posted_value)) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid week in YYYY-W## format.';
            return;
        }

        // Extract year and week number
        list($year, $week_part) = explode('-W', $posted_value);
        $week = (int) $week_part;

        // Validate week number range (1-53)
        if ($week < 1 || $week > 53) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid week in YYYY-W## format.';
        }
    }

    /**
     * Run special validation tests based on the provided validation data.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'key', 'label', 'posted_value', 'test_to_run'
     * @return void
     */
    private function run_special_test(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];
        $test_to_run = $validation_data['test_to_run'];

        $inner_bracket_contents = $this->extract_content($test_to_run, '[', ']');

        if ($inner_bracket_contents !== '') {
            $test_name = $this->get_test_name($test_to_run);
            $inner_value = $this->extract_content($test_to_run, '[', ']');

            switch ($test_name) {
                case 'matches':
                    $this->matches($key, $label, $posted_value, $inner_value);
                    break;
                case 'differs':
                    $this->differs($key, $label, $posted_value, $inner_value);
                    break;
                case 'min_length':
                    $this->min_length($key, $label, $posted_value, intval($inner_value));
                    break;
                case 'max_length':
                    $this->max_length($key, $label, $posted_value, intval($inner_value));
                    break;
                case 'greater_than':
                    $this->greater_than($key, $label, $posted_value, intval($inner_value));
                    break;
                case 'less_than':
                    $this->less_than($key, $label, $posted_value, intval($inner_value));
                    break;
                case 'exact_length':
                    $this->exact_length($key, $label, $posted_value, intval($inner_value));
                    break;
            }
        } else {
            $this->attempt_invoke_callback($key, $label, $posted_value, $test_to_run);
        }
    }

    /**
     * Extracts content between specified start and end strings within a given string.
     *
     * @param string $string The input string to search within.
     * @param string $start The starting string to search for.
     * @param string $end The ending string to search for.
     * @return string Returns the extracted content.
     */
    private function extract_content(string $string, string $start, string $end): string {
        $pos = stripos($string, $start);
        $str = substr($string, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        $content = trim($str_three); // Remove whitespaces
        return $content;
    }

    /**
     * Gets the test name from the test to run string containing square brackets.
     *
     * @param string $test_to_run The string containing the test name and parameters.
     * @return string Returns the extracted test name.
     */
    private function get_test_name(string $test_to_run): string {
        $pos = stripos($test_to_run, '[');
        $test_name = substr($test_to_run, 0, $pos);
        return $test_name;
    }

    /**
     * Check if the value matches another field value.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param string $compare_field The name of the field to compare against.
     * @return void
     */
    private function matches(string $key, string $label, $posted_value, string $compare_field): void {
        $compare_value = post($compare_field, true);

        if ($posted_value !== $compare_value) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must match the ' . str_replace('_', ' ', $compare_field) . ' field.';
        }
    }

    /**
     * Check if the value differs from another field value.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param string $compare_field The name of the field to compare against.
     * @return void
     */
    private function differs(string $key, string $label, $posted_value, string $compare_field): void {
        $compare_value = post($compare_field, true);

        if ($posted_value === $compare_value) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must differ from the ' . str_replace('_', ' ', $compare_field) . ' field.';
        }
    }

    /**
     * Check if the value meets minimum length requirements.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param int $min_length The minimum required length.
     * @return void
     */
    private function min_length(string $key, string $label, $posted_value, int $min_length): void {
        if ((strlen($posted_value) < $min_length) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be at least ' . $min_length . ' characters long.';
        }
    }

    /**
     * Check if the value meets maximum length requirements.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param int $max_length The maximum allowed length.
     * @return void
     */
    private function max_length(string $key, string $label, $posted_value, int $max_length): void {
        if ((strlen($posted_value) > $max_length) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field cannot exceed ' . $max_length . ' characters.';
        }
    }

    /**
     * Check if the numeric value is greater than a specified value.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param int $compare_value The value to compare against.
     * @return void
     */
    private function greater_than(string $key, string $label, $posted_value, int $compare_value): void {
        if (($posted_value !== '') && (is_numeric($posted_value)) && ($posted_value <= $compare_value)) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be greater than ' . $compare_value . '.';
        }
    }

    /**
     * Check if the numeric value is less than a specified value.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param int $compare_value The value to compare against.
     * @return void
     */
    private function less_than(string $key, string $label, $posted_value, int $compare_value): void {
        if (($posted_value !== '') && (is_numeric($posted_value)) && ($posted_value >= $compare_value)) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be less than ' . $compare_value . '.';
        }
    }

    /**
     * Check if the value has an exact length.
     *
     * @param string $key The key of the field being validated.
     * @param string $label The label of the field being validated.
     * @param mixed $posted_value The value of the field being validated.
     * @param int $exact_length The exact required length.
     * @return void
     */
    private function exact_length(string $key, string $label, $posted_value, int $exact_length): void {
        if ((strlen($posted_value) !== $exact_length) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be exactly ' . $exact_length . ' characters long.';
        }
    }

    /**
     * Attempts to invoke a callback method for validation.
     *
     * @param string $key The key associated with the input field.
     * @param string $label The label for the input field.
     * @param mixed $posted_value The value posted for validation.
     * @param string $test_to_run The name of the test to run.
     * @return void
     */
    private function attempt_invoke_callback(string $key, string $label, $posted_value, string $test_to_run): void {
        $chars = substr($test_to_run, 0, 9);
        if ($chars === 'callback_') {
            $target_module = ucfirst(segment(1));
            $target_method = str_replace('callback_', '', $test_to_run);
            
            // Store the module name for constructor (lowercase, full path including hyphens)
            $module_name = strtolower(segment(1));
            
            if (!class_exists($target_module)) {
                $modules_bits = explode('-', $target_module);
                $target_module = ucfirst(end($modules_bits));
                // Keep $module_name as the full hyphenated path
            }
            
            if (class_exists($target_module)) {
                try {
                    $callback = new $target_module($module_name);  // ← FIXED: Pass module_name
                    
                    if (method_exists($callback, $target_method)) {
                        $outcome = $callback->$target_method($posted_value);
                        
                        if (is_string($outcome)) {
                            $outcome = str_replace('{label}', $label, $outcome);
                            $this->form_submission_errors[$key][] = $outcome;
                        }
                    } else {
                        $this->form_submission_errors[$key][] = 'Unable to execute validation callback.';
                    }
                } catch (Exception $e) {
                    $this->form_submission_errors[$key][] = 'Unable to execute validation callback.';
                }
            }
        }
    }

    /**
     * Protects against Cross-Site Request Forgery (CSRF) attacks.
     *
     * @return void
     */
    private function csrf_protect(): void {
        // Make sure they have posted csrf_token
        $posted_csrf_token = post('csrf_token');

        if ($posted_csrf_token === '') {
            $this->csrf_block_request();
        } else {
            $expected = $_SESSION['csrf_token'] ?? '';

            if (!is_string($posted_csrf_token) || !hash_equals($expected, $posted_csrf_token)) {
                $this->csrf_block_request();
            }

        }
    }

    /**
     * Handles blocking of CSRF requests.
     *
     * This method is invoked when Trongate's CSRF protection is triggered.
     * If the request originates from Trongate MX, it sends a 403 response code and provides
     * additional debugging information in development mode. Otherwise, it redirects to the base URL.
     *
     * @return void
     */
    private function csrf_block_request(): void {

        if (from_trongate_mx() === true) {
            http_response_code(403);
            if (strtolower(ENV) === 'dev') {
                echo 'Trongate\'s CSRF protection has blocked the request. For more details, refer to: https://trongate.io/documentation/read/trongate_mx/trongate-mx-security/csrf-protection ***  This message will NOT be displayed unless ENV is not set to a value of \'DEV\' or \'dev\'';
            }
            die();
        }

        header("location: " . BASE_URL);
        die();
    }

}