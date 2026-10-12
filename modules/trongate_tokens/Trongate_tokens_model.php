<?php
/**
 * Trongate Tokens Model
 * 
 * Handles all database operations for token-based authentication and authorization.
 * This model provides methods for token validation, generation, and management.
 */
class Trongate_tokens_model extends Model {

    private int $default_token_lifespan = 86400; // one day (24 hours)

    /**
     * Gather user tokens from HTTP headers, cookies, and session.
     * 
     * Checks for tokens in the following locations (in priority order):
     * 1. HTTP headers
     * 2. Cookies
     * 3. Session
     *
     * @return array An array of sanitized user tokens
     */
    public function gather_user_tokens(): array {
        $user_tokens = [];

        // Check for token in headers
        if (isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            $user_tokens[] = htmlspecialchars($_SERVER['HTTP_TRONGATETOKEN'], ENT_QUOTES, 'UTF-8');
        }

        // Check for token in cookie
        if (isset($_COOKIE['trongatetoken'])) {
            $user_tokens[] = htmlspecialchars($_COOKIE['trongatetoken'], ENT_QUOTES, 'UTF-8');
        }

        // Check for token in session
        if (isset($_SESSION['trongatetoken'])) {
            $user_tokens[] = htmlspecialchars($_SESSION['trongatetoken'], ENT_QUOTES, 'UTF-8');
        }

        return $user_tokens;
    }

    /**
     * Validate token against a single user level.
     *
     * @param array $user_tokens An array of user tokens to search
     * @param int $user_level The user level to filter tokens
     * @return string|bool The valid token if found, or false if none is found
     */
    public function validate_token_single_level(array $user_tokens, int $user_level): string|bool {
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['user_level_id'] = $user_level;
        $params['nowtime'] = time();

        foreach ($user_tokens as $token) {
            $params['token'] = $this->hash_token($token);
            
            $sql = 'SELECT 
                        trongate_tokens.token 
                    FROM 
                        trongate_tokens 
                    INNER JOIN
                        trongate_users 
                    ON  
                        trongate_tokens.user_id = trongate_users.id
                    ' . $where_condition . ' 
                    AND 
                        trongate_users.user_level_id = :user_level_id
                    AND 
                        expiry_date > :nowtime';

            $rows = $this->db->query_bind($sql, $params, 'object');

            if (!empty($rows)) {
                return $token;
            }
        }

        return false;
    }

    /**
     * Validate token against multiple user levels.
     *
     * @param array $user_tokens An array of user tokens to search
     * @param array $user_levels An array of user levels to filter tokens
     * @return string|bool The valid token if found, or false if none is found
     */
    public function validate_token_multi_level(array $user_tokens, array $user_levels): string|bool {
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

        // Build the user level conditions
        $and_condition = ' AND (';
        $count = 0;
        
        foreach ($user_levels as $user_level) {
            $count++;
            $this_property = 'user_level_' . $count;
            $params[$this_property] = $user_level;

            if ($count > 1) {
                $and_condition .= ' OR';
            }

            $and_condition .= ' trongate_users.user_level_id = :' . $this_property;
        }
        
        $and_condition .= ')';

        foreach ($user_tokens as $token) {
            $params['token'] = $this->hash_token($token);
            
            $sql = 'SELECT 
                        trongate_tokens.token 
                    FROM 
                        trongate_tokens 
                    INNER JOIN
                        trongate_users 
                    ON  
                        trongate_tokens.user_id = trongate_users.id
                    ' . $where_condition . ' 
                    ' . $and_condition . '
                    AND 
                        expiry_date > :nowtime';

            $rows = $this->db->query_bind($sql, $params, 'object');

            if (!empty($rows)) {
                return $token;
            }
        }

        return false;
    }

    /**
     * Validate token for any user level type.
     *
     * @param array $user_tokens An array of user tokens to search
     * @return string|bool The valid token if found, or false if none is found
     */
    public function validate_token_any_level(array $user_tokens): string|bool {
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

        foreach ($user_tokens as $token) {
            $params['token'] = $this->hash_token($token);
            
            $sql = 'SELECT 
                        trongate_tokens.token 
                    FROM 
                        trongate_tokens 
                    ' . $where_condition . '
                    AND 
                        expiry_date > :nowtime';

            $rows = $this->db->query_bind($sql, $params, 'object');

            if (!empty($rows)) {
                return $token;
            }
        }

        return false;
    }

