<?php

/**
 * Class Standard_endpoints
 *
 * This class extends Trongate and provides standard endpoints for API operations.
 */
class Standard_endpoints extends Trongate {

    private $operators = [
        "!=" => "%21=",
        "<=" => "%3C=",
        ">=" => "%3E=",
        "=" => "=",
        "<" => "%3C",
        ">" => "%3E"
    ];


    /**
     * Standard_endpoints constructor.
     */
    function __construct() {
        parent::__construct();
    }


    /**
     * Function: get_request_type()
     * Description: Retrieves the HTTP request method (GET, POST, PUT, DELETE) from the current request.
     *              It also checks for the X-HTTP-Method-Override header to handle cases where the
     *              actual request method is overridden.
     * Returns:
     *    - string: The HTTP request method.
     */
    private function get_request_type() {
        header('Access-Control-Allow-Headers: X-HTTP-Method-Override');
        $request_type = $_SERVER['REQUEST_METHOD'];

        // Check for X-HTTP-Method-Override header
        if (($request_type !== 'GET') && (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))) {
            // Get the desired HTTP method from the X-HTTP-Method-Override header
            $request_type = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        return $request_type;
    }


    /**
     * Function: index()
     * Description: Entry point for handling API requests. It determines the HTTP request method
     *              and routes the request to the appropriate method (get(), create(), destroy(), etc.).
     *              If the request method is not recognized, it defaults to the 'GET' method.
     * Returns: void
     */
    public function index() {
        $request_type = $this->get_request_type();
        switch ($request_type) {
            case 'GET':
                $this->get();
                break;
            case 'POST':
                $this->create();
                break;
            case 'DELETE':
                $this->destroy();
                break;
            default:
                $this->get();
                break;
        }
    }


    /**
     * Function: get($return_row_count = false)
     * Description: Handles GET requests to retrieve data from the database table. It determines whether
     *              to return the full row count or the actual data based on the $return_row_count parameter.
     *              If $return_row_count is set to true, it returns the count of rows; otherwise, it returns
     *              the actual data rows.
     * Parameters:
     *     - $return_row_count (boolean): A flag to indicate whether to return the row count (default: false)
     * Returns: void
     */
    public function get($return_row_count = false) { //GET
        //get the endpoint from the api.json file (and make sure allowed!)
        $endpoint_name =  $return_row_count === true ? 'Count' : 'Get';
        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);
        $input = $this->get_target_endpoint($table_name, $endpoint_name);

        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $standard_query = 'SELECT * FROM ' . $table_name;
        $rows = $this->fetch_rows($standard_query, $input['params']);

        $output['token'] = $input['token'];
        $output['body'] = $return_row_count === true ? count($rows) : json_encode($rows);
        $output['code'] = 200;

        if ($after_hook !== '') {
            $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
        }

