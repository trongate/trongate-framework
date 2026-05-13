<?php
/**
 * Login Model
 *
 * Handles authentication, rate limiting, token management,
 * and password resets against configurable user tables.
 * Supports multiple user levels with independent configurations.
 */
class Login_model extends Model {

    private array $full_config = [];

    /**
     * Constructor
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
    }

    // -----------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------

    /**
     * Load the full configuration.
     *
     * Tries APPPATH/config/login.php first, then falls back to
     * modules/login/config/login.php within the module directory.
     *
     * @return array The full config array
     */
    public function load_config(): array {
        if (!empty($this->full_config)) {
            return $this->full_config;
        }

        $config = [];

        // Try app-level config first
        $app_config = APPPATH . '/config/login.php';
        if (file_exists($app_config)) {
            require $app_config;
        } else {
            // Fall back to module-level config
            $module_config = __DIR__ . '/config/login.php';
            if (file_exists($module_config)) {
                require $module_config;
            }
        }

        $this->full_config = $config['login'] ?? [];
        return $this->full_config;
    }

    /**
     * Get the configuration for a specific user level.
     *
     * Merges global settings with the level-specific config.
     *
     * @param int $user_level_id The trongate_user_levels.id
     * @return array The resolved config for this user level
     */
    public function get_level_config(int $user_level_id): array {
        $full = $this->load_config();

        $level_specific = $full['user_levels'][$user_level_id] ?? [];

        if (empty($level_specific)) {
            throw new \Exception('No login configuration found for user level: ' . $user_level_id);
        }

        // Merge with globals
        $defaults = [
            'target_table'        => '',
            'user_ref_field'      => 'trongate_user_id',
            'redirect_on_success' => '',
            'allow_remember'      => 0,
            'remember_days'       => 0,
            'view_file'           => $full['default_view_file'] ?? 'login_default',
            'fields'              => [
                'identifiers' => [
                    'email_address' => ['column' => 'email_address', 'label' => 'Email Address']
                ],
                'password'   => ['column' => 'password', 'label' => 'Password']
            ]
        ];

        $config = array_merge($defaults, $level_specific);

        // Backward compatibility: detect old 'identifier' format
        if (isset($config['fields']['identifier']) && !isset($config['fields']['identifiers'])) {
            $old = $config['fields']['identifier'];
            $config['fields']['identifiers'] = [
                $old['column'] => ['column' => $old['column'], 'label' => $old['label']]
            ];
            unset($config['fields']['identifier']);
        }

        return $config;
    }

    /**
     * Get a global config value.
     *
     * @param string $key The config key
     * @return mixed The value, or null if not found
     */
    public function get_global_config(string $key): mixed {
        $full = $this->load_config();
        return $full[$key] ?? null;
    }

    /**
     * Get the list of all configured user levels.
     *
     * @return array Array of user level IDs (int)
     */
    public function get_configured_levels(): array {
        $full = $this->load_config();
        $levels = $full['user_levels'] ?? [];
        return array_keys($levels);
    }

    /**
     * Get all configured user level configs, keyed by level ID.
     *
     * @return array Full user_levels config array
     */
    public function get_configured_level_configs(): array {
        $full = $this->load_config();
        return $full['user_levels'] ?? [];
    }

    /**
     * Get the URL slug for a user level, respecting secret_login_word.
     *
     * Returns the secret word if one is configured for the given level,
     * or the numeric ID otherwise.
     *
     * @param int $user_level_id The user level ID
     * @return string The slug (secret word or numeric ID)
     */
    public function get_login_url(int $user_level_id): string {
        $full = $this->load_config();

        if (isset($full['user_levels'][$user_level_id])) {
            $config = $full['user_levels'][$user_level_id];
            if (!empty($config['secret_login_word'])) {
                return $config['secret_login_word'];
            }
        }

        return (string) $user_level_id;
    }

    // -----------------------------------------------------------------
    // Authentication
    // -----------------------------------------------------------------

