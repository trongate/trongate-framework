<?php
class Trongate_tokens extends Trongate {

    private $default_token_lifespan = 86400; //one day

    /**
     * Authenticate the user based on a token in the HTTP headers.
     *
     * This method checks for the presence of a token in the HTTP headers and validates it against the 'trongate_tokens' table.
     *
     * @return void
     */
    function auth(): void {
        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'no token';
            die();
        } else {
            $token = $_SERVER['HTTP_TRONGATETOKEN'];
            $result = $this->model->get_one_where('token', $token, 'trongate_tokens');

            if ($result === false) {
                http_response_code(401);
                echo 'false';
            } else {
                http_response_code(200);
                echo $token;
            }
        }
    }

    /**
     * Fetches the 'trongate_user_id' via HTTP POST request.
     * 
     * Upon success, outputs the 'trongate_user_id' to the browser with an HTTP response code of 200.
     * Upon failure to find a token or user ID, returns a 401 status code with an error message.
     * Upon failure to find a token, returns a 422 status code with an error message.
     *
     * @return void
     */
    function id(): void {

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'no token';
            die();
        } else {
            $token = $_SERVER['HTTP_TRONGATETOKEN'];
            $result = $this->model->get_one_where('token', $token, 'trongate_tokens');

            if ($result === false) {
                http_response_code(401);
                echo 'false';
                die();
            } else {
                http_response_code(200);
                echo $result->user_id;
                die();
            }
        }
    }

    /**
     * Fetches the Trongate user object via HTTP POST request.
     * 
     * If successful, returns the user object as JSON response with a 200 status code.
     * If the token is not provided or invalid, returns a 422 status code with an error message.
     * If unable to match the token with a user, returns a 400 status code with an error message.
     *
     * @return void
     */
    function user(): void {
        // Fetch the Trongate user object via HTTP POST request

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'No token!';
            die();
        } else {
            $params['token'] = $_SERVER['HTTP_TRONGATETOKEN'];

            $sql = 'SELECT
                        trongate_users.code as trongate_user_code,
                        trongate_users.user_level_id,
                        trongate_user_levels.level_title as user_level,
                        trongate_tokens.token,
                        trongate_tokens.user_id as trongate_user_id,
                        trongate_tokens.expiry_date 
                    FROM
                        trongate_tokens
                    INNER JOIN
                        trongate_users
                    ON
                        trongate_tokens.user_id = trongate_users.id
                    INNER JOIN
                        trongate_user_levels
                    ON
                        trongate_users.user_level_id = trongate_user_levels.id 
                    WHERE trongate_tokens.token = :token';

            $rows = $this->model->query_bind($sql, $params, 'object');

            if (!empty($rows)) {
                http_response_code(200);
                echo json_encode($rows[0]);
                die();
            } else {
                http_response_code(400);
                echo 'Unable to match token with user.';
                die();
            }
        }
    }

    /**
     * Destroy a token based on an HTTP POST request.
     *
     * This method deletes a token from the 'trongate_tokens' table upon receiving a valid HTTP header token.
     *
     * @return void
     */
    function destroy(): void {
        // Check for the presence of a token via HTTP POST request

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'No token found in here!';
            die();
        } else {
            $params['token'] = $_SERVER['HTTP_TRONGATETOKEN'];
            $sql = 'DELETE FROM trongate_tokens WHERE token = :token';
            $this->model->query_bind($sql, $params);
            http_response_code(200);
            echo 'Token deleted.';
            die();
        }
    }

    /**
     * Retrieves the Trongate user ID based on a provided token, session, or cookie.
     *
     * @param string|null $token Optional. The token to retrieve the user ID for.
     * @return int|false The Trongate user ID if found, or false if not found.
     *                 Returns the Trongate user ID if it is found based on the provided token, session, or cookie.
     *                 Returns false if the user ID is not found.
     */
    function _get_user_id(?string $token = null): int|false {
        if (isset($token)) {
            $params['cookie_token'] = $token;
            $params['session_token'] = $token;
        } else {
            // Attempt to fetch Trongate user object from sessions or cookie
            $params['cookie_token'] = (isset($_COOKIE['trongatetoken']) ? $_COOKIE['trongatetoken'] : '');
            $params['session_token'] = (isset($_SESSION['trongatetoken']) ? $_SESSION['trongatetoken'] : '');
        }

        $sql = 'SELECT user_id FROM trongate_tokens WHERE token = :cookie_token OR token = :session_token';
        $rows = $this->model->query_bind($sql, $params, 'object');
        $trongate_user_id = (isset($rows[0]) ? $rows[0]->user_id : false);
        return $trongate_user_id;
    }

    /**
     * Get the Trongate user object based on a token, session, or cookie.
     *
     * This method attempts to retrieve the Trongate user object based on the provided token, or it checks session and cookie values if no token is provided.
     *
     * @param string|null $token (optional) The token to use for fetching the user object.
     * @return object|false The Trongate user object if found, or false if not found.
     */
    function _get_user_obj(?string $token = null): object|false {
        if (isset($token)) {

            if (gettype($token) !== 'string') {
                settype($token, 'string');
            }

            $params['cookie_token'] = $token;
            $params['session_token'] = $token;
        } else {
            // Attempt to fetch Trongate user object from sessions or cookie
            $params['cookie_token'] = (isset($_COOKIE['trongatetoken']) ? $_COOKIE['trongatetoken'] : '');
            $params['session_token'] = (isset($_SESSION['trongatetoken']) ? $_SESSION['trongatetoken'] : '');
        }

        $sql = 'SELECT
                    trongate_users.code as trongate_user_code,
                    trongate_users.user_level_id,
                    trongate_user_levels.level_title as user_level,
                    trongate_tokens.token,
                    trongate_tokens.user_id as trongate_user_id,
                    trongate_tokens.expiry_date 
                FROM
                    trongate_tokens
                INNER JOIN
                    trongate_users
                ON
                    trongate_tokens.user_id = trongate_users.id
                INNER JOIN
                    trongate_user_levels
                ON
                    trongate_users.user_level_id = trongate_user_levels.id 
                WHERE 
                    trongate_tokens.token = :cookie_token 
                OR 
                    trongate_tokens.token = :session_token';
        $rows = $this->model->query_bind($sql, $params, 'object');
        $trongate_user_obj = (isset($rows[0]) ? $rows[0] : false);
        return $trongate_user_obj;
    }

    /**
     * Fetch a token object based on a provided token.
     *
     * This method retrieves a token object from the 'trongate_tokens' table based on a provided token.
     *
     * @param string $token The token to use for fetching the token object.
     * @return object|false The token object if found, or false if not found.
     */
    function _fetch_token_obj(string $token): object|false {
        $data['token'] = $token;
        $sql = 'SELECT * FROM trongate_tokens WHERE token = :token';
        $token_objs = $this->model->query_bind($sql, $data, 'object');

        if ($token_objs === false) {
            return false; // Token not found
        } else {
            $token_obj = $token_objs[0];
            return $token_obj;
        }
    }

    /**
     * Generate a token after pre-token validation tests.
     *
     * This method generates a token after performing pre-token validation tests, which involes checking if 'user_id' is set and if it's a numeric value.
     * 
     * Pre-token validation tests are invoked via _pre_token_validation(), which only works when ENV is explicitly set to 'dev'. In other environments, token generation is not allowed.
     *
     * Upon successful generation of the token, it returns the token with an HTTP response code of 200.
     * If the request method is not POST, it returns a 403 Forbidden error.
     * If 'user_id' is not provided or is not numeric, it returns a 400 Bad Request error.
     *
     * @return void
     */
    function generate(): void {
        if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
            http_response_code(403);
            echo 'Forbidden';
            die();
        } else {
            // Fetch posted data
            $posted_data = file_get_contents('php://input');
            $input = (array) json_decode($posted_data);
            $data = $this->_pre_token_validation($input);
        }

        $token = $this->_generate_token($data);
        http_response_code(200);
        echo $token;
    }

    /**
     * Perform pre-token validation checks on the provided input data.
     *
     * This method ensures that the input data array contains a valid numeric 'user_id' property
     * before proceeding with token generation. If the environment is not set to 'dev', pre-token
     * validation is disabled, and token issuance is prevented. In such cases, appropriate HTTP
     * response codes are set to indicate the failure reason.
     *
     * @param array $input An array containing input data to be validated.
     * @return array The input data array after validation checks have been performed.
     */
    function _pre_token_validation(array $input): array {
        // Validation tests that happen before a new token is issued

        if (ENV !== 'dev') {
            // Pre-token validation is disabled in non-development environments
            echo 'Pre-token validation failed: Forbidden';
            http_response_code(403);
            die();
        }

        if (!isset($input['user_id'])) {
            // If 'user_id' is not provided, return a 400 Bad Request error
            http_response_code(400);
            echo 'Pre-token validation failed: No user_id submitted!';
            die();
        } elseif (!is_numeric($input['user_id'])) {
            // If 'user_id' is not numeric, return a 400 Bad Request error
            http_response_code(400);
            echo 'Pre-token validation failed: Non-numeric user_id submitted!';
            die();
        }

        return $input;
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
    function _generate_token(array $data): string {
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
    function regenerate(): void {
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
     * Attempt to validate and return a token based on optional user level(s) condition.
     *
     * @param int|array|null $user_levels User levels to filter tokens.
     * @return string|false The valid token if found, or false if none is found.
     */
    function _attempt_get_valid_token($user_levels = null): string|false {
        // Initialize array to store user tokens
        $user_tokens = [];

        // Check for token in cookie
        if (isset($_COOKIE['trongatetoken'])) {
            $user_tokens[] = $_COOKIE['trongatetoken'];
        }

        // Check for token in session
        if (isset($_SESSION['trongatetoken'])) {
            $user_tokens[] = $_SESSION['trongatetoken'];
        }

        // If no tokens found, return false
        if (empty($user_tokens)) {
            return false;
        }

        // Determine type of user levels provided
        $user_levels_type = gettype($user_levels);

        // Execute SQL query based on user levels
        switch ($user_levels_type) {
            case 'integer':
                // Allow access for ONE user level type
                $token = $this->_execute_sql_single($user_tokens, $user_levels);
                break;
            case 'array':
                // Allow access for MORE THAN ONE user level type
                $token = $this->_execute_sql_multi($user_tokens, $user_levels);
                break;
            default:
                // Allow access for ANY user level type
                $token = $this->_execute_sql_default($user_tokens);
                break;
        }

        return $token;
    }

    /**
     * Execute SQL query for a single user level.
     *
     * @param string[] $user_tokens An array of user tokens to search.
     * @param int $user_levels The user level to filter tokens.
     * @return string|false The valid token if found, or false if none is found.
     */
    function _execute_sql_single(array $user_tokens, int $user_levels) {
        // allow access for ONE user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['user_level_id'] = $user_levels; //int
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
    function _execute_sql_multi(array $user_tokens, array $user_levels) {
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
    function _execute_sql_default(array $user_tokens) {
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

    /**
     * Destroy tokens from session and cookie storage.
     *
     * This method removes tokens from both session and cookie storage and deletes them from the database.
     *
     * @return void
     */
    function _destroy(): void {
        if (isset($_SESSION['trongatetoken'])) {
            $tokens_to_delete[] = $_SESSION['trongatetoken'];
            $_SESSION['trongatetoken'] = 'x'; // fallback
            unset($_SESSION['trongatetoken']);
        }

        if (isset($_COOKIE['trongatetoken'])) {
            // destroy the cookie
            $tokens_to_delete[] = $_COOKIE['trongatetoken'];
            $past_date = time() - 86400;
            setcookie('trongatetoken', 'x', $past_date, '/');
        }

        if (isset($tokens_to_delete)) {
            foreach ($tokens_to_delete as $token) {
                $params['token'] = $token;
                $sql = 'delete from trongate_tokens where token = :token';
                $this->model->query_bind($sql, $params);
            }
        }

        $this->_delete_old_tokens();
    }

    /**
     * Delete old tokens from the database.
     *
     * This method deletes tokens from the database that have expired based on their expiry date.
     *
     * @param int|null $user_id User ID to delete tokens for (optional).
     *
     * @return void
     */
    function _delete_old_tokens($user_id = null): void {
        $sql = 'delete from trongate_tokens where expiry_date < :nowtime';
        $data['nowtime'] = time();

        if (isset($user_id)) {
            $sql .= ' or user_id = :user_id';
            $data['user_id'] = $user_id;
        }

        $this->model->query_bind($sql, $data);
    }
}
