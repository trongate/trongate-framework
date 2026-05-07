<?php
/**
 * Login Module
 *
 * Portable, configurable authentication module for Trongate v2.
 * Supports multiple user levels, each with its own target table,
 * field mappings, and view files.
 */
class Login extends Trongate {

    /**
     * Constructor
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
    }

    /**
     * Determine the target user level from the URL.
     *
     * Scans URL segments for configured secret_login_word values.
     * If any level has a secret configured, only the correct secret
     * word grants access — numeric level IDs alone are rejected.
     *
     * If no level has a secret configured, falls back to extracting
     * the user level ID from segment(3) as an integer.
     *
     * @return int The user level ID
     */
    private function resolve_level(): int {

        $levels = $this->model->get_configured_level_configs();
        $last_segment = get_last_part(current_url(), '/');

        // Check if last URL segment matches a configured secret word
        foreach ($levels as $level_id => $config) {
            if (!empty($config['secret_login_word']) && $last_segment === $config['secret_login_word']) {
                return $this->resolve_level_from_secret($levels, (int) $level_id);
            }
        }

        // No secret matched — try numeric segment
        $segment = segment(3, 'int');

        if ($segment > 0 && isset($levels[$segment])) {
            // Only reject numeric access if this specific level has a secret
            if (!empty($levels[$segment]['secret_login_word'])) {
                $this->show_404();
                die();
            }
            return $segment;
        }

        // No level could be determined
        $this->show_404();
        die();
    }

    /**
     * Resolve level ID when a secret word has been matched.
     *
     * If the last URL segment is a numeric, configured level, that
     * level is used. Otherwise, the matched secret's own level is returned.
     *
     * @param array $levels All configured level configs
     * @param int $matched_level The level matched by secret word
     * @return int The resolved user level ID
     */
    private function resolve_level_from_secret(array $levels, int $matched_level): int {
        $last = $this->url->get_last_segment();
        $level = (int) $last;

        if ($level > 0 && isset($levels[$level])) {
            return $level;
        }

        return $matched_level;
    }

    // -----------------------------------------------------------------
    // Login
    // -----------------------------------------------------------------

    /**
     * Default route - shows login form.
     *
     * @return void
     */
    public function index(): void {
        $this->login();
    }

    /**
     * Display the login form for a given user level.
     *
     * URL: /login/{user_level_id}
     *
     * @return void
     */
    public function login(): void {
        // Guard: require a third URL segment to identify the user level
        if (segment(3) === '') {
            redirect(BASE_URL);
            return;
        }

        $user_level_id = $this->resolve_level();

        // Redirect if already logged in
        $token = $this->trongate_tokens->attempt_get_valid_token($user_level_id);

        if ($token !== false) {
            $config = $this->model->get_level_config($user_level_id);
            redirect($config['redirect_on_success']);
            return;
        }

        // Destroy lingering tokens
        $this->trongate_tokens->destroy();

        $config = $this->model->get_level_config($user_level_id);
        $level_slug = $this->model->get_login_url($user_level_id);

        $data['form_location'] = BASE_URL . 'login/submit_login/' . $level_slug;
        $data['user_level_id'] = $user_level_id;
        $data['login_url'] = $level_slug;
        $data['fields'] = $config['fields'];
        $data['identifier_label'] = $this->model->get_identifier_label($user_level_id);
        $data['allow_remember'] = $config['allow_remember'] ?? 0;
        $data['view_module'] = $this->module_name;
        $data['view_file'] = $config['view_file'];
        // Only include forgot-password URL when enabled for this user level
        if (!empty($config['enable_forgot_password'])) {
            $data['forgot_password_url'] = 'login/forgot_password/' . $level_slug;
        }

        // Determine which view file to use
        $view_file = $config['view_file'] ?? 'login_default';

        $this->view($view_file, $data);
    }

