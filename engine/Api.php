<?php

/**
 * Class Api - Handles API-related functionalities.
 */
class Api extends Trongate {
    function __construct() {
        parent::__construct();
    }

    /**
     * Provides an API explorer for the specified table.
     *
     * This function checks if the environment is set to 'dev', and if not, returns a forbidden status code.
     * It then generates a new golden token, fetches the endpoints for the specified table,
     * retrieves the location of the API settings file, retrieves HTTP status codes,
     * fetches table columns, and finally includes the API explorer view file for rendering.
     *
     * @return void
     */
    public function explorer(): void {

        if (strtolower(ENV) !== 'dev') {
            http_response_code(403); //forbidden
            echo "API Explorer disabled since not in 'dev' mode.";
            die();
        }

        $target_table = segment(3);
        $this->make_sure_table_exists($target_table);

        $golden_token = $this->generate_new_golden_token();

        //fetch an array of all endpoints that exist for this table
        $endpoints = $this->fetch_endpoints($target_table);

        //this is the location for the api.json file that defines the API endpoints
        $endpoint_settings_location = '/modules/' . $target_table . '/assets/api.json';
        $http_status_codes = $this->get_status_codes();
        $columns = $this->get_table_columns($target_table);
        $view_file = $file_path = APPPATH . 'engine/views/api_explorer.php';
        require_once $view_file;
    }

    /**
     * Ensures that the specified table exists in the database.
     *
     * This function checks if the specified table exists in the database.
     * If the table does not exist, it returns a 422 Unprocessable Entity status code
     * and outputs 'invalid table name'.
     *
     * @param string $table The name of the table to check for existence.
     * @return void
     */
    private function make_sure_table_exists(string $table): void {
        $all_tables = $this->get_all_tables();
        if (!in_array($table, $all_tables)) {
            http_response_code(422);
            echo 'invalid table name';
            die();
        }
    }

    /**
     * Retrieves a list of all tables in the database.
     *
     * This function executes a SQL query to retrieve a list of all tables in the database.
     * It then extracts the table names from the query result and returns them as an array.
     *
     * @return array An array containing the names of all tables in the database.
     */
    private function get_all_tables(): array {
        $tables = [];
        $sql = 'show tables';
        $column_name = 'Tables_in_' . DATABASE;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $tables[] = $row[$column_name];
        }

