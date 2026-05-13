<?php
/**
 * Password Handler Child Module
 *
 * Provides shared password operations — hashing, verification, strength
 * validation, reset-token lifecycle, and reset-email composition — for the
 * Login module and any other module (sign-up, profile-edit, admin-set) that
 * needs to touch passwords. Loaded by the Login parent module. The controller
 * exposes the public API; the model handles only DB access. No URL endpoints.
 */
class Password_handler extends Trongate {

    private array $password_config = [];

    /**
     * Constructor
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        $this->parent_module = 'login';
    }

    // -----------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------

    /**
     * Load password-related configuration from the login config file.
     *
     * Reads APPPATH/config/login.php. If absent, the built-in defaults
     * apply. New password-strength keys are expected under
     * $config['login']['password']; the legacy top-level keys
     * 'password_hash_cost' and 'reset_token_lifespan' remain readable
     * from their existing location.
     *
     * @return array The merged password configuration
     */
    private function load_password_config(): array {
        if (!empty($this->password_config)) {
            return $this->password_config;
        }

        $defaults = [
            'min_length'           => 8,
            'require_upper'        => false,
            'require_lower'        => false,
            'require_digit'        => false,
            'require_special'      => false,
            'deny_common'          => [],
            'hash_cost'            => 11,
            'reset_token_lifespan' => 3600
        ];

        $config = [];

        $app_config = APPPATH . '/config/login.php';
        if (file_exists($app_config)) {
            require $app_config;
        }

        $login = $config['login'] ?? [];

        // Legacy top-level keys (backward compatibility)
        if (isset($login['password_hash_cost'])) {
            $defaults['hash_cost'] = $login['password_hash_cost'];
        }
        if (isset($login['reset_token_lifespan'])) {
            $defaults['reset_token_lifespan'] = $login['reset_token_lifespan'];
        }

        // New password sub-array (overlays defaults)
        $password = $login['password'] ?? [];
        $this->password_config = array_merge($defaults, $password);

        return $this->password_config;
    }

    // -----------------------------------------------------------------
    // Hashing & Verification
    // -----------------------------------------------------------------

