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
    $data = ['type' => $type, 'name' => $name, 'value' => $value, 'checked' => $checked, 'attributes' => $attributes];
    return Modules::run('form/generate_input_element', $data);
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
    $data = ['name' => $name, 'value' => $value, 'checked' => $checked, 'attributes' => $attributes];
    return Modules::run('form/form_checkbox', $data);
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
    $data = ['name' => $name, 'value' => $value, 'checked' => $checked, 'attributes' => $attributes];
    return Modules::run('form/form_radio', $data);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_input', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_email', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_password', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_search', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = (string) $value;
    }

    return Modules::run('form/form_number', $attributes);
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
    $data = ['name' => $name, 'value' => $value, 'attributes' => $attributes];
    return Modules::run('form/form_hidden', $data);
}

/**
 * Generate the opening tag for an HTML form.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form.
 */
function form_open(string $location, array $attributes = []): string {
    $data = ['location' => $location, 'attributes' => $attributes];
    return Modules::run('form/form_open', $data);
}

/**
 * Generate the opening tag for an HTML form with file upload support.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array $attributes An optional array of HTML attributes for the form.
 * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
 */
function form_open_upload(string $location, array $attributes = []): string {
    $data = ['location' => $location, 'attributes' => $attributes];
    return Modules::run('form/form_open_upload', $data);
}

/**
 * Generates hidden CSRF token input field and a closing form tag.
 *
 * @return string The HTML closing tag for the form.
 */
function form_close(): string {
    return Modules::run('form/form_close');
}

/**
 * Get a string representation of HTML attributes from an associative array.
 *
 * @param array|null $attributes An associative array of HTML attributes.
 * @return string A string representation of HTML attributes.
 */
function get_attributes_str($attributes): string {
    return Modules::run('form/get_attributes_str', $attributes);
}

/**
 * Generate an HTML label element.
 *
 * @param string $label_text The text or HTML to be used as the label content.
 * @param string|null $input_id The id of the input element to associate with the label. Default is null.
 * @param array $attributes An associative array of HTML attributes for the label element. Defaults to empty array.
 * @return string The generated HTML label element with attributes.
 *
 * Note: The label_text is not escaped by default. If using user-generated content,
 * ensure it is properly sanitized before passing it to this function.
 */
function form_label(string $label_text, array $attributes = []): string {
    $data = ['label_text' => $label_text, 'attributes' => $attributes];
    return Modules::run('form/form_label', $data);
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

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_textarea', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_date', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_datetime_local', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_time', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_month', $attributes);
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
    $attributes['name'] = $name;

    if ($value !== null) {
        $attributes['value'] = $value;
    }

    return Modules::run('form/form_week', $attributes);
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
    $attributes['name'] = $name;
    $data = ['submit_value' => $value, 'attributes' => $attributes];
    return Modules::run('form/form_submit', $data);
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
    $data = ['button_value' => $value, 'attributes' => $attributes];
    return Modules::run('form/form_button', $data);
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
    $data = ['name' => $name, 'options' => $options, 'selected' => $selected_key, 'attributes' => $attributes];
    return Modules::run('form/form_dropdown', $data);
}

/**
 * Generate an HTML file input element.
 *
 * @param string $name The name attribute for the file input.
 * @param array $attributes An array of HTML attributes for the file input.
 * @return string The generated HTML for the file input element.
 */
function form_file_select(string $name, array $attributes = []): string {
    return Modules::run('form/form_file_select', $attributes);
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
    $data = ['field_name' => $field_name, 'clean_up' => $clean_up, 'cast_numeric' => $cast_numeric];
    return Modules::run('form/post', $data);
}

/**
 * Generates and returns validation error messages in HTML or JSON format.
 *
 * @param string|int|null $first_arg Optional HTML to open each error message, HTTP status code for JSON output, or null.
 * @param string|null $closing_html Optional HTML to close each error message.
 * @return string|null Returns a string of formatted validation errors or null if no errors are present.
 */
function validation_errors(string|int|null $first_arg = null, ?string $closing_html = null): ?string {
    return Modules::run('validation/display_errors', ['first_arg' => $first_arg, 'closing_html' => $closing_html]);
}
