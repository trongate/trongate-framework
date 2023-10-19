<?php
/**
 * IMPORTANT NOTE REGARDING STRIP_TAGS():
 *
 * It's possible that you may have to write and use your own, unique 
 * string filter methods depending on your specific use case. With this 
 * being the case, please note that strip_tags function has an optional 
 * second argument, which is a string of allowed HTML tags and attributes. 
 * If you want to allow certain HTML tags or attributes in the string, 
 * you can pass a list of allowed tags and attributes as the second argument.
 *
 * Example 1: 
 * $string = '<p>This is a <strong>test</strong> string.</p>';
 * $filtered_string = strip_tags($string, '<strong>');
 * echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string."
 *
 * Example 2:
 * In this example, we allow both 'strong' tags and 'em' tags...
 * $string = '<p>This is a <strong>test</strong> string.</p>';
 * $filtered_string = strip_tags($string, '<strong><em>');
 * echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string."
 *
 * Example 3:
 * In this example, we allow the style attribute for the <em> tag...
 * $string = '<p>This is a <strong>test</strong> string.</p><em style="color:red">Emphasis</em>';
 * $filtered_string = strip_tags($string, '<strong><em style>');
 * echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string.<em style="color:red">Emphasis</em>"
 *
 * FINALLY 
 * If you pass an array of allowed tags into strip_tags, before a database insert, 
 * use html_entity_decode() when displaying the stored string in the browser. 
 */

/**
 * Generate the opening tag for an HTML form.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @param string|null $additional_code An optional additional code to include in the form tag.
 * @return string The HTML opening tag for the form.
 */
function form_open(string $location, ?array $attributes = null, ?string $additional_code = null) {
    $extra = '';

    if (isset($attributes['method'])) {
        $method = $attributes['method'];
        unset($attributes['method']);
    } else {
        $method = 'post';
    }

    if (isset($attributes)) {
        foreach ($attributes as $key => $value) {
            $extra.= ' '.$key.'="'.$value.'"';
        }
    }

    if (filter_var($location, FILTER_VALIDATE_URL) === FALSE) {
        $location = BASE_URL.$location;
    }

    if (isset($additional_code)) {
        $extra.= ' '.$additional_code;
    }

    $html = '<form action="'.$location.'" method="'.$method.'"'.$extra.'>';
    return $html;
}

/**
 * Generate the opening tag for an HTML form with file upload support.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @param string|null $additional_code An optional additional code to include in the form tag.
 * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
 */
function form_open_upload(string $location, ?array $attributes = null, ?string $additional_code = null) {
    $html = form_open($location, $attributes, $additional_code);
    $html = str_replace('>', ' enctype="multipart/form-data">', $html);
    return $html;
}

/**
 * Generate the closing tag for an HTML form, including CSRF token and inline validation errors (if any).
 *
 * @return string The HTML closing tag for the form.
 */
/**
 * Generate the closing tag for an HTML form, including CSRF token and inline validation errors (if any).
 *
 * @return string The HTML closing tag for the form.
 */
function form_close(): string {
    // 1. Generate the CSRF token
    $csrf_token = bin2hex(random_bytes(32));

    // 2. Set the token as a session variable
    $_SESSION['csrf_token'] = $csrf_token;

    // 3. Create the hidden form field for CSRF token
    $html = '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

    // 4. Add the closing form tag
    $html .= '</form>';

    // 5. Include inline validation errors (if any)
    if (isset($_SESSION['form_submission_errors'])) {
        $errors_json = json_encode($_SESSION['form_submission_errors']);
        $inline_validation_js = highlight_validation_errors($errors_json);
        $html .= $inline_validation_js;
        unset($_SESSION['form_submission_errors']);
    }

    return $html;
}

/**
 * Build and return an output string from a file.
 *
 * @return string The output string generated from the specified file.
 */
function build_output_str() {
    $output_str = file_get_contents(APPPATH . 'engine/views/highlight_errors.txt');
    return $output_str;
}

/**
 * Highlight validation errors using provided JSON data.
 *
 * @param string $errors_json JSON data containing validation errors.
 * @return string HTML code for highlighting validation errors.
 */
function highlight_validation_errors($errors_json) {
    $code = '<div class="inline-validation-builder">';
    $output_str = build_output_str();
    $code .= '<script>let validationErrorsJson  = ' . json_encode($errors_json) . '</script>';
    $code .= '<script>';
    $code .= $output_str;
    $code .= '</script></div>';
    return $code;
}

/**
 * Get a string representation of HTML attributes from an associative array.
 *
 * @param array|null $attributes An associative array of HTML attributes.
 * @return string A string representation of HTML attributes.
 */