    /**
     * Validate login credentials against the configured user table
     * for a specific user level.
     *
     * Uses timing-safe pattern: always performs password_verify even
     * when the user doesn't exist, to prevent username enumeration.
     *
     * @param string $identifier The submitted identifier (email, username, etc.)
     * @param string $password The submitted plain-text password
     * @param int $user_level_id The user level to authenticate against
     * @return bool True if credentials are valid
     */
    public function validate_credentials(string $identifier, string $password, int $user_level_id): bool {
        $config = $this->get_level_config($user_level_id);
        $password_field = $config['fields']['password']['column'];

        $user = $this->find_user($identifier, $user_level_id);

        // Get a timing-safe hash
        $hash_to_verify = $this->get_timing_safe_hash($user_level_id);

        if ($user !== false && !empty($user->{$password_field})) {
            $hash_to_verify = $user->{$password_field};
        }

        $this->module('login-password_handler');
        $password_valid = $this->password_handler->verify_password($password, $hash_to_verify);

        return ($user !== false)
            && !empty($user->{$password_field})
            && (int) ($user->active ?? 1) === 1
            && $password_valid;
    }

    /**
     * Find a user record in the configured target table for a user level.
     *
     * @param string $identifier The identifier value
     * @param int $user_level_id The user level whose config to use
     * @return object|bool The user record, or false
     */
    /**
     * Get the list of identifier columns for a given user level.
     *
     * @param int $user_level_id The user level
     * @return array Array of column names
     */
    public function get_identifier_columns(int $user_level_id): array {
        $config = $this->get_level_config($user_level_id);
        $columns = [];

        foreach ($config['fields']['identifiers'] as $ident) {
            $columns[] = $ident['column'];
        }

        return $columns;
    }

    /**
     * Get a human-readable combined identifier label.
     *
     * e.g. ['Username', 'Email'] -> 'Username or Email'
     *
     * @param int $user_level_id The user level
     * @return string The combined label
     */
    public function get_identifier_label(int $user_level_id): string {
        $config = $this->get_level_config($user_level_id);
        $labels = [];

        foreach ($config['fields']['identifiers'] as $ident) {
            $labels[] = $ident['label'];
        }

        return implode(' or ', $labels);
    }

    /**
     * Find a user record, trying each configured identifier column.
     *
     * Iterates through all identifier columns in order and returns
     * the first matching record. The submitted value can match any
     * of the configured identifiers (e.g. username OR email).
     *
     * @param string $identifier   The submitted identifier value
     * @param int    $user_level_id The user level to search against
     * @return object|bool The user record on success, false if not found
     */
    public function find_user(string $identifier, int $user_level_id): object|bool {
        $config = $this->get_level_config($user_level_id);
        $table = $config['target_table'];
        $ident_columns = $this->get_identifier_columns($user_level_id);

        foreach ($ident_columns as $col) {
            $sql = 'SELECT * FROM ' . $this->quote_id($table)
                 . ' WHERE ' . $this->quote_id($col)
                 . ' = :identifier LIMIT 1';

            $rows = $this->db->query_bind($sql, ['identifier' => $identifier], 'object');

            if (!empty($rows)) {
                return $rows[0];
            }
        }

        return false;
    }

    /**
     * Find a user by their configured identifier (e.g. username) or email.
     *
     * Intended for the forgot-password flow. Delegates to find_user()
     * which now tries all configured identifiers automatically.
     *
     * @param string $submitted    The value submitted by the user
     * @param int    $user_level_id The user level to search against
     * @return object|bool The user record on success, false if not found
     */
    public function find_user_lax(string $submitted, int $user_level_id): object|bool {
        return $this->find_user($submitted, $user_level_id);
    }

    /**
     * Get a timing-safe bcrypt hash for the given user level.
     *
     * Resolves the target table and password column from the level config,
     * then delegates to the password_handler child module.
     *
     * @param int $user_level_id The user level
     * @return string A valid bcrypt hash
     */
    private function get_timing_safe_hash(int $user_level_id): string {
        $config = $this->get_level_config($user_level_id);
        $this->module('login-password_handler');

        return $this->password_handler->get_timing_safe_hash(
            $config['target_table'],
            $config['fields']['password']['column']
        );
    }

    // -----------------------------------------------------------------
    // Rate Limiting
    // -----------------------------------------------------------------