    /**
     * Delete specific tokens from the database.
     *
     * @param array $tokens An array of tokens to delete
     * @return void
     */
    public function delete_tokens(array $tokens): void {
        foreach ($tokens as $token) {
            $params['token'] = $this->hash_token($token);
            $sql = 'DELETE FROM trongate_tokens WHERE token = :token';
            $this->db->query_bind($sql, $params);
        }
    }

    /**
     * Delete old tokens from the database.
     * 
     * This function deletes tokens that have expired. If a user ID is provided,
     * it also deletes tokens associated with that user.
     *
     * @param int|null $user_id Optional user ID to delete tokens for a specific user
     * @return void
     */
    public function delete_old_tokens(?int $user_id = null): void {
        $params['nowtime'] = time();
        
        $sql = 'DELETE FROM trongate_tokens WHERE expiry_date < :nowtime';

        if (isset($user_id)) {
            $sql .= ' OR user_id = :user_id';
            $params['user_id'] = $user_id;
        }

        $this->db->query_bind($sql, $params);
    }

    /**
     * Retrieves the Trongate user ID based on a provided token, session, cookie, or page header.
     * 
     * This method attempts to retrieve the Trongate user ID based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token Optional. The token to retrieve the user ID for
     * @return int|bool The Trongate user ID if found, or false if not found
     */
    public function get_user_id(?string $token = null): int|bool {
        $tokens = $this->build_token_array($token);

        if (empty($tokens)) {
            return false;
        }

        // Build WHERE clause with placeholders
        $placeholders = [];
        $params = [];
        foreach ($tokens as $index => $token_value) {
            $param_name = 'token_' . $index;
            $placeholders[] = "token = :$param_name";
            $params[$param_name] = $this->hash_token($token_value);
        }
        
        $where_clause = implode(' OR ', $placeholders);
        $sql = 'SELECT user_id FROM trongate_tokens WHERE ' . $where_clause;
        
        $rows = $this->db->query_bind($sql, $params, 'object');
        return !empty($rows) ? (int) $rows[0]->user_id : false;
    }

    /**
     * Get the Trongate user object based on a token, session, cookie, or page headers.
     * 
     * This method attempts to retrieve the Trongate user object based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token Optional. The token to use for fetching the user object
     * @return object|bool The Trongate user object if found, or false if not found
     */
    public function get_user_obj(?string $token = null): object|bool {
        $tokens = $this->build_token_array($token);

        if (empty($tokens)) {
            return false;
        }

        // Build WHERE clause with placeholders
        $placeholders = [];
        $raw_tokens = [];
        $params = [];
        foreach ($tokens as $index => $token_value) {
            $param_name = 'token_' . $index;
            $placeholders[] = "t.token = :$param_name";
            $params[$param_name] = $this->hash_token($token_value);
            $raw_tokens[$params[$param_name]] = $token_value;
        }
        
        $where_clause = implode(' OR ', $placeholders);
        
        $sql = '
            SELECT
                u.code as trongate_user_code,
                u.user_level_id,
                ul.level_title as user_level,
                t.token,
                t.user_id as trongate_user_id,
                t.expiry_date 
            FROM
                trongate_tokens t
            INNER JOIN
                trongate_users u ON t.user_id = u.id
            INNER JOIN
                trongate_user_levels ul ON u.user_level_id = ul.id
            WHERE ' . $where_clause;

        $rows = $this->db->query_bind($sql, $params, 'object');

        if (empty($rows)) {
            return false;
        }

        // Only the hash is stored; hand back the raw token the caller presented
        $rows[0]->token = $raw_tokens[$rows[0]->token];
        return $rows[0];
    }