function get_attributes_str($attributes) {
    $attributes_str = '';

    if (!isset($attributes)) {
        return $attributes_str;
    }

    foreach ($attributes as $a_key => $a_value) {
        $attributes_str .= ' ' . $a_key . '="' . $a_value . '"';
    }

    return $attributes_str;
}

/**
 * Generate an HTML label element with optional attributes.
 *
 * @param string $label_text The text to display inside the label.
 * @param array|null $attributes An associative array of HTML attributes for the label.
 * @param string|null $additional_code Additional HTML code to append to the label element.
 * @return string The generated HTML label element.
 */
function form_label($label_text, $attributes = null, $additional_code = null) {
    $extra = '';

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    return '<label' . $extra . '>' . $label_text . '</label>';
}

/**
 * Generate an HTML input element.
 *
 * @param string $name The name attribute for the input element.
 * @param mixed $value (optional) The initial value for the input element. Default is null.
 * @param array|null $attributes (optional) Additional attributes for the input element. Default is null.
 * @param string|null $additional_code (optional) Additional code to include in the input element. Default is null.
 *
 * @return string The HTML representation of the input element.
 */
function form_input(string $name, $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $extra = '';
    if (!isset($value)) {
        $value = '';
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    return '<input type="text" name="' . $name . '" value="' . $value . '"' . $extra . '>';
}

/**
 * Generate an HTML input element with type "number".
 *
 * @param string $name The name attribute for the input element.
 * @param int|float|string|null $value The initial value of the input element.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string|null The generated HTML input element with type "number", or null if not generated.
 */
function form_number($name, $value = null, $attributes = null, $additional_code = null): ?string {
    $html = form_input($name, $value, $attributes, $additional_code);
    $html = str_replace('type="text"', 'type="number"', $html);
    return $html;
}

/**
 * Generate an HTML input element with type "password".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The initial value of the input element (password). Use null for no initial value.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "password".
 */
function form_password($name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $html = form_input($name, $value, $attributes, $additional_code);
    $html = str_replace('type="text"', 'type="password"', $html);
    return $html;
}

/**
 * Generate an HTML input element with type "email".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The initial value of the input element (email). Use null for no initial value.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "email".
 */
function form_email($name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $html = form_input($name, $value, $attributes, $additional_code);
    $html = str_replace('type="text"', 'type="email"', $html);
    return $html;
}

/**
 * Generate an HTML hidden input field.
 *
 * @param string $name The name attribute for the hidden input field.
 * @param string|null $value The initial value of the hidden input field. If not provided, it will be empty.
 * @param string|null $additional_code Additional HTML code to append to the hidden input field.
 * @return string The generated HTML hidden input field.
 */
function form_hidden($name, $value = null, $additional_code = null) {
    $html = form_input($name, $value, $additional_code);
    $html = str_replace(' type="text" ', ' type="hidden" ', $html);
    return $html;
}

/**
 * Generate an HTML textarea element.
 *
 * @param string $name The name attribute for the textarea element.
 * @param string|null $value The initial value of the textarea. If not provided, it will be empty.
 * @param array|null $attributes An associative array of HTML attributes for the textarea.
 * @param string|null $additional_code Additional HTML code to append to the textarea element.
 * @return string The generated HTML textarea element.
 */
function form_textarea($name, $value = null, $attributes = null, $additional_code = null) {
    $extra = '';
    if (!isset($value)) {
        $value = '';
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    return '<textarea name="' . $name . '"' . $extra . '>' . $value . '</textarea>';
}

/**
 * Generate an HTML submit button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, the button's name will be used as the value.
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @param string|null $additional_code Additional HTML code to append to the button element.
 * @return string The generated HTML submit button element.
 */
function form_submit(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $extra = '';
    if (!isset($value)) {
        $value = $name;
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    return '<button type="submit" name="' . $name . '" value="' . $value . '"' . $extra . '>' . $value . '</button>';
}

/**
 * Generate an HTML button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button.
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @param string|null $additional_code Additional HTML code to append to the button element.
 * @return string The generated HTML button element.
 */
function form_button(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $html = form_submit($name, $value, $attributes, $additional_code);
    $html = str_replace(' type="submit" ', ' type="button" ', $html);
    return $html;
}

/**
 * Generate an HTML input element with type "radio".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the radio button.
 * @param bool $checked Whether the radio button should be checked (true) or unchecked (false).
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "radio".
 */
function form_radio(string $name, ?string $value = null, bool $checked = false, ?array $attributes = null, ?string $additional_code = null): string {
    $extra = '';

    if (!isset($value)) {
        $value = '1';
    }

    if ($checked === true) {
        $extra .= ' checked';
    }

    if (isset($attributes)) {
        $extra .= get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    $html = '<input type="radio" name="' . $name . '" value="' . $value . '"' . $extra . '>';
    return $html;
}

/**
 * Generate an HTML input element with type "checkbox".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the checkbox when checked.
 * @param bool $checked Whether the checkbox should be checked (true) or unchecked (false).
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "checkbox".
 */
function form_checkbox(string $name, ?string $value = null, bool $checked = false, ?array $attributes = null, ?string $additional_code = null): string {
    $html = form_radio($name, $value, $checked, $attributes, $additional_code);
    $html = str_replace(' type="radio" ', ' type="checkbox" ', $html);
    return $html;
}

/**
 * Generate an HTML select menu.
 *
 * @param string $name The name attribute for the select element.
 * @param array $options An associative array of options (value => text).
 * @param string|null $selected_key The key of the selected option, if any.
 * @param array|null $attributes An array of HTML attributes for the select element.
 * @param string|null $additional_code Additional HTML code to include in the select element.
 * @return string The generated HTML for the select menu.
 */
function form_dropdown(string $name, array $options, ?string $selected_key = null, ?array $attributes = null, ?string $additional_code = null): string {
    $extra = '';
    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    $html = '<select name="' . $name . '"' . $extra . '>
';

    foreach ($options as $option_key => $option_value) {
        $selected = ($option_key == $selected_key) ? ' selected' : '';
        $html .= '<option value="' . $option_key . '"' . $selected . '>' . $option_value . '</option>
';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Generate an HTML file input element.
 *
 * @param string $name The name attribute for the file input.
 * @param array|null $attributes An array of HTML attributes for the file input.
 * @param string|null $additional_code Additional HTML code to include in the file input.
 * @return string The generated HTML for the file input element.
 */
function form_file_select(string $name, ?array $attributes = null, ?string $additional_code = null): string {
    $value = null;
    $html = form_input($name, $value, $attributes, $additional_code);
    $html = str_replace(' type="text" ', ' type="file" ', $html);
    return $html;
}

/**
 * Retrieve and clean a value from the POST data.
 *
 * @param string $field_name The name of the POST field to retrieve.
 * @param bool|null $clean_up Whether to clean up the retrieved value (default is null).
 * @return string|int|float The value retrieved from the POST data.
 */
function post(string $field_name, ?bool $clean_up = null) {
    if (!isset($_POST[$field_name])) {
        $value = '';
    } else {
        $value = $_POST[$field_name];

        if (isset($clean_up)) {
            $value = filter_string($value);
            
            if (is_numeric($value)) {
                $var_type = (strpos($value, '.') !== false) ? 'double' : 'int';
                settype($value, $var_type);
            }
        }
    }

    return $value;
}

/**
 * Filter and sanitize a string.
 *
 * @param string $string The input string to be filtered and sanitized.
 * @param string[] $allowed_tags An optional array of allowed HTML tags (default is an empty array).
 * @return string The filtered and sanitized string.
 */
function filter_string(string $string, array $allowed_tags = []) {
    // Remove HTML & PHP tags
    $string = strip_tags($string, implode('', $allowed_tags));

    // Apply XSS filtering
    $string = htmlspecialchars($string);

    // Convert multiple consecutive whitespaces to a single space, except for line breaks
    $string = preg_replace('/[^\S\r\n]+/', ' ', $string);

    // Trim leading and trailing white space
    $string = trim($string);

    return $string;
}

/**
 * Filter and sanitize a name.
 *
 * @param string $name The input name to be filtered and sanitized.
 * @param string[] $allowed_chars An optional array of allowed characters.
 * @return string The filtered and sanitized name.
 */
function filter_name(string $name, array $allowed_chars = []) {
    // Similar to filter_string() but better suited for usernames, etc.

    // Remove HTML & PHP tags (please read note above for more!)
    $name = strip_tags($name);

    // Apply XSS filtering
    $name = htmlspecialchars($name);

    // Create a regex pattern that includes the allowed characters
    $pattern = '/[^a-zA-Z0-9\s';
    $pattern .= !empty($allowed_chars) ? '[' . implode('', $allowed_chars) . ']' : ']';
    $pattern .= '/';

    // Replace any characters that are not in the allowed list
    $name = preg_replace($pattern, '', $name);

    // Convert double spaces to single spaces
    $name = preg_replace('/\s+/', ' ', $name);

    // Trim leading and trailing white space
    $name = trim($name);

    return $name;
}