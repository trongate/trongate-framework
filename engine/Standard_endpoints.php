<?php
class Standard_endpoints extends Trongate {

    private $operators = [
        "!=" => "%21=",
        "<=" => "%3C=",
        ">=" => "%3E=",
        "=" => "=",
        "<" => "%3C",
        ">" => "%3E"
    ];

    function __construct() {
        parent::__construct();
    }

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

    public function get($return_row_count=false) { //GET
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

        $standard_query = 'SELECT * FROM '.$table_name;
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

    public function search($return_row_count=false) { //POST
        $request_type = $this->get_request_type();
        if($request_type !== 'POST') {
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

        $standard_query = 'SELECT * FROM '.$table_name;
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

    public function count() { //GET or POST
        $request_type = $this->get_request_type();
        if ($request_type !== 'GET') {
            $this->search(true);
        } else {
            $this->get(true);
        }
    }

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
        if(count($input['params']) == 0) {
            $this->api_manager_error(400, 'No posted data!');
        }

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $columns_exist_result = $this->make_sure_columns_exist($table_name, $input['params']);

        if($columns_exist_result !== true) {
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

    function make_sure_columns_exist($table_name, $params, $valid_columns=null) {

        if(!isset($valid_columns)) {
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

    private function get_all_columns($table) {
        $columns = [];
        $sql = 'describe '.$table;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $columns[] = $row['Field'];
        }

        return $columns;   
    }

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

        if($post === '') {
            $this->api_manager_error(400, 'No posted data!');
        }

        $posted_items = json_decode($post, true);
        if (!is_array($posted_items) || substr($post, 0, 1) !== '[' || substr($post, -1) !== ']') {
            $this->api_manager_error(400, 'Invalid format: Not an array of objects!');
        }

        if(count($posted_items) === 0) {
            $this->api_manager_error('400', 'No posted data!');
        }

        $input['params'] = $posted_items;

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        //loop through the posted data and make sure all of the columns exist on the table
        $valid_columns = $this->get_all_columns($table_name);
        foreach($input['params'] as $posted_item) {
            $columns_exist_result = $this->make_sure_columns_exist($table_name, $posted_item);

            if($columns_exist_result !== true) {
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
        if(count($input['params']) == 0) {
            $this->api_manager_error(400, 'No posted data!');
        }

        if ($before_hook !== '') {
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $columns_exist_result = $this->make_sure_columns_exist($table_name, $input['params']);

        if($columns_exist_result !== true) {
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

        $standard_query = 'SELECT * FROM '.$table_name;
        $rows = $this->fetch_rows($standard_query, $input['params']);

        // Build an array of items to go, based on 'id'
        $update_ids = [];
        foreach($rows as $row) {
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

            if(count($update_ids)>0) {
                // Build the SQL query to delete rows from the 'tasks' table where 'id' values match those in the array
                $sql = "DELETE FROM tasks WHERE id IN ({$update_ids_str})";
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

    public function delete_one() { //DELETE
        $allowed_request_types = (segment(1) === 'api') ? array('POST', 'DELETE') : array('DELETE');
        $target_segment = (segment(1) === 'api') ? 4 : 2;
        $update_id = intval(segment($target_segment));

        if($update_id <= 0) {
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
            $input = $this->attempt_invoke_before_hook($table_name, $before_hook, $input);
        }

        $record_obj = $this->model->get_where($update_id, $table_name);

        if($record_obj === false) {
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

    private function attempt_invoke_before_hook($table_name, $before_hook, $input) {
        $input = Modules::run($table_name.'/'.$before_hook, $input);
        return $input;
    }

    private function attempt_invoke_after_hook($table_name, $after_hook, $output) {
        $output = Modules::run($table_name.'/'.$after_hook, $output);
        return $output;
    }

    private function fetch_rows($standard_query, $submitted_params) {
        $where_data = $this->extract_where_data($submitted_params);
        $order_by_clause = $this->extract_order_by_clause($submitted_params);
        $limit_offset_clause = $this->extract_limit_offset_clause($submitted_params);

        $sql = $standard_query;
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];

        if($where_clause !== '') {
            $sql.= ' '.$where_clause;
        }

        if($order_by_clause !== '') {
            $sql.= ' '.$order_by_clause;
        }

        if($limit_offset_clause !== '') {
            $sql.= ' '.$limit_offset_clause;
        }

        try {
            $rows = $this->model->query_bind($sql, $params, 'object');
            return $rows;
        } catch (Exception $e) {
            $this->api_manager_error(400, $e);
        }
    }

    private function extract_where_data($submitted_params) {
        //returns a WHERE clause and an array of params
        $params = [];
        $counter = 0;
        $ignore_keys = array('orderBy', 'order_by', 'limit', 'offset', 'ixd');
        $where_clause = '';
        foreach($submitted_params as $key => $value) {
            $key_bits = explode(' ', trim($key));
            //ignore limit, offset etc...
            if(in_array($key_bits[0], $ignore_keys)) {
                continue;
            }

            $conjunction = ($where_clause === '') ? 'WHERE' : (strtoupper($key_bits[0]) === 'OR' ? 'OR' : 'AND');
            $first_three = substr($key, 0, 3);
            if (strtoupper($first_three) === 'OR ') {
                $key = substr($key, 3);
                $key_bits = explode(' ', trim($key)); //must be re-established, having dealt with 'OR' scenario
            }

            $column = $key_bits[0];

            if(count($key_bits)>1) {
                $last_bit = $key_bits[count($key_bits)-1];
                $operator = ($last_bit === '!') ? '!=' : $operator = $last_bit;
            } else {
                $operator = '=';
            }

            $counter++;
            $property_name = 'arg'.$counter;
            $where_clause.= $conjunction.' '.$column.$operator.'? ';
            $params[] = $value;
        }

        $where_data['where_clause'] = trim($where_clause);
        $where_data['params'] = $params;
        return $where_data;
    }

    private function extract_order_by_clause($submitted_params) {
        foreach($submitted_params as $key => $value) {
            if($key === 'orderBy' OR $key === 'order_by') {
                $order_by_clause = 'ORDER BY '.$value;
                return $order_by_clause;
            }
        }

        $order_by_clause = '';
        return $order_by_clause;
    }

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

    private function clean_output($output) {
        $allowed_keys = ['body', 'code', 'token'];
        return array_intersect_key($output, array_flip($allowed_keys));
    }

    public function attempt_serve_standard_endpoint($endpoint_index) {
        $standard_endpoints = $this->get_standard_endpoints();
        $target_endpoint = $standard_endpoints[$endpoint_index];
        $request_name = $target_endpoint['request_name'];
        $target_method = strtolower(url_title($request_name));
        $target_method = str_replace('-', '_', $target_method);
        $this->$target_method();
    }

    public function attempt_find_endpoint_index() {

        $request_type = $this->get_request_type();
        $current_url = remove_query_string(current_url());

        $first_segment = remove_query_string(segment(1));
        $second_segment = remove_query_string(segment(2));
        $ditch = $second_segment === '' ? BASE_URL.$first_segment : BASE_URL.$first_segment.'/';
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
           
            if(($resource_path === $restful_identifier) && ($request_type === $target_request_type)) {
                $target_endpoint_index = $i;
                break;
            }
        }

        if(!isset($target_endpoint_index)) {
            $target_endpoint_index = '';
        }

        return $target_endpoint_index;
    }

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

            if($request_type === 'GET') {
                $query_params = $this->fetch_query_params_from_url();
                $input['params']= $this->reduce_query_params($query_params);
            } else {
                //attemp get params from post
                $query_params = $this->fetch_query_params_from_post();
                $input['params']= $this->reduce_query_params($query_params);
            }

        }

        if(!isset($input['params'])) {
            $input['params'] = [];
        }

        return $input;
    }

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

        if($trongate_user === false) {
            // Test for aaa token
            $allowed = $this->test_for_aaa_token($token);
            if($allowed === true) {
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

        if(segment(1) === 'api') {

            //user id segment authorization (only for Trongate API paths!)
            if (isset($endpoint_auth_rules['userIdSegment'])) {
                $target_value = segment($endpoint_auth_rules['userIdSegment']);
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

    private function test_for_aaa_token($token) {
        $token_obj = $this->model->get_one_where('token', $token, 'trongate_tokens');
        $code = $token_obj->code ?? '';
        $allowed = $code === 'aaa' ? true : false;
        return $allowed;
    }

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

            if ($trongate_user_id === $target_trongate_user_id && ($trongate_user_id>0)) {
                return; //match for user owned segment
            } else {
                $this->api_manager_error(401, 'Invalid token.');
            }

        }
    }

    private function fetch_query_params_from_url() {
        $query_str = parse_url(urldecode(current_url()), PHP_URL_QUERY);
        settype($query_str, 'string');
        $query_params = [];
        $query_str_bits = explode('&', $query_str);
        $operators = $this->operators;

        foreach($query_str_bits as $query_str_bit) {
            $row_data = $this->extract_query_param($query_str_bit, $operators);
            if($row_data !== false) {
                $query_params[] = $row_data;
            }
        }

        return $query_params;
    }

    private function fetch_query_params_from_post() {
        $query_params = [];

        //get posted params
        $post = file_get_contents('php://input');

        if($post === '') {
            return $query_params;
        }

        $posted_args = json_decode($post, true);
        $operators = $this->operators;

        if(!isset($posted_args)) {
            $this->api_manager_error(400, 'Invalid JSON!');
        }

        foreach($posted_args as $key => $value) {
            $key = str_replace('>=', ' >=', $key);
            $key = str_replace('<=', ' <=', $key);
            $key = str_replace('!', ' !', $key);
            $key = str_replace('<', ' <', $key);
            $key = str_replace('>', ' >', $key);

            $unwanted_chars = array('or ', 'OR ' , '!', 'and ', 'AND ', '!=', '>', '<', '>=', '<=');
            $key_string = $key;
            foreach ($unwanted_chars as $char) {
                $key_string = str_replace($char, '', $key_string);
            }

            $row_data['key'] = trim($key_string);
    
            //figure out what the operator is
            $key_bits = explode(' ', trim($key));
            if(count($key_bits) === 1) {
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

            if(strlen($key)>3) {
                $first_three = substr($key, 0, 3);
                if(strtoupper($first_three === 'OR ')) {
                    $row_data['key'] = 'OR '.$row_data['key'];
                }
            }

            $row_data['value'] = trim($value);
            $query_params[] = $row_data;
        }

        return $query_params;        
    }

    private function extract_query_param($query_str_bit, $operators) {
        //build a bit list of all possible operators
        foreach($operators as $operator_plain => $operator_encoded) {
            $relevant_operator = $operator_encoded;
            $str_bits = explode($operator_encoded, $query_str_bit);

            if(count($str_bits) !== 2) {
                $str_bits = explode($operator_plain, $query_str_bit);
                $relevant_operator = $operator_plain;
            }

            if(count($str_bits) === 2) {
                $row_data['key'] = $str_bits[0];
                $row_data['operator'] = $relevant_operator;
                $row_data['value'] = $str_bits[1];
                return $row_data;
            }
        }
        return false;
    }

    function reduce_query_params($query_params) {
        //reduce query params to simple key/value pairs
        $params = [];
        foreach($query_params as $query_param) {
            $param_key = $query_param['key'] ?? '';
            $param_operator = $query_param['operator'] ?? '';
            $param_value = $query_param['value'] ?? '';

            if($param_operator === '=') {
                $key = $param_key;
            } else {
                $param_operator = str_replace('!=', '!', $param_operator);
                $param_operator = str_replace('%21=', '!', $param_operator);
                $param_operator = str_replace('%3C', '<', $param_operator);
                $param_operator = str_replace('%3E', '<', $param_operator);
                $key = $param_key.' '.$param_operator;
            }

            $params[$key] = $param_value;

        }
        return $params;
    }

    private function api_manager_error($response_status_code, $error_msg) {
        http_response_code($response_status_code);
        if (strtolower(ENV) === 'dev') {
            echo $error_msg;
        }
        die();
    }

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