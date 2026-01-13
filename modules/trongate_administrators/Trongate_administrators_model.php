<?php
class Trongate_administrators_model extends Model {

    private string $table_name = 'trongate_administrators';
    private int $max_failed_attempts = 3;
    private int $block_time_seconds = 900; // Fifteen minutes
    private int $min_block_timestamp = 1000; // Threshold for "active" blocks

	/**
	 * Find a single record based on a column value.
	 *
	 * @param string $column The column name to match
	 * @param mixed $value The value to match
	 * @return object|false Returns record object if found, false otherwise
	 */
	private function find_one(string $column, $value): object|false {
	    $sql = "SELECT * FROM {$this->table_name} WHERE {$column} = :{$column} LIMIT 1";
	    $params = [$column => $value];
	    
	    $rows = $this->db->query_bind($sql, $params, 'object');
	    return !empty($rows) ? $rows[0] : false;
	}

    /**
     * Get data from database for a specific record
     * 
     * Retrieves a single record by ID and prepares it for form display.
     * Returns false if record not found.
     * 
     * @param int $record_id The record ID to fetch
     * @return array<string, mixed>|false Record data formatted for form display
     *                                    Returns false if record not found
     */
    public function get_data_from_db(int $record_id): array|false {
        $record = $this->find_one('id', $record_id);
        
        if ($record === false) {
            return false;
        }
        
        return (array) $record;
    }
    
    /**
     * Get raw data from POST request
     * 
     * Retrieves form data from POST request without any processing.
     * This method returns exactly what was posted, no conversions.
     * 
     * @return array<string, mixed> Raw POST data
     */
    public function get_data_from_post(): array {
        return [
            'username' => post('username', true),
            'active'   => post('active', true)
        ];
    }
    
    /**
     * Convert posted administrator data for database storage
     * 
     * Takes raw POST data and converts it to database-ready format.
     * Specifically handles active checkbox conversion from boolean to integer (0/1).
     * 
     * @param array<string, mixed> $post_data Raw POST data
     * @return array<string, mixed> Database-ready data
     */
    public function convert_posted_data_for_db(array $post_data): array {
        $data = $post_data;
        $data['active'] = (int) (bool) $data['active'];
        return $data;
    }

	/**
	 * Reset expired login blocks by clearing failed login counters.
	 * 
	 * Finds records with active but expired login blocks (login_blocked_until > min threshold 
	 * AND < current time) and resets their failed login tracking fields.
	 * 
	 * @return void
	 */
	public function remove_expired_restrictions(): void {
	    $params['current_time'] = time();
	    $params['min_timestamp'] = $this->min_block_timestamp;
	    
	    $sql = 'SELECT * FROM ' . $this->table_name . ' 
	            WHERE login_blocked_until > :min_timestamp 
	            AND login_blocked_until < :current_time';
	    
	    $rows = $this->db->query_bind($sql, $params, 'object');

	    $data = [
	        'failed_login_attempts' => 0,
	        'last_failed_attempt' => 0,
	        'login_blocked_until' => 0,
	        'failed_login_ip' => ''
	    ];

	    foreach($rows as $row) {
	        $update_id = (int) $row->id;
	        $this->db->update($update_id, $data, $this->table_name);
	    }
	}

	/**
	 * Determines if a login attempt is allowed for a given username or IP address.
	 *
	 * This function checks if the specified username (if provided) or the
	 * current IP address has exceeded the maximum number of failed login
	 * attempts and is temporarily blocked.
	 *
	 * @param string|null $username Optional. The username to check against. Defaults to null.
	 *
	 * @return bool Returns true if the login attempt is allowed; false if blocked.
	 */
	public function is_login_attempt_allowed(?string $username = null): bool {
	    $params = [
	        'failed_login_ip' => ip_address(),
	        'current_time' => time(),
	        'max_attempts' => $this->max_failed_attempts
	    ];
	    
	    if ($username !== null) {
	        $params['username'] = $username;
	        $sql = 'SELECT * FROM ' . $this->table_name . ' 
	                WHERE (username = :username OR failed_login_ip = :failed_login_ip)
	                AND failed_login_attempts >= :max_attempts
	                AND login_blocked_until > :current_time';
	    } else {
	        $sql = 'SELECT * FROM ' . $this->table_name . ' 
	                WHERE failed_login_ip = :failed_login_ip
	                AND failed_login_attempts >= :max_attempts
	                AND login_blocked_until > :current_time';
	    }
	    
	    $rows = $this->db->query_bind($sql, $params, 'object');
	    return empty($rows);
	}

