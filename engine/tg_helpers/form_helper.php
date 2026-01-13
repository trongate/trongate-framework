<?php
/**
 * Generates an HTML input element.
 *
 * @param string $type The type of input element.
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the input element. Default is null.
 * @param bool|string|null $checked Whether the input element should be checked (for radio/checkbox). Default is false.
 * @param array $attributes An associative array of HTML attributes for the input. Default is empty array.
 * @return string The generated HTML input element.
 */
function generate_input_element(string $type, string $name, ?string $value = null, bool|string|null $checked = false, array $attributes = []): string {
    $attributes['type'] = $type;
    $attributes['name'] = $name;
    
    if ($value !== null) {
        $attributes['value'] = $value;
    }
    
    if ($type === 'radio' || $type === 'checkbox') {
        $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($is_checked === true) {
            $attributes['checked'] = 'checked';
        }
    }
    
    $html = '<input' . get_attributes_str($attributes);
    return $html . '>';
}

/**
 * Generates a checkbox form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|bool|int $value The value attribute for the input element. Defaults to '1'.
 * @param mixed $checked Whether the checkbox should be checked. Accepts true, 'true', 1, '1', 'on', etc.
 * @param array $attributes Additional attributes for the input element as an associative array.
 * @return string The generated HTML input element.
 * 
 * @example form_checkbox('agree', 1, true) // Checked checkbox with value '1'
 * @example form_checkbox('newsletter', 'yes', post('newsletter')) // With posted value
 * @example form_checkbox('active') // Unchecked checkbox with default value '1'
 * @example form_checkbox('featured', 1, (bool) $record->is_featured) // From database record
 */
function form_checkbox(string $name, string|bool|int $value = '1', mixed $checked = false, array $attributes = []): string {
    // Convert value to string
    $value = (string) $value;
    
    // Validate and convert checked state to boolean
    $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    
    // Generate the checkbox input element
    return generate_input_element('checkbox', $name, $value, $is_checked, $attributes);
}

/**
 * Generates a radio button form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|bool|int $value The value attribute for the input element.
 * @param mixed $checked Whether the radio button should be checked. Accepts true, 'true', 1, '1', 'on', etc.
 * @param array $attributes Additional attributes for the input element as an associative array.
 * @return string The generated HTML input element.
 * 
 * @example form_radio('color', 'red', post('color') === 'red') // Compare with posted value
 * @example form_radio('size', 'large', $selected_size === 'large') // Compare with variable
 * @example form_radio('option', 'yes', true) // Checked radio button
 */
function form_radio(string $name, string|bool|int $value = '', mixed $checked = false, array $attributes = []): string {    
    // Convert value to string
    $value = (string) $value;
    
    // Validate and convert checked state to boolean
    $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    
    // Generate the radio input element
    return generate_input_element('radio', $name, $value, $is_checked, $attributes);
}

/**
 * Generates a text input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_input(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('text', $name, $value, false, $attributes);
}

/**
 * Generates an email input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_email(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('email', $name, $value, false, $attributes);
}

/**
 * Generates a password input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_password(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('password', $name, $value, false, $attributes);
}

/**
 * Generates a search input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_search(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('search', $name, $value, false, $attributes);
}

/**
 * Generates a number input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|int|float|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_number(string $name, string|int|float|null $value = null, array $attributes = []): string {
    return generate_input_element('number', $name, $value, false, $attributes);
}

/**
 * Generates a hidden input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|int|float|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_hidden(string $name, string|int|float|null $value = null, array $attributes = []): string {
    return generate_input_element('hidden', $name, $value, false, $attributes);
}

/**
 * Generate the opening tag for an HTML form.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form.
 */
