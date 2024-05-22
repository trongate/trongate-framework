<?php

/**
 * Class Api - Handles API-related functionalities.
 */
class Api extends Trongate {
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Displays the API explorer interface for development purposes.
     * This method provides a user interface for exploring and testing API endpoints during development.
     * If the application is not in 'dev' mode, the API explorer will be disabled with a 403 Forbidden response.
     * If the specified table does not exist in the database, a 404 Not Found response will be returned.
     *
     * @return void This method does not return anything directly, but it outputs HTML content for the API explorer.
     */
    public function explorer(): void {

        if (strtolower(ENV) !== 'dev') {
            http_response_code(403); // Forbidden
            echo "API Explorer disabled since not in 'dev' mode.";
            die();
        }

        $target_table = segment(3);

        if (!$this->model->table_exists($target_table)) {
            http_response_code(404); // Not Found
            echo "The specified table '<b>{$target_table}</b>' does not exist in the database.";
            die();
        }

        $special_token = $this->generate_special_token();

        // Fetch an array of all endpoints that exist for this table
        $endpoints = $this->fetch_endpoints($target_table);

        // This is the location for the api.json file that defines the API endpoints
        $endpoint_settings_location = '/modules/' . $target_table . '/assets/api.json';
        $http_status_codes = $this->get_status_codes();
        $columns = $this->model->describe_table($target_table, true);
        $view_file = $file_path = APPPATH . 'engine/views/api_explorer.php';
        require_once $view_file;
    }

    /**
     * Retrieves HTTP status codes and their descriptions.
     *
     * @param int|null $status_code The HTTP status code to retrieve the description for. Defaults to null.
     * @return array|string|null If a specific status code is provided, returns its description as a string. 
     *                           If no status code is provided, returns an array of all HTTP status codes and descriptions.
     *                           If the provided status code is not found, returns "Unknown HTTP Response Code".
     */
    public function get_status_codes(?int $status_code = null): array|string|null {
        $http_status_codes = [
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            422 => "Unprocessable Entity",
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported"
        ];

        if ($status_code !== null) {
            if (array_key_exists($status_code, $http_status_codes)) {
                return $http_status_codes[$status_code];
            } else {
                return "Unknown HTTP Response Code";
            }
        }

        return $http_status_codes;
    }

    /**
     * Handle GET requests for API endpoints.
     *
     * @return void
     */
    public function get(): void {
        $request_type = $_SERVER['REQUEST_METHOD'];

        if ($request_type !== 'GET') {
            $this->search();
            return;
        }

        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();

        if (is_numeric(segment(4))) {
            $se->find_one();
        } else {
            $se->get();
        }
    }

    /**
     * Handle search by POST requests for API endpoints.
     *
     * @return void
     */
    public function search(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->search();
    }

    /**
     * Check if an API endpoint exists using a GET request.
     *
     * @return void
     */
    public function exists(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->exists();
    }

    /**
     * Handle GET requests to count items via an API endpoint.
     *
     * @return void
     */
    public function count(): void {
        $request_type = $_SERVER['REQUEST_METHOD'];
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->count();
    }

    /**
     * Handle POST requests for creating items via an API endpoint.
     *
     * @return void
     */
    public function create(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->create();
    }

    /**
     * Handle POST requests to insert items via an API endpoint.
     *
     * @return void
     */
    public function insert(): void {
        $request_type = $_SERVER['REQUEST_METHOD'];
        if ($request_type === 'GET') {
            http_response_code(400);
            die();
        }
        echo 'API insert batch';
        die();
    }

    /**
     * Handle POST requests for batch insert via an API endpoint.
     *
     * @return void
     */
    public function batch(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->insert();
    }

    /**
     * Handle POST or PUT requests to update items via an API endpoint.
     *
     * @return void
     */
    public function update(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->update();
    }

    /**
     * Handle POST or DELETE requests to delete items via an API endpoint.
     *
     * @return void
     */
    public function destroy(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->destroy();
    }

    /**
     * Handle POST or DELETE requests for deleting one record via an API endpoint.
     *
     * @return void
     */
    public function delete(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->delete_one();
    }

    /**
     * Validate a token for an API endpoint.
     *
     * @param array $token_validation_data Data for token validation.
     * @return mixed
     */
    public function validate_token(array $token_validation_data) {
        //get an array of ALL the rules for this endpoint
        $target_endpoint = $token_validation_data['module_endpoints'][$token_validation_data['endpoint']];
        $table_name = segment(1) === 'api' || segment(1) === 'trongate_filezone' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $token = $se->make_sure_allowed($target_endpoint, $table_name);
        return $token;
    }

    /**
     * Generate a special token for temporarily bypassing token auth rules.
     *
     * @return string
     */
    private function generate_special_token(): string {
        $token_data['user_id'] = 0;
        $token_data['code'] = 'aaa'; //for bypassing auth rules

        //get rid of any existing tokens before generating a new (special) token
        $sql = 'delete from trongate_tokens where user_id = :user_id and code = :code';
        $this->model->query_bind($sql, $token_data);

        $this->module('trongate_tokens');
        $token_data['expiry_date'] = time() + 7200; //two hours
        $special_token = $this->trongate_tokens->_generate_token($token_data);
        return $special_token;
    }

    /**
     * Fetch API endpoints for a specific table.
     *
     * @param string $target_table The name of the target table.
     * @return array
     */
    private function fetch_endpoints(string $target_table): array {
        if ($target_table === '') {
            http_response_code(422);
            echo "No target table set";
            die();
        }

        $file_path = APPPATH . 'modules/' . $target_table . '/assets/api.json';
        $settings = file_get_contents($file_path);
        $endpoints = json_decode($settings, true);
        return $endpoints;
    }

}