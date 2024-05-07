<?php
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
    public function set_rules(string $key, string $label, $rules): void {

        if ((!isset($_POST[$key])) && (isset($_FILES[$key]))) {

            if (!isset($_POST[$key])) {
                $_POST[$key] = '';
            }

            $posted_value = $_FILES[$key];
            $tests_to_run[] = 'validate_file';
        } else {

            if (isset($_POST[$key])) {
                $_POST[$key] = trim($_POST[$key]);
            }

            $posted_value = isset($_POST[$key]) ? $_POST[$key] : '';
            $tests_to_run = $this->get_tests_to_run($rules);
        }

        $validation_data['key'] = $key;
        $validation_data['label'] = $label;
        $validation_data['posted_value'] = $posted_value;

        foreach ($tests_to_run as $test_to_run) {
            $this->posted_fields[$key] = $label;
            $validation_data['test_to_run'] = $test_to_run;
            $this->run_validation_test($validation_data, $rules);
        }

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
            case 'validate_file':
                $this->validate_file($validation_data['key'], $validation_data['label'], $rules);
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

            if ((!isset($_POST[$key])) && (isset($_FILES[$key]))) {
                $posted_value = $_FILES[$key];
                $tests_to_run[] = 'validate_file';
            } else {
                $posted_value = $_POST[$key];
                $rules = $this->build_rules_str($value);
                $tests_to_run = $this->get_tests_to_run($rules);
            }

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
        extract($validation_data);
        $posted_value = trim($validation_data['posted_value']);

        if ($posted_value == '') {
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
        extract($validation_data);
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
        extract($validation_data);
        if ($posted_value !== '') {

            $result = ctype_digit(strval($posted_value));

            if ($result == false) {
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
        extract($validation_data);
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
        extract($validation_data);
        if ($posted_value !== '') {
            try {
                $parsed_date = parse_date($posted_value);

                if ($parsed_date instanceof DateTime) {
                    return true;
                } else {
                    throw new Exception('Invalid date format'); // Triggering catch block if parsing fails
                }
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid date in the format ' . DEFAULT_DATE_FORMAT . '.';
                return false;
            }
        }

        return false; // Return false when $posted_value is empty
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
        extract($validation_data);

        if ($posted_value !== '') {
            $parsed_datetime = parse_datetime($posted_value);

            if ($parsed_datetime instanceof DateTime) {
                return true;
            } else {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a valid date and time in the format ' . DEFAULT_DATE_FORMAT . ', HH:ii.';
                return false;
            }
        }

        return false; // Return false when $posted_value is empty
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
        extract($validation_data);

        if ($posted_value !== '') {
            $pattern = '/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/'; // Regex pattern for HH:MM format

            if (preg_match($pattern, $posted_value, $matches)) {
                return true;
            }

            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid time value.';
            return false;
        }

        return false; // If $posted_value is empty
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

        if (!isset($_POST[$target_field])) {
            $got_error = true;
        } else {
            $target_value = $_POST[$target_field];

            if ($posted_value !== $target_value) {
                $got_error = true;
            }
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
        // Initialize error flag
        $got_error = false;

        if (!isset($_POST[$target_field])) {
            // If target field is not set, consider it different
            $got_error = true;
        } else {
            $target_value = $_POST[$target_field];

            if ($posted_value == $target_value) {
                // If posted value matches the target value, set error flag
                $got_error = true;
            }
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
        if ((strlen($_POST[$key]) < $inner_value) && ($posted_value !== '')) {
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
        if ((strlen($_POST[$key]) > $inner_value) && ($posted_value !== '')) {
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
        if (((is_numeric($_POST[$key])) && ($_POST[$key] <= $inner_value)) && ($posted_value !== '')) {
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
        if (((is_numeric($_POST[$key])) && ($_POST[$key] >= $inner_value)) && ($posted_value !== '')) {
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
        extract($validation_data);

        if ($posted_value !== '') {
            if (!filter_var($posted_value, FILTER_VALIDATE_EMAIL)) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid email address.';
                return;
            }

            // Check if the email address contains an @ symbol and a valid domain name
            $at_pos = strpos($posted_value, '@');
            if ($at_pos === false || $at_pos === 0) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' is not properly formatted.';
                return;
            }

            // Make sure the email address is not too long
            if (strlen($posted_value) > 254) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' is too long.';
                return;
            }

            // Check if the internet is available
            if ($sock = @fsockopen('www.google.com', 80)) {
                fclose($sock);
                $domain_name = substr($posted_value, $at_pos + 1);
                if (!checkdnsrr($domain_name, 'MX')) {
                    $this->form_submission_errors[$key][] = 'The ' . $label . ' field contains an invalid domain name';
                    return;
                }
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
        if ((strlen($_POST[$key]) !== $inner_value) && ($posted_value !== '')) {
            $error_msg = 'The ' . $label . ' field must be ' . $inner_value . ' characters in length.';

            if ($inner_value === 1) {
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
        extract($validation_data);
        $pos = strpos($test_to_run, '[');

        if (is_numeric($pos)) {

            if ($posted_value == '') {
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
     * Validates a file based on the provided rules.
     *
     * @param string $key The key associated with the file input.
     * @param string $label The label for the file input.
     * @param mixed $rules The rules for file validation.
     * @return void
     */
    private function validate_file(string $key, string $label, $rules): void {
        if (!isset($_FILES[$key])) {
            $this->handle_missing_file_error($key, $label);
            return;
        }
        if ($_FILES[$key]['name'] == '') {
            $this->handle_empty_file_error($key, $label);
            return;
        }
        $this->run_file_validation($key, $rules);
    }

    /**
     * Handles the error for a missing file.
     *
     * @param string $key The key associated with the file input.
     * @param string $label The label for the file input.
     * @return void
     */
    private function handle_missing_file_error(string $key, string $label): void {
        $error_msg = 'You are required to select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    /**
     * Handles the error for an empty file.
     *
     * @param string $key The key associated with the file input.
     * @param string $label The label for the file input.
     * @return void
     */
    private function handle_empty_file_error(string $key, string $label): void {
        $error_msg = 'You did not select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    /**
     * Runs the file validation logic.
     *
     * @param string $key The key associated with the file input.
     * @param mixed $rules The rules for file validation.
     * @return void
     */
    private function run_file_validation(string $key, $rules): void {
        // File validation logic here
        require_once 'File_validation.php';
        $file_validation = new File_validation;
        $file_validation->run_file_validation($key, $rules);
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
        if ($chars == 'callback_') {
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
        // Make sure they have posted csrf_token
        if (!isset($_POST['csrf_token'])) {
            $this->csrf_block_request();
        } else {
            $posted_csrf_token = $_POST['csrf_token'];
            $expected_csrf_token = $_SESSION['csrf_token'];

            if (!hash_equals($expected_csrf_token, $posted_csrf_token)) {
                $this->csrf_block_request();
            }

            unset($_POST['csrf_token']);
        }
    }

    /**
     * Blocks the request in case of a CSRF attack.
     *
     * @return void
     */
    private function csrf_block_request(): void {
        header("location: " . BASE_URL);
        die();
    }
}

class Validation_Helper extends Validation {
    //  Validation_Helper" is deprecated and will be removed in future versions. Use "Validation" instead.
}
