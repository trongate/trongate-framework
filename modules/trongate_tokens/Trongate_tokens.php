<?php
/**
 * Token-based authentication class for managing user sessions and authorization.
 * Handles token generation, validation, and user session management with security controls.
 * Provides user identification and access level verification through token validation.
 */
class Trongate_tokens extends Trongate {

    /**
     * Class constructor.
     * 
     * Blocks all URL access to this module while allowing internal code usage.
     * 
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url($this->module_name);
    }

    /**
     * Attempt to validate and return a token based on optional user level(s) condition.
     * 
     * This method checks for a valid token in the following locations, in order of priority:
     * 1. HTTP headers ($_SERVER['HTTP_TRONGATETOKEN'])
     * 2. Cookies ($_COOKIE['trongatetoken'])
     * 3. Session ($_SESSION['trongatetoken'])
     *
     * @param int|array|null $user_levels User levels to filter tokens (int for single level, array for multiple, null for any)
     * @return string|false The valid token if found, or false if none is found
     */
    public function attempt_get_valid_token(int|array|null $user_levels = null): string|false {
        $user_tokens = $this->model->gather_user_tokens();
        
        if (empty($user_tokens)) {
            return false;
        }

        return match(gettype($user_levels)) {
            'integer' => $this->model->validate_token_single_level($user_tokens, $user_levels),
            'array' => $this->model->validate_token_multi_level($user_tokens, $user_levels),
            default => $this->model->validate_token_any_level($user_tokens)
        };
    }

    /**
     * Destroy tokens from session, cookie, and HTTP headers.
     * 
     * This method removes tokens from session, cookie, and HTTP headers storage,
     * and deletes them from the database.
     *
     * @return void
     */
    public function destroy(): void {
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
            $this->model->delete_tokens($tokens_to_delete);
        }

        // Delete expired tokens from the database
        $this->model->delete_old_tokens();
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
        $this->model->delete_old_tokens($user_id);
    }

    /**
     * Retrieves the Trongate user ID based on a provided token, session, cookie, or page header.
     * 
     * This method attempts to retrieve the Trongate user ID based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token Optional. The token to retrieve the user ID for
     * @return int|false The Trongate user ID if found, or false if not found
     */
    public function get_user_id(?string $token = null): int|false {
        return $this->model->get_user_id($token);
    }

    /**
     * Get the Trongate user object based on a token, session, cookie, or page headers.
     * 
     * This method attempts to retrieve the Trongate user object based on the provided token,
     * or it checks session, cookie, and page header values if no token is provided.
     *
     * @param string|null $token Optional. The token to use for fetching the user object
     * @return object|false The Trongate user object if found, or false if not found
     */
    public function get_user_obj(?string $token = null): object|false {
        return $this->model->get_user_obj($token);
    }

    /**
     * Retrieves the user level associated with the given token or the current user token.
     *
     * @param string|null $token Optional. The token used to identify the user. If not provided, the token of the current user is used
     * @return string|false The user level title if found, otherwise false
     */
    public function get_user_level(?string $token = null): string|false {
        // If token is not provided, get the user object to fetch the token
        if (!$token) {
            $user_obj = $this->model->get_user_obj();
            if ($user_obj === false) {
                return false;
            }
            $token = $user_obj->token;
        }

        // Call get_user_obj() with the provided or retrieved token
        $user_obj = $this->model->get_user_obj($token);

        // Return user level title if user object is found, otherwise return false
        return $user_obj !== false ? $user_obj->user_level : false;
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
        return $this->model->generate_token($data);
    }

    /**
     * Regenerate a token with a new expiration date.
     * 
     * This method regenerates a token with a new expiration date. It validates
     * the input format of the old token and the expiration date before proceeding.
     * If the input format is invalid or the old token does not exist, appropriate
     * HTTP response codes are set to indicate the failure reason.
     * 
     * Expected URL format: /trongate_tokens/regenerate/{old_token}/{new_expiry_date}
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

        // Attempt to regenerate the token
        $new_token = $this->model->regenerate_token($old_token, $expiry_date);

        if ($new_token === false) {
            http_response_code(404); // Not Found
            echo 'Token not found.';
            die();
        }

        // Return the new token
        http_response_code(200); // OK
        echo $new_token;
    }

}