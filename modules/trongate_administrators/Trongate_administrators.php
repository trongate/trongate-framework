<?php
/**
 * Administrator management class for user authentication and account operations.
 * Handles login, user management, permissions, and session handling with security validation.
 */
class Trongate_administrators extends Trongate {

    // NOTE: Uncomment the line below to enforce a custom login segment.
    // private string $secret_login_segment = "tg-admin";
    private int $default_limit = 20;
    private array $per_page_options = [10, 20, 50, 100];
    private string $dashboard_home;
    private string $login_url;

    /**
     * Constructor - sets up module-specific properties
     * 
     * @param string|null $module_name The name of the module (automatically passed by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        $this->dashboard_home = $this->module_name . '/manage';
        $this->login_url = isset($this->secret_login_segment) ? $this->secret_login_segment : $this->module_name . '/login';
    }

    /**
     * Display form for creating or editing a record
     * 
     * @return void
     */
    public function create(): void {
        $this->trongate_security->make_sure_allowed();
        
        $update_id = segment(3, 'int');
        
        if ($update_id > 0) {
            $data = $this->model->get_data_from_db($update_id);
            if ($data === false) {
                $this->not_found();
                return;
            }
        } else {
            $data = $this->model->get_data_from_post();
        }

        $data['headline'] = ($update_id > 0) ? 'Update Record' : 'Create New Record';
        $data['cancel_url'] = ($update_id > 0) 
            ? BASE_URL . $this->module_name . '/show/' . $update_id 
            : BASE_URL . $this->module_name . '/manage';

        $data['update_password_url'] = str_replace('/create', '/update_password', current_url());
        $data['form_location'] = BASE_URL . $this->module_name . '/submit/' . $update_id;
        $data['view_module'] = $this->module_name;
        $data['view_file'] = 'create';
        
        $this->templates->admin($data);
    }

    /**
     * Display detailed view of a single record
     * 
     * @return void
     */
    public function show(): void {
        $token = $this->trongate_security->make_sure_allowed();
        
        $update_id = segment(3, 'int');
        
        if ($update_id === 0) {
            $this->not_found();
            return;
        }

        $record_data = $this->model->get_data_from_db($update_id);

        if ($record_data === false) {
            $this->not_found();
            return;
        }
        
        $my_user_obj = $this->model->get_user_by_token($token);
        $logged_in_user_id = (int) ($my_user_obj->id ?? 0);

        $is_own_account = ($update_id === $logged_in_user_id) ? true : false;
        $data = $this->model->prepare_for_display($record_data);
        $data['update_id'] = $update_id;
        $data['headline'] = $is_own_account ? 'Your Account Details' : 'Record Details';
        $data['is_own_account'] = $is_own_account;
        $data['back_url'] = $this->get_back_url();
        $data['view_module'] = $this->module_name;
        $data['view_file'] = 'show';
        
        $this->templates->admin($data);
    }

    /**
     * Redirect user to their own profile/show page.
     * 
     * @return void
     */
    public function update_your_details(): void {
        $token = $this->trongate_security->make_sure_allowed();
        $my_user_obj = $this->model->get_user_by_token($token);
        $logged_in_user_id = (int) ($my_user_obj->id ?? 0);
        $target_url = $this->module_name.'/show/'.$logged_in_user_id;
        redirect($target_url);
    }

    /**
     * Display confirmation page before deleting a record
     * 
     * @return void
     */
    public function delete_conf(): void {
        $token = $this->trongate_security->make_sure_allowed();
        
        $update_id = segment(3, 'int');
        
        if ($update_id === 0) {
            $this->not_found();
            return;
        }
        
        $my_user_obj = $this->model->get_user_by_token($token);
        $logged_in_user_id = (int) ($my_user_obj->id ?? 0);

        if ($update_id === $logged_in_user_id) {
            set_flashdata('You cannot delete your own account');
            redirect($this->module_name . '/show/' . $update_id);
            return;
        }
        
        $record_data = $this->model->get_data_from_db($update_id);
        
        if ($record_data === false) {
            $this->not_found();
            return;
        }
        
        $data['update_id'] = $update_id;
        $data['headline'] = 'Delete Record';
        $data['cancel_url'] = BASE_URL . $this->module_name . '/show/' . $update_id;
        $data['form_location'] = BASE_URL . $this->module_name . '/submit_delete/' . $update_id;
        $data['view_module'] = $this->module_name;
        $data['view_file'] = 'delete_conf';
        $this->templates->admin($data);
    }