    /**
     * Hash a plain-text password using bcrypt.
     *
     * The cost factor is read from config ('password_hash_cost'),
     * defaulting to 11.
     *
     * @param string $plain The plain-text password
     * @return string The bcrypt hash
     */
    public function hash_password(string $plain): string {
        $config = $this->load_password_config();
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => $config['hash_cost']]);
    }

    /**
     * Verify a plain-text password against a stored bcrypt hash.
     *
     * @param string $plain The submitted plain-text password
     * @param string $hash The stored bcrypt hash
     * @return bool True if the password matches the hash
     */
    public function verify_password(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    /**
     * Get a timing-safe bcrypt hash for the given table.
     *
     * Returns an existing hash from the target table when possible, or a
     * dummy hash otherwise. Used during credential verification to keep
     * timing roughly constant whether or not the submitted user exists,
     * preventing username enumeration via response-time analysis.
     *
     * @param string $target_table The user table
     * @param string $password_field The column that stores password hashes
     * @return string A valid bcrypt hash
     */
    public function get_timing_safe_hash(string $target_table, string $password_field): string {
        $existing = $this->model->find_existing_password_hash($target_table, $password_field);

        if ($existing !== null) {
            return $existing;
        }

        return $this->hash_password('timing_protection_dummy');
    }

    // -----------------------------------------------------------------
    // Strength Validation
    // -----------------------------------------------------------------

    /**
     * Validate a password against the configured strength rules.
     *
     * Returns true on pass, or a single human-readable error string on the
     * first failed check so the user fixes one thing at a time. Check order:
     * minimum length, digit, uppercase, lowercase, special character, deny-list.
     *
     * @param string $plain The plain-text password to validate
     * @return true|string True on pass, error message string on first failure
     */
    public function validate_strength(string $plain): true|string {
        $config = $this->load_password_config();
        $min = (int) $config['min_length'];

        if (strlen($plain) < $min) {
            return 'Password must be at least ' . $min . ' characters long.';
        }

        if (!empty($config['require_digit']) && !preg_match('/\d/', $plain)) {
            return 'Password must contain at least one digit.';
        }

        if (!empty($config['require_upper']) && !preg_match('/[A-Z]/', $plain)) {
            return 'Password must contain at least one uppercase letter.';
        }

        if (!empty($config['require_lower']) && !preg_match('/[a-z]/', $plain)) {
            return 'Password must contain at least one lowercase letter.';
        }

        if (!empty($config['require_special']) && !preg_match('/[^A-Za-z0-9]/', $plain)) {
            return 'Password must contain at least one special character.';
        }

        $deny = $config['deny_common'] ?? [];
        if (!empty($deny) && is_array($deny) && in_array($plain, $deny, true)) {
            return 'That password is on the list of disallowed passwords. Please choose another.';
        }

        return true;
    }

    // -----------------------------------------------------------------
    // Reset Token Lifecycle
    // -----------------------------------------------------------------

    /**
     * Generate a password reset token for the given identifier and table.
     *
     * Writes a row into the password_resets table with the configured
     * lifespan. Returns the 64-character token string.
     *
     * @param string $identifier The user's identifier (e.g. email or username)
     * @param string $target_table The user table the token applies to
     * @return string The new reset token
     */
    public function generate_reset_token(string $identifier, string $target_table): string {
        $config = $this->load_password_config();
        $expiry = time() + (int) $config['reset_token_lifespan'];

        $token = make_rand_str(64);

        $this->model->insert_reset_token($identifier, $target_table, $token, $expiry);

        return $token;
    }

    /**
     * Validate a reset token.
     *
     * Returns the password_resets row if the token exists, has not been
     * used, and has not expired. Returns false otherwise.
     *
     * @param string $token The reset token
     * @return object|bool The reset record on success, false otherwise
     */
    public function validate_reset_token(string $token): object|bool {
        return $this->model->find_active_reset_token($token);
    }

    /**
     * Find any reset-token row by token string, regardless of state.
     *
     * Returns the row whether the token has been used or has expired —
     * unlike `validate_reset_token()`, which returns false in those cases.
     * Used so the consumed/expired-token error page can derive the
     * originating user level and link back to the correct form.
     *
     * @param string $token The reset token
     * @return object|bool The reset record on success, false if no such row
     */
    public function find_reset_token_row(string $token): object|bool {
        return $this->model->find_reset_token_row($token);
    }

    /**
     * Mark a reset token as consumed.
     *
     * Idempotent — calling this on an already-consumed token is a no-op.
     *
     * @param string $token The reset token
     * @return void
     */
    public function consume_reset_token(string $token): void {
        $this->model->mark_reset_token_used($token);
    }

    // -----------------------------------------------------------------
    // Password Write
    // -----------------------------------------------------------------

    /**
     * Hash a new password and write it to a target table.
     *
     * Hashes the plain-text password, then delegates the column-validated
     * UPDATE to the model. Returns false if the target table is missing
     * either the identifier column or the password column.
     *
     * @param string $target_table The user table
     * @param string $identifier_column The column to match on
     * @param string $identifier_value The value to match
     * @param string $password_field The column that stores the hashed password
     * @param string $new_plain The new plain-text password
     * @return bool True on success, false if the table or columns are wrong
     */
    public function update_password_for_identifier(
        string $target_table,
        string $identifier_column,
        string $identifier_value,
        string $password_field,
        string $new_plain
    ): bool {
        $hash = $this->hash_password($new_plain);

        return $this->model->write_password_hash_for_identifier(
            $target_table,
            $identifier_column,
            $identifier_value,
            $password_field,
            $hash
        );
    }

    // -----------------------------------------------------------------
    // Reset Email Composition
    // -----------------------------------------------------------------

    /**
     * Send a password reset email via the trongate_email module.
     *
     * Builds a plain-text message and delegates delivery to the dedicated
     * Trongate email module, which handles SMTP communication, MIME
     * formatting, and connection management.
     *
     * @param string $to_email The recipient email address
     * @param string $reset_link The full reset URL
     * @return bool True if sent successfully
     */
    public function send_reset_email(string $to_email, string $reset_link): bool {
        $body = "Hello,\n\n";
        $body .= "We received a request to reset your password.\n\n";
        $body .= "Click the link below to reset your password:\n";
        $body .= $reset_link . "\n\n";
        $body .= "This link will expire in 1 hour.\n\n";
        $body .= "If you did not request a password reset, you can safely ignore this email.\n";

        $html_body = nl2br($body);

        $this->module('trongate_email');

        return $this->trongate_email->send([
            'to_email' => $to_email,
            'subject' => 'Password Reset Request',
            'body_html' => '<p>' . $html_body . '</p>'
        ]);
    }

}