    /**
     * Generate a token based on provided data.
     *
     * @param array $data An array containing token generation parameters:
     *   - 'user_id' (int) : required - The user's ID
     *   - 'expiry_date' (int) : optional - Unix timestamp for token expiration
     *   - 'set_cookie' (bool) : optional - If true, set the token as a cookie
     *   - 'code' (string) : optional - Custom code for the token
     * @return string The generated token
     */
    public function generate_token(array $data): string {
        // Generate a 32-character random string
        $random_string = make_rand_str();

        // Set expiry date if not provided
        if (!isset($data['expiry_date'])) {
            $data['expiry_date'] = time() + $this->default_token_lifespan;
        }

        // Prepare database data
        $db_data = [
            'user_id' => $data['user_id'],
            'expiry_date' => $data['expiry_date'],
            'token' => $this->hash_token($random_string)
        ];

        // Add optional code if provided
        if (isset($data['code'])) {
            $db_data['code'] = $data['code'];
        }

        // Insert token into database
        $this->db->insert($db_data, 'trongate_tokens');

        // Set token in cookie or session
        if (($data['set_cookie'] ?? false) === true) {
            $this->set_token_cookie($random_string, $data['expiry_date']);
            return $random_string;
        }

        $_SESSION['trongatetoken'] = $random_string;
        return $random_string;
    }

    /**
     * Regenerate a token with a new expiration date.
     * 
     * @param string $old_token The old token to replace
     * @param int $expiry_date The new expiration date as Unix timestamp
     * @return string|bool The new token if successful, or false if old token not found
     */
    public function regenerate_token(string $old_token, int $expiry_date): string|bool {
        // Check if the token exists
        $sql = 'SELECT * FROM trongate_tokens WHERE token = :token LIMIT 1';
        $rows = $this->db->query_bind($sql, ['token' => $this->hash_token($old_token)], 'object');

        if (empty($rows)) {
            return false;
        }

        $token = $rows[0];

        // Generate new token and update database
        $new_token = make_rand_str();
        $update_data = [
            'expiry_date' => $expiry_date,
            'token' => $this->hash_token($new_token)
        ];

        $this->db->update($token->id, $update_data, 'trongate_tokens');
        return $new_token;
    }

    /**
     * Build an array of tokens for lookup based on provided token or current session/cookie/header.
     * 
     * @param string|null $token Optional. The specific token to use
     * @return array An array of sanitized token strings
     */
    private function build_token_array(?string $token = null): array {
        $tokens = [];

        // Use provided token if available
        if (isset($token)) {
            $tokens[] = $token;
            return $tokens;
        }

        // Otherwise, check all possible sources
        if (isset($_COOKIE['trongatetoken'])) {
            $tokens[] = htmlspecialchars($_COOKIE['trongatetoken'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($_SESSION['trongatetoken'])) {
            $tokens[] = htmlspecialchars($_SESSION['trongatetoken'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            $tokens[] = htmlspecialchars($_SERVER['HTTP_TRONGATETOKEN'], ENT_QUOTES, 'UTF-8');
        }

        // Remove duplicates and return
        return array_unique($tokens);
    }

    /**
     * Set (or, with a past expiry, clear) the trongatetoken cookie.
     *
     * The cookie is HttpOnly and SameSite=Lax. It is Secure when the request
     * arrived over HTTPS (directly or via a proxy's X-Forwarded-Proto), unless
     * TOKEN_COOKIE_SECURE in config/config.php is set to true or false.
     * Clearing a cookie needs the same path, secure and samesite as setting it.
     *
     * @param string $value The cookie value (the raw token)
     * @param int $expires Unix timestamp; pass a past time to delete the cookie
     * @return void
     */
    public function set_token_cookie(string $value, int $expires): void {
        setcookie('trongatetoken', $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $this->cookie_should_be_secure(),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Decide whether the token cookie gets the Secure flag.
     *
     * @return bool
     */
    private function cookie_should_be_secure(): bool {
        if (defined('TOKEN_COOKIE_SECURE') && is_bool(TOKEN_COOKIE_SECURE)) {
            return TOKEN_COOKIE_SECURE;
        }

        // HTTPS is unset, '' or 'off' on plain HTTP; a proxy lists the client-facing scheme first
        $https = strtolower($_SERVER['HTTPS'] ?? 'off');
        return ($https !== 'off' && $https !== '')
            || str_starts_with(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''), 'https');
    }

    /**
     * Hash a raw token for storage and lookup.
     *
     * Only this hash is written to trongate_tokens.token, so a leaked copy of
     * the table cannot be replayed as a login token.
     *
     * @param string $token The raw token
     * @return string 64-character sha256 hex digest
     */
    private function hash_token(string $token): string {
        return hash('sha256', $token);
    }

}