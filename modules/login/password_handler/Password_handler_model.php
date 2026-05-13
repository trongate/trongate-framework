<?php
/**
 * Password Handler Model
 *
 * Pure DB layer for the password_handler child module. Owns the queries
 * against the password_resets table and the password-column UPDATE on
 * target user tables. All business logic (hashing, strength validation,
 * config loading, email composition, timing-safe fallback) lives in the
 * Password_handler controller; this model is only invoked by it.
 */
class Password_handler_model extends Model {

    // -----------------------------------------------------------------
    // Password Hash Lookup
    // -----------------------------------------------------------------

    /**
     * Find an existing password hash on the target table.
     *
     * Returns the first non-empty hash from the password column, or null
     * if the table contains no rows with a stored hash. Used by the
     * controller's timing-safe-hash routine.
     *
     * @param string $target_table The user table
     * @param string $password_field The column that stores password hashes
     * @return string|null The hash if found, null otherwise
     */
    public function find_existing_password_hash(string $target_table, string $password_field): ?string {
        $sql = 'SELECT ' . $this->quote_id($password_field)
             . ' FROM ' . $this->quote_id($target_table)
             . ' WHERE ' . $this->quote_id($password_field) . ' IS NOT NULL'
             . ' AND ' . $this->quote_id($password_field) . " != '' LIMIT 1";

        $rows = $this->db->query_bind($sql, [], 'object');

        if (!empty($rows) && !empty($rows[0]->{$password_field})) {
            return $rows[0]->{$password_field};
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Reset Token CRUD
    // -----------------------------------------------------------------

    /**
     * Insert a new reset-token row into the password_resets table.
     *
     * @param string $identifier The user's identifier (e.g. email or username)
     * @param string $target_table The user table the token applies to
     * @param string $token The 64-character token string
     * @param int $expiry Unix timestamp at which the token expires
     * @return void
     */
    public function insert_reset_token(string $identifier, string $target_table, string $token, int $expiry): void {
        $this->db->insert([
            'target_table' => $target_table,
            'identifier'   => $identifier,
            'token'        => $token,
            'expiry_date'  => $expiry,
            'used'         => 0,
            'created_at'   => time()
        ], 'password_resets');
    }

    /**
     * Find an active reset-token row by token string.
     *
     * Returns the row only if the token exists, has not been used, and
     * has not expired. Returns false otherwise.
     *
     * @param string $token The reset token
     * @return object|bool The reset record on success, false otherwise
     */
    public function find_active_reset_token(string $token): object|bool {
        $sql = 'SELECT * FROM password_resets'
             . ' WHERE token = :token'
             . '   AND used = 0'
             . '   AND expiry_date > :now'
             . ' LIMIT 1';

        $rows = $this->db->query_bind($sql, [
            'token' => $token,
            'now' => time()
        ], 'object');

        return !empty($rows) ? $rows[0] : false;
    }

    /**
     * Find any reset-token row by token string, regardless of state.
     *
     * Returns the row whether the token has been used or has expired.
     * Used when the consumed/expired-token error page needs to derive
     * the originating user level so it can link back to the correct
     * forgot-password form.
     *
     * @param string $token The reset token
     * @return object|bool The reset record on success, false if no such row
     */
    public function find_reset_token_row(string $token): object|bool {
        $sql = 'SELECT * FROM password_resets WHERE token = :token LIMIT 1';

        $rows = $this->db->query_bind($sql, ['token' => $token], 'object');

        return !empty($rows) ? $rows[0] : false;
    }

    /**
     * Mark a reset-token row as used.
     *
     * Idempotent — running this against an already-consumed token is a no-op.
     *
     * @param string $token The reset token
     * @return void
     */
    public function mark_reset_token_used(string $token): void {
        $sql = 'UPDATE password_resets SET used = 1 WHERE token = :token';
        $this->db->query_bind($sql, ['token' => $token]);
    }

    // -----------------------------------------------------------------
    // Password Hash Write
    // -----------------------------------------------------------------

    /**
     * Write a pre-hashed password to a target table.
     *
     * Verifies that the target table contains both the identifier column
     * and the password column before issuing the UPDATE. Returns false if
     * either column is missing, rather than corrupting the row. The hash
     * is computed by the controller — this method does not hash.
     *
     * @param string $target_table The user table
     * @param string $identifier_column The column to match on
     * @param string $identifier_value The value to match
     * @param string $password_field The column that stores the hashed password
     * @param string $hash The pre-computed bcrypt hash to write
     * @return bool True on success, false if the table or columns are wrong
     */
    public function write_password_hash_for_identifier(
        string $target_table,
        string $identifier_column,
        string $identifier_value,
        string $password_field,
        string $hash
    ): bool {
        $columns = $this->db->describe_table($target_table, false);

        if ($columns === false || !is_array($columns)) {
            return false;
        }

        $names = array_column($columns, 'Field');

        if (!in_array($identifier_column, $names, true)) {
            return false;
        }

        if (!in_array($password_field, $names, true)) {
            return false;
        }

        $sql = 'UPDATE ' . $this->quote_id($target_table)
             . ' SET ' . $this->quote_id($password_field) . ' = :hash'
             . ' WHERE ' . $this->quote_id($identifier_column) . ' = :identifier';

        $this->db->query_bind($sql, [
            'hash' => $hash,
            'identifier' => $identifier_value
        ]);

        return true;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

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
