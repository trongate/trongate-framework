<?php
class Validation_helper {

    /** @var array Holds the form submission errors. */
    public array $form_submission_errors = [];

    /** @var array Holds the posted fields. */
    public array $posted_fields = [];

    /**
     * Set rules for form field validation.
     *
     * @param string $key The key of the form field.
     * @param string $label The label of the form field.
     * @param string $rules The rules for validation.
     * @return void
     */
    public function set_rules(string $key, string $label, string $rules): void {

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
     * Run a specific validation test based on the provided data and rules.
     *
     * @param array $validation_data The validation data including 'key', 'label', and 'posted_value'.
     * @param mixed $rules The rules for validation.
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
            case 'in_the_future':
                $this->in_the_future($validation_data);
                break;
            case 'in_the_past':
                $this->in_the_past($validation_data);
                break;
            default:
                $this->run_special_test($validation_data);
                break;
        }

    }

    /**
     * Runs the validation process.
     *
     * @param array|null $validation_array The validation array (optional).
     * @return bool|null Returns true if validation passes, false if validation fails, or null if validation array is not provided and no form submission errors exist.
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
        } elseif ($validation_array === null && count($this->form_submission_errors) === 0) {
            return null;
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

        foreach($validation_array as $key => $value) {

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
     * Builds a rules string based on the provided value.
     *
     * @param mixed $value The value to build rules from, typically an array.
     * @return string Returns the constructed rules string.
     */
    private function build_rules_str(mixed $value): string {
        $rules_str = '';
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($k !== 'label') {
                    if (is_bool($v)) {
                        $rules_str .= $k . '|';
                    } else {
                        $rules_str .= $k . '[' . $v . ']|';
                    }
                }
            }
        }

        if ($rules_str !== '') {
            $rules_str = substr($rules_str, 0, -1);
        }

        return $rules_str;
    }

    /**
     * Retrieves an array of tests to run based on the provided rules string.
     *
     * @param string $rules The string containing rules separated by '|'.
     * @return array The array of tests to run.
     */
    private function get_tests_to_run(string $rules): array {
        $tests_to_run = explode('|', $rules);
        return $tests_to_run;
    }

    /**
     * Checks for the presence of a required field.
     *
     * @param array $validation_data The array containing validation data.
     * @return void
     */
    private function check_for_required(array $validation_data): void {
        extract($validation_data);
        $posted_value = trim($validation_data['posted_value']);

        if ($posted_value == '') {
            $this->form_submission_errors[$key][] = 'The '.$label.' field is required.';  
        }

    }

    /**
     * Checks if the posted value is numeric.
     *
     * @param array $validation_data The array containing validation data.
     * @return void
     */
    private function check_for_numeric(array $validation_data): void {
        extract($validation_data);
        if ((!is_numeric($posted_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be numeric.';
        }
    }

    /**
     * Checks if the posted value is an integer.
     *
     * @param array $validation_data The array containing validation data.
     * @return void
     */
    private function check_for_integer(array $validation_data): void {
        extract($validation_data);
        if ($posted_value !== '') {

            $result = ctype_digit(strval($posted_value));

            if ($result == false) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must be an integer.';
            }

        }

    }

    /**
     * Checks if the provided value contains a decimal.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return void
     */
    private function check_for_decimal(array $validation_data): void {
        extract($validation_data);
        if ($posted_value !== '') {

            if ((float) $posted_value == floor($posted_value)) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a number with a decimal.';
            }

        }
    }

    /**
     * Validate the datepicker input.
     *
     * @param array $validation_data The validation data including 'key', 'label', and 'posted_value'.
     * @return bool Returns true if the input is a valid date, otherwise returns false.
     */
    private function valid_datepicker(array $validation_data): bool {
        extract($validation_data);
        $result = parse_date($posted_value);

        if ($result instanceof DateTime) {
            return true;
        } else {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be a valid date in the format '.DEFAULT_DATE_FORMAT.'.';
            return false;
        }
    }

    /**
     * Alias of the 'valid_datepicker' method.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns the result from 'valid_datepicker', which can be true or false.
     */
    private function valid_datepicker_us(array $validation_data): bool {
        return $this->valid_datepicker($validation_data);
    }

    /**
     * Alias of the 'valid_datepicker' method.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns the result from 'valid_datepicker', which can be true or false.
     */
    private function valid_datepicker_eu(array $validation_data): bool {
        return $this->valid_datepicker($validation_data);
    }

    /**
     * Validates the datetimepicker input.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns true if the input is a valid date and time, otherwise adds an error message and returns false.
     */
    private function valid_datetimepicker(array $validation_data): bool {
        extract($validation_data);
        $result = parse_date($posted_value);

        // Extracting date and time components
        $date_time_parts = explode(', ', $posted_value);
        $date_part = $date_time_parts[0] ?? '';
        $time_part = $date_time_parts[1] ?? '';

        // Validating date and time parts separately
        $date_result = parse_date($date_part);
        $time_result = parse_time($time_part);

        if ($result instanceof DateTime && $date_result instanceof DateTime && $time_result instanceof DateTime) {
            return true;
        } else {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be a valid date and time in the format MM/DD/YYYY, HH:ii.';
            return false;
        }
    }

    /**
     * Alias of the 'valid_datetimepicker' method.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns the result from 'valid_datetimepicker', which can be true or false.
     */
    private function valid_datetimepicker_us(array $validation_data): bool {
        return $this->valid_datetimepicker($validation_data);
    }

    /**
     * Alias of the 'valid_datetimepicker' method.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return bool Returns the result from 'valid_datetimepicker', which can be true or false.
     */
    private function valid_datetimepicker_eu(array $validation_data): bool {
        return $this->valid_datetimepicker($validation_data);
    }

    /**
     * Validates the provided time value.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return void|null Returns null if the time value is valid, otherwise adds an error message to form_submission_errors.
     */
    private function valid_time(array $validation_data): void {
        extract($validation_data);

        if ($posted_value !== '') {
            $got_error = true;
            $bits = explode(':', $posted_value);

            $num_bits = count($bits);
            $score = 0;

            if ($num_bits == 2) {
                if ((is_numeric($bits[0])) && ($bits[0] < 24)) {
                    $score++;
                }

                if ((is_numeric($bits[1])) && ($bits[1] < 60)) {
                    $score++;
                }

                if ($score == 2) {
                    $got_error = false;
                }
            }

            if ($got_error == true) {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must contain a valid time value.';
            }
        }
    }

    /**
     * Check if the submitted value is in the future.
     *
     * @param array $validation_data The validation data including 'key', 'label', and 'posted_value'.
     * @return bool Returns true if the date is in the future, false otherwise.
     */
    private function in_the_future(array $validation_data): bool {
        extract($validation_data);

        // Convert input string to a DateTime object
        $result = parse_date($posted_value);

        // Check if the result is a valid DateTime object and represents a future date
        if ($result instanceof DateTime) {
            $current_date = new DateTime(); // Get the current date and time
            if ($result > $current_date) {
                return true; // Date is in the future
            } else {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a date in the future.';
                return false;
            }
        } else {
            $this->form_submission_errors[$key][] = 'The submitted ' . $label . ' value could not be converted into a valid date object.';
            return false;
        }
    }

    /**
     * Check if the provided date is in the past.
     *
     * @param array $validation_data The validation data including 'key', 'label', and 'posted_value'.
     * @return bool Returns true if the date is in the past, false otherwise.
     */
    private function in_the_past(array $validation_data): bool {
        extract($validation_data);

        // Convert input string to a DateTime object
        $result = parse_date($posted_value);

        // Check if the result is a valid DateTime object and represents a past date
        if ($result instanceof DateTime) {
            $current_date = new DateTime(); // Get the current date and time
            if ($result < $current_date) {
                return true; // Date is in the past
            } else {
                $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be a date in the past.';
                return false;
            }
        } else {
            $this->form_submission_errors[$key][] = 'The submitted ' . $label . ' value could not be converted into a valid date object.';
            return false;
        }
    }

    /**
     * Validates if the posted value matches the target field value.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param string $target_field The target field to compare against.
     * @return void|null Returns null if the values match, otherwise adds an error message to form_submission_errors.
     */
    private function matches(string $key, string $label, string $posted_value, string $target_field): void {
        $got_error = false;

        if (!isset($_POST[$target_field])) {
            $got_error = true;
        } else {
            $target_value = $_POST[$target_field];

            if (($posted_value !== $target_value)) {
                $got_error = true;
            }

        }
        
        if ($got_error == true) {

            if (isset($this->posted_fields[$target_field])) {
                $target_field = $this->posted_fields[$target_field];
            }

           $this->form_submission_errors[$key][] = 'The '.$label.' field does not match the '.$target_field.' field.'; 
        }

    }

    /**
     * Validates if the posted value differs from the target field value.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param string $target_field The target field to compare against.
     * @return void|null Returns null if the values differ, otherwise adds an error message to form_submission_errors.
     */
    private function differs(string $key, string $label, string $posted_value, string $target_field): void {
        $got_error = false;

        $target_value = $_POST[$target_field];

        if (($posted_value == $target_value)) {
            $got_error = true;
        }

        if (isset($this->posted_fields[$target_field])) {
            $target_field = $this->posted_fields[$target_field];
        }

        if ($got_error == true) {
           $this->form_submission_errors[$key][] = 'The '.$label.' field must not match the '.$target_field.' field.'; 
        }

    }

    /**
     * Validates if the posted value meets the minimum length requirement.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param int $inner_value The minimum length required.
     * @return void|null Returns null if the length requirement is met, otherwise adds an error message to form_submission_errors.
     */
    private function min_length(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((strlen($_POST[$key]) < $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be at least ' . $inner_value . ' characters in length.';
        }
    }

    /**
     * Validates if the posted value meets the maximum length requirement.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param int $inner_value The maximum length allowed.
     * @return void|null Returns null if the length requirement is met, otherwise adds an error message to form_submission_errors.
     */
    private function max_length(string $key, string $label, string $posted_value, int $inner_value): void {
        if ((strlen($_POST[$key]) > $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The ' . $label . ' field must be no more than  ' . $inner_value . ' characters in length.';
        }
    }

    /**
     * Validates if the posted value is greater than the inner value.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param int $inner_value The value for comparison.
     * @return void|null Returns null if the value is greater than the inner value, otherwise adds an error message to form_submission_errors.
     */
    private function greater_than(string $key, string $label, string $posted_value, int $inner_value): void {
        if (((is_numeric($_POST[$key])) && ($_POST[$key]<=$inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must greater than '.$inner_value.'.';
        }
    }

    /**
     * Validates if the posted value is less than the inner value.
     *
     * @param string $key The key associated with the field.
     * @param string $label The label associated with the field.
     * @param string $posted_value The value posted from the field.
     * @param int $inner_value The value for comparison.
     * @return void|null Returns null if the value is less than the inner value, otherwise adds an error message to form_submission_errors.
     */
    private function less_than(string $key, string $label, string $posted_value, int $inner_value): void {
        if (((is_numeric($_POST[$key])) && ($_POST[$key]>=$inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must less than '.$inner_value.'.';
        }
    }

    /**
     * Validates the email input.
     *
     * @param array $validation_data The validation data containing key, label, and posted value.
     * @return void|null Returns null if the input is a valid email, otherwise adds an error message and returns null.
     */
    private function valid_email(array $validation_data): void {
        extract($validation_data);

        if ($posted_value !== '') {

            if (!filter_var($posted_value, FILTER_VALIDATE_EMAIL)) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid email address.';
                return;
            }

            // Check if the email address contains an @ symbol and a valid domain name
            $at_pos = strpos($posted_value, '@');
            if ($at_pos === false || $at_pos === 0) {
                $this->form_submission_errors[$key][] = 'The '.$label.' is not properly formatted.';
                return;
            }

            // Make sure the email address is not too long
            if (strlen($posted_value) > 254) {
                $this->form_submission_errors[$key][] = 'The '.$label.' is too long.';
                return;
            }

            // Check if the internet is available
            if($sock = @fsockopen('www.google.com', 80)) {
               fclose($sock);
                $domain_name = substr($posted_value, $at_pos + 1);
                if (!checkdnsrr($domain_name, 'MX')) {
                    $this->form_submission_errors[$key][] = 'The '.$label.' field contains an invalid domain name';
                    return;
                }
            }

        }

    }

    /**
     * Validates the exact length of the input.
     *
     * @param string $key The key associated with the input field.
     * @param string $label The label or name of the input field.
     * @param mixed $posted_value The value submitted for validation.
     * @param int $inner_value The exact length expected for the input.
     * @return void|null Returns null if the input matches the exact length, otherwise adds an error message and returns null.
     */
    private function exact_length(string $key, string $label, mixed $posted_value, int $inner_value): void {
        if((strlen($_POST[$key])!=$inner_value) && ($posted_value !== '')) {

            $error_msg = 'The '.$label.' field must be '.$inner_value.' characters in length.';

            if ($inner_value == 1) {
                $error_msg = str_replace('characters in length.', 'character in length.', $error_msg);
            }

            $this->form_submission_errors[$key][] = $error_msg;
        }
    }

    /**
     * Runs a special validation test based on the submitted data inside square brackets.
     *
     * @param array $validation_data The validation data containing key, label, posted value, and test to run.
     * @return bool Returns true if the validation is successful, otherwise returns false.
     */

    /**
     * Runs a special validation test based on the submitted data inside square brackets.
     *
     * @param array $validation_data The validation data including 'key', 'label', and 'posted_value'.
     * @return void
     */
    private function run_special_test(array $validation_data): void {
        extract($validation_data);
        $pos = strpos($test_to_run, '[');

        if (is_numeric($pos)) {

            if ($posted_value == '') {
                return; // No need to do tests since no value submitted
            }

            // Get the value between the square brackets
            $inner_value = $this->_extract_content($test_to_run, '[', ']');

            $test_name = $this->_get_test_name($test_to_run);

            switch ($test_name) {
                case 'matches':
                    $this->matches($key, $label, $posted_value, $inner_value);
                    break;
                case 'differs':
                    $this->differs($key, $label, $posted_value, $inner_value);
                    break;
                case 'min_length':
                    $this->min_length($key, $label, $posted_value, $inner_value);
                    break;
                case 'max_length':
                    $this->max_length($key, $label, $posted_value, $inner_value);
                    break;
                case 'greater_than':
                    $this->greater_than($key, $label, $posted_value, $inner_value);
                    break;
                case 'less_than':
                    $this->less_than($key, $label, $posted_value, $inner_value);
                    break;
                case 'exact_length':
                    $this->exact_length($key, $label, $posted_value, $inner_value);
                    break;
            }

        } else {
            $this->attempt_invoke_callback($key, $label, $posted_value, $test_to_run);
        }
    }

    /**
     * Extracts content from a string between specified start and end strings.
     *
     * @param string $string The string to extract content from.
     * @param string $start The starting string.
     * @param string $end The ending string.
     * @return string Returns the extracted content between start and end strings.
     */
    private function _extract_content(string $string, string $start, string $end): string {
        $pos = stripos($string, $start);
        $str = substr($string, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        $content = trim($str_three); // remove whitespaces
        return $content;
    }

    /**
     * Extracts the test name from a provided string.
     *
     * @param string $test_to_run The string containing test name and possibly inner value.
     * @return string Returns the extracted test name.
     */
    private function _get_test_name(string $test_to_run): string { 
        $pos = stripos($test_to_run, '[');
        $test_name = substr($test_to_run, 0, $pos);
        return $test_name;
    }

    /**
     * Validates the file input based on specified rules.
     *
     * @param string $key The key representing the file input.
     * @param string $label The label or description of the file input.
     * @param mixed $rules The rules to validate the file against.
     * @return void
     */
    private function validate_file(string $key, string $label, $rules): void {
        if(!isset($_FILES[$key])) {
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
     * @param string $key The key representing the file input.
     * @param string $label The label or description of the file input.
     * @return void
     */
    private function handle_missing_file_error(string $key, string $label): void {
        $error_msg = 'You are required to select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    /**
     * Handles the error for an empty file.
     *
     * @param string $key The key representing the file input.
     * @param string $label The label or description of the file input.
     * @return void
     */
    private function handle_empty_file_error(string $key, string $label): void {
        $error_msg = 'You did not select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    /**
     * Runs the validation for a file based on specified rules.
     *
     * @param string $key The key representing the file input.
     * @param mixed $rules The rules to validate the file against.
     * @return void
     */
    private function run_file_validation(string $key, $rules): void {
        // file validation logic here
        require_once('file_validation_helper.php');
    }

    /**
     * Attempts to invoke a callback method for validation purposes.
     *
     * @param string $key The key representing the field being validated.
     * @param string $label The label or description of the field being validated.
     * @param mixed $posted_value The value posted for validation.
     * @param string $test_to_run The test or callback method to execute for validation.
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
                
                $static_check = new ReflectionMethod($target_module,$target_method); 
                if($static_check->isStatic())
                {
                    // STATIC METHOD
                    $outcome = $target_module::$target_method($posted_value);
                }
                else
                {
                    // INSTANTIATED
                    $callback = new $target_module;
                    $outcome = $callback->$target_method($posted_value);
                }

                if (gettype($outcome) == 'string') {
                    $outcome = str_replace('{label}', $label, $outcome);
                    $this->form_submission_errors[$key][] = $outcome;
                }

            }

        }

    }

    /**
     * Get the value of a segment from the URL.
     *
     * @param int $num The segment number to retrieve.
     * @return string The value of the segment. Returns an empty string if the segment is not set.
     */
    public function url_segment(int $num): string {
        $segments = SEGMENTS;
        
        if (isset($segments[$num])) {
            $value = $segments[$num];
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Protect the form from Cross-Site Request Forgery (CSRF) attacks.
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
     * Redirects the user to the base URL and terminates the script.
     */
    private function csrf_block_request(): void {
        header("location: ".BASE_URL);
        die();
    }    

}

/**
 * Get validation errors as a string.
 *
 * @param string|null $opening_html The opening HTML tag. Default is null.
 * @param string|null $closing_html The closing HTML tag. Default is null.
 * @return string The validation errors as a string.
 */
function validation_errors($opening_html = null, $closing_html = null) {

    if (isset($_SESSION['form_submission_errors'])) {

        $validation_err_str = '';
        $validation_errors = [];
        $closing_html = (isset($closing_html)) ? $closing_html : false;
        $form_submission_errors = $_SESSION['form_submission_errors'];

        if ((isset($opening_html)) && (gettype($closing_html) == 'boolean')) {
            //build individual form field validation error(s)
            if (isset($form_submission_errors[$opening_html])) {
                $validation_err_str.= '<div class="validation-error-report">';
                $form_field_errors = $form_submission_errors[$opening_html];
                foreach($form_field_errors as $validation_error) {
                    $validation_err_str.= '<div>&#9679; '.$validation_error.'</div>';
                }
                $validation_err_str.= '</div>';
            }

            return $validation_err_str;

        } else {
            //normal error reporting
            foreach($form_submission_errors as $key => $form_field_errors) {
                foreach($form_field_errors as $form_field_error) {
                    $validation_errors[] = $form_field_error;
                }
            }

            if (!isset($opening_html)) {

                if (defined('ERROR_OPEN') && defined('ERROR_CLOSE')) {
                    $opening_html = ERROR_OPEN;
                    $closing_html = ERROR_CLOSE;
                } else {
                    $opening_html = '<p style="color: red;">';
                    $closing_html = '</p>';
                }

            }

            foreach($validation_errors as $form_submission_error) {
                $validation_err_str.= $opening_html.$form_submission_error.$closing_html;
            }

            unset($_SESSION['form_submission_errors']);
        }
        echo $validation_err_str;
    }

}