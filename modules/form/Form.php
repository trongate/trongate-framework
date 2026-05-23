<?php
/**
 * Form Module - Framework Service for Form Generation and Handling
 * 
 * This module provides form-related functionality as a framework service.
 * It is accessed exclusively via Service Routing from helper functions.
 * 
 * Security: Uses BASE_URL check instead of block_url() to minimize dependencies
 * and maximize performance for core framework services.
 */

// Prevent direct script access - lightweight security check
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class Form extends Trongate {

    /**
     * Generates an HTML input element.
     *
     * @param array $data Array containing 'type', 'name', 'value', 'checked', and 'attributes' keys.
     * @return string The generated HTML input element.
     */
    public function generate_input_element(array $data): string {
        $type = $data['type'] ?? 'text';
        $name = $data['name'] ?? '';
        $value = $data['value'] ?? null;
        $checked = $data['checked'] ?? false;
        $attributes = $data['attributes'] ?? [];
        
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
        
        $html = '<input' . $this->get_attributes_str($attributes);
        return $html . '>';
    }

    /**
     * Generates a checkbox form field element.
     *
     * @param array $data Array containing 'name', 'value', 'checked', and 'attributes' keys.
     * @return string The generated HTML input element.
     */
    public function form_checkbox(array $data): string {
        $name = $data['name'] ?? '';
        $value = $data['value'] ?? '1';
        $checked = $data['checked'] ?? false;
        $attributes = $data['attributes'] ?? [];
        
        // Convert value to string
        $value = (string) $value;
        
        // Validate and convert checked state to boolean
        $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        
        // Generate the checkbox input element
        return $this->generate_input_element([
            'type' => 'checkbox',
            'name' => $name,
            'value' => $value,
            'checked' => $is_checked,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a radio button form field element.
     *
     * @param array $data Array containing 'name', 'value', 'checked', and 'attributes' keys.
     * @return string The generated HTML input element.
     */
    public function form_radio(array $data): string {
        $name = $data['name'] ?? '';
        $value = $data['value'] ?? '';
        $checked = $data['checked'] ?? false;
        $attributes = $data['attributes'] ?? [];
        
        // Convert value to string
        $value = (string) $value;
        
        // Validate and convert checked state to boolean
        $is_checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        
        // Generate the radio input element
        return $this->generate_input_element([
            'type' => 'radio',
            'name' => $name,
            'value' => $value,
            'checked' => $is_checked,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a text input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_input(array $attributes = []): string {
        return $this->generate_input_element([
            'type' => 'text',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates an email input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_email(array $attributes = []): string {
        $attributes['type'] = 'email';
        return $this->generate_input_element([
            'type' => 'email',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a password input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_password(array $attributes = []): string {
        $attributes['type'] = 'password';
        return $this->generate_input_element([
            'type' => 'password',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a search input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_search(array $attributes = []): string {
        $attributes['type'] = 'search';
        return $this->generate_input_element([
            'type' => 'search',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a number input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_number(array $attributes = []): string {
        $attributes['type'] = 'number';
        return $this->generate_input_element([
            'type' => 'number',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a hidden input form field element.
     *
     * @param array $data Array containing 'name', 'value', and 'attributes' keys.
     * @return string The generated HTML input element.
     */
    public function form_hidden(array $data): string {
        $name = $data['name'] ?? '';
        $value = $data['value'] ?? null;
        $attributes = $data['attributes'] ?? [];
        
        $attributes['type'] = 'hidden';
        return $this->generate_input_element([
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generate the opening tag for an HTML form.
     *
     * @param array $data Array containing 'location', 'method', and 'attributes' keys.
     * @return string The HTML opening tag for the form.
     */
    public function form_open(array $data): string {
        $location = $data['location'] ?? '';
        $method = $data['method'] ?? 'post';
        $attributes = $data['attributes'] ?? [];
        
        $extra = '';
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
     * @param array $data Array containing 'location' and 'attributes' keys.
     * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
     */
    public function form_open_upload(array $data): string {
        $location = $data['location'] ?? '';
        $attributes = $data['attributes'] ?? [];
        
        $attributes['enctype'] = 'multipart/form-data';
        return $this->form_open([
            'location' => $location,
            'method' => 'post',
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates hidden CSRF token input field and a closing form tag.
     *
     * @return string The HTML closing tag for the form.
     */
    public function form_close(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $html = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
        $html .= '</form>';
        
        // Handshake with the Validation module for JS field highlighting
        // Use Modules::run() to access validation module
        $js_injection = Modules::run('validation/get_js_injection');
        $html .= $js_injection;
        
        // Clear validation errors to prevent them from persisting to other forms
        unset($_SESSION['form_submission_errors']);
        return $html;
    }

    /**
     * Get a string representation of HTML attributes from an associative array.
     *
     * @param array $attributes An associative array of HTML attributes.
     * @return string A string representation of HTML attributes.
     */
    public function get_attributes_str(array $attributes = []): string {
        if (empty($attributes)) {
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
     * @param array $data Array containing 'label_text', 'input_id', and 'attributes' keys.
     * @return string The generated HTML label element with attributes.
     */
    public function form_label(array $data): string {
        $label_text = $data['label_text'] ?? '';
        $input_id = $data['input_id'] ?? '';
        $attributes = $data['attributes'] ?? [];
        
        if ($input_id !== '') {
            $attributes['for'] = $input_id;
        }
        
        $attributes_str = $this->get_attributes_str($attributes);
        return '<label' . $attributes_str . '>' . $label_text . '</label>';
    }

    /**
     * Generate an HTML textarea element.
     *
     * @param array $attributes An associative array of HTML attributes for the textarea.
     * @return string The generated HTML textarea element.
     */
    public function form_textarea(array $attributes = []): string {
        $name = $attributes['name'] ?? '';
        $value = $attributes['value'] ?? null;
        
        unset($attributes['value']); // Remove value from attributes since it goes in content
        
        $attributes['name'] = $name;
        
        $html = '<textarea' . $this->get_attributes_str($attributes) . '>' . htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>';
        
        return $html;
    }

    /**
     * Generates a date input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_date(array $attributes = []): string {
        $attributes['type'] = 'date';
        return $this->generate_input_element([
            'type' => 'date',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a datetime-local input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_datetime_local(array $attributes = []): string {
        $attributes['type'] = 'datetime-local';
        return $this->generate_input_element([
            'type' => 'datetime-local',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a time input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_time(array $attributes = []): string {
        $attributes['type'] = 'time';
        return $this->generate_input_element([
            'type' => 'time',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a month input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_month(array $attributes = []): string {
        $attributes['type'] = 'month';
        return $this->generate_input_element([
            'type' => 'month',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generates a week input form field element.
     *
     * @param array $attributes An associative array of HTML attributes for the input element.
     * @return string The generated HTML input element.
     */
    public function form_week(array $attributes = []): string {
        $attributes['type'] = 'week';
        return $this->generate_input_element([
            'type' => 'week',
            'name' => $attributes['name'] ?? '',
            'value' => $attributes['value'] ?? null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Generate an HTML submit button element.
     *
     * @param array $data Array containing 'submit_value' and 'attributes' keys.
     * @return string The generated HTML submit button element.
     */
    public function form_submit(array $data): string {
        $submit_value = $data['submit_value'] ?? 'Submit';
        $attributes = $data['attributes'] ?? [];
        
        $attributes['type'] = 'submit';
        $attributes['value'] = $submit_value;
        
        $html = '<button' . $this->get_attributes_str($attributes) . '>' . $submit_value . '</button>';
        
        return $html;
    }

    /**
     * Generate an HTML button element.
     *
     * @param array $data Array containing 'button_value' and 'attributes' keys.
     * @return string The generated HTML button element.
     */
    public function form_button(array $data): string {
        $button_value = $data['button_value'] ?? 'Button';
        $attributes = $data['attributes'] ?? [];
        
        $attributes['type'] = 'button';
        
        $html = '<button' . $this->get_attributes_str($attributes) . '>' . $button_value . '</button>';
        
        return $html;
    }

    /**
     * Generate an HTML select menu.
     *
     * @param array $data Array containing 'name', 'options', 'selected', and 'attributes' keys.
     * @return string The generated HTML select menu.
     */
    public function form_dropdown(array $data): string {
        $name = $data['name'] ?? '';
        $options = $data['options'] ?? [];
        $selected = $data['selected'] ?? null;
        $attributes = $data['attributes'] ?? [];
        
        $attributes['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        
        $html = '<select' . $this->get_attributes_str($attributes) . ">\n";

        foreach ($options as $option_key => $option_value) {
            $option_attributes = ['value' => htmlspecialchars((string)$option_key, ENT_QUOTES, 'UTF-8')];
            if ($selected !== null && (string)$option_key === (string)$selected) {
                $option_attributes['selected'] = 'selected';
            }
            $html .= '    <option' . $this->get_attributes_str($option_attributes) . '>' 
                   . htmlspecialchars((string)$option_value, ENT_QUOTES, 'UTF-8') . "</option>\n";
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Generate an HTML file input element.
     *
     * @param array $attributes An array of HTML attributes for the file input.
     * @return string The generated HTML for the file input element.
     */
    public function form_file_select(array $attributes = []): string {
        $attributes['type'] = 'file';
        return $this->generate_input_element([
            'type' => 'file',
            'name' => $attributes['name'] ?? '',
            'value' => null,
            'checked' => false,
            'attributes' => $attributes
        ]);
    }

    /**
     * Retrieve a specific value or the entire request payload.
     *
     * @param array $data Array containing 'field_name', 'clean_up', and 'cast_numeric' keys.
     * @return string|int|float|array The requested value or entire payload.
     * @throws Exception If JSON input is malformed.
     */
    public function post(array $data): string|int|float|array {
        $field_name = $data['field_name'] ?? null;
        $clean_up = $data['clean_up'] ?? false;
        $cast_numeric = $data['cast_numeric'] ?? false;

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
}