    /**
     * Display password update form
     * 
     * @return void
     */
    public function update_password(): void {
        $token = $this->trongate_security->make_sure_allowed();
        $update_id = segment(3, 'int');
        
        $record_data = $this->model->get_data_from_db($update_id);

        if ($record_data === false) {
            $this->not_found();
            return;
        }

        $my_user_obj = $this->model->get_user_by_token($token);
        $logged_in_user_id = (int) ($my_user_obj->id ?? 0);
        $is_own_account = ($update_id === $logged_in_user_id) ? true : false;

        $data['headline'] = $is_own_account ? 'Update Your Password' : 'Update Password';
        $data['cancel_url'] = ($update_id > 0) 
            ? BASE_URL . $this->module_name . '/show/' . $update_id 
            : BASE_URL . $this->module_name . '/manage';

        $data['form_location'] = str_replace('/update_password/', '/submit_update_password/', current_url());
        $data['view_module'] = $this->module_name;
        $data['view_file'] = 'update_password';
        $this->templates->admin($data);
    }

    /**
     * Handle password update form submission
     * 
     * @return void
     */
    public function submit_update_password(): void {
        $this->trongate_security->make_sure_allowed();
        
        $submit = post('submit', true);
        
        if ($submit !== 'Update Password') {
            $update_id = segment(3, 'int');
            redirect($this->module_name . '/show/' . $update_id);
            return;
        }
        
        $this->validation->set_rules('password', 'password', 'required|min_length[8]');
        $this->validation->set_rules('confirm_password', 'password confirmation', 'required|matches[password]');
        
        if ($this->validation->run() !== true) {
            $this->update_password();
            return;
        }
        
        $update_id = segment(3, 'int');
        $record_data = $this->model->get_data_from_db($update_id);

        if ($record_data === false) {
            $this->not_found();
            return;
        }
        
        $password = post('password', true);
        $this->model->update_password($update_id, $password);
        
        set_flashdata('Password updated successfully');
        redirect($this->module_name . '/show/' . $update_id);
    }

    /**
     * Log out the current user and redirect to login page.
     * 
     * @return void
     */
    public function logout(): void {
        $this->trongate_tokens->destroy(); // Destroy the authentication token
        redirect($this->login_url);
    }

    /**
     * Display 404-style not found page for missing record(s)
     * 
     * @return void
     */
    public function not_found(): void {
        $data = [
            'headline' => 'Record Not Found',
            'message' => 'The record you\'re looking for doesn\'t exist or has been deleted.',
            'back_url' => $this->get_back_url(),
            'back_label' => 'Go Back',
            'view_module' => $this->module_name,
            'view_file' => 'not_found'
        ];
        $this->templates->admin($data);
    }

    /**
     * Determine appropriate back URL for navigation
     * 
     * @return string The URL to go back to
     */
    private function get_back_url(): string {
        $previous_url = previous_url();
        if ($previous_url !== '' && strpos($previous_url, BASE_URL . $this->module_name . '/manage') === 0) {
            return $previous_url;
        }
        return BASE_URL . $this->module_name . '/manage';
    }

    /**
     * Handle form submission for creating/updating records
     * 
     * @return void
     */
    public function submit(): void {
        $this->trongate_security->make_sure_allowed();
        
        $submit = post('submit', true);
        
        if ($submit !== 'Submit') {
            redirect($this->module_name . '/manage');
            return;
        }
        
        $this->validation->set_rules('username', 'username', 'required|min_length[2]|max_length[50]|callback_username_check');
        
        if ($this->validation->run() !== true) {
            $this->create();
            return;
        }
        
        $update_id = segment(3, 'int');
        
        // Get raw POST data and convert for database
        $post_data = $this->model->get_data_from_post();
        $db_data = $this->model->convert_posted_data_for_db($post_data);
        
        if ($update_id > 0) {
            // Verify record exists before updating
            $existing_record = $this->model->get_data_from_db($update_id);
            if ($existing_record === false) {
                redirect($this->module_name . '/manage');
                return;
            }
            
            $this->model->update($update_id, $db_data);
            $flash_msg = 'Record updated successfully';
        } else {
            $update_id = $this->model->create_new_record($db_data);
            $flash_msg = 'Record created successfully';
        }
        
        set_flashdata($flash_msg);
        redirect($this->module_name . '/show/' . $update_id);
    }

    /**
     * Handle record deletion after confirmation
     * 
     * @return void
     */
    public function submit_delete(): void {
        $token = $this->trongate_security->make_sure_allowed();
        
        $submit = post('submit', true);
        
        // Handle non-confirmation immediately
        if ($submit !== 'Yes - Delete Now') {
            redirect($this->module_name . '/manage');
            return;
        }
        
        $update_id = segment(3, 'int');
        
        // Handle invalid ID immediately
        if ($update_id === 0) {
            redirect($this->module_name . '/manage');
            return;
        }

        // Prevent self-deletion
        $my_user_obj = $this->model->get_user_by_token($token);
        $logged_in_user_id = (int) ($my_user_obj->id ?? 0);
        
        if ($update_id === $logged_in_user_id) {
            set_flashdata('You cannot delete your own account');
            redirect($this->module_name . '/show/' . $update_id);
            return;
        }
        
        // Verify record exists before deleting
        $record_data = $this->model->get_data_from_db($update_id);
        if ($record_data === false) {
            redirect($this->module_name . '/manage');
            return;
        }
        
        // Perform deletion
        $this->model->delete_record($update_id);
        set_flashdata('The record was successfully deleted');
        redirect($this->module_name . '/manage');
    }