    /**
     * Check whether a login attempt is allowed for the given identifier and user level.
     *
     * @param string $identifier The submitted identifier
     * @param int $user_level_id The user level
     * @return bool True if allowed, false if blocked
     */
    public function is_login_allowed(string $identifier, int $user_level_id): bool {
        $this->remove_expired_restrictions($user_level_id);

        $config = $this->get_level_config($user_level_id);
        $max_attempts = $this->get_global_config('max_failed_attempts') ?? 3;
        $block_duration = $this->get_global_config('block_duration') ?? 900;
        $block_time = time() - $block_duration;

        // Check by identifier
        $count = $this->count_recent_attempts($user_level_id, 'identifier', $identifier, $block_time);
        if ($count >= $max_attempts) {
            return false;
        }

        // Check by IP
        $count = $this->count_recent_attempts($user_level_id, 'ip_address', ip_address(), $block_time);
        if ($count >= $max_attempts) {
            return false;
        }

        return true;
    }

    /**
     * Count recent failed attempts matching a field.
     *
     * @param int $user_level_id
     * @param string $field The column to check ('identifier' or 'ip_address')
     * @param string $value The value to match
     * @param int $since_time Unix timestamp cutoff
     * @return int The count
     */
    private function count_recent_attempts(int $user_level_id, string $field, string $value, int $since_time): int {
        $config = $this->get_level_config($user_level_id);
        $table = $config['target_table'];

        $sql = 'SELECT COUNT(*) as cnt FROM login_attempts'
             . ' WHERE target_table = :target_table'
             . ' AND ' . $this->quote_id($field) . ' = :value'
             . ' AND attempted_at > :since_time';

        $rows = $this->db->query_bind($sql, [
            'target_table' => $table,
            'value' => $value,
            'since_time' => $since_time
        ], 'object');

        return !empty($rows) ? (int) $rows[0]->cnt : 0;
    }

    /**
     * Record a failed login attempt.
     *
     * @param string $identifier The submitted identifier
     * @param int $user_level_id The user level
     * @return void
     */
    public function record_failed_attempt(string $identifier, int $user_level_id): void {
        $config = $this->get_level_config($user_level_id);

        $this->db->insert([
            'target_table'  => $config['target_table'],
            'identifier'    => $identifier,
            'ip_address'    => ip_address(),
            'attempted_at'  => time()
        ], 'login_attempts');
    }

    /**
     * Remove expired restriction records.
     *
     * @param int $user_level_id The user level
     * @return void
     */
    public function remove_expired_restrictions(int $user_level_id): void {
        $config = $this->get_level_config($user_level_id);
        $block_duration = $this->get_global_config('block_duration') ?? 900;
        $cutoff = time() - $block_duration;

        $sql = 'DELETE FROM login_attempts'
             . ' WHERE target_table = :target_table'
             . ' AND attempted_at < :cutoff';

        $this->db->query_bind($sql, [
            'target_table' => $config['target_table'],
            'cutoff' => $cutoff
        ]);
    }

    /**
     * Clear all failed attempts for a given identifier.
     *
     * @param string $identifier The identifier that just logged in
     * @param int $user_level_id The user level
     * @return void
     */
    public function clear_failed_attempts(string $identifier, int $user_level_id): void {
        $config = $this->get_level_config($user_level_id);

        $sql = 'DELETE FROM login_attempts'
             . ' WHERE target_table = :target_table'
             . ' AND identifier = :identifier';

        $this->db->query_bind($sql, [
            'target_table' => $config['target_table'],
            'identifier' => $identifier
        ]);
    }

    /**
     * Unlock all users (dev mode only).
     *
     * @return void
     */
    public function unlock_all(): void {
        $sql = 'TRUNCATE TABLE login_attempts';
        $this->db->query($sql);
    }

    // -----------------------------------------------------------------
    // Login / Token Management
    // -----------------------------------------------------------------

    /**
     * Log a user in and create an authentication token.
     *
     * @param string $identifier The user's identifier
     * @param int $user_level_id The user level
     * @param int $remember 0 for session, 1 for persistent cookie
     * @return string|bool The token string, or false on failure
     */
    public function log_user_in(string $identifier, int $user_level_id, int $remember = 0): string|bool {
        $this->module('trongate_tokens');
        $config = $this->get_level_config($user_level_id);
        $user = $this->find_user($identifier, $user_level_id);

        if ($user === false) {
            return false;
        }

        $trongate_user_id = (int) $user->{$config['user_ref_field']};

        if ($trongate_user_id <= 0) {
            return false;
        }

        $token_data = ['user_id' => $trongate_user_id];

        if ($remember === 1 && $config['allow_remember'] === 1) {
            $token_data['expiry_date'] = time() + (86400 * $config['remember_days']);
            $token = $this->trongate_tokens->generate_token($token_data);
            setcookie('trongatetoken', $token, $token_data['expiry_date'], '/');
        } else {
            $token = $this->trongate_tokens->generate_token($token_data);
            $_SESSION['trongatetoken'] = $token;
        }

        $this->update_login_stats($config, $user);
        return $token;
    }