	/**
	 * Get a timing-safe hash for password verification.
	 * 
	 * Returns a valid bcrypt hash that ensures constant-time verification
	 * regardless of whether the submitted username exists. This prevents
	 * username enumeration via timing attacks.
	 * 
	 * Uses any active user's hash when available, otherwise generates a
	 * valid bcrypt hash for fresh installs.
	 * 
	 * @return string A valid bcrypt hash for constant-time verification
	 */
	private function get_timing_safe_hash(): string {
	    $user_obj = $this->find_one('active', 1);
	    
	    // Use real user's hash when available
	    if ($user_obj !== false && !empty($user_obj->password)) {
	        return $user_obj->password;
	    }
	    
	    // Generate a valid bcrypt hash for timing protection
	    // Uses existing hash_password() method with correct settings
	    return $this->hash_password('timing_protection_dummy');
	}

	/**
	 * Validate submitted credentials with timing-attack protection.
	 *
	 * Authentication succeeds only when ALL conditions are met:
	 * 1. A record exists with the submitted username
	 * 2. The record has a non-empty password hash
	 * 3. password_verify() returns true for the submitted password
	 * 4. The record is marked active (active = 1)
	 *
	 * Timing protection: Always performs password_verify() with a valid
	 * bcrypt hash, ensuring consistent response time.
	 *
	 * @param string $username The submitted username
	 * @param string $password The submitted password
	 * @return bool True if authentication succeeds, false otherwise
	 */
	public function validate_credentials(string $username, string $password): bool {
	    // Attempt to retrieve the submitted user
	    $submitted_user = $this->find_one('username', $username);
	    
	    // Determine which hash to verify against
	    $hash_to_verify = $this->get_timing_safe_hash(); // Default (timing-safe)
	    
	    // Use submitted user's hash if available and valid
	    if ($submitted_user !== false && !empty($submitted_user->password)) {
	        $hash_to_verify = $submitted_user->password;
	    }
	    
	    // ALWAYS perform password verification (timing-safe operation)
	    $password_valid = $this->verify_password($password, $hash_to_verify);
	    
	    // Authentication succeeds ONLY when all conditions are met
	    return ($submitted_user !== false) 
	        && !empty($submitted_user->password)
	        && $password_valid 
	        && ((int) $submitted_user->active === 1);
	}

	/**
	 * Update a record in the database.
	 * 
	 * @param int $update_id The ID of the record to update
	 * @param array $data The data to update
	 * @return bool True if successful
	 */
	public function update(int $update_id, array $data): bool {
	    return $this->db->update($update_id, $data, $this->table_name);
	}

	/**
	 * Update a user's password.
	 * 
	 * This method hashes the provided plain-text password and updates the record.
	 * 
	 * @param int $update_id The ID of the user record to update
	 * @param string $new_password The new plain-text password
	 * @return bool True if successful
	 */
	public function update_password(int $update_id, string $new_password): bool {
	    $data['password'] = $this->hash_password($new_password);
	    return $this->db->update($update_id, $data, $this->table_name);
	}