    /**
     * Display the login form.
     *
     * Prepares the login form by:
     * - Resolving the correct form submission URL (policed if a secret segment is set)
     * - Destroying any existing user tokens (logging out)
     * - Checking that login attempts are allowed
     * - Rendering the 'login' view with the prepared data
     *
     * @return void
     */
    public function login(): void {
        $data['form_location'] = $this->police_secret_login_url();
        $this->trongate_tokens->destroy(); // Log user out.
        $this->make_sure_login_attempt_allowed(); 
        $this->view('login', $data);
    }

    /**
     * Ensure login attempts are permitted before proceeding with authentication.
     * 
     * This private method performs two sequential security checks:
     * 1. First removes any expired login restrictions (auto-unblocks accounts whose block time has elapsed)
     * 2. Then verifies that the current login attempt is allowed based on current throttling rules
     * 
     * If the login attempt is not permitted (due to IP or account restrictions),
     * the user is immediately redirected to the 'not_allowed' page, halting further execution.
     * 
     * @return void
     */
    private function make_sure_login_attempt_allowed(): void {
        $this->model->remove_expired_restrictions();
        $login_attempt_allowed = $this->model->is_login_attempt_allowed();

        if ($login_attempt_allowed !== true) {
            redirect($this->module_name . '/not_allowed');
        }
    }

    /**
     * Display the "Access Temporarily Blocked" page for users exceeding login attempt limits.
     * 
     * This controller method renders a dedicated view that informs users their login access
     * has been temporarily restricted due to excessive failed authentication attempts.
     * 
     * @return void
     */
    public function not_allowed(): void {
        $data = [];

        if (str_contains(previous_url(), 'submit_login')) {
            $data['login_url'] = $this->login_url;
        }

        $this->view('not_allowed', $data);
    }

    /**
     * Get the admin login form submission URL.
     *
     * If a secret login segment is set, ensures access via the default
     * module path triggers a 404 and terminates execution.
     *
     * @return string The login form submission endpoint.
     */
    public function police_secret_login_url(): string {

        // Handle secret login segment for specific URL configurations.
        $form_location = $this->module_name.'/submit_login';

        // Redirect to 404 if accessed incorrectly from within the module.
        if (isset($this->secret_login_segment)) {
            if (is_numeric(strpos(current_url(), $this->module_name))) {
                $this->templates->error_404();
                die();
            }
            $form_location = $this->secret_login_segment . '/submit_login';
        }

        return $form_location;
    }

    /**
     * Handle login form submission
     * 
     * @return void
     */
    public function submit_login(): void {
        $this->validation->set_rules('username', 'username', 'required|callback_login_check');
        $this->validation->set_rules('password', 'password', 'required|min_length[5]');
        
        $result = $this->validation->run();
        $username = post('username', true);

        if ($result === true) {
            $remember = (int) (bool) post('remember', true);
            $this->log_user_in($username, $remember);
        } else {
            // Handle failed login
            $should_block = $this->model->increment_failed_login_attempts($username);
            
            if ($should_block) {
                // Clear validation errors before blocking
                unset($_SESSION['form_submission_errors']);
                redirect($this->module_name . '/not_allowed');
            }
            
            $this->login();
        }
    }

    /**
     * Log a user in and handle the authentication response.
     * 
     * @param string $username The username to authenticate
     * @param int $remember 0 for session-only, 1 for persistent cookie (30 days)
     * @return void
     */
    public function log_user_in(string $username, int $remember): void {
        
        $token = $this->model->log_user_in($username, $remember);

        if ($token === false) {
            http_response_code(500); 
            $fail_msg = (ENV === 'dev') ? 'Could not find record with username of: '.$username : '';
            die($fail_msg);
        }

        // Reset failed login counters and lockout information.
        $this->model->after_login_tasks($username);

        $is_mx_request = from_trongate_mx();

        if ($is_mx_request === true) {
            http_response_code(200);
            echo $token;
            die();
        }

        redirect($this->dashboard_home);
    }

    /**
     * Login validation callback
     * 
     * @param string $submitted_username The username submitted in the login form
     * @return string|bool True if credentials are valid, error message otherwise
     */
    public function login_check(string $submitted_username): string|bool {
        block_url('trongate_administrators/login_check');

        $submitted_password = post('password');
        $validate_credentials = $this->model->validate_credentials($submitted_username, $submitted_password);

        if ($validate_credentials === true) {
            return true;
        }
        
        return 'You did not enter a correct username and/or password.';
    }