    /**
     * Update login statistics on the user's record.
     *
     * After a successful login, checks whether the target table has
     * columns named 'num_logins' and/or 'last_login'. If present, they
     * are updated automatically &mdash; num_logins is incremented and
     * last_login is set to the current Unix timestamp.
     *
     * This method is purely opt-in: if the columns do not exist on the
     * target table, nothing happens. No schema changes are required.
     *
     * @param array $config The resolved user-level configuration
     * @param object $user  The authenticated user record
     * @return void
     */
    private function update_login_stats(array $config, object $user): void {
        $table = $config['target_table'];
        $user_ref_field = $config['user_ref_field'];
        $user_id = (int) $user->{$user_ref_field};

        if ($user_id <= 0) {
            return;
        }

        // Fetch full column info including types
        $columns = $this->db->describe_table($table, false);

        if ($columns === false || !is_array($columns)) {
            return; // Table not found or error
        }

        $set_clauses = [];
        $params = ['user_id' => $user_id];
        $has_last_login = false;

        foreach ($columns as $col) {
            $col_name = $col['Field'] ?? '';

            if ($col_name === 'num_logins') {
                $set_clauses[] = 'num_logins = num_logins + 1';
            }

            if ($col_name === 'last_login') {
                $has_last_login = true;
                $type = strtolower($col['Type'] ?? '');

                if (str_contains($type, 'int')) {
                    // Integer column — store Unix timestamp
                    $params['last_login'] = time();
                } elseif (str_starts_with($type, 'date')) {
                    // DATE column — store Y-m-d
                    $params['last_login'] = date('Y-m-d');
                } elseif (str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp')) {
                    // DATETIME or TIMESTAMP column
                    $params['last_login'] = date('Y-m-d H:i:s');
                } else {
                    // Unknown type — skip last_login update
                    $has_last_login = false;
                }

                if ($has_last_login) {
                    $set_clauses[] = 'last_login = :last_login';
                }
            }
        }

        if (empty($set_clauses)) {
            return; // Neither column exists (or unknown type)
        }

        $set_sql = implode(', ', $set_clauses);
        $sql = "UPDATE {$table} SET {$set_sql} WHERE {$user_ref_field} = :user_id";
        $this->db->query_bind($sql, $params);
    }

    // -----------------------------------------------------------------
    // Password Reset
    // -----------------------------------------------------------------

    /**
     * Generate a password reset token and return it.
     *
     * Resolves the user's target table from the level config, then delegates
     * token creation to the password_handler child module.
     *
     * @param string $identifier The user's identifier (e.g., email)
     * @param int $user_level_id The user level
     * @return string|bool The reset token string, or false if user not found
     */
    public function generate_reset_token(string $identifier, int $user_level_id): string|bool {
        $user = $this->find_user($identifier, $user_level_id);

        if ($user === false) {
            return false;
        }

        $config = $this->get_level_config($user_level_id);
        $this->module('login-password_handler');

        return $this->password_handler->generate_reset_token($identifier, $config['target_table']);
    }

    /**
     * Validate a password reset token.
     *
     * @param string $token The reset token
     * @return object|bool The reset record, or false if invalid/expired
     */
    public function validate_reset_token(string $token): object|bool {
        $this->module('login-password_handler');
        return $this->password_handler->validate_reset_token($token);
    }

