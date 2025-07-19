<?php
/**
 * Trongate Validation Class
 *
 * Provides server-side form and file validation with built-in CSRF protection.
 * To bypass CSRF for API endpoints:
 *     define('API_SKIP_CSRF', true);
 *
 * All input is retrieved via the global post() helper so JSON,
 * multipart/form-data, x-www-form-urlencoded and dot/bracket notation all work
 * identically.
 */
class Validation {

    /** @var array Holds the form submission errors. */
    public array $form_submission_errors = [];

    /** @var array Holds the posted fields. */
    public array $posted_fields = [];

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
            // File handling
            $file = $_FILES[$key];
            $file['field_name'] = $key;

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->handle_upload_error($key, $label, $file['error']);
                return;
            }

            // Run security validation
            try {
                $this->validate_file_content($file);
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = $e->getMessage();
                return;
            }

            $validation_data['posted_value'] = $file;
            $tests_to_run = $this->get_tests_to_run($rules);
            
            // Extract and process file-specific rules
            foreach ($tests_to_run as $test_to_run) {
                $this->posted_fields[$key] = $label;
                $validation_data['test_to_run'] = $test_to_run;
                $this->run_validation_test($validation_data, $rules);
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
            case 'valid_datepicker':
                $this->valid_datepicker($validation_data);
                break;
            case 'valid_datepicker_us':
                $this->valid_datepicker_us($validation_data);
                break;
            case 'valid_datepicker_eu':
                $this->valid_datepicker_eu($validation_data);
                break;
            case 'valid_datepicker_uk':
                $this->valid_datepicker_eu($validation_data);
                break;
            case 'valid_datetimepicker':
                $this->valid_datetimepicker($validation_data);
                break;
            case 'valid_datetimepicker_us':
                $this->valid_datetimepicker_us($validation_data);
                break;
            case 'valid_datetimepicker_eu':
                $this->valid_datetimepicker_eu($validation_data);
                break;
            case 'valid_time':
                $this->valid_time($validation_data);
                break;
            case 'allowed_types':
                $this->check_allowed_file_types($validation_data, $rules);
                break;
            case 'max_size':
                $this->check_file_size($validation_data, $rules);
                break;
            case 'max_width':
            case 'max_height':
            case 'min_width':
            case 'min_height':
            case 'square':
                $this->check_image_dimensions($validation_data, $rules);
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

        if ($posted_value !== '') {
            $result = ctype_digit(strval($posted_value));

            if ($result === false) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be an integer.';
            }
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

        if ($posted_value !== '') {
            if ((float) $posted_value == floor($posted_value)) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a number with a decimal.';
            }
        }
    }

    /**
     * Validates a datepicker value.
     *
     * @param array $validation_data An array containing validation data.
     *                              Required keys: 'posted_value', 'key', 'label'
     * @return bool Returns true if the datepicker value is valid, false otherwise.
     * @throws Exception If the posted value is not a valid date in the expected format.
     */
    private function valid_datepicker(array $validation_data): bool {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value !== '') {
            try {
                $parsed_date = parse_date($posted_value);

                if ($parsed_date instanceof DateTime) {
                    return true;
                } else {
                    throw new Exception('Invalid date format');
                }
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid date in the format ' . DEFAULT_DATE_FORMAT . '.';
                return false;
            }
        }

        return false;
    }

    /**
     * Validates a datepicker value by invoking the valid_datepicker() function.
     * This function serves as an alias for valid_datepicker().
     *
     * @param array $validation_data An array containing validation data.
     * @return bool Returns true if the datepicker value is valid, false otherwise.
     * @throws Exception If the posted value is not a valid date in the expected format.
     */
    private function valid_datepicker_us(array $validation_data): bool {
        return $this->valid_datepicker($validation_data);
    }

    /**
     * Validates a datepicker value by invoking the valid_datepicker() function.
     * This function serves as an alias for valid_datepicker().
     *
     * @param array $validation_data An array containing validation data.
     * @return bool Returns true if the datepicker value is valid, false otherwise.
     * @throws Exception If the posted value is not a valid date in the expected format.
     */
    private function valid_datepicker_eu(array $validation_data): bool {
        return $this->valid_datepicker($validation_data);
    }

    /**
     * Validates the datetimepicker input.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * 
     * @return bool Returns true if the input is a valid date and time, otherwise adds an error message and returns false.
     */
    private function valid_datetimepicker(array $validation_data): bool {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value !== '') {
            $parsed_datetime = parse_datetime($posted_value);

            if ($parsed_datetime instanceof DateTime) {
                return true;
            } else {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid date and time in the format ' . DEFAULT_DATE_FORMAT . ', HH:ii.';
                return false;
            }
        }

        return false;
    }

    /**
     * Alias of valid_datetimepicker().
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns true if the input is a valid date and time, otherwise adds an error message and returns false.
     */
    private function valid_datetimepicker_us(array $validation_data): bool {
        return $this->valid_datetimepicker($validation_data);
    }

    /**
     * Alias of valid_datetimepicker().
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns true if the input is a valid date and time, otherwise adds an error message and returns false.
     */
    private function valid_datetimepicker_eu(array $validation_data): bool {
        return $this->valid_datetimepicker($validation_data);
    }

    /**
     * Validates the time input.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns true if the input is a valid time, otherwise adds an error message and returns false.
     */
    private function valid_time(array $validation_data): bool {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value !== '') {
            $pattern = '/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/'; // Regex pattern for HH:MM format

            if (preg_match($pattern, $posted_value, $matches)) {
                return true;
            }

            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid time value.';
            return false;
        }

        return false;
    }

    /**
     * Validates if the posted value matches a specified target field's value.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be compared.
     * @param string $target_field The target field to compare against.
     * @return void
     */
    private function matches(string $key, string $label, string $posted_value, string $target_field): void {
        $got_error = false;
        
        $target_value = post($target_field, true);
        if ($target_value === '') {
            $got_error = true;
        } else if ($posted_value !== $target_value) {
            $got_error = true;
        }

        if ($got_error) {
            $target_label = $this->posted_fields[$target_field] ?? $target_field;
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field does not match the ' . $target_label . ' field.';
        }
    }

    /**
     * Validates if the posted value differs from a specified target field's value.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be compared.
     * @param string $target_field The target field to compare against.
     * @return void
     */
    private function differs(string $key, string $label, string $posted_value, string $target_field): void {
        $got_error = false;
        
        $target_value = post($target_field, true);
        if ($target_value === '') {
            $got_error = true;
        } else if ($posted_value == $target_value) {
            $got_error = true;
        }

        if ($got_error) {
            $target_label = $this->posted_fields[$target_field] ?? $target_field;
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must not match the ' . $target_label . ' field.';
        }
    }

    /**
     * Validates if the posted value meets a minimum length requirement.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be checked.
     * @param int $inner_value The minimum length requirement.
     * @return void
     */
    private function min_length(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((strlen($posted_value) < $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be at least ' . $inner_value . ' characters in length.';
        }
    }

    /**
     * Validates if the posted value meets a maximum length requirement.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be checked.
     * @param int $inner_value The maximum length requirement.
     * @return void
     */
    private function max_length(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((strlen($posted_value) > $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be no more than  ' . $inner_value . ' characters in length.';
        }
    }

    /**
     * Validates if the posted value is greater than a specified inner value.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be compared.
     * @param int $inner_value The value for comparison.
     * @return void
     */
    private function greater_than(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((is_numeric($posted_value) && ($posted_value <= $inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be greater than ' . $inner_value . '.';
        }
    }

    /**
     * Validates if the posted value is less than a specified inner value.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be compared.
     * @param int $inner_value The value for comparison.
     * @return void
     */
    private function less_than(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((is_numeric($posted_value) && ($posted_value >= $inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be less than ' . $inner_value . '.';
        }
    }

    /**
     * Validates the provided email address.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return void
     */
    private function valid_email(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];

        if ($posted_value !== '') {
            if (!filter_var($posted_value, FILTER_VALIDATE_EMAIL)) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid email address.';
                return;
            }

            // Make sure the email address is not too long
            if (strlen($posted_value) > 254) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' is too long.';
                return;
            }

            // Optional: Check domain has valid MX or A record
            $domain = substr(strrchr($posted_value, "@"), 1);
            if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field contains a domain with no valid DNS records.';
                return;
            }
        }
    }

    /**
     * Validates if the posted value matches the exact length specified.
     *
     * @param string $key The key associated with the posted value.
     * @param string $label The label/name of the posted value.
     * @param string $posted_value The posted value to be checked for length.
     * @param int $inner_value The expected length of the posted value.
     * @return void
     */
    private function exact_length(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((strlen($posted_value) !== $inner_value) && ($posted_value !== '')) {
            $error_msg = 'The ' . $label . ' field must be ' . $inner_value . ' characters in length.';

            if ($inner_value == 1) {
                $error_msg = str_replace('characters in length.', 'character in length.', $error_msg);
            }

            $this->form_submission_errors[$key][] = $error_msg;
        }
    }

    /**
     * Runs a special validation test based on the provided test name and value within square brackets.
     *
     * @param array $validation_data The validation data containing key, label, posted value, and test to run.
     * @return void
     */
    private function run_special_test(array $validation_data): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $posted_value = $validation_data['posted_value'];
        $test_to_run = $validation_data['test_to_run'];

        $pos = strpos($test_to_run, '[');

        if (is_numeric($pos)) {
            if ($posted_value === '') {
                return; // No need to perform tests if no value is submitted
            }

            // Get the value between the square brackets
            $inner_value = $this->_extract_content($test_to_run, '[', ']');
            $test_name = $this->_get_test_name($test_to_run);

            // Validating based on the test name and inner value
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
    private function _extract_content(string $string, string $start, string $end): string {
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
    private function _get_test_name(string $test_to_run): string {
        $pos = stripos($test_to_run, '[');
        $test_name = substr($test_to_run, 0, $pos);
        return $test_name;
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
            $target_module = ucfirst($this->url_segment(1));
            $target_method = str_replace('callback_', '', $test_to_run);

            if (!class_exists($target_module)) {
                $modules_bits = explode('-', $target_module);
                $target_module = ucfirst(end($modules_bits));
            }

            if (class_exists($target_module)) {
                $static_check = new ReflectionMethod($target_module, $target_method);
                if ($static_check->isStatic()) {
                    // STATIC METHOD
                    $outcome = $target_module::$target_method($posted_value);
                } else {
                    // INSTANTIATED
                    $callback = new $target_module;
                    $outcome = $callback->$target_method($posted_value);
                }

                if (is_string($outcome)) {
                    $outcome = str_replace('{label}', $label, $outcome);
                    $this->form_submission_errors[$key][] = $outcome;
                }
            }
        }
    }

    /**
     * Retrieves a segment from the URL.
     *
     * @param int $num The segment number to retrieve.
     * @return string The requested URL segment.
     */
    private function url_segment(int $num): string {
        $segments = SEGMENTS;

        if (isset($segments[$num])) {
            $value = $segments[$num];
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Protects against Cross-Site Request Forgery (CSRF) attacks.
     *
     * @return void
     */
    private function csrf_protect(): void {
        // 1. Fast exit if the opt-out constant exists and is boolean true.
        if (defined('API_SKIP_CSRF') && constant('API_SKIP_CSRF') === true) {
            return;
        }

        // 2. Standard CSRF check for everything else.
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

    /**
     * Get file validation tests to run based on provided rules.
     *
     * @param string $rules A string containing validation rules separated by '|'.
     * @return array An array containing file validation tests to run.
     */
    private function get_file_validation_tests(string $rules): array {
        $tests = [];
        $rules_array = explode('|', $rules);
        
        foreach ($rules_array as $rule) {
            if (strpos($rule, '[') !== false) {
                // Extract rule name without the parameters
                $rule_name = substr($rule, 0, strpos($rule, '['));
                $tests[] = $rule_name;
            } else {
                $tests[] = $rule;
            }
        }
        
        return $tests;
    }

    /**
     * Validates if the uploaded file type is allowed.
     *
     * @param array $validation_data The validation data array.
     * @param string $rules The complete rules string.
     * @return void
     */
    private function check_allowed_file_types(array $validation_data, string $rules): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $file = $validation_data['posted_value'];
        
        // Extract allowed types from rules string
        $allowed_types = $this->_extract_content($rules, 'allowed_types[', ']');
        $allowed_types = explode(',', $allowed_types);
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' must be one of the following types: ' . implode(', ', $allowed_types);
        }
    }

    /**
     * Validates if the uploaded file size is within limits.
     *
     * @param array $validation_data The validation data array.
     * @param string $rules The complete rules string.
     * @return void
     */
    private function check_file_size(array $validation_data, string $rules): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $file = $validation_data['posted_value'];
        
        // Extract max size from rules string
        $max_size = (float) $this->_extract_content($rules, 'max_size[', ']');
        $file_size = $file['size'] / 1024; // Convert to KB
        
        if ($file_size > $max_size) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' exceeds the maximum allowed size of ' . $max_size . ' KB';
        }
    }

    /**
     * Validates image dimensions according to the specified rules.
     *
     * @param array $validation_data The validation data array.
     * @param string $rules The complete rules string.
     * @return void
     */
    private function check_image_dimensions(array $validation_data, string $rules): void {
        $key = $validation_data['key'];
        $label = $validation_data['label'];
        $file = $validation_data['posted_value'];
        
        // First verify it's an image file
        if (!getimagesize($file['tmp_name'])) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' must be a valid image file';
            return;
        }
        
        $dimensions = getimagesize($file['tmp_name']);
        $width = $dimensions[0];
        $height = $dimensions[1];
        
        // Extract dimension rules
        foreach (['max_width', 'max_height', 'min_width', 'min_height'] as $rule) {
            if (strpos($rules, $rule) !== false) {
                $value = (int) $this->_extract_content($rules, $rule . '[', ']');
                
                switch ($rule) {
                    case 'max_width':
                        if ($width > $value) {
                            $this->form_submission_errors[$key][] = 'The ' . $label . ' width cannot exceed ' . $value . ' pixels';
                        }
                        break;
                    case 'max_height':
                        if ($height > $value) {
                            $this->form_submission_errors[$key][] = 'The ' . $label . ' height cannot exceed ' . $value . ' pixels';
                        }
                        break;
                    case 'min_width':
                        if ($width < $value) {
                            $this->form_submission_errors[$key][] = 'The ' . $label . ' width must be at least ' . $value . ' pixels';
                        }
                        break;
                    case 'min_height':
                        if ($height < $value) {
                            $this->form_submission_errors[$key][] = 'The ' . $label . ' height must be at least ' . $value . ' pixels';
                        }
                        break;
                }
            }
        }
        
        // Check for square image requirement
        if (strpos($rules, 'square') !== false && $width !== $height) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' must be square (width must equal height)';
        }
    }

    /**
     * Validates the content of an uploaded file to detect potential security threats.
     *
     * This method reads the first 256 bytes of the file and scans for dangerous patterns,
     * such as PHP code, CDATA sections, or dangerous functions like `eval` and `exec`.
     * If a threat is detected, an error is added to the form submission errors array.
     *
     * @param array $file An associative array containing file information, including:
     *                    - 'tmp_name': The temporary file path.
     *                    - 'field_name': The name of the file upload field.
     * @return void
     * @throws RuntimeException If the file cannot be opened for scanning.
     */
    private function validate_file_content(array $file): void {
        // This replaces both is_text_file() and scan_text_file() from File class
        if (($file_handle = @fopen($file['tmp_name'], 'rb')) === FALSE) {
            throw new RuntimeException('Unable to open file for security scanning');
        }

        $content = fread($file_handle, 256); // Read first 256 bytes
        fclose($file_handle);

        // These are the same patterns from the old scan_text_file() method
        $dangerous_patterns = [
            '/<\?php/i',
            '/\<\!\[CDATA\[/i',
            '/\<\!DOCTYPE/i',
            '/\<\!ENTITY/i',
            '/\beval\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i'
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->form_submission_errors[$file['field_name']][] = 
                    'Potential security threat detected in file';
                return;
            }
        }
    }

}