    /**
     * Handle login form submission.
     *
     * URL: POST /login/submit_login/{user_level_id}
     *
     * @return void
     */
    public function submit_login(): void {
        $user_level_id = $this->resolve_level();
        $config = $this->model->get_level_config($user_level_id);
        $ident_label = strtolower($this->model->get_identifier_label($user_level_id));

        $level_slug = $this->model->get_login_url($user_level_id);

        // Rate limiting check (before validation)
        $this->model->remove_expired_restrictions($user_level_id);

        if (!$this->model->is_login_allowed(post('identifier', true), $user_level_id)) {
            redirect('login/not_allowed/' . $level_slug);
            return;
        }

        // Set validation rules with custom credentials callback
        $this->validation->set_rules('identifier', $ident_label, 'required|callback_credentials_valid');
        $this->validation->set_rules('password', 'password', 'required');

        if ($this->validation->run() === true) {
            // Credentials are valid — log the user in
            $identifier = post('identifier', true);
            $remember = (int) (bool) post('remember');
            $token = $this->model->log_user_in($identifier, $user_level_id, $remember);

            if ($token === false) {
                http_response_code(500);
                $msg = 'Authentication succeeded but token generation failed. ';
                $msg .= 'Ensure the target table has a valid ' . $config['user_ref_field'] . '.';
                echo (ENV === 'dev') ? $msg : 'An internal error occurred.';
                die();
            }

            $this->model->clear_failed_attempts($identifier, $user_level_id);
            redirect($config['redirect_on_success']);
        }

        // Validation failed — record the attempt
        $this->model->record_failed_attempt(post('identifier', true), $user_level_id);

        // Check if the user is now rate-limited
        if (!$this->model->is_login_allowed(post('identifier', true), $user_level_id)) {
            redirect('login/not_allowed/' . $level_slug);
            return;
        }

        // Redisplay form with validation errors
        $this->login();
    }

    /**
     * Display rate-limited page.
     *
     * URL: /login/not_allowed/{user_level_id}
     *
     * @return void
     */
    public function not_allowed(): void {
        $user_level_id = $this->resolve_level();
        $block_duration = $this->model->get_global_config('block_duration') ?? 900;

        $data['block_duration'] = (int) ($block_duration / 60);
        $data['user_level_id'] = $user_level_id;
        $data['login_url'] = $this->model->get_login_url($user_level_id);
        $data['view_module'] = $this->module_name;
        $data['view_file'] = 'not_allowed';

        $this->view('not_allowed', $data);
    }

    /**
     * Unlock all rate-limited users (dev mode only).
     *
     * URL: /login/unlock
     *
     * @return void
     */
    public function unlock(): void {
        if (ENV !== 'dev') {
            http_response_code(403);
            echo 'This endpoint is only available in development mode.';
            die();
        }

        $this->model->unlock_all();

        echo 'All rate-limit restrictions have been cleared.';
    }

    /**
     * Log the user out.
     *
     * Determines the correct login URL from the user's current token
     * before destroying it, then redirects to that level's login form.
     * Falls back to the homepage if no token is found.
     *
     * If the user's level does not have a secret_login_word but other
     * levels do, the numeric ID cannot be used (it would 404). In that
     * case, the user is sent to the homepage as a safe fallback.
     *
     * URL: /login/logout
     *
     * @return void
     */
    public function logout(): void {
        // Determine user level from the current token BEFORE destroying it
        $user_obj = $this->trongate_tokens->get_user_obj();

        $redirect_target = BASE_URL; // Default: homepage

        if ($user_obj !== false && isset($user_obj->user_level_id)) {
            $level_id = (int) $user_obj->user_level_id;
            $levels = $this->model->get_configured_level_configs();

            if (isset($levels[$level_id])) {
                $config = $levels[$level_id];

                if (!empty($config['secret_login_word'])) {
                    $redirect_target = 'login/login/' . $config['secret_login_word'];
                } else {
                    $redirect_target = 'login/login/' . $level_id;
                }
            }
        }

        // Destroy all tokens
        $this->trongate_tokens->destroy();

        redirect($redirect_target);
    }