    /**
     * Update a user's password using a validated reset token.
     *
     * Orchestrates the login-specific reset flow: delegates the password
     * write and token consumption to the password_handler child module,
     * then wipes existing auth tokens for the user so all sessions are
     * forced to re-authenticate.
     *
     * @param string $token The valid reset token
     * @param string $new_password The new plain-text password
     * @return bool True on success
     */
    public function reset_password(string $token, string $new_password): bool {
        $this->module('login-password_handler');
        $handler = $this->password_handler;

        $reset = $handler->validate_reset_token($token);

        if ($reset === false) {
            return false;
        }

        // Find the password field and primary identifier column for this target table
        $full = $this->load_config();
        $pw_field = 'password';
        $ident_field = 'email_address';

        foreach ($full['user_levels'] as $level_config) {
            if ($level_config['target_table'] === $reset->target_table) {
                $pw_field = $level_config['fields']['password']['column'];

                // Resolve the first identifier column (supports both old and new formats)
                if (isset($level_config['fields']['identifiers'])) {
                    $first_ident = reset($level_config['fields']['identifiers']);
                    $ident_field = $first_ident['column'];
                } elseif (isset($level_config['fields']['identifier']['column'])) {
                    $ident_field = $level_config['fields']['identifier']['column'];
                }

                break;
            }
        }

        $success = $handler->update_password_for_identifier(
            $reset->target_table,
            $ident_field,
            $reset->identifier,
            $pw_field,
            $new_password
        );

        if ($success === false) {
            return false;
        }

        $handler->consume_reset_token($token);

        // Delete existing auth tokens for this user (force re-login)
        $user = $this->find_user($reset->identifier, $this->get_level_id_for_table($reset->target_table));

        if ($user !== false) {
            $this->module('trongate_tokens');

            $level_config = $this->get_level_config($this->get_level_id_for_table($reset->target_table));
            $trongate_user_id = (int) $user->{$level_config['user_ref_field']};

            if ($trongate_user_id > 0) {
                $this->trongate_tokens->delete_old_tokens($trongate_user_id);
            }
        }

        return true;
    }

    /**
     * Send a password reset email via the password_handler child module.
     *
     * @param string $to_email The recipient email address
     * @param string $reset_link The full reset URL
     * @return bool True if sent successfully
     */
    public function send_reset_email(string $to_email, string $reset_link): bool {
        $this->module('login-password_handler');
        return $this->password_handler->send_reset_email($to_email, $reset_link);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Find the user level ID for a given target table name.
     *
     * @param string $table_name
     * @return int The user level ID, or 0 if not found
     */
    /**
     * Get the user level ID associated with a reset token.
     *
     * @param string $token The reset token
     * @return int|null The user level ID, or null if token is invalid
     */
    public function get_level_id_for_token(string $token): ?int {
        $reset = $this->validate_reset_token($token);

        if ($reset === false) {
            return null;
        }

        return $this->get_level_id_for_table($reset->target_table);
    }

    /**
     * Get the user level ID associated with a reset token, ignoring state.
     *
     * Unlike get_level_id_for_token(), this still resolves the level when
     * the token has been used or has expired — used by the
     * consumed/expired-token error page so it can link back to the correct
     * forgot-password form.
     *
     * @param string $token The reset token
     * @return int|null The user level ID, or null if the token is unknown
     */
    public function get_level_id_for_token_any(string $token): ?int {
        $this->module('login-password_handler');
        $reset = $this->password_handler->find_reset_token_row($token);

        if ($reset === false) {
            return null;
        }

        $level_id = $this->get_level_id_for_table($reset->target_table);

        return ($level_id > 0) ? $level_id : null;
    }

    /**
     * Get the user level ID for a given target table name.
     *
     * @param string $table_name
     * @return int The user level ID, or 0 if not found
     */
    private function get_level_id_for_table(string $table_name): int {
        $full = $this->load_config();

        foreach ($full['user_levels'] as $level_id => $level_config) {
            if ($level_config['target_table'] === $table_name) {
                return (int) $level_id;
            }
        }

        return 0;
    }

    /**
     * Hash a password using bcrypt.
     *
     * Delegates to the password_handler child module so the same cost
     * factor and bcrypt configuration is used everywhere.
     *
     * @param string $password Plain-text password
     * @return string bcrypt hash
     */
    public function hash_password(string $password): string {
        $this->module('login-password_handler');
        return $this->password_handler->hash_password($password);
    }

    /**
     * Safely quote a database identifier.
     *
     * @param string $id Table or column name
     * @return string Safely quoted identifier
     */
    private function quote_id(string $id): string {
        return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $id) . '`';
    }

}