function form_open(string $location, array $attributes = []): string {
    $extra = '';
    $method = 'post';

    if (isset($attributes['method'])) {
        $method = $attributes['method'];
        unset($attributes['method']);
    }
    foreach ($attributes as $key => $value) {
        $extra .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    if (!filter_var($location, FILTER_VALIDATE_URL) && strpos($location, '/') !== 0) {
        $location = BASE_URL . $location;
    }

    return '<form action="' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '" method="' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '"' . $extra . '>';
}

/**
 * Generate the opening tag for an HTML form with file upload support.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
 */
function form_open_upload(string $location, array $attributes = []): string {
    $attributes['enctype'] = 'multipart/form-data';
    return form_open($location, $attributes);
}

/**
 * Generates hidden CSRF token input field and a closing form tag.
 *
 * @return string The HTML closing tag for the form.
 */
function form_close(): string {
    // Ensure a CSRF token exists in the session
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Generate the hidden CSRF token input
    $html = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
    $html .= '</form>';
    
    // Check if form submission errors exist
    if (isset($_SESSION['form_submission_errors'])) {
        // Inject the errors as JSON
        $errors_json = json_encode($_SESSION['form_submission_errors'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $html .= '<script>window.trongateValidationErrors = ' . $errors_json . ';</script>';
        
        // Inject the validation JavaScript
        $js_code = file_get_contents(APPPATH . 'engine/tg_helpers/injectables/js/highlight_validation_errors.js');
        $js_code = str_replace('{{BASE_URL}}', BASE_URL, $js_code);
        $html .= '<script>' . $js_code . '</script>';
        
        // Clear the session errors
        unset($_SESSION['form_submission_errors']);
    }
    
    return $html;
}

/**
 * Get a string representation of HTML attributes from an associative array.
 *
 * @param array|null $attributes An associative array of HTML attributes.
 * @return string A string representation of HTML attributes.
 */
function get_attributes_str($attributes): string {
    if (!is_array($attributes) || empty($attributes)) {
        return '';
    }

    $attributes_str = '';
    foreach ($attributes as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        if ($value === true) {
            $attributes_str .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        } else {
            $attributes_str .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    return $attributes_str;
}

/**
 * Generate an HTML label element.
 *
 * @param string $label_text The text or HTML to be used as the label content.
 * @param array $attributes An associative array of HTML attributes for the label element. Defaults to empty array.
 * @return string The generated HTML label element with attributes.
 * 
 * Note: The label_text is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_label(string $label_text, array $attributes = []): string {
    $attributes_str = get_attributes_str($attributes);
    return '<label' . $attributes_str . '>' . $label_text . '</label>';
}

/**
 * Generate an HTML textarea element.
 *
 * @param string $name The name attribute for the textarea element.
 * @param string|null $value The initial value of the textarea. If not provided, it will be empty.
 * @param array $attributes An associative array of HTML attributes for the textarea.
 * @return string The generated HTML textarea element.
 */
function form_textarea(string $name, ?string $value = null, array $attributes = []): string {
    $attributes['name'] = $name;
    
    $html = '<textarea' . get_attributes_str($attributes) . '>' . htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>';
    
    return $html;
}

/**
 * Generates a date input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_date(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('date', $name, $value, false, $attributes);
}

/**
 * Generates a datetime-local input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element in YYYY-MM-DDTHH:MM format. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_datetime_local(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('datetime-local', $name, $value, false, $attributes);
}

/**
 * Generates a time input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element in HH:MM or HH:MM:SS format. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_time(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('time', $name, $value, false, $attributes);
}

/**
 * Generates a month input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element in YYYY-MM format. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_month(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('month', $name, $value, false, $attributes);
}

/**
 * Generates a week input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element in YYYY-W## format. Default is null.
 * @param array $attributes Additional attributes for the input element as an associative array. Default is empty array.
 * @return string The generated HTML input element.
 */
function form_week(string $name, ?string $value = null, array $attributes = []): string {
    return generate_input_element('week', $name, $value, false, $attributes);
}

/**
 * Generate an HTML submit button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, defaults to "Submit".
 * @param array $attributes An associative array of HTML attributes for the button.
 * @return string The generated HTML submit button element.
 * 
 * Note: The value is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_submit(string $name, ?string $value = null, array $attributes = []): string {
    $value = $value ?? 'Submit';  // FIX: Ensure value is never null
    
    $attributes['type'] = 'submit';
    $attributes['name'] = $name;
    $attributes['value'] = $value;
    
    $html = '<button' . get_attributes_str($attributes) . '>' . $value . '</button>';
    
    return $html;
}

/**
 * Generate an HTML button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, defaults to "Submit".
 * @param array $attributes An associative array of HTML attributes for the button.
 * @return string The generated HTML button element.
 * 
 * Note: The value is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_button(string $name, ?string $value = null, array $attributes = []): string {
    $attributes['type'] = 'button';
    $attributes['name'] = $name;
    $value = $value ?? 'Submit';
    
    $html = '<button' . get_attributes_str($attributes) . '>' . $value . '</button>';
    
    return $html;
}

/**
 * Generate an HTML select menu.
 *
 * @param string $name The name attribute for the select element.
 * @param array<string|int,string> $options Associative array of options where keys are values and values are display text.
 * @param string|int|null $selected_key The key of the selected option. Can be string or integer.
 * @param array $attributes An array of HTML attributes for the select element.
 * @return string The generated HTML select menu.
 * 
 * Note: Ensure proper sanitization of user-generated content passed to this function.
 */
function form_dropdown(string $name, array $options, string|int|null $selected_key = null, array $attributes = []): string {
    $attributes['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    
    $html = '<select' . get_attributes_str($attributes) . ">\n";

    foreach ($options as $option_key => $option_value) {
        $option_attributes = ['value' => htmlspecialchars((string)$option_key, ENT_QUOTES, 'UTF-8')];
        if ($selected_key !== null && (string)$option_key === (string)$selected_key) {
            $option_attributes['selected'] = 'selected';
        }
        $html .= '    <option' . get_attributes_str($option_attributes) . '>' 
               . htmlspecialchars((string)$option_value, ENT_QUOTES, 'UTF-8') . "</option>\n";
    }

    $html .= '</select>';
    return $html;
}

/**
 * Generate an HTML file input element.
 *
 * @param string $name The name attribute for the file input.
 * @param array $attributes An array of HTML attributes for the file input.
 * @return string The generated HTML for the file input element.
 */
function form_file_select(string $name, array $attributes = []): string {
    return generate_input_element('file', $name, null, false, $attributes);
}

/**
 * Retrieve a specific value or the entire request payload.
 *
 * Accepts application/x-www-form-urlencoded, multipart/form-data, or
 * application/json input. Nested keys may be addressed with dot notation
 * (user.profile.name) or bracket notation (user[profile][name]).
 *
 * Values keep their original type unless casting is explicitly requested.
 * Missing keys yield an empty string.
 *
 * @param string|bool|null $field_name   Key to fetch, or null/true for all data
 * @param bool             $clean_up     Trim and collapse whitespace
 * @param bool             $cast_numeric Allow numeric strings to become int|float
 *
 * @return string|int|float|array
 * @throws Exception If JSON input is malformed
 */
function post(
    string|bool|null $field_name = null,
    bool $clean_up = false,
    bool $cast_numeric = false
): string|int|float|array {

    static $request_data = null;

    /* ---------- One-time parse ---------- */
    if ($request_data === null) {
        $content_type   = $_SERVER['CONTENT_TYPE'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';

        $is_json = stripos($content_type, 'application/json') !== false;

        if ($request_method === 'POST' && !$is_json) {
            $request_data = $_POST;
        } else {
            $raw = file_get_contents('php://input');
            if ($raw === '' || $raw === false) {
                $request_data = [];
            } elseif ($is_json) {
                $request_data = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON: ' . json_last_error_msg());
                }
            } else {
                parse_str($raw, $request_data);
            }
        }
        $request_data = $request_data ?? [];
    }

    /* ---------- Return whole payload ---------- */
    if (is_null($field_name) || is_bool($field_name)) {
        $output = $request_data;

        if ($field_name === true || $clean_up === true) {
            array_walk_recursive($output, function (&$item) {
                if (is_string($item)) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                }
            });
        }

        return $output;
    }

    /* ---------- Fetch single key ---------- */
    $value = '';

    // Dot notation
    if (strpos($field_name, '.') !== false) {
        $keys  = explode('.', $field_name);
        $level = $request_data;
        foreach ($keys as $key) {
            if (is_array($level) && array_key_exists($key, $level)) {
                $level = $level[$key];
            } else {
                return '';
            }
        }
        $value = $level;
    }
    // Bracket notation
    elseif (preg_match_all('/(?:^[^\[]+)|\[[^\]]*\]/', $field_name, $matches) > 1) {
        $level = $request_data;
        foreach ($matches[0] as $part) {
            $key = trim($part, '[]');
            if ($key === '') {
                break;
            }
            if (is_array($level) && array_key_exists($key, $level)) {
                $level = $level[$key];
            } else {
                return '';
            }
        }
        $value = $level;
    }
    // Simple key
    else {
        $value = array_key_exists($field_name, $request_data) ? $request_data[$field_name] : '';
    }

    if ($value === '') {
        return '';
    }

    /* ---------- Cleanup ---------- */
    if ($clean_up) {
        if (is_string($value)) {
            $value = trim(preg_replace('/\s+/', ' ', $value));
        } elseif (is_array($value)) {
            array_walk_recursive($value, function (&$item) {
                if (is_string($item)) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                }
            });
        }
    }

    /* ---------- Optional numeric cast ---------- */
    if ($cast_numeric && is_string($value) && is_numeric($value) && !str_starts_with($value, '0')) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : (float) $value;
    }

    return $value;
}

/**
 * Generates and returns validation error messages in HTML or JSON format.
 *
 * @param string|int|null $first_arg Optional HTML to open each error message, HTTP status code for JSON output, or null.
 * @param string|null $closing_html Optional HTML to close each error message.
 * @return string|null Returns a string of formatted validation errors or null if no errors are present.
 */
function validation_errors(string|int|null $first_arg = null, ?string $closing_html = null): ?string {
    if (!isset($_SESSION['form_submission_errors'])) {
        return null;
    }

    $form_submission_errors = $_SESSION['form_submission_errors'];

    if (is_int($first_arg) && $first_arg >= 400 && $first_arg <= 499) {
        return json_validation_errors($form_submission_errors, $first_arg);
    }

    if (isset($first_arg) && !isset($closing_html)) {
        return inline_validation_errors($form_submission_errors, $first_arg);
    }

    return general_validation_errors($form_submission_errors, $first_arg, $closing_html);
}

/**
 * Generates JSON-formatted validation errors and sends an HTTP response.
 *
 * @param array $errors The validation errors.
 * @param int $status_code The HTTP status code to send.
 * @return never
 */
function json_validation_errors(array $errors, int $status_code): never {
    $json_errors = array_map(
        fn($field, $messages) => ['field' => $field, 'messages' => $messages],
        array_keys($errors),
        $errors
    );

    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($json_errors, JSON_THROW_ON_ERROR);
    unset($_SESSION['form_submission_errors']);
    exit();
}

/**
 * Generates inline validation errors for a specific field.
 *
 * @param array $errors The validation errors.
 * @param string $field The field to display errors for.
 * @return string The formatted inline validation errors.
 */
function inline_validation_errors(array $errors, string $field): string {
    if (!isset($errors[$field])) {
        return '';
    }

    $validation_err_str = '<div class="validation-error-report">';
    foreach ($errors[$field] as $validation_error) {
        $validation_err_str .= '<div>&#9679; ' . htmlspecialchars($validation_error, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    $validation_err_str .= '</div>';

    return $validation_err_str;
}

/**
 * Generates general validation errors.
 *
 * @param array $errors The validation errors.
 * @param string|null $opening_html HTML to open each error message.
 * @param string|null $closing_html HTML to close each error message.
 * @return string The formatted general validation errors.
 */
function general_validation_errors(array $errors, ?string $opening_html = null, ?string $closing_html = null): string {
    if (!isset($opening_html, $closing_html)) {
        $opening_html = defined('ERROR_OPEN') ? ERROR_OPEN : '<p style="color: red;">';
        $closing_html = defined('ERROR_CLOSE') ? ERROR_CLOSE : '</p>';
    }

    $validation_err_str = '';
    foreach ($errors as $field_errors) {
        foreach ($field_errors as $error) {
            $validation_err_str .= $opening_html . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . $closing_html;
        }
    }

    unset($_SESSION['form_submission_errors']);
    return $validation_err_str;
}