    /**
     * Display paginated list of records with per-page selector
     * 
     * @return void
     */
    public function manage(): void {
        $this->trongate_security->make_sure_allowed();
        
        $limit = $this->get_limit();
        $offset = $this->get_offset();
        
        $rows = $this->model->get_all_paginated($limit, $offset);
        $rows = $this->model->prepare_records_for_display($rows);
        $total_rows = $this->model->count_all();
  
        $data = [
            'rows' => $rows,
            'pagination_data' => $this->get_pagination_data($limit, $total_rows),
            'view_module' => $this->module_name,
            'view_file' => 'manage',
            'per_page_options' => $this->per_page_options,
            'selected_per_page' => $this->get_selected_per_page()
        ];
        
        $this->templates->admin($data);
    }

    /**
     * Ensure the current user has appropriate access permissions.
     * 
     * @return string|null The authentication token if access is granted, null otherwise
     */
    public function make_sure_allowed(): ?string {
        block_url('trongate_administrators/make_sure_allowed');
        $token = $this->trongate_tokens->attempt_get_valid_token(1);

        // Handle API/MX requests
        if (from_trongate_mx() === true) {
            http_response_code($token ? 200 : 401);
            echo $token ?: '';
            die();
        }

        // Handle web requests with no valid token
        if ($token === false) {
            if (ENV === 'dev') {
                // DEV: Auto-login as first active user
                $user_obj = $this->model->get_any_active_user();
                
                if ($user_obj !== false) {
                    $this->model->log_user_in($user_obj->username, 1);
                    $token = $this->trongate_tokens->attempt_get_valid_token(1);
                } else {
                    http_response_code(500);
                    die('No active users found in database');
                }
            } else {
                // PROD: Redirect to login page
                redirect($this->login_url);
            }
        }

        return $token;
    }

    /**
     * Get current pagination limit from session
     * 
     * @return int The current page limit
     */
    private function get_limit(): int {
        if (isset($_SESSION['selected_per_page'])) {
            return $this->per_page_options[$_SESSION['selected_per_page']];
        }
        return $this->default_limit;
    }
    
    /**
     * Calculate pagination offset based on page number
     * 
     * @return int The offset for database queries
     */
    private function get_offset(): int {
        $page_num = segment(3, 'int');
        return ($page_num > 1) ? ($page_num - 1) * $this->get_limit() : 0;
    }

    /**
     * Generate pagination configuration data
     * 
     * @param int $limit Number of records per page
     * @param int $total_rows Total number of records
     * @return array Pagination configuration
     */
    private function get_pagination_data(int $limit, int $total_rows): array {
        return [
            'total_rows' => $total_rows,
            'page_num_segment' => 3,
            'limit' => $limit,
            'pagination_root' => $this->module_name . '/manage',
            'record_name_plural' => 'records',
            'include_showing_statement' => true
        ];
    }

    /**
     * Get selected per-page index from session
     * 
     * @return int The index of the selected per-page option
     */
    private function get_selected_per_page(): int {
        return $_SESSION['selected_per_page'] ?? 1;
    }

    /**
     * Set number of records per page for pagination
     * 
     * @return void
     */
    public function set_per_page(): void {
        $this->trongate_security->make_sure_allowed();
        
        $selected_index = segment(3, 'int');
        
        if (!isset($this->per_page_options[$selected_index])) {
            $selected_index = 1;
        }
        
        $_SESSION['selected_per_page'] = $selected_index;
        redirect($this->module_name . '/manage');
    }

    /**
     * Validates username availability and format for create/update operations.
     * 
     * This validation callback ensures that submitted usernames meet the following criteria:
     * 1. Optional field (empty string passes validation)
     * 2. Contains only letters, numbers, and underscores (a-z, A-Z, 0-9, _)
     * 3. Is not already taken by another record
     * 
     * For new records (update_id = 0): Checks if username exists in the system
     * For existing records (update_id > 0): Allows same username for current record,
     * but prevents duplication with other records.
     * 
     * The {label} placeholder in error messages is automatically replaced by the
     * validation library with the appropriate field label.
     * 
     * @param string $str The username value submitted in the form
     * @return string|bool Returns true if validation passes, 
     *                     or an error message string if validation fails
     */
    public function username_check(string $str): string|bool {
        block_url('trongate_administrators/username_check');

        if ($str === '') {
            return true;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $str)) {
            return 'The {label} can only contain letters, numbers, and underscores.';
        }
        
        $update_id = (int) segment(3);
        $is_available = $this->model->is_username_available($str, $update_id);
        
        if ($is_available === false) {
            return $update_id === 0 
                ? 'The {label} is already taken. Please choose another.'
                : 'The {label} is already in use by another account.';
        }
        
        return true;
    }
}