    /**
     * Check if a user is logged in with the given user level.
     *
     * @param int|null $user_level_id The user level to check, or null for any
     * @return string|bool True if logged in, error message otherwise
     */
    public function is_logged_in(?int $user_level_id = null): string|bool {
        if ($user_level_id !== null) {
            $token = $this->trongate_tokens->attempt_get_valid_token($user_level_id);
        } else {
            $token = $this->trongate_tokens->attempt_get_valid_token();
        }

        if ($token !== false) {
            return true;
        }

        return 'You must be logged in to access this page.';
    }

    // -----------------------------------------------------------------
    // Forgot Password (delegates to child module)
    // -----------------------------------------------------------------

    /**
     * Display the forgot password form.
     *
     * URL: /login/forgot_password/{user_level_id}
     *
     * @return void
     */
    public function forgot_password(): void {
        $user_level_id = $this->resolve_level();
        $config = $this->model->get_level_config($user_level_id);

        if (empty($config['enable_forgot_password'])) {
            $this->show_404();
            die();
        }

        $this->module('login-forgot_password');
        $this->forgot_password->form();
    }

    /**
     * Handle forgot password form submission.
     *
     * URL: POST /login/submit_forgot_password/{user_level_id}
     *
     * @return void
     */
    public function submit_forgot_password(): void {
        $user_level_id = $this->resolve_level();
        $config = $this->model->get_level_config($user_level_id);

        if (empty($config['enable_forgot_password'])) {
            $this->show_404();
            die();
        }

        $this->module('login-forgot_password');
        $this->forgot_password->submit();
    }

    /**
     * Display the reset password form (from email link).
     *
     * URL: /login/reset_password/{token}
     *
     * @return void
     */
    public function reset_password(): void {
        // Resolve level from the reset token to check per-level config
        $token = segment(3);
        $user_level_id = $this->model->get_level_id_for_token($token);

        if ($user_level_id === null) {
            // Invalid token — pass through to the child module for error handling
            $this->module('login-forgot_password');
            $this->forgot_password->reset();
            return;
        }

        $config = $this->model->get_level_config($user_level_id);

        if (empty($config['enable_forgot_password'])) {
            $this->show_404();
            die();
        }

        $this->module('login-forgot_password');
        $this->forgot_password->reset();
    }

    /**
     * Handle the new password submission.
     *
     * URL: POST /login/submit_reset_password
     *
     * @return void
     */
    public function submit_reset_password(): void {
        $token = post('token', true);
        $user_level_id = $this->model->get_level_id_for_token($token);

        if ($user_level_id === null) {
            // Pass through to child module — it handles invalid tokens gracefully
            $this->module('login-forgot_password');
            $this->forgot_password->submit_reset();
            return;
        }

        $config = $this->model->get_level_config($user_level_id);

        if (empty($config['enable_forgot_password'])) {
            $this->show_404();
            die();
        }

        $this->module('login-forgot_password');
        $this->forgot_password->submit_reset();
    }

    // -----------------------------------------------------------------
    // Custom Validation Callback
    // -----------------------------------------------------------------

    /**
     * Custom validation callback that checks credentials against the database.
     *
     * Invoked by the validation module as part of the 'identifier' field rules.
     * Returns true if credentials are valid, or an error string otherwise.
     *
     * @param string $identifier The submitted identifier (pre-cleaned by post()).
     * @return string|bool True if valid, error message string if invalid.
     */
    public function credentials_valid(string $identifier): string|bool {
        block_url('login/credentials_valid');

        $user_level_id = $this->resolve_level();
        $password = post('password');  // Raw value — do not clean or trim

        $valid = $this->model->validate_credentials($identifier, $password, $user_level_id);

        if ($valid === true) {
            return true;
        }

        return 'The {label} and/or password you entered is incorrect.';
    }

    /**
     * Display the 404 error page.
     *
     * @return void
     */
    public function show_404(): void {
        $this->templates->error_404();
    }

}