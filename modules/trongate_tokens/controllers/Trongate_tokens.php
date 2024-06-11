<?php
class Trongate_tokens extends Trongate {

    private $default_token_lifespan = 86400; // one day

    /**
     * Attempt to validate and return a token based on optional user level(s) condition.
     * This method checks for a valid token in the following locations, in order of priority:
     * 1. HTTP headers ($_SERVER['HTTP_TRONGATETOKEN'])
     * 2. Cookies ($_COOKIE['trongatetoken'])
     * 3. Session ($_SESSION['trongatetoken'])
     *
     * @param int|array|null $user_levels User levels to filter tokens.
     * @return string|bool The valid token if found, or false if none is found.
     */
    public function _attempt_get_valid_token($user_levels = null): string|bool {
        // Initialize array to store user tokens
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

        // If no tokens found, return false
        if (empty($user_tokens)) {
            return false;
        }

        // Determine type of user levels provided
        $user_levels_type = gettype($user_levels);

        // Initialize token variable
        $token = false;

        // Execute SQL query based on user levels
        switch ($user_levels_type) {
            case 'integer':
                // Allow access for ONE user level type
                $token = $this->execute_sql_single($user_tokens, $user_levels);
                break;
            case 'array':
                // Allow access for MORE THAN ONE user level type
                $token = $this->execute_sql_multi($user_tokens, $user_levels);
                break;
            default:
                // Allow access for ANY user level type
                $token = $this->execute_sql_default($user_tokens);
                break;
        }

        return $token;
    }

    /**
     * Destroy tokens from session, cookie, and HTTP headers.
     *
     * This method removes tokens from session, cookie, and HTTP headers storage, and deletes them from the database.
     *
     * @return void
     */
    public function _destroy(): void {
        $tokens_to_delete = [];

        // Check and unset session token
        if (isset($_SESSION['trongatetoken'])) {
            $tokens_to_delete[] = $_SESSION['trongatetoken'];
            $_SESSION['trongatetoken'] = 'x'; // fallback
            unset($_SESSION['trongatetoken']);
        }

        // Check and destroy cookie token
        if (isset($_COOKIE['trongatetoken'])) {
            $tokens_to_delete[] = $_COOKIE['trongatetoken'];
            $past_date = time() - 86400;
            setcookie('trongatetoken', 'x', $past_date, '/');
        }

        // Check and add token from HTTP headers
        if (isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            $tokens_to_delete[] = htmlspecialchars($_SERVER['HTTP_TRONGATETOKEN'], ENT_QUOTES, 'UTF-8');
        }

        // Delete tokens from the database
        if (!empty($tokens_to_delete)) {
            foreach ($tokens_to_delete as $token) {
                $params['token'] = $token;
                $sql = 'delete from trongate_tokens where token = :token';
                $this->model->query_bind($sql, $params);
            }
        }

        // Delete expired tokens from the database
        $this->_delete_old_tokens();
    }

    /**
     * Delete old tokens from the database.
     *
     * This function deletes tokens that have expired. If a user ID is provided,
     * it also deletes tokens associated with that user.
     *
     * @param int|null $user_id Optional user ID to delete tokens for a specific user.
     * @return void
     */
    public function _delete_old_tokens(?int $user_id = null): void {
        $sql = 'delete from trongate_tokens where expiry_date < :nowtime';
        $data['nowtime'] = time();

        if (isset($user_id)) {
            $sql .= ' or user_id = :user_id';
            $data['user_id'] = $user_id;
        }

        $this->model->query_bind($sql, $data);
    }

