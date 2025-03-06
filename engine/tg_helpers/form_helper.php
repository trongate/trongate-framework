<?php

/**
 * Generates an HTML input element.
 *
 * @param string $type The type of input element.
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the input element. Default is null.
 * @param bool|string|null $checked Whether the input element should be checked (for radio/checkbox). Default is false.
 * @param array|null $attributes An associative array of HTML attributes for the input. Default is null.
 * @return string The generated HTML input element.
 */
function generate_input_element(string $type, string $name, ?string $value = null, bool|string|null $checked = false, ?array $attributes = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = $type;
    $attributes['name'] = $name;
    
    if ($value !== null) {
        $attributes['value'] = $value;
    }
    
    if (($type === 'radio' || $type === 'checkbox') && 
        ($checked === true || $checked === '1' || $checked === 1 || strtolower($checked) === 'on')) {
        $attributes['checked'] = 'checked';
    }
    
    $html = '<input' . get_attributes_str($attributes);
    return $html . '>';
}

/**
 * Generates a checkbox form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|bool|int|null $value The value attribute for the input element. Defaults to '1'.
 * @param mixed $checked Whether the checkbox should be checked. Accepts true, 'true', 1, '1', 'on', etc.
 * @param array|null $attributes Additional attributes for the input element as an associative array.
 * @return string The generated HTML input element.
 */
function form_checkbox(string $name, string|bool|int|null $value = null, mixed $checked = false, ?array $attributes = null): string {
    // Convert value to string, defaulting to '1' if null
    $value = $value !== null ? (string) $value : '1';
    
    // Validate and convert checked state to boolean
    $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    
    // Generate the checkbox input element
    return generate_input_element('checkbox', $name, $value, $is_checked, $attributes);
}

/**
 * Generates a radio button form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|bool|int|null $value The value attribute for the input element.
 * @param mixed $checked Whether the radio button should be checked. Accepts true, 'true', 1, '1', 'on', etc.
 * @param array|null $attributes Additional attributes for the input element as an associative array.
 * @return string The generated HTML input element.
 */
