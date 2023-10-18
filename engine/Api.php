<?php
/**
 * Class Api - Handles API-related functionalities.
 */
class Api extends Trongate {
    function __construct() {
        parent::__construct();
    }

    /**
     * Display the API explorer for development.
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
        $endpoint_settings_location = '/modules/'.$target_table.'/assets/api.json';
        $http_status_codes = $this->get_status_codes();
        $columns = $this->get_table_columns($target_table);
        $view_file = $file_path = APPPATH.'engine/views/api_explorer.php';
        require_once $view_file;
    }

    /**
     * Ensure that a table exists.
     *
     * @param string $table The name of the table to check.
     * @return void
     */
    private function make_sure_table_exists(string $table): void {
        $all_tables = $this->get_all_tables();
        if(!in_array($table, $all_tables)) {
            http_response_code(422);
            echo 'invalid table name'; die();
        }
    }

    /**
     * Return an array of all tables in the database.
     *
     * @return array
     */
    private function get_all_tables(): array {
        $tables = [];
        $sql = 'show tables';
        $column_name = 'Tables_in_'.DATABASE;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $tables[] = $row[$column_name];
        }

        return $tables;
    }

    /**
     * Generate a special token for temporarily bypassing token auth rules.
     *
     * @return string
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
     * Fetch API endpoints for a specific table.
     *
     * @param string $target_table The name of the target table.
     * @return array
     */
    private function fetch_endpoints(string $target_table): array {
        if ($target_table === '') {
            http_response_code(422);
            echo "No target table set"; die();
        }

        $file_path = APPPATH.'modules/'.$target_table.'/assets/api.json';
        $settings = file_get_contents($file_path);
        $endpoints = json_decode($settings, true);   
        return $endpoints;    
    }

    /**
     * Get HTTP status codes and their descriptions.
     *
     * @return array
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
     * Get columns of a table.
     *
     * @param string $table The name of the table.
     * @param bool|null $simplify_output Whether to simplify the output.
     * @return array|string[]
     */
    private function get_table_columns(string $table, ?bool $simplify_output = null): array {
        $sql = 'describe '.$table;
        $rows = $this->model->query($sql, 'array');

        if(isset($simplify_output)) {
            $columns = [];
            foreach($rows as $row) {
                $columns[] = $row['Field'];
            }
            return $columns;
        } else {
            return $rows;
        }  
    }

    /**
     * Handle GET requests for API endpoints.
     *
     * @return void
     */
    public function get(): void {
        $request_type = $_SERVER['REQUEST_METHOD'];

        if($request_type !== 'GET') {
            $this->search();
            return;
        }

        require_once('Standard_endpoints.php');
        $se = new Standard_endpoints();

        if(is_numeric(segment(4))) {
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
        echo 'API insert batch'; die();
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

}