    /**
     * Retrieves the Trongate user ID based on a provided token, session, cookie, or page header.
     *
     * This method attempts to retrieve the Trongate user ID based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token Optional. The token to retrieve the user ID for.
     * @return int|false The Trongate user ID if found, or false if not found.
     */
    public function _get_user_id(?string $token = null): int|false {
        $params = [];

        // Prepare parameters based on provided token, session, cookie, or page headers
        if (isset($token)) {
            $params['token'] = $token;
        } else {
            if (isset($_COOKIE['trongatetoken'])) {
                $params['cookie'] = htmlspecialchars($_COOKIE['trongatetoken'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($_SESSION['trongatetoken'])) {
                $params['session'] = htmlspecialchars($_SESSION['trongatetoken'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($_SERVER['HTTP_TRONGATETOKEN'])) {
                $params['header'] = htmlspecialchars($_SERVER['HTTP_TRONGATETOKEN'], ENT_QUOTES, 'UTF-8');
            }
        }

        // If no params, return false immediately
        if (empty($params)) {
            return false;
        }

        $where_clause = implode(' OR ', array_map(fn($key) => "token = :$key", array_keys($params)));
        $sql = 'SELECT user_id FROM trongate_tokens WHERE ' . $where_clause;
        $rows = $this->model->query_bind($sql, $params, 'object');
        return isset($rows[0]) ? $rows[0]->user_id : false;
    }

    /**
     * Get the Trongate user object based on a token, session, cookie, or page headers.
     *
     * This method attempts to retrieve the Trongate user object based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token (optional) The token to use for fetching the user object.
     * @return object|false The Trongate user object if found, or false if not found.
     */
    public function _get_user_obj(?string $token = null): object|false {
        $params = [];

        // Prepare parameters based on provided token, session, cookie, or page headers
        if (isset($token)) {
            $params['token'] = $token;
        } else {
            if (isset($_COOKIE['trongatetoken'])) {
                $params['cookie'] = htmlspecialchars($_COOKIE['trongatetoken'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($_SESSION['trongatetoken'])) {
                $params['session'] = htmlspecialchars($_SESSION['trongatetoken'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($_SERVER['HTTP_TRONGATETOKEN'])) {
                $params['header'] = htmlspecialchars($_SERVER['HTTP_TRONGATETOKEN'], ENT_QUOTES, 'UTF-8');
            }
        }

        // If no params, return false immediately
        if (empty($params)) {
            return false;
        }

        // Construct the SQL query
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
                trongate_user_levels ul ON u.user_level_id = ul.id ';

        // Add WHERE clause if token is provided or found in page headers, cookie, or session
        if (isset($token) || !empty($params)) {
            $where_clause = implode(' OR ', array_map(fn($key) => "t.token = :$key", array_keys($params)));
            $sql .= 'WHERE ' . $where_clause;
        }

        // Execute the query and return the result
        $rows = $this->model->query_bind($sql, $params, 'object');
        return isset($rows[0]) ? $rows[0] : false;
    }

    /**
     * Retrieves the user level associated with the given token or the current user token.
     *
     * @param string|null $token (optional) The token used to identify the user. If not provided, the token of the current user is used.
     * @return string|false The user level title if found, otherwise false.
     */
    public function _get_user_level(?string $token = null): string|false {
        // If token is not provided, get the user object to fetch the token
        if (!$token) {
            $user_obj = $this->_get_user_obj();
            if ($user_obj === false) {
                return false; // Return false if user object not found
            }
            $token = $user_obj->token;
        }

        // Call _get_user_obj() with the provided or retrieved token
        $user_obj = $this->_get_user_obj($token);

        // Return user level title if user object is found, otherwise return false
        return $user_obj !== false ? $user_obj->user_level : false;
    }

    /**
     * Generate a token based on provided data.
     *
     * @param array $data An array containing token generation parameters.
     *   - 'user_id' (int) : required - The user's ID.
     *   - 'expiry_date' (int) : optional - Unix timestamp for token expiration.
     *   - 'set_cookie' (bool) : optional - If true, set the token as a cookie.
     *   - 'code' (string) : optional - Custom code for the token.
     *
     * @return string The generated token.
     */
    public function _generate_token(array $data): string {
        // Generate a 32-character random string
        $random_string = make_rand_str();

        // Build data array variables (required for table insert)
        if (!isset($data['expiry_date'])) {
            $data['expiry_date'] = time() + $this->default_token_lifespan;
        }

        $data['token'] = $random_string;
        $params = $data;

        if (isset($params['set_cookie'])) {
            unset($params['set_cookie']);
        }

        $this->model->insert($params, 'trongate_tokens');

        if (isset($data['set_cookie'])) {
            setcookie('trongatetoken', $random_string, $data['expiry_date'], '/');
        } else {
            $_SESSION['trongatetoken'] = $random_string;
        }

        return $random_string;
    }

    /**
     * Regenerate a token with a new expiration date.
     *
     * This method regenerates a token with a new expiration date. It validates
     * the input format of the old token and the expiration date before proceeding.
     * If the input format is invalid or the old token does not exist, appropriate
     * HTTP response codes are set to indicate the failure reason.
     *
     * @return void
     */
    public function regenerate(): void {
        $old_token = segment(3);
        $expiry_date = segment(4);

        // Validate input format
        if (strlen($old_token) !== 32 || !is_numeric($expiry_date) || $expiry_date < time()) {
            http_response_code(400); // Bad Request
            die();
        }

        // Check if the token exists
        $sql = 'SELECT * FROM trongate_tokens WHERE token = :token LIMIT 1';
        $tokens = $this->model->query_bind($sql, ['token' => $old_token], 'object');

        if (empty($tokens)) {
            http_response_code(404); // Not Found
            echo 'Token not found.';
            die();
        }

        $token = $tokens[0];

        // Generate new token and update database
        $new_token = make_rand_str();
        $update_data = [
            'expiry_date' => $expiry_date,
            'token' => $new_token
        ];

        $this->model->update($token->id, $update_data, 'trongate_tokens');

        // Return the new token
        http_response_code(200); // OK
        echo $new_token;
    }

    /**
     * Execute SQL query for a single user level.
     *
     * @param string[] $user_tokens An array of user tokens to search.
     * @param int $user_levels The user level to filter tokens.
     * @return string|false The valid token if found, or false if none is found.
     */
    private function execute_sql_single(array $user_tokens, int $user_levels): string|false {
        // allow access for ONE user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['user_level_id'] = $user_levels; // int
        $params['nowtime'] = time();

        foreach ($user_tokens as $token) {
            $params['token'] = $token;
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
                            trongate_users.user_level_id = :user_level_id';
            $sql .= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

            if (count($rows) > 0) {
                $token = $rows[0]->token;
                return $token;
            }
        }

        return false;
    }

    /**
     * Execute SQL query for multiple user levels.
     *
     * @param string[] $user_tokens An array of user tokens to search.
     * @param int[] $user_levels An array of user levels to filter tokens.
     * @return string|false The valid token if found, or false if none is found.
     */
    private function execute_sql_multi(array $user_tokens, array $user_levels): string|false {
        // allow access for MORE THAN ONE user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

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
        $and_condition = ltrim(trim($and_condition));

        foreach ($user_tokens as $token) {
            $params['token'] = $token;
            $sql = 'SELECT 
                            trongate_tokens.token 
                    FROM 
                            trongate_tokens 
                    INNER JOIN
                            trongate_users 
                    ON  
                            trongate_tokens.user_id = trongate_users.id
                    ' . $where_condition . ' 
                    ' . $and_condition;
            $sql .= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

            if (count($rows) > 0) {
                $token = $rows[0]->token;
                return $token;
            }
        }

        return false;
    }

    /**
     * Execute SQL query for any user level type.
     *
     * @param string[] $user_tokens An array of user tokens to search.
     * @return string|false The valid token if found, or false if none is found.
     */
    private function execute_sql_default(array $user_tokens): string|false {
        // allow access for ANY user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

        foreach ($user_tokens as $token) {
            $params['token'] = $token;
            $sql = 'SELECT 
                            trongate_tokens.token 
                    FROM 
                            trongate_tokens 
                    ' . $where_condition;
            $sql .= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

            if (count($rows) > 0) {
                $token = $rows[0]->token;
                return $token;
            }
        }

        return false;
    }

}