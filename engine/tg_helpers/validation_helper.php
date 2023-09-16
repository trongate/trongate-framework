<?php

declare(strict_types=1);

class Validation_helper
{
    public $form_submission_errors = [];

    public $posted_fields = [];

    public function set_rules($key, $label, $rules): void
    {
        if (! isset($_POST[$key]) && (isset($_FILES[$key]))) {
            if (! isset($_POST[$key])) {
                $_POST[$key] = '';
            }

            $posted_value = $_FILES[$key];
            $tests_to_run[] = 'validate_file';
        } else {
            if (isset($_POST[$key])) {
                $_POST[$key] = trim($_POST[$key]);
            }

            $posted_value = $_POST[$key] ?? '';
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

    public function run($validation_array = null)
    {
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
        }
        return true;
    }

    public function url_segment($num)
    {
        $segments = SEGMENTS;

        if (isset($segments[$num])) {
            $value = $segments[$num];
        } else {
            $value = '';
        }

        return $value;
    }

    private function run_validation_test($validation_data, $rules = null): void
    {
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
            case 'valid_datepicker_us':
                $this->valid_datepicker_us($validation_data);
                break;
            case 'valid_datepicker_eu':
                $this->valid_datepicker_eu($validation_data);
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

    private function process_validation_array($validation_array): void
    {
        foreach ($validation_array as $key => $value) {
            if (isset($value['label'])) {
                $label = $value['label'];
            } else {
                $label = str_replace('_', ' ', $key);
            }

            if (! isset($_POST[$key]) && (isset($_FILES[$key]))) {
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

    private function build_rules_str($value)
    {
        $rules_str = '';
        if (gettype($value) === 'array') {
            foreach ($value as $k => $v) {
                if ($k !== 'label') {
                    if (gettype($v) === 'boolean') {
                        $rules_str .= $k.'|';
                    } else {
                        $rules_str .= $k.'['.$v.']|';
                    }
                }
            }
        }

        if ($rules_str !== '') {
            $rules_str = substr($rules_str, 0, -1);
        }

        return $rules_str;
    }

    private function get_tests_to_run($rules)
    {
        return explode('|', $rules);
    }

    private function check_for_required($validation_data): void
    {
        extract($validation_data);
        $posted_value = trim($validation_data['posted_value']);

        if ($posted_value === '') {
            $this->form_submission_errors[$key][] = 'The '.$label.' field is required.';
        }
    }

    private function check_for_numeric($validation_data): void
    {
        extract($validation_data);
        if ((! is_numeric($posted_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be numeric.';
        }
    }

    private function check_for_integer($validation_data): void
    {
        extract($validation_data);
        if ($posted_value !== '') {
            $result = ctype_digit(strval($posted_value));

            if ($result === false) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must be an integer.';
            }
        }
    }

    private function check_for_decimal($validation_data): void
    {
        extract($validation_data);
        if ($posted_value !== '') {
            if ((float) $posted_value === floor($posted_value)) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a number with a decimal.';
            }
        }
    }

    private function valid_datepicker_us($validation_data)
    {
        extract($validation_data);
        if ($posted_value !== '') {
            try {
                $posted_date = new DateTime($posted_value);

                return true;
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid datepicker value of the format mm-dd-yyyy.';
            }
        }
    }

    private function valid_datepicker_eu($validation_data)
    {
        extract($validation_data);
        if ($posted_value !== '') {
            $day = substr($posted_value, 0, 2);
            $month = substr($posted_value, 3, 2);
            $year = substr($posted_value, 6, 4);

            $posted_value = $month.'/'.$day.'/'.$year;

            try {
                $posted_date = new DateTime($posted_value);

                return true;
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid datepicker value of the format dd-mm-yyyy.';
            }
        }
    }

    private function valid_datetimepicker_us($validation_data)
    {
        extract($validation_data);
        if ($posted_value !== '') {
            try {
                $posted_date = str_replace(' at ', ' ', $posted_value);
                $posted_date = new DateTime($posted_date);

                return true;
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid datetime picker value.';
            }
        }
    }

    private function valid_datetimepicker_eu($validation_data)
    {
        extract($validation_data);
        if ($posted_value !== '') {
            try {
                $posted_date = str_replace(' at ', ' ', $posted_value);
                $day = substr($posted_value, 0, 2);
                $month = substr($posted_value, 3, 2);
                $year = substr($posted_value, 6, 4);

                $posted_value = $month.'/'.$day.'/'.$year;

                $posted_date = new DateTime($posted_value);

                return true;
            } catch (Exception $e) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid datetime picker value.';
            }
        }
    }

    private function valid_time($validation_data): void
    {
        extract($validation_data);
        if ($posted_value !== '') {
            $got_error = true;

            $bits = explode(':', $posted_value);

            $num_bits = count($bits);
            $score = 0;
            if ($num_bits === 2) {
                if (is_numeric($bits[0]) && ($bits[0] < 24)) {
                    $score++;
                }

                if (is_numeric($bits[1]) && ($bits[1] < 60)) {
                    $score++;
                }

                if ($score === 2) {
                    $got_error = false;
                }
            }

            if ($got_error === true) {
                $this->form_submission_errors[$key][] = 'The '.$label.' field must contain a valid time value.';
            }
        }
    }

    private function matches($key, $label, $posted_value, $target_field): void
    {
        $got_error = false;

        if (! isset($_POST[$target_field])) {
            $got_error = true;
        } else {
            $target_value = $_POST[$target_field];

            if (($posted_value !== $target_value)) {
                $got_error = true;
            }
        }

        if ($got_error === true) {
            if (isset($this->posted_fields[$target_field])) {
                $target_field = $this->posted_fields[$target_field];
            }

            $this->form_submission_errors[$key][] = 'The '.$label.' field does not match the '.$target_field.' field.';
        }
    }

    private function differs($key, $label, $posted_value, $target_field): void
    {
        //returns false if form element does not differ from the one in the parameter.
        $got_error = false;

        $target_value = $_POST[$target_field];

        if (($posted_value === $target_value)) {
            $got_error = true;
        }

        if (isset($this->posted_fields[$target_field])) {
            $target_field = $this->posted_fields[$target_field];
        }

        if ($got_error === true) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must not match the '.$target_field.' field.';
        }
    }

    private function min_length($key, $label, $posted_value, $inner_value): void
    {
        if ((strlen($_POST[$key]) < $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be at least '.$inner_value.' characters in length.';
        }
    }

    private function max_length($key, $label, $posted_value, $inner_value): void
    {
        if ((strlen($_POST[$key]) > $inner_value) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must be no more than  '.$inner_value.' characters in length.';
        }
    }

    private function greater_than($key, $label, $posted_value, $inner_value): void
    {
        if ((is_numeric($_POST[$key]) && ($_POST[$key] <= $inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must greater than '.$inner_value.'.';
        }
    }

    private function less_than($key, $label, $posted_value, $inner_value): void
    {
        if ((is_numeric($_POST[$key]) && ($_POST[$key] >= $inner_value)) && ($posted_value !== '')) {
            $this->form_submission_errors[$key][] = 'The '.$label.' field must less than '.$inner_value.'.';
        }
    }

    private function valid_email($validation_data): void
    {
        extract($validation_data);

        if ($posted_value !== '') {
            if (! filter_var($posted_value, FILTER_VALIDATE_EMAIL)) {
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
            if ($sock = @fsockopen('www.google.com', 80)) {
                fclose($sock);
                $domain_name = substr($posted_value, $at_pos + 1);
                if (! checkdnsrr($domain_name, 'MX')) {
                    $this->form_submission_errors[$key][] = 'The '.$label.' field contains an invalid domain name';

                    return;
                }
            }
        }
    }

    private function exact_length($key, $label, $posted_value, $inner_value): void
    {
        if ((strlen($_POST[$key]) !== $inner_value) && ($posted_value !== '')) {
            $error_msg = 'The '.$label.' field must be '.$inner_value.' characters in length.';

            if ($inner_value === 1) {
                $error_msg = str_replace('characters in length.', 'character in length.', $error_msg);
            }

            $this->form_submission_errors[$key][] = $error_msg;
        }
    }

    private function run_special_test($validation_data)
    {
        extract($validation_data);
        $pos = strpos($test_to_run, '[');

        if (is_numeric($pos)) {
            if ($posted_value === '') {
                return true; //no need to do tests since no value submitted
            }

            //get the value between the square brackets
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

    private function _extract_content($string, $start, $end)
    {
        $pos = stripos($string, $start);
        $str = substr($string, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        return trim($str_three);
    }

    private function _get_test_name($test_to_run)
    {
        $pos = stripos($test_to_run, '[');
        return substr($test_to_run, 0, $pos);
    }

    private function validate_file($key, $label, $rules): void
    {
        if (! isset($_FILES[$key])) {
            $this->handle_missing_file_error($key, $label);

            return;
        }
        if ($_FILES[$key]['name'] === '') {
            $this->handle_empty_file_error($key, $label);

            return;
        }
        $this->run_file_validation($key, $rules);
    }

    private function handle_missing_file_error($key, $label): void
    {
        $error_msg = 'You are required to select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    private function handle_empty_file_error($key, $label): void
    {
        $error_msg = 'You did not select a file.';
        $this->form_submission_errors[$key][] = $error_msg;
    }

    private function run_file_validation($key, $rules): void
    {
        // file validation logic here
        require_once 'file_validation_helper.php';
    }

    private function attempt_invoke_callback($key, $label, $posted_value, $test_to_run): void
    {
        $chars = substr($test_to_run, 0, 9);
        if ($chars === 'callback_') {
            $target_module = ucfirst($this->url_segment(1));
            $target_method = str_replace('callback_', '', $test_to_run);

            if (! class_exists($target_module)) {
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
                    $callback = new $target_module();
                    $outcome = $callback->$target_method($posted_value);
                }

                if (gettype($outcome) === 'string') {
                    $outcome = str_replace('{label}', $label, $outcome);
                    $this->form_submission_errors[$key][] = $outcome;
                }
            }
        }
    }

    private function csrf_protect(): void
    {
        //make sure they have posted csrf_token
        if (! isset($_POST['csrf_token'])) {
            $this->csrf_block_request();
        } else {
            $result = password_verify(session_id(), $_POST['csrf_token']);
            if ($result === false) {
                $this->csrf_block_request();
            }

            unset($_POST['csrf_token']);
        }
    }

    private function csrf_block_request(): void
    {
        header('location: '.BASE_URL);
        exit;
    }
}

function validation_errors($opening_html = null, $closing_html = null)
{
    if (isset($_SESSION['form_submission_errors'])) {
        $validation_err_str = '';
        $validation_errors = [];
        $closing_html = $closing_html ?? false;
        $form_submission_errors = $_SESSION['form_submission_errors'];

        if (isset($opening_html) && (gettype($closing_html) === 'boolean')) {
            //build individual form field validation error(s)
            if (isset($form_submission_errors[$opening_html])) {
                $validation_err_str .= '<div class="validation-error-report">';
                $form_field_errors = $form_submission_errors[$opening_html];
                foreach ($form_field_errors as $validation_error) {
                    $validation_err_str .= '<div>&#9679; '.$validation_error.'</div>';
                }
                $validation_err_str .= '</div>';
            }

            return $validation_err_str;
        }
        //normal error reporting
        foreach ($form_submission_errors as $key => $form_field_errors) {
            foreach ($form_field_errors as $form_field_error) {
                $validation_errors[] = $form_field_error;
            }
        }

        if (! isset($opening_html)) {
            if (defined('ERROR_OPEN') && defined('ERROR_CLOSE')) {
                $opening_html = ERROR_OPEN;
                $closing_html = ERROR_CLOSE;
            } else {
                $opening_html = '<p style="color: red;">';
                $closing_html = '</p>';
            }
        }

        foreach ($validation_errors as $form_submission_error) {
            $validation_err_str .= $opening_html.$form_submission_error.$closing_html;
        }

        unset($_SESSION['form_submission_errors']);

        echo $validation_err_str;
    }
}
