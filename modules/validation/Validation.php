<?php
/**
 * Validation Module - Framework Service for Form Validation
 * 
 * This module provides validation functionality as a framework service.
 * It is accessed exclusively via Service Routing from helper functions.
 * 
 * Security: Uses BASE_URL check instead of block_url() to minimize dependencies
 * and maximize performance for core framework services.
 */

// Prevent direct script access - lightweight security check
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class Validation extends Trongate {

    /**
     * Array of form submission errors
     * 
     * @var array<string, array<string>>
     */
    public array $form_submission_errors = [];

    /**
     * Array of posted fields with their labels
     * 
     * @var array<string, string>
     */
    public array $posted_fields = [];

    /**
     * Caller object for callback methods
     * 
     * @var object|null
     */
    private ?object $caller = null;

    /**
     * Constructor for Validation class
     * 
     * @param string|null $module_name The module name
     * @param object|null $caller The calling controller instance for callbacks
     */
    public function __construct(?string $module_name = null, ?object $caller = null) {
        parent::__construct($module_name);
        $this->caller = $caller;
    }

    /**
     * Sets the validation language for error messages
     * 
     * @param string $lang The language code (e.g., 'en', 'fr', 'es')
     * @return void
     */
    public function set_language(string $lang): void {
        $this->language->set_language($lang);
        $this->model->load_validation_language($lang);
    }

    /**
     * Resets the validation language to the system default.
     * Removes the sticky language preference from the session.
     *
     * @return void
     */
    public function reset_language(): void {
        $this->language->reset_language();
    }

    /**
     * Sets the calling controller instance to allow for callback methods.
     * 
     * @param object $caller The calling controller instance
     * @return void
     */
    public function set_caller(object $caller): void {
        $this->caller = $caller;
    }

    /**
     * Configures validation rules for a specific field.
     * 
     * @param string $key The field name/key
     * @param string $label The human-readable field label
     * @param string $rules The validation rules string (pipe-separated)
     * @return void
     */
    public function set_rules(string $key, string $label, string $rules): void {
        $validation_data['key'] = $key;
        $validation_data['label'] = $label;

        if (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
            $validation_data['posted_value'] = $_FILES[$key];
            $validation_data['is_file'] = true;
        } else {
            $validation_data['posted_value'] = post($key, true);
            $validation_data['is_file'] = false;
        }

        $tests_to_run = explode('|', $rules);
        $this->posted_fields[$key] = $label;

        foreach ($tests_to_run as $test_to_run) {
            $validation_data['test_to_run'] = $test_to_run;
            $this->run_validation_test($validation_data);

            // EARLY EXIT: If an error was added for this field, stop processing further rules
            if (isset($this->form_submission_errors[$key])) {
                break;  // Stop processing more rules for this field
            }
        }

        $_SESSION['form_submission_errors'] = $this->form_submission_errors;
    }

    /**
     * Parses and executes a single validation rule.
     * 
     * @param array<string, mixed> $validation_data The validation data array
     * @return void
     */
    private function run_validation_test(array $validation_data): void {
        $test_to_run = $validation_data['test_to_run'];
        $param = null;

        // Parse rules with parameters, e.g., max_length[10]
        if (strpos($test_to_run, '[') !== false && strpos($test_to_run, ']') !== false) {
            $parts = explode('[', $test_to_run);
            $method = $parts[0];
            $param = rtrim($parts[1], ']');
        } else {
            $method = $test_to_run;
        }

        $validation_data['param'] = $param;

        // Handle Callbacks
        if (str_starts_with($method, 'callback_')) {
            $callback_method = str_replace('callback_', '', $method);

            if (isset($this->caller) && method_exists($this->caller, $callback_method)) {
                $result = $this->caller->$callback_method($validation_data['posted_value']);

                if ($result !== true) {
                    $error_msg = $this->model->resolve_error_message($result, $validation_data);
                    $this->form_submission_errors[$validation_data['key']][] = $error_msg;
                }
            } else {
                $error_msg = "Validation failed: Callback method '$callback_method' not found.";
                $this->form_submission_errors[$validation_data['key']][] = $error_msg;
            }
            return;
        }

        // Delegate to Validation_model
        $this->form_submission_errors = $this->model->execute_rule($method, $validation_data, $this->form_submission_errors);
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
     * Blocks a request that failed CSRF validation.
     *
     * @return void
     */
    private function csrf_block_request(): void {
        // Check if this is an AJAX/API request (XML HTTP Request)
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($is_ajax) {
            // Return 403 Forbidden for API/AJAX requests
            http_response_code(403);
            die('CSRF token validation failed');
        } else {
            // Redirect to home page for standard form submissions
            redirect(BASE_URL);
        }
    }

    /**
     * Finalizes validation. Returns true if no errors found.
     * Supports passing an array of rules directly (Option 2 syntax).
     * 
     * @param array<string, array<string, mixed>>|null $rules Optional associative array of validation rules
     * @return bool True if validation passes, false otherwise
     */
    public function run(?array $rules = null): bool {
        // 1. Security First - Validate CSRF token before any processing
        $this->csrf_protect();

        // 2. If rules are passed as an array, parse and execute them
        if (isset($rules)) {
            foreach ($rules as $field => $data) {
                // Use provided label, or fallback to field name
                $label = $data['label'] ?? $field;
                $rule_list = [];

                foreach ($data as $rule => $value) {
                    // Skip the label key as it's not a validation test
                    if ($rule === 'label') {
                        continue;
                    }

                    // If value is true, it's a basic rule (e.g., 'required' => true)
                    // If value is anything else, it's a param (e.g., 'min_length' => 3)
                    if ($value === true) {
                        $rule_list[] = $rule;
                    } else {
                        $rule_list[] = $rule . '[' . $value . ']';
                    }
                }

                // Register and execute the rules for this field
                $this->set_rules($field, $label, implode('|', $rule_list));
            }
        }

        // 3. Final check for errors (gathered during set_rules phase)
        if (count($this->form_submission_errors) > 0) {
            $_SESSION['form_submission_errors'] = $this->form_submission_errors;
            return false;
        }

        return true;
    }

    /**
     * Displays validation errors in various formats.
     * 
     * Supports three render types:
     * 1. JSON: When first argument is an HTTP error code (400-499)
     * 2. Inline: When only first argument is provided (field name)
     * 3. Standard: When both opening and closing HTML tags are provided
     * 
     * @param array<string, mixed> $data The data array containing render parameters
     * @return string|null The rendered error HTML or null if no errors
     */
    public function display_errors(array $data = []): ?string {
        $first_arg = $data['first_arg'] ?? null;
        $closing_html = $data['closing_html'] ?? null;
        $render_type = $this->get_render_type($first_arg, $closing_html);

        if ($render_type === 'null') {
            return null;
        }

        $form_submission_errors = $_SESSION['form_submission_errors'];

        return match ($render_type) {
            'json'     => $this->json_validation_errors($form_submission_errors, $first_arg),
            'inline'   => $this->inline_validation_errors($form_submission_errors, $first_arg),
            'standard' => $this->general_validation_errors($form_submission_errors, $first_arg, $closing_html),
        };
    }

    /**
     * Renders validation errors as JSON response with HTTP error code.
     * 
     * @param array<string, array<string>> $errors The validation errors array
     * @param int $http_code The HTTP status code (400-499)
     * @return string Empty string (outputs JSON directly)
     */
    private function json_validation_errors(array $errors, int $http_code): string {
        http_response_code($http_code);
        header('Content-Type: application/json');

        // RENDERED = DELETED
        unset($_SESSION['form_submission_errors']);
        return json_encode($errors);
    }

    /**
     * Renders inline validation errors for a specific field.
     * 
     * @param array<string, array<string>> $errors The validation errors array
     * @param string $field The field name to display errors for
     * @return string The HTML for inline validation errors
     */
    private function inline_validation_errors(array $errors, string $field): string {
        if (!isset($errors[$field])) {
            return '';
        }

        $html = '<ul class="validation-errors validation-errors--inline">';
        foreach ($errors[$field] as $error) {
            $html .= '<li>' . out($error) . '</li>';
        }
        $html .= '</ul>';

        // SURGICAL DELETION: Remove only this field's errors
        unset($_SESSION['form_submission_errors'][$field]);

        // Cleanup: If the array is now empty, remove the parent key
        if (count($_SESSION['form_submission_errors']) === 0) {
            unset($_SESSION['form_submission_errors']);
        }

        return $html;
    }

    /**
     * Renders general validation errors as a summary list.
     * 
     * @param array<string, array<string>> $errors The validation errors array
     * @param string|null $open The opening HTML tag for each error
     * @param string|null $close The closing HTML tag for each error
     * @return string The HTML for general validation errors
     */
    private function general_validation_errors(array $errors, ?string $open = null, ?string $close = null): string {
        $open  = $open ?? (defined('ERROR_OPEN') ? ERROR_OPEN : '<li>');
        $close = $close ?? (defined('ERROR_CLOSE') ? ERROR_CLOSE : '</li>');

        $items = '';
        foreach ($errors as $field_errors) {
            foreach ($field_errors as $error) {
                $items .= $open . out($error) . $close;
            }
        }

        $html = ($items !== '') ? '<ul class="validation-errors validation-errors--summary">' . $items . '</ul>' : '';

        // RENDERED = DELETED
        unset($_SESSION['form_submission_errors']);
        return $html;
    }

    /**
     * Determines the render type based on input parameters.
     * 
     * @param mixed $first_arg The first argument passed to display_errors
     * @param mixed $closing_html The closing HTML tag argument
     * @return string The render type: 'json', 'inline', 'standard', or 'null'
     */
    private function get_render_type($first_arg, $closing_html): string {
        if (!isset($_SESSION['form_submission_errors'])) {
            return 'null';
        }

        if (is_int($first_arg) && $first_arg >= 400 && $first_arg <= 499) {
            return 'json';
        }

        if (isset($first_arg) && !isset($closing_html)) {
            return 'inline';
        }

        return 'standard';
    }

    /**
     * Generates JS injection for automatic error highlighting.
     * 
     * @return string The JavaScript injection HTML
     */
    public function get_js_injection(): string {
        if (!isset($_SESSION['form_submission_errors'])) {
            return '';
        }

        $errors_json = json_encode($_SESSION['form_submission_errors'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $html = '<script>window.trongateValidationErrors = ' . $errors_json . ';</script>';

        $trigger = defined('MODULE_ASSETS_TRIGGER') ? MODULE_ASSETS_TRIGGER : '_module';
        $js_url = BASE_URL . $this->module_name . $trigger . '/js/highlight_validation_errors.js';

        $html .= '<script src="' . $js_url . '"></script>';

        return $html;
    }
}