        $output = $this->clean_output($output);
        http_response_code($output['code']);
        echo $output['body'];
        die();
    }


    /**
     * Function: search($return_row_count = false)
     * Description: Handles POST requests to search for data in the database table. It determines whether
     *              to return the full row count or the actual data based on the $return_row_count parameter.
     *              If $return_row_count is set to true, it returns the count of rows; otherwise, it returns
     *              the actual data rows.
     * Parameters:
     *     - $return_row_count (boolean): A flag to indicate whether to return the row count (default: false)
     * Returns: void
     */
    public function search($return_row_count = false) { //POST
        $request_type = $this->get_request_type();
        if ($request_type !== 'POST') {
            http_response_code(400);
            die();
        }

        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);
        $endpoint_name = $return_row_count === true ? 'Count By Post' : 'Search';

        $input = $this->get_target_endpoint($table_name, $endpoint_name);
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $standard_query = 'SELECT * FROM ' . $table_name;
        $rows = $this->fetch_rows($standard_query, $input['params']);

        $output['token'] = $input['token'];
        $output['body'] = $return_row_count === true ? count($rows) : json_encode($rows);
        $output['code'] = 200;

        if ($after_hook !== '') {
            $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
        }

        $output = $this->clean_output($output);
        http_response_code($output['code']);
        echo $output['body'];
        die();
    }


    /**
     * Function: find_one()
     * Description: Retrieves a single record from the database table based on the provided ID.
     *              Handles GET requests.
     * Parameters: None
     * Returns: void
     */
    public function find_one() { //GET
        $request_type = $this->get_request_type();
        if ($request_type !== 'GET') {
            http_response_code(400);
            die();
        }

        $target_segment = segment(1) === 'api' ? 4 : 2;
        $update_id = intval(segment($target_segment));

        if ($update_id > 0) {
            $table_name = segment(1) === 'api' ? segment(3) : segment(1);
            $table_name = remove_query_string($table_name);
            $input = $this->get_target_endpoint($table_name, 'Find One');
            $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
            $after_hook = $input['target_endpoint']['afterHook'] ?? '';
            unset($input['target_endpoint']);

            if ($before_hook !== '') {
                $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
            }

            $result = $this->model->get_where($update_id, $table_name);
            if ($result === false) {
                $this->api_manager_error(404, 'Record not found');
            }

            $output['token'] = $input['token'];
            $output['body'] = json_encode($result);
            $output['code'] = 200;

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } else {
            $this->api_manager_error(400, 'Non-numeric update ID!');
        }
    }


    /**
     * Function: exists()
     * Description: Checks whether a record with the provided ID exists in the database table.
     *              Handles GET requests.
     * Parameters: None
     * Returns: void
     */
    public function exists() { //GET
        $request_type = $this->get_request_type();
        if ($request_type !== 'GET') {
            http_response_code(400);
            die();
        }

        $target_segment = segment(1) === 'api' ? 4 : 2;
        $update_id = intval(segment($target_segment));
        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);

        //get the endpoint from the api.json file (and make sure allowed!)
        $input = $this->get_target_endpoint($table_name, 'Exists');
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $result = $this->model->get_where($update_id, $table_name);

        $output['token'] = $input['token'];
        $output['body'] = $result === false ? 'false' : 'true';
        $output['code'] = 200;

        if ($after_hook !== '') {
            $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
        }

        $output = $this->clean_output($output);
        http_response_code($output['code']);
        echo $output['body'];
        die();
    }

    /**
     * Function: count()
     * Description: Counts the number of records in the database table.
     *              Handles both GET and POST requests.
     * Parameters: None
     * Returns: void
     */
    public function count() { //GET or POST
        $request_type = $this->get_request_type();
        if ($request_type !== 'GET') {
            $this->search(true);
        } else {
            $this->get(true);
        }
    }


    /**
     * Function: create()
     * Description: Creates a new record in the database table.
     *              Handles POST requests.
     * Parameters: None
     * Returns: void
     */
    public function create() {
        $request_type = $this->get_request_type();
        if ($request_type !== 'POST') {
            http_response_code(400);
            die();
        }
        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);

        $input = $this->get_target_endpoint($table_name, 'Create');
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        //make sure params have been posted
        if (count($input['params']) == 0) {
            $this->api_manager_error(400, 'No posted data!');
        }

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $columns_exist_result = $this->make_sure_columns_exist($table_name, $input['params']);

        if ($columns_exist_result !== true) {
            $this->api_manager_error(400, $columns_exist_result);
        }

        //attempt insert new record
        try {
            $new_id = $this->model->insert($input['params'], $table_name);
            $new_record_obj = $this->model->get_where($new_id, $table_name);

            $output['token'] = $input['token'];
            $output['code'] = 200;
            $output['body'] = json_encode($new_record_obj);

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }


    /**
     * Function: make_sure_columns_exist($table_name, $params, $valid_columns = null)
     * Description: Checks if the provided columns exist in the specified database table.
     * Parameters:
     *     $table_name (string) - The name of the database table to check.
     *     $params (array) - An associative array where keys represent column names and values represent column values.
     *     $valid_columns (array|null) - (Optional) An array containing the names of valid columns in the table.
     * Returns:
     *     true if all columns exist in the table.
     *     A string message describing the non-existent columns if any are found.
     */
    function make_sure_columns_exist($table_name, $params, $valid_columns = null) {

        if (!isset($valid_columns)) {
            $valid_columns = $this->get_all_columns($table_name);
        }

        $invalid_columns = [];
        foreach ($params as $column => $value) {
            if (!in_array($column, $valid_columns)) {
                $invalid_columns[] = $column;
            }
        }
        if (count($invalid_columns) === 0) {
            return true;
        } elseif (count($invalid_columns) === 1) {
            $message = "The {$invalid_columns[0]} column does not exist on the {$table_name} table!";
            return $message;
        } else {
            $message = "The following columns do not exist on the {$table_name} table: " . implode(', ', $invalid_columns);
            return $message;
        }
    }


    /**
     * Function: get_all_columns($table)
     * Description: Retrieves all column names of a specified database table.
     * Parameters:
     *     $table (string) - The name of the database table to retrieve column names from.
     * Returns:
     *     An array containing the names of all columns in the specified table.
     */
    private function get_all_columns($table) {
        $columns = [];
        $sql = 'describe ' . $table;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $columns[] = $row['Field'];
        }

        return $columns;
    }

    /**
     * Function: insert()
     * Description: Batch inserts records into the specified database table.
     * Parameters: None
     * Returns: None
     */
    public function insert() { // BATCH INSERT!

        $request_type = $this->get_request_type();
        if ($request_type !== 'POST') {
            http_response_code(400);
            die();
        }
        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);
        $input = $this->get_target_endpoint($table_name, 'Insert Batch');

        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        //fetch the posted data
        $post = trim(file_get_contents('php://input'));

        if ($post === '') {
            $this->api_manager_error(400, 'No posted data!');
        }

        $posted_items = json_decode($post, true);
        if (!is_array($posted_items) || substr($post, 0, 1) !== '[' || substr($post, -1) !== ']') {
            $this->api_manager_error(400, 'Invalid format: Not an array of objects!');
        }

        if (count($posted_items) === 0) {
            $this->api_manager_error('400', 'No posted data!');
        }

        $input['params'] = $posted_items;

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        //loop through the posted data and make sure all of the columns exist on the table
        $valid_columns = $this->get_all_columns($table_name);
        foreach ($input['params'] as $posted_item) {
            $columns_exist_result = $this->make_sure_columns_exist($table_name, $posted_item);

            if ($columns_exist_result !== true) {
                $this->api_manager_error(400, $columns_exist_result);
            }
        }

        //attempt batch insert records
        try {

            $row_count = $this->model->insert_batch($table_name, $input['params']);
            $output['body'] = $row_count;
            $output['code'] = 200;

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }

    /**
     * Function: update()
     * Description: Updates a record in the specified database table.
     * Parameters: None
     * Returns: None
     */
    public function update() { //POST or PUT
        $allowed_request_types = (segment(1) === 'api') ? array('POST', 'PUT') : array('PUT');
        $target_segment = (segment(1) === 'api') ? 4 : 2;
        $update_id = intval(segment($target_segment));

        $request_type = $this->get_request_type();

        if (!in_array($request_type, $allowed_request_types)) {
            http_response_code(400);
            die();
        }

        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);

        $input = $this->get_target_endpoint($table_name, 'Update');
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        //make sure params have been posted
        if (count($input['params']) == 0) {
            $this->api_manager_error(400, 'No posted data!');
        }

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $columns_exist_result = $this->make_sure_columns_exist($table_name, $input['params']);

        if ($columns_exist_result !== true) {
            $this->api_manager_error(400, $columns_exist_result);
        }

        //attempt update record
        try {
            unset($input['params']['id']);
            $this->model->update($update_id, $input['params'], $table_name);
            $record_obj = $this->model->get_where($update_id, $table_name);

            $output['token'] = $input['token'];
            $output['code'] = 200;
            $output['body'] = json_encode($record_obj);

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }


    /**
     * Function: destroy()
     * Description: Handles deletion of records from a specified table based on POST or DELETE request.
     *              Validates request type, retrieves target endpoint details, invokes hooks if provided,
     *              fetches rows based on specified query parameters, builds an array of IDs to delete,
     *              validates IDs, constructs SQL query, executes deletion, handles response,
     *              and manages errors appropriately.
     */
    public function destroy() { //POST or DELETE
        $allowed_request_types = (segment(1) === 'api') ? array('POST', 'DELETE') : array('DELETE');
        $request_type = $this->get_request_type();

        if (!in_array($request_type, $allowed_request_types)) {
            http_response_code(400);
            die();
        }

        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);

        $input = $this->get_target_endpoint($table_name, 'Destroy');
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $standard_query = 'SELECT * FROM ' . $table_name;
        $rows = $this->fetch_rows($standard_query, $input['params']);

        // Build an array of items to go, based on 'id'
        $update_ids = [];
        foreach ($rows as $row) {
            $update_ids[] = $row->id;
        }

        try {

            // Check that all the array values are integers
            foreach ($update_ids as $update_id) {
                if (!is_int($update_id)) {
                    $this->api_manager_error(400, 'Invalid input: array contains non-integer value!');
                }
            }

            // Convert the array of update IDs to a comma-separated string
            $update_ids_str = implode(',', $update_ids);

            if (count($update_ids) > 0) {
                // Build the SQL query to delete rows from the table where 'id' values match those in the array
                $sql = "DELETE FROM $table_name WHERE id IN ({$update_ids_str})";
                $this->model->query($sql);
            }

            $output['token'] = $input['token'];
            $output['code'] = 200;
            $output['body'] = count($update_ids);

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }


    /**
     * Function: delete_one()
     * Description: Handles deletion of a single record from a specified table based on DELETE request.
     *              Validates request type, extracts update ID from request URL, retrieves target endpoint details,
     *              invokes hooks if provided, fetches record to delete, executes deletion, handles response,
     *              and manages errors appropriately.
     */
    public function delete_one() { //DELETE
        $allowed_request_types = (segment(1) === 'api') ? array('POST', 'DELETE') : array('DELETE');
        $target_segment = (segment(1) === 'api') ? 4 : 2;
        $update_id = intval(segment($target_segment));

        if ($update_id <= 0) {
            $this->api_manager_error(400, 'Invalid update ID!');
        }

        $request_type = $this->get_request_type();

        if (!in_array($request_type, $allowed_request_types)) {
            http_response_code(400);
            die();
        }

        $table_name = segment(1) === 'api' ? segment(3) : segment(1);
        $table_name = remove_query_string($table_name);

        $input = $this->get_target_endpoint($table_name, 'Delete One');
        $before_hook = $input['target_endpoint']['beforeHook'] ?? '';
        $after_hook = $input['target_endpoint']['afterHook'] ?? '';
        unset($input['target_endpoint']);

        if ($before_hook !== '') {
            $input['params']['id'] = $update_id; //set id to delete
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $record_obj = $this->model->get_where($update_id, $table_name);

        if ($record_obj === false) {
            http_response_code(422);
            echo 'false';
            die();
        }

        //attempt delete record
        try {
            $this->model->delete($update_id, $table_name);

            $output['token'] = $input['token'];
            $output['code'] = 200;
            $output['body'] = 'true';

            if ($after_hook !== '') {
                $output = $this->attempt_invoke_after_hook($table_name, $after_hook, $output);
            }

            $output = $this->clean_output($output);
            http_response_code($output['code']);
            echo $output['body'];
            die();
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }


    /**
     * Function: attempt_invoke_before_hook()
     * Description: Attempts to invoke a "before" hook for a specified table.
     *              Invokes the hook function if provided, passing input data,
     *              and returns the modified input data after hook invocation.
     * Parameters:
     *    - $table_name (string): The name of the table for which the hook is invoked.
     *    - $before_hook (string): The name of the hook function to invoke.
     *    - $input (array): The input data to be passed to the hook function.
     * Returns:
     *    - array: The modified input data after hook invocation.
     */
    private function attempt_invoke_before_hook($table_name, $before_hook, $input) {
        $input = Modules::run($table_name . '/' . $before_hook, $input);
        return $input;
    }


    /**
     * Function: attempt_invoke_after_hook()
     * Description: Attempts to invoke an "after" hook for a specified table.
     *              Invokes the hook function if provided, passing output data,
     *              and returns the modified output data after hook invocation.
     * Parameters:
     *    - $table_name (string): The name of the table for which the hook is invoked.
     *    - $after_hook (string): The name of the hook function to invoke.
     *    - $output (array): The output data to be passed to the hook function.
     * Returns:
     *    - array: The modified output data after hook invocation.
     */
    private function attempt_invoke_after_hook($table_name, $after_hook, $output) {
        $output = Modules::run($table_name . '/' . $after_hook, $output);
        return $output;
    }


    /**
     * Function: fetch_rows()
     * Description: Fetches rows from the database based on a standard query along with submitted parameters.
     *              Extracts WHERE, ORDER BY, and LIMIT-OFFSET clauses from submitted parameters,
     *              constructs SQL query accordingly, binds parameters, executes the query,
     *              and returns the fetched rows.
     * Parameters:
     *    - $standard_query (string): The standard SQL query without WHERE, ORDER BY, or LIMIT-OFFSET clauses.
     *    - $submitted_params (array): The submitted parameters containing WHERE, ORDER BY, and LIMIT-OFFSET clauses.
     * Returns:
     *    - array: The fetched rows from the database.
     */
    private function fetch_rows($standard_query, $submitted_params) {
        $where_data = $this->extract_where_data($submitted_params);
        $order_by_clause = $this->extract_order_by_clause($submitted_params);
        $limit_offset_clause = $this->extract_limit_offset_clause($submitted_params);

        $sql = $standard_query;
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];

        if ($where_clause !== '') {
            $sql .= ' ' . $where_clause;
        }

        if ($order_by_clause !== '') {
            $sql .= ' ' . $order_by_clause;
        }

        if ($limit_offset_clause !== '') {
            $sql .= ' ' . $limit_offset_clause;
        }

        try {
            $rows = $this->model->query_bind($sql, $params, 'object');
            return $rows;
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }


    /**
     * Function: extract_where_data()
     * Description: Extracts WHERE clause and parameters from submitted parameters.
     *              Iterates through submitted parameters, ignores certain keys (e.g., orderBy, limit, offset),
     *              constructs WHERE clause based on key-value pairs, handles conjunctions (AND, OR),
     *              handles comparison operators, and builds an array of parameters for binding.
     * Parameters:
     *    - $submitted_params (array): The submitted parameters containing key-value pairs for WHERE clause.
     * Returns:
     *    - array: An associative array containing WHERE clause and parameters.
     */
    private function extract_where_data($submitted_params) {
        //returns a WHERE clause and an array of params
        $params = [];
        $counter = 0;
        $ignore_keys = array('orderBy', 'order_by', 'limit', 'offset', 'ixd');
        $where_clause = '';
        foreach ($submitted_params as $key => $value) {
            $key_bits = explode(' ', trim($key));
            //ignore limit, offset etc...
            if (in_array($key_bits[0], $ignore_keys)) {
                continue;
            }

            $conjunction = ($where_clause === '') ? 'WHERE' : (strtoupper($key_bits[0]) === 'OR' ? 'OR' : 'AND');
            $first_three = substr($key, 0, 3);
            if (strtoupper($first_three) === 'OR ') {
                $key = substr($key, 3);
                $key_bits = explode(' ', trim($key)); //must be re-established, having dealt with 'OR' scenario
            }

            $column = $key_bits[0];

            if (count($key_bits) > 1) {
                $last_bit = $key_bits[count($key_bits) - 1];
                $operator = ($last_bit === '!') ? '!=' : $operator = $last_bit;
            } else {
                $operator = '=';
            }

            $counter++;
            $property_name = 'arg' . $counter;
            $where_clause .= $conjunction . ' ' . $column . $operator . '? ';
            $params[] = $value;
        }

        $where_data['where_clause'] = trim($where_clause);
        $where_data['params'] = $params;
        return $where_data;
    }


    /**
     * Function: extract_order_by_clause()
     * Description: Extracts the ORDER BY clause from submitted parameters.
     *              Iterates through submitted parameters to find 'orderBy' or 'order_by' key,
     *              constructs the ORDER BY clause based on the found value, and returns it.
     * Parameters:
     *    - $submitted_params (array): The submitted parameters containing 'orderBy' or 'order_by' key.
     * Returns:
     *    - string: The constructed ORDER BY clause, or an empty string if not found.
     */
    private function extract_order_by_clause($submitted_params) {
        foreach ($submitted_params as $key => $value) {
            if ($key === 'orderBy' or $key === 'order_by') {
                $order_by_clause = 'ORDER BY ' . $value;
                return $order_by_clause;
            }
        }

        $order_by_clause = '';
        return $order_by_clause;
    }


    /**
     * Function: extract_limit_offset_clause()
     * Description: Extracts the LIMIT-OFFSET clause from submitted parameters.
     *              Iterates through submitted parameters to find 'limit' and 'offset' keys,
     *              constructs the LIMIT-OFFSET clause based on the found values, and returns it.
     * Parameters:
     *    - $submitted_params (array): The submitted parameters containing 'limit' and 'offset' keys.
     * Returns:
     *    - string: The constructed LIMIT-OFFSET clause, or an empty string if not found.
     */
    private function extract_limit_offset_clause($submitted_params) {
        $limit_offset_clause = 'LIMIT [offset], [limit]';
        $limit = null;
        $offset = null;

        foreach ($submitted_params as $key => $value) {
            if (strtolower($key) === 'limit' && is_numeric($value)) {
                $limit = (int) $value;
            } elseif (strtolower($key) === 'offset' && is_numeric($value)) {
                $offset = (int) $value;
            }
        }

        if ($limit === null && $offset === null) {
            $limit_offset_clause = '';
        } else {
            $limit_offset_clause = str_replace('[limit]', $limit ?? '18446744073709551615', $limit_offset_clause);
            $limit_offset_clause = str_replace('[offset]', $offset ?? 0, $limit_offset_clause);
        }

        return $limit_offset_clause;
    }


    /**
     * Function: clean_output()
     * Description: Cleans the output array by keeping only the allowed keys.
     * Parameters:
     *    - $output (array): The output array to be cleaned.
     * Returns:
     *    - array: The cleaned output array containing only allowed keys.
     */
    private function clean_output($output) {
        $allowed_keys = ['body', 'code', 'token'];
        return array_intersect_key($output, array_flip($allowed_keys));
    }


    /**
     * Function: attempt_serve_standard_endpoint()
     * Description: Attempts to serve a standard endpoint based on the provided endpoint index.
     *              Retrieves standard endpoints, identifies the target endpoint based on the index,
     *              extracts request name and constructs method name, invokes the corresponding method.
     * Parameters:
     *    - $endpoint_index (int): The index of the standard endpoint to serve.
     */
    public function attempt_serve_standard_endpoint($endpoint_index) {
        $standard_endpoints = $this->get_standard_endpoints();
        $target_endpoint = $standard_endpoints[$endpoint_index];
        $request_name = $target_endpoint['request_name'];
        $target_method = strtolower(url_title($request_name));
        $target_method = str_replace('-', '_', $target_method);
        $this->$target_method();
    }


    /**
     * Function: attempt_find_endpoint_index()
     * Description: Attempts to find the index of the endpoint based on the current request type and URL.
     *              Retrieves the current request type and URL, processes the resource path,
     *              replaces numeric segments with '{id}', matches the resource path with standard endpoints,
     *              considering both request type and RESTful identifier, and returns the index of the matched endpoint.
     * Returns:
     *    - int|string: The index of the matched endpoint, or an empty string if no match found.
     */
    public function attempt_find_endpoint_index() {

        $request_type = $this->get_request_type();
        $current_url = remove_query_string(current_url());

        $first_segment = remove_query_string(segment(1));
        $second_segment = remove_query_string(segment(2));
        $ditch = $second_segment === '' ? BASE_URL . $first_segment : BASE_URL . $first_segment . '/';
        $resource_path = str_replace($ditch, '', $current_url);

        $segments = explode('/', $resource_path);
        foreach ($segments as &$segment) {
            if (is_numeric($segment)) {
                $segment = '{id}';
            }
        }

        $resource_path = implode('/', $segments);

        if (substr($resource_path, -1) === '/') {
            $resource_path = substr($resource_path, 0, -1);
        }

        $standard_endpoints = $this->get_standard_endpoints();
        $resource_path = str_replace('%7Bid%7D', '{id}', $resource_path);

        foreach ($standard_endpoints as $i => $standard_endpoint) {
            $target_request_type =  $standard_endpoint['request_type'];
            $restful_identifier = $standard_endpoint['restful_identifier'];

            if (($resource_path === $restful_identifier) && ($request_type === $target_request_type)) {
                $target_endpoint_index = $i;
                break;
            }
        }

        if (!isset($target_endpoint_index)) {
            $target_endpoint_index = '';
        }

        return $target_endpoint_index;
    }


    /**
     * Function: get_target_endpoint()
     * Description: Retrieves the target endpoint details from the endpoint settings file based on the provided table name and endpoint name.
     *              Checks if the endpoint settings file exists, reads the JSON content,
     *              searches for the specified endpoint by name, handles 'Search' endpoint with fallback,
     *              validates the existence of the target endpoint, ensures token authentication if required,
     *              extracts module name, endpoint name, and query parameters from the URL if enabled,
     *              and returns an array containing target endpoint details and extracted parameters.
     * Parameters:
     *    - $table_name (string): The name of the table/module to which the endpoint belongs.
     *    - $endpoint_name (string): The name of the endpoint to retrieve.
     * Returns:
     *    - array: An array containing target endpoint details and extracted parameters.
     */
    private function get_target_endpoint($table_name, $endpoint_name) {
        $file_path = APPPATH . 'modules/' . $table_name . '/assets/api.json';

        if (!file_exists($file_path)) {
            if (segment(1) !== 'api') {
                load('error_404');
                die();
            }

            $this->api_manager_error(404, 'Endpoint settings not found!');
        }

        $endpoints = json_decode(file_get_contents($file_path), true);

        if ($endpoint_name === 'Search') {
            $input['target_endpoint'] = $endpoints[$endpoint_name] ?? false;

            // If the 'Search' endpoint is not found, try 'Get By Post' as fallback
            if ($input['target_endpoint'] === false) {
                $input['target_endpoint'] = $endpoints['Get By Post'] ?? false;
            }
        } else {
            $input['target_endpoint'] = $endpoints[$endpoint_name] ?? false;
        }

        if ($input['target_endpoint'] === false) {
            if (segment(1) !== 'api') {
                load('error_404');
                die();
            }

            $this->api_manager_error(404, 'Endpoint settings not found!');
        }

        //token auth (make sure allowed)
        $input['token'] = $this->make_sure_allowed($input['target_endpoint'], $table_name);
        $input['module_name'] = $table_name;
        $input['endpoint'] = $endpoint_name;

        //read query params from the URL
        $request_type = strtoupper($input['target_endpoint']['request_type']);
        $enable_params = $input['target_endpoint']['enableParams'] ?? false;

        if (($enable_params === true) && ($endpoint_name !== 'Insert Batch')) {

            if ($request_type === 'GET') {
                $query_params = $this->fetch_query_params_from_url();
                $input['params'] = $this->reduce_query_params($query_params);
            } else {
                //attemp get params from post
                $query_params = $this->fetch_query_params_from_post();
                $input['params'] = $this->reduce_query_params($query_params);
            }
        }

        if (!isset($input['params'])) {
            $input['params'] = [];
        }

        return $input;
    }


    /**
     * Function: make_sure_allowed()
     * Description: Ensures that the user is allowed to access the target endpoint based on authorization rules.
     *              Checks if authorization rules are declared for the target endpoint,
     *              retrieves the user token from the HTTP header,
     *              validates the token, retrieves user details from the token if valid,
     *              checks authorization rules such as user roles, user IDs, user segments,
     *              and ensures access based on these rules or through AAA tokens.
     * Parameters:
     *    - $target_endpoint (array): Details of the target endpoint including authorization rules.
     *    - $table_name (string): The name of the table/module to which the endpoint belongs.
     * Returns:
     *    - string|void: The user token if access is allowed, otherwise triggers an API error response.
     */
    public function make_sure_allowed($target_endpoint, $table_name) {

        if (!isset($target_endpoint['authorization'])) {
            $msg = 'Endpoint not activated since no authorization rules have been declared.';
            $this->api_manager_error(412, $msg);
        }

        //attempt get user token
        $this->module('trongate_tokens');
        $token = (isset($_SERVER['HTTP_TRONGATETOKEN']) ? $_SERVER['HTTP_TRONGATETOKEN'] : false);

        $endpoint_auth_rules = $target_endpoint['authorization'];
        $var_type = gettype($endpoint_auth_rules);

        if ($var_type === 'string' && $endpoint_auth_rules === '*') {
            return $token; // wide open endpoint
        }

        if ($token === '' || $token === false) {
            $this->api_manager_error(401, 'Invalid token.');
        }

        //attempt fetch user object from the token
        $trongate_user = $this->trongate_tokens->_get_user_obj($token);

        if ($trongate_user === false) {
            // Test for aaa token
            $allowed = $this->test_for_aaa_token($token);
            if ($allowed === true) {
                return $token; //aaa token submitted - access allowed
            } else {
                $this->api_manager_error(401, 'Invalid token.');
            }
        }

        //let's go through all of the authoriation rules and attempt to pass ONE of them
        $user_level = $trongate_user->user_level ?? '';
        $trongate_user_id = $trongate_user->trongate_user_id ?? '';
        $trongate_user_code = $trongate_user->trongate_user_code ?? '';

        //role based authorization
        if (isset($endpoint_auth_rules['roles'])) {
            if (in_array($user_level, $endpoint_auth_rules['roles'])) {
                return $token; //match for allowed user role!
            }
        }

        //id based authorization
        if (isset($endpoint_auth_rules['userIds'])) {
            if (in_array($trongate_user_id, $endpoint_auth_rules['userIds'])) {
                return $token; //match for allowed trongate_user_id
            }
        }

        if (segment(1) === 'api') {

            //user id segment authorization (only for Trongate API paths!)
            if (isset($endpoint_auth_rules['userIdSegment'])) {
                $target_value = segment($endpoint_auth_rules['userIdSegment']);
                settype($target_value, 'int');

                if ($trongate_user_id === $target_value) {
                    return $token; //match for trongate_user_id on target segment
                }
            }

            //user code segment authorization (only for Trongate API paths!)
            if (isset($endpoint_auth_rules['userCodeSegment'])) {
                $target_value = segment($endpoint_auth_rules['userCodeSegment']);

                if ($trongate_user_code === $target_value) {
                    return $token; //match for trongate_user_code on target segment
                }
            }

            //user owned segment authorization (only for Trongate API paths!)
            if (isset($endpoint_auth_rules['userOwnedSegment'])) {
                $test_data['table_name'] = $table_name;
                $test_data['trongate_user_id'] = $trongate_user_id;
                $test_data['user_owned_settings'] = $endpoint_auth_rules['userOwnedSegment'];
                $this->run_user_owned_test($test_data); //true or false
                return $token;
            }
        }

        //safety net
        $this->api_manager_error(401, 'Invalid token.');
    }


    /**
     * Function: test_for_aaa_token()
     * Description: Tests if the provided token is an AAA token.
     *              Retrieves the token object from the database based on the provided token,
     *              checks if the token code is 'aaa', and returns true if it is, otherwise returns false.
     * Parameters:
     *    - $token (string): The token to be tested.
     * Returns:
     *    - bool: True if the token is an AAA token, false otherwise.
     */
    private function test_for_aaa_token($token) {
        $token_obj = $this->model->get_one_where('token', $token, 'trongate_tokens');
        $code = $token_obj->code ?? '';
        $allowed = $code === 'aaa' ? true : false;
        return $allowed;
    }


    /**
     * Function: run_user_owned_test()
     * Description: Runs a test to determine if the user is the owner of a specific record based on provided test data.
     *              Retrieves the column and value information from the test data,
     *              fetches the record from the target table where the column value matches the provided value,
     *              validates if the record exists and if the user is the owner based on user ID comparison,
     *              and triggers an API error response if the test fails.
     * Parameters:
     *    - $test_data (array): An array containing data required for the user-owned test including column, segment number, table name, and user ID.
     * Returns:
     *    - void: This function does not return any value. It triggers an API error response if the test fails.
     */
    private function run_user_owned_test($test_data) {
        $column = $test_data['user_owned_settings']['column'];
        $value = segment($test_data['user_owned_settings']['segmentNum']);
        $target_table = $test_data['table_name'];
        $record = $this->model->get_one_where($column, $value, $target_table);

        if ($record === false) {
            $this->api_manager_error(401, 'Invalid token.');
        } else {

            $trongate_user_id = $test_data['trongate_user_id'] ?? 0;
            $target_trongate_user_id = $record->trongate_user_id ?? 0;
            settype($trongate_user_id, 'int');
            settype($target_trongate_user_id, 'int');

            if ($trongate_user_id === $target_trongate_user_id && ($trongate_user_id > 0)) {
                return; //match for user owned segment
            } else {
                $this->api_manager_error(401, 'Invalid token.');
            }
        }
    }


    /**
     * Function: fetch_query_params_from_url()
     * Description: Fetches query parameters from the current URL and extracts them into an array.
     *              Parses the query string from the current URL,
     *              splits it into individual query parameter bits,
     *              extracts each query parameter and its value, and stores them in an array.
     * Parameters:
     *    - None
     * Returns:
     *    - array: An array containing the extracted query parameters.
     */
    private function fetch_query_params_from_url() {
        $query_str = parse_url(urldecode(current_url()), PHP_URL_QUERY);
        settype($query_str, 'string');
        $query_params = [];
        $query_str_bits = explode('&', $query_str);
        $operators = $this->operators;

        foreach ($query_str_bits as $query_str_bit) {
            $row_data = $this->extract_query_param($query_str_bit, $operators);
            if ($row_data !== false) {
                $query_params[] = $row_data;
            }
        }

        return $query_params;
    }


    /**
     * Function: fetch_query_params_from_post()
     * Description: Fetches query parameters from the POST request body and extracts them into an array.
     *              Retrieves the posted parameters from the request body,
     *              decodes the JSON formatted parameters,
     *              iterates through each parameter, extracts its key, operator, and value,
     *              and stores them in an array.
     * Parameters:
     *    - None
     * Returns:
     *    - array: An array containing the extracted query parameters.
     */
    private function fetch_query_params_from_post() {
        $query_params = [];

        //get posted params
        $post = file_get_contents('php://input');

        if ($post === '') {
            return $query_params;
        }

        $posted_args = json_decode($post, true);
        $operators = $this->operators;

        if (!isset($posted_args)) {
            $this->api_manager_error(400, 'Invalid JSON!');
        }

        foreach ($posted_args as $key => $value) {
            $key = str_replace('>=', ' >=', $key);
            $key = str_replace('<=', ' <=', $key);
            $key = str_replace('!', ' !', $key);
            $key = str_replace('<', ' <', $key);
            $key = str_replace('>', ' >', $key);

            $unwanted_chars = array('or ', 'OR ', '!', 'and ', 'AND ', '!=', '>', '<', '>=', '<=');
            $key_string = $key;
            foreach ($unwanted_chars as $char) {
                $key_string = str_replace($char, '', $key_string);
            }

            $row_data['key'] = trim($key_string);

            //figure out what the operator is
            $key_bits = explode(' ', trim($key));
            if (count($key_bits) === 1) {
                $row_data['operator'] = '=';
            } else {
                $row_data['operator'] = '=';
                $key = str_replace('!', '!=', $key);
                $operators_keys = array_keys($operators);

                foreach ($operators_keys as $needle) {
                    if (strpos($key, $needle) !== false) {
                        $row_data['operator'] = $needle;
                        break;
                    }
                }
            }

            if (strlen($key) > 3) {
                $first_three = substr($key, 0, 3);
                if (strtoupper($first_three === 'OR ')) {
                    $row_data['key'] = 'OR ' . $row_data['key'];
                }
            }

            $row_data['value'] = (gettype($value) === 'string') ? trim($value) : $value;
            $query_params[] = $row_data;
        }

        return $query_params;
    }

    /**
     * Function: extract_query_param()
     * Description: Extracts a query parameter from a query string bit based on provided operators.
     *              Iterates through each operator in the list of provided operators,
     *              splits the query string bit into key and value using the current operator,
     *              and returns an array containing the extracted key, operator, and value.
     * Parameters:
     *    - $query_str_bit (string): The query string bit to extract the parameter from.
     *    - $operators (array): An array containing the list of possible operators.
     * Returns:
     *    - mixed: An array containing the extracted key, operator, and value if found, otherwise false.
     */
    private function extract_query_param($query_str_bit, $operators) {
        //build a bit list of all possible operators
        foreach ($operators as $operator_plain => $operator_encoded) {
            $relevant_operator = $operator_encoded;
            $str_bits = explode($operator_encoded, $query_str_bit);

            if (count($str_bits) !== 2) {
                $str_bits = explode($operator_plain, $query_str_bit);
                $relevant_operator = $operator_plain;
            }

            if (count($str_bits) === 2) {
                $row_data['key'] = $str_bits[0];
                $row_data['operator'] = $relevant_operator;
                $row_data['value'] = $str_bits[1];
                return $row_data;
            }
        }
        return false;
    }


    /**
     * Function: reduce_query_params()
     * Description: Reduces query parameters to simple key/value pairs.
     *              Iterates through each query parameter,
     *              extracts its key, operator, and value,
     *              constructs a key based on the key and operator,
     *              and stores the key/value pair in an array.
     * Parameters:
     *    - $query_params (array): An array containing the query parameters to be reduced.
     * Returns:
     *    - array: An array containing the reduced key/value pairs of query parameters.
     */
    function reduce_query_params($query_params) {
        //reduce query params to simple key/value pairs
        $params = [];
        foreach ($query_params as $query_param) {
            $param_key = $query_param['key'] ?? '';
            $param_operator = $query_param['operator'] ?? '';
            $param_value = $query_param['value'] ?? '';

            if ($param_operator === '=') {
                $key = $param_key;
            } else {
                $param_operator = str_replace('!=', '!', $param_operator);
                $param_operator = str_replace('%21=', '!', $param_operator);
                $param_operator = str_replace('%3C', '<', $param_operator);
                $param_operator = str_replace('%3E', '<', $param_operator);
                $key = $param_key . ' ' . $param_operator;
            }

            $params[$key] = $param_value;
        }
        return $params;
    }


    /**
     * Function: api_manager_error()
     * Description: Handles API manager errors by setting the HTTP response code,
     *              and optionally echoing the error message in development environment.
     * Parameters:
     *    - $response_status_code (int): The HTTP response status code to be set.
     *    - $error_msg (string): The error message to be displayed or processed.
     * Returns: void
     */
    private function api_manager_error($response_status_code, $error_msg) {
        http_response_code($response_status_code);
        if (strtolower(ENV) === 'dev') {
            echo $error_msg;
        }
        die();
    }

    /**
     * Function: get_standard_endpoints()
     * Description: Retrieves an array of standard endpoints used in the API manager.
     *              Each endpoint contains information such as request name, request type,
     *              restful identifier, and URL segments.
     * Returns:
     *    - array: An array containing standard endpoint configurations.
     */
    private function get_standard_endpoints() {
        $standard_endpoints = [
            [
                'request_name' => 'Get',
                'request_type' => 'GET',
                'restful_identifier' => '',
                'url_segments' => 'api/get/[table]'
            ],
            [
                'request_name' => 'Search',
                'request_type' => 'POST',
                'restful_identifier' => 'search',
                'url_segments' => 'api/search/[table]'
            ],
            [
                'request_name' => 'Find One',
                'request_type' => 'GET',
                'restful_identifier' => '{id}',
                'url_segments' => 'api/get/[table]/{id}'
            ],
            [
                'request_name' => 'Exists',
                'request_type' => 'GET',
                'restful_identifier' => '{id}/exists',
                'url_segments' => 'api/exists/[table]/{id}'
            ],
            [
                'request_name' => 'Count',
                'request_type' => 'GET',
                'restful_identifier' => 'count',
                'url_segments' => 'api/count/[table]'
            ],
            [
                'request_name' => 'Count By Post',
                'request_type' => 'POST',
                'restful_identifier' => 'count',
                'url_segments' => 'api/count/[table]'
            ],
            [
                'request_name' => 'Create',
                'request_type' => 'POST',
                'restful_identifier' => '',
                'url_segments' => 'api/create/[table]'
            ],
            [
                'request_name' => 'Insert Batch',
                'request_type' => 'POST',
                'restful_identifier' => 'insert',
                'url_segments' => 'api/batch/[table]'
            ],
            [
                'request_name' => 'Update',
                'request_type' => 'PUT',
                'restful_identifier' => '{id}',
                'url_segments' => 'api/update/[table]/{id}'
            ],
            [
                'request_name' => 'Destroy',
                'request_type' => 'DELETE',
                'restful_identifier' => '',
                'url_segments' => 'api/destroy/[table]'
            ],
            [
                'request_name' => 'Delete One',
                'request_type' => 'DELETE',
                'restful_identifier' => '{id}',
                'url_segments' => 'api/delete/[table]/{id}'
            ],
        ];
        return $standard_endpoints;
    }
}