function form_radio(string $name, string|bool|int|null $value = null, mixed $checked = false, ?array $attributes = null): string {    
    // Convert value to string if not null
    $value = $value !== null ? (string) $value : null;
    
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
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_input(string $name, ?string $value = null, ?array $attributes = null): string {
    return generate_input_element('text', $name, $value, false, $attributes);
}

/**
 * Generates an email input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_email(string $name, ?string $value = null, ?array $attributes = null): string {
    return generate_input_element('email', $name, $value, false, $attributes);
}

/**
 * Generates a password input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_password(string $name, ?string $value = null, ?array $attributes = null): string {
    return generate_input_element('password', $name, $value, false, $attributes);
}

/**
 * Generates a search input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_search(string $name, ?string $value = null, ?array $attributes = null): string {
    return generate_input_element('search', $name, $value, false, $attributes);
}

/**
 * Generates a number input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|int|float|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_number(string $name, string|int|float|null $value = null, ?array $attributes = null): string {
    return generate_input_element('number', $name, $value, false, $attributes);
}

/**
 * Generates a hidden input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|int|float|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_hidden(string $name, string|int|float|null $value = null, ?array $attributes = null): string {
    return generate_input_element('hidden', $name, $value, false, $attributes);
}

/**
 * Generate the opening tag for an HTML form.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form.
 */
function form_open(string $location, ?array $attributes = null): string {
    $extra = '';
    $method = 'post';

    if (is_array($attributes)) {
        if (isset($attributes['method'])) {
            $method = $attributes['method'];
            unset($attributes['method']);
        }
        foreach ($attributes as $key => $value) {
            $extra .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
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
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
 */
function form_open_upload(string $location, ?array $attributes = null): string {
    $attributes = is_array($attributes) ? $attributes : [];
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
        // Pass the error array directly to highlight_validation_errors
        $html .= highlight_validation_errors($_SESSION['form_submission_errors']);
        
        // Clear the session errors after processing
        unset($_SESSION['form_submission_errors']);
    }

    return $html;
}

    /**
     * Generates a date input form field element.
     *
     * @param string $name The name attribute for the input element.
     * @param string|null $value The value attribute for the input element. Default is null.
     * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
     * @param string|null $additional_code Additional HTML code to be included. Default is null.
     * @return string The generated HTML input element.
     */
    function form_date(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
        return generate_input_element('date', $name, $value, false, $attributes, $additional_code);
    }

    /**
     * Generates a datetime-local input form field element.
     *
     * @param string $name The name attribute for the input element.
     * @param string|null $value The value attribute for the input element. Default is null.
     * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
     * @param string|null $additional_code Additional HTML code to be included. Default is null.
     * @return string The generated HTML input element.
     */
    function form_datetime(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
        return generate_input_element('datetime-local', $name, $value, false, $attributes, $additional_code);
    }

/**
 * Highlight validation errors using provided JSON data.
 *
 * @param array $errors_data Array containing validation errors.
 * @return string HTML code for highlighting validation errors.
 */
function highlight_validation_errors(array $errors_data): string {
    // Safely encode the errors data into JSON
    $errors_json = json_encode($errors_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    if ($errors_json === false) {
        error_log('JSON encoding failed for validation errors.');
        return '';
    }

    // Read the JavaScript template
    $output_str = file_get_contents(APPPATH . 'engine/views/highlight_errors.txt');
    if ($output_str === false) {
        error_log('Failed to read highlight_errors.txt file');
        return '';
    }

    // Inject JSON data safely into the JavaScript context
    return '<div class="inline-validation-builder"><script>let validationErrorsJson = ' . $errors_json . ';</script><script>' . $output_str . '</script></div>';
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
 * @param array|null $attributes An associative array of HTML attributes for the label element. Defaults to null.
 * @return string The generated HTML label element with attributes.
 * 
 * Note: The label_text is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_label(string $label_text, ?array $attributes = null): string {
    $attributes_str = get_attributes_str($attributes);
    return '<label' . $attributes_str . '>' . $label_text . '</label>';
}

/**
 * Generate an HTML textarea element.
 *
 * @param string $name The name attribute for the textarea element.
 * @param string|null $value The initial value of the textarea. If not provided, it will be empty.
 * @param array|null $attributes An associative array of HTML attributes for the textarea.
 * @return string The generated HTML textarea element.
 */
function form_textarea(string $name, ?string $value = null, ?array $attributes = null): string {
    $attributes = $attributes ?? [];
    $attributes['name'] = $name;
    
    $html = '<textarea' . get_attributes_str($attributes) . '>' . htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>';
    
    return $html;
}

/**
 * Generates a date input form field element.
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value attribute for the input element. Default is null.
 * @param array|null $attributes Additional attributes for the input element as an associative array. Default is null.
 * @return string The generated HTML input element.
 */
function form_date(string $name, ?string $value = null, ?array $attributes = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'date';
    $attributes['name'] = $name;
    
    if ($value !== null) {
        $attributes['value'] = $value;
    }
    
    $html = '<input' . get_attributes_str($attributes) . '>';
    return $html;
}

/**
 * Generate an HTML submit button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, defaults to "Submit".
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @return string The generated HTML submit button element.
 * 
 * Note: The value is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_submit(string $name, ?string $value = null, ?array $attributes = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'submit';
    $attributes['name'] = $name;
    $attributes['value'] = $value ?? 'Submit';
    
    $html = '<button' . get_attributes_str($attributes) . '>' . $value . '</button>';
    
    return $html;
}

/**
 * Generate an HTML button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, defaults to "Submit".
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @return string The generated HTML button element.
 * 
 * Note: The value is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_button(string $name, ?string $value = null, ?array $attributes = null): string {
    $attributes = $attributes ?? [];
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
 * @param array|null $attributes An array of HTML attributes for the file input.
 * @return string The generated HTML for the file input element.
 */
function form_file_select(string $name, ?array $attributes = null): string {
    return generate_input_element('file', $name, null, false, $attributes);
}

/**
 * Retrieve and optionally clean a value from request data (form-encoded or JSON).
 *
 * This function handles data from any HTTP method (GET, POST, PUT, PATCH, DELETE)
 * and supports both form-encoded and JSON payloads. It can optionally clean up
 * the retrieved value by trimming whitespace and normalizing internal spaces.
 *
 * @param string $field_name The name of the field to retrieve, supports dot notation for nested fields.
 * @param bool $clean_up Whether to clean up the retrieved value (default is false).
 * 
 * @return string|int|float|array The value retrieved from the request data:
 *         - string: for text inputs
 *         - int: for integer values
 *         - float: for decimal numbers
 *         - array: for JSON objects or arrays
 *         - empty string: if the field is not found
 * 
 * @throws Exception If there's an error reading or decoding the input data.
 */
function post(string $field_name, bool $clean_up = false): string|int|float|array {
    static $request_data = null;
    if ($request_data === null) {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($content_type, 'application/json') === false) {
            $request_data = $_POST;  // Standard POST form data
        } else {
            // Handle PUT, PATCH, DELETE and JSON requests
            $raw_data = file_get_contents('php://input');
            if (empty($raw_data)) {
                $request_data = [];
            } elseif (stripos($content_type, 'application/json') !== false) {
                $request_data = json_decode($raw_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
                }
            } else {
                // Handle form-encoded data for PUT/PATCH/DELETE
                parse_str($raw_data, $request_data);
            }
        }
    }

    // Handle dot notation for nested fields
    $fields = explode('.', $field_name);
    $value = $request_data;
    foreach ($fields as $field) {
        if (isset($value[$field])) {
            $value = $value[$field];
        } else {
            return '';
        }
    }

    // Clean up the value if requested
    if ($clean_up) {
        if (is_string($value)) {
            $value = trim(preg_replace('/\s+/', ' ', $value));
        } elseif (is_array($value)) {
            array_walk_recursive($value, function(&$item) {
                if (is_string($item)) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                }
            });
        }
    }

    // Convert numeric strings to their appropriate type
    if (is_numeric($value) && !is_array($value)) {
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