        return $tables;
    }

    /**
     * Generates a new golden token.
     *
     * This function generates a new golden token for bypassing authentication rules.
     * It sets the user_id to 0 and the code to 'aaa', then deletes any existing tokens
     * with the same user_id and code from the database. After that, it sets the expiry
     * date of the new token to two hours from the current time and generates the token.
     * Finally, it returns the generated golden token.
     *
     * @return string The generated golden token.
     */
    private function generate_new_golden_token(): string {
        $token_data['user_id'] = 0;
        $token_data['code'] = 'aaa'; //for bypassing auth rules

        //get rid of any existing tokens before generating a new (golden) token
        $sql = 'delete from trongate_tokens where user_id = :user_id and code = :code';
        $this->model->query_bind($sql, $token_data);

        $this->module('trongate_tokens');
        $token_data['expiry_date'] = time() + 7200; //two hours
        $golden_token = $this->trongate_tokens->_generate_token($token_data);
        return $golden_token;
    }

    /**
     * Fetches API endpoints for the specified table.
     *
     * This function retrieves the API endpoints defined in the 'api.json' file
     * located in the assets directory of the specified table module.
     * If the target table is empty, it returns a 422 Unprocessable Entity status code
     * and outputs 'No target table set'.
     *
     * @param string $target_table The name of the target table for which endpoints are to be fetched.
     * @return array An array containing the API endpoints for the specified table.
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

    /**
     * Retrieves HTTP status codes and their corresponding descriptions.
     *
     * This function returns an associative array containing HTTP status codes as keys
     * and their corresponding descriptions as values.
     *
     * @return array An associative array containing HTTP status codes and descriptions.
     */
    private function get_status_codes(): array {
        $http_status_codes = array(
            "CODE_200" => "OK",
            "CODE_201" => "Created",
            "CODE_202" => "Accepted",
            "CODE_203" => "Non-Authoritative Information",
            "CODE_204" => "No Content",
            "CODE_205" => "Reset Content",
            "CODE_206" => "Partial Content",
            "CODE_300" => "Multiple Choices",
            "CODE_301" => "Moved Permanently",
            "CODE_302" => "Found",
            "CODE_303" => "See Other",
            "CODE_304" => "Not Modified",
            "CODE_305" => "Use Proxy",
            "CODE_307" => "Temporary Redirect",
            "CODE_400" => "Bad Request",
            "CODE_401" => "Unauthorized",
            "CODE_402" => "Payment Required",
            "CODE_403" => "Forbidden",
            "CODE_404" => "Not Found",
            "CODE_405" => "Method Not Allowed",
            "CODE_406" => "Not Acceptable",
            "CODE_407" => "Proxy Authentication Required",
            "CODE_408" => "Request Timeout",
            "CODE_409" => "Conflict",
            "CODE_410" => "Gone",
            "CODE_411" => "Length Required",
            "CODE_412" => "Precondition Failed",
            "CODE_413" => "Request Entity Too Large",
            "CODE_414" => "Request-URI Too Long",
            "CODE_415" => "Unsupported Media Type",
            "CODE_416" => "Requested Range Not Satisfiable",
            "CODE_417" => "Expectation Failed",
            "CODE_422" => "Unprocessable Entity",
            "CODE_500" => "Internal Server Error",
            "CODE_501" => "Not Implemented",
            "CODE_502" => "Bad Gateway",
            "CODE_503" => "Service Unavailable",
            "CODE_504" => "Gateway Timeout",
            "CODE_505" => "HTTP Version Not Supported"
        );

        return $http_status_codes;
    }

    /**
     * Retrieves the columns of a specified database table.
     *
     * This function executes a 'DESCRIBE' SQL query to fetch the columns of the specified database table.
     * If the $simplify_output parameter is set to true, it returns an array containing only the names of the columns.
     * Otherwise, it returns an array containing the complete rows describing each column.
     *
     * @param string $table The name of the database table.
     * @param bool|null $simplify_output Optional. If set to true, only column names are returned. Default is null.
     * @return array An array containing either column names or complete column descriptions, depending on the $simplify_output parameter.
     */
    private function get_table_columns(string $table, ?bool $simplify_output = null): array {
        $sql = 'describe ' . $table;
        $rows = $this->model->query($sql, 'array');

        if (isset($simplify_output)) {
            $columns = [];
            foreach ($rows as $row) {
                $columns[] = $row['Field'];
            }
            return $columns;
        } else {
            return $rows;
        }
    }

    /**
     * Handles GET requests.
     *
     * This function first checks if the request method is 'GET'.
     * If it's not, it delegates the request to the 'search' method and returns.
     * Otherwise, it requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * If the fourth segment of the URI is numeric, it calls the 'find_one' method of the 'Standard_endpoints' class.
     * Otherwise, it calls the 'get' method of the 'Standard_endpoints' class.
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
     * Handles search requests.
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'search' method of the 'Standard_endpoints' class to handle the search request.
     *
     * @return void
     */
    public function search(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->search();
    }

    /**
     * Handles requests to check if a resource exists.
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'exists' method of the 'Standard_endpoints' class to handle the request to check if a resource exists.
     *
     * @return void
     */
    public function exists(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->exists();
    }

    /**
     * Handles requests to count resources.
     *
     * This function first checks the request method.
     * It requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'count' method of the 'Standard_endpoints' class to handle the request to count resources.
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
     * Handles requests to create a new resource (record).
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'create' method of the 'Standard_endpoints' class to handle the request to create a new resource.
     *
     * @return void
     */
    public function create(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->create();
    }

    /**
     * Handles requests to insert data into the database.
     *
     * This function first checks the request method.
     * If the request method is 'GET', it returns a 400 Bad Request status code and terminates.
     * Otherwise, it echoes 'API insert batch' and terminates.
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
     * Handles batch insert requests.
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'insert' method of the 'Standard_endpoints' class to handle the batch insert request.
     *
     * @return void
     */
    public function batch(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->insert();
    }

    /**
     * Handles requests to update existing resources.
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'update' method of the 'Standard_endpoints' class to handle the request to update existing resources.
     *
     * @return void
     */
    public function update(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->update();
    }

    /**
     * Handles requests to delete existing resources.
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'destroy' method of the 'Standard_endpoints' class to handle the request to delete existing resources.
     *
     * @return void
     */
    public function destroy(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->destroy();
    }

    /**
     * Handles requests to delete a single resource (record).
     *
     * This function requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It then calls the 'delete_one' method of the 'Standard_endpoints' class to handle the request to delete a single resource.
     *
     * @return void
     */
    public function delete(): void {
        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();
        $se->delete_one();
    }

    /**
     * Validates the token for endpoint access.
     *
     * This function retrieves all the rules for the specified endpoint from the module endpoints data.
     * It determines the table name based on the segment of the URI.
     * It then requires the 'Standard_endpoints.php' file and creates an instance of the 'Standard_endpoints' class.
     * It calls the 'make_sure_allowed' method of the 'Standard_endpoints' class to validate the token against the endpoint rules.
     *
     * @param array $token_validation_data An array containing data required for token validation, including module endpoints and endpoint name.
     * @return mixed The validated token, if validation succeeds.
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
}