    /**
     * Hash a plain-text password using bcrypt.
     *
     * @param string $password
     *
     * @return string
     */
    private function hash_password(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /**
     * Verify a plain-text password against a stored hash.
     *
     * @param string $plain_text_password
     * @param string $hashed_password
     *
     * @return bool
     */
    public function verify_password(string $plain_text_password, string $hashed_password): bool {
        return password_verify($plain_text_password, $hashed_password);
    }

	/**
	 * Increment failed login attempt counter for a user.
	 *
	 * @param string $username The username that failed authentication
	 * @return bool Returns true if user should be blocked, false otherwise
	 */
	public function increment_failed_login_attempts(string $username): bool {
	    $user_record = $this->find_one('username', $username);
	    
	    if ($user_record === false) {
	        return false; // User doesn't exist - no action needed
	    }
	    
	    $failed_login_attempts = (int) $user_record->failed_login_attempts;
	    
	    $data = [
	        'failed_login_attempts' => $failed_login_attempts + 1,
	        'last_failed_attempt' => time(),
	        'failed_login_ip' => ip_address()
	    ];
	    
	    // Check if user should be blocked
	    $should_block = ($data['failed_login_attempts'] >= $this->max_failed_attempts);
	    
	    if ($should_block) {
	        $data['login_blocked_until'] = time() + $this->block_time_seconds;
	    }
	    
	    $update_id = (int) $user_record->id;
	    $this->db->update($update_id, $data, $this->table_name);
	    
	    return $should_block;
	}

	/**
	 * Log a user in and persist authentication state.
	 *
	 * @param string $username
	 * @param int $remember 0 for session-only, 1 for persistent cookie (30 days)
	 *
	 * @return string|bool
	 * Returns the generated authentication token on success,
	 * or FALSE if the user could not be logged in.
	 */
	public function log_user_in(string $username, int $remember = 0): string|bool {
	    $this->module('trongate_tokens');
	    $user = $this->find_one('username', $username);

	    if ($user === false) {
	        return false;
	    }

	    $token_data = [
	        'user_id' => (int) $user->trongate_user_id
	    ];

	    if ($remember === 1) {
	        // Persist login for 30 days
	        $token_data['expiry_date'] = time() + (86400 * 30);
	        $token = $this->trongate_tokens->generate_token($token_data);
	        setcookie('trongatetoken', $token, $token_data['expiry_date'], '/');
	        return $token;
	    }

	    // Session-only authentication
	    $token = $this->trongate_tokens->generate_token($token_data);
	    $_SESSION['trongatetoken'] = $token;

	    return $token;
	}

	/**
	 * Reset failed login counters and lockout information for a user after a successful login.
	 *
	 * This method clears the following fields for the specified user:
	 * - failed_login_attempts
	 * - last_failed_attempt
	 * - login_blocked_until
	 * - failed_login_id
	 *
	 * @param string $username The username of the administrator who has successfully logged in.
	 * @return void
	 */
	public function after_login_tasks(string $username): void {
	    $data = [
	        'failed_login_attempts' => 0,
	        'last_failed_attempt' => 0,
	        'login_blocked_until' => 0,
	        'failed_login_ip' => ''
	    ];
	    
	    // First, find the user ID by username
	    $user = $this->find_one('username', $username);
	    
	    if ($user !== false) {
	        $update_id = (int) $user->id;
	        $this->db->update($update_id, $data, $this->table_name);
	    }
	}

	/**
	 * Fetch a user record by ID.
	 *
	 * @param int $user_id The user identifier to look up
	 * @return object|false Returns user object if found, false otherwise
	 */
	public function get_user_by_id(int $user_id): object|false {
	    return $this->find_one('id', $user_id);
	}

	/**
	 * Fetch a user record by authentication token.
	 * 
	 * @param string $token The authentication token to look up
	 * @return object|false Returns user object if found, false otherwise
	 */
	public function get_user_by_token(string $token): object|false {
	    $params['token'] = $token;
	    $sql = 'SELECT
	                    '.$this->table_name.'.* 
	                FROM
	                    '.$this->table_name.'
	                INNER JOIN
	                    trongate_tokens
	                ON
	                    '.$this->table_name.'.trongate_user_id = trongate_tokens.user_id 
	                WHERE 
	                    trongate_tokens.token = :token';
	    $rows = $this->db->query_bind($sql, $params, 'object');

	    if (!empty($rows)) {
	        $user_obj = $rows[0];
	        return $user_obj;
	    }

	    return false;
	}

	/**
	 * Fetch any active user.
	 *
	 * @return object|false Returns user object if found, false otherwise
	 */
	public function get_any_active_user(): object|false {
	    $sql = "SELECT * FROM {$this->table_name} WHERE active = 1 ORDER BY id LIMIT 1";
	    $rows = $this->db->query_bind($sql, [], 'object');
	    return !empty($rows) ? $rows[0] : false;
	}

    /**
     * Retrieve a paginated set of records from the database table.
     *
     * @param int $limit  Number of records to return.
     * @param int $offset Number of records to skip.
     *
     * @return array<object>  Array of record objects.
     */
	public function get_all_paginated(int $limit, int $offset): array {
        $sql = "SELECT * FROM {$this->table_name} ORDER BY id LIMIT :limit OFFSET :offset";
        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        return $this->db->query_bind($sql, $params, 'object');
	}

    /**
     * Prepare multiple records for display in list views.
     *
     * @param array<object> $rows
     *
     * @return array<object>
     */
    public function prepare_records_for_display(array $rows): array {
        $prepared = [];

        foreach ($rows as $row) {
            $prepared[] = (object) $this->prepare_for_display((array) $row);
        }

        return $prepared;
    }

    /**
     * Prepare a single record for display.
     *
     * Adds derived, human-readable values without mutating raw data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function prepare_for_display(array $data): array {
        if (array_key_exists('active', $data)) {
            $data['active_formatted'] = ((int) $data['active'] === 1)
                ? 'Active'
                : 'Not Active';
        }

        return $data;
    }

    /**
     * Count all records in the database table.
     *
     * @return int  Total number of records.
     */
    public function count_all(): int {
        return $this->db->count($this->table_name);
    }

	/**
	 * Check if a username is available for use.
	 * 
	 * During record creation ($update_id = 0): 
	 * - Returns false if username exists
	 * - Returns true if username is available
	 * 
	 * During record update ($update_id > 0):
	 * - Returns false if username exists AND belongs to another record
	 * - Returns true if username is available or belongs to the same record
	 *
	 * @param string $username The username to check
	 * @param int $update_id The ID of the record being updated (0 for new records)
	 * @return bool Returns true if available, false if taken
	 */
	public function is_username_available(string $username, int $update_id = 0): bool {
	    $params['username'] = $username;
	    
	    if ($update_id > 0) {
	        // For updates: check if username exists on ANOTHER record
	        $sql = 'SELECT id FROM ' . $this->table_name . ' 
	                WHERE username = :username 
	                AND id != :update_id';
	        $params['update_id'] = $update_id;
	    } else {
	        // For creates: check if username exists at all
	        $sql = 'SELECT id FROM ' . $this->table_name . ' 
	                WHERE username = :username';
	    }
	    
	    $rows = $this->db->query_bind($sql, $params, 'object');
	    
	    return empty($rows);
	}

	/**
	 * Create a new record with associated user account.
	 * 
	 * This method performs a two-step creation process:
	 * 1. First creates a record in the 'trongate_users' table to establish a user ID
	 * 2. Then creates a record in the main table with the foreign key reference
	 * 
	 * @param array $data Record data including username, password, etc.
	 * @return int Returns the ID of the newly created record
	 */
	public function create_new_record(array $data): int {
	    // Create trongate_users entry and get ID
	    $trongate_user_data = [
	        'code' => make_rand_str(32),
	        'user_level_id' => 1
	    ];

	    $data['trongate_user_id'] = $this->db->insert($trongate_user_data, 'trongate_users');
	    
	    // Set defaults
	    $data['active'] = $data['active'] ?? 1;
	    $data['failed_login_attempts'] = $data['failed_login_attempts'] ?? 0;
	    $data['last_failed_attempt'] = $data['last_failed_attempt'] ?? 0;
	    $data['login_blocked_until'] = $data['login_blocked_until'] ?? 0;
	    $data['failed_login_ip'] = $data['failed_login_ip'] ?? '';
	    
	    // CRITICAL: Generate random password or require password field
	    $data['password'] = $this->hash_password(make_rand_str(16)); // Random password
	    
	    $new_record_id = $this->db->insert($data, $this->table_name);
	    return $new_record_id;
	}

	/**
	 * Delete a record and its associated user account.
	 * 
	 * This method performs a two-step deletion process:
	 * 1. First deletes the record from the main table
	 * 2. Then deletes the associated record from 'trongate_users' table
	 * 
	 * @param int $update_id The ID of the record to delete
	 * @return bool True if successful
	 */
	public function delete_record(int $update_id): bool {
	    // Get foreign key before deletion
	    $sql = 'SELECT trongate_user_id FROM ' . $this->table_name . ' WHERE id = :id';
	    $params['id'] = $update_id;
	    $rows = $this->db->query_bind($sql, $params, 'object');
	    
	    if (empty($rows)) {
	        return false;
	    }
	    
	    // Delete from main table
	    $this->db->delete($update_id, $this->table_name);
	    
	    // Delete associated trongate_users record
	    $trongate_user_id = $rows[0]->trongate_user_id;
	    if ($trongate_user_id > 0) {
	        $this->db->delete($trongate_user_id, 'trongate_users');
	    }
	    
	    return true;
	}
}