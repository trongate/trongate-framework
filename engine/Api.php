<?php
class Api extends Trongate {

    function __construct() {
        parent::__construct();
    }

    function get() {
        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->_process_get_with_get($module_name, $module_endpoints);
        } else {
            $this->_process_get_with_post($module_name, $module_endpoints);
        }

    }

    function _process_get_with_get($module_name, $module_endpoints) {

        $update_id = segment(4);
        if (is_numeric($update_id)) {
            $this->_find_one($module_name, $module_endpoints, $update_id);
            return;
        }

        $endpoint_name = 'Get';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $params = $this->_get_params_from_url(4);
        $sql = 'select * from '.$module_name;

        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $target_endpoint = $module_endpoints[$input['endpoint']];

        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $num_params = count($params);

        if ($num_params < 1) { 
            $rows = $this->model->get('id', $module_name);
            $output['body'] = json_encode($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];

        } else {
            //params were submitted via URL
            $sql = 'select * from '.$module_name;
            $params = json_encode($params);
            $params = ltrim($params);
            $params = json_decode($params);
            $params = get_object_vars($params);
            $query_info = $this->_add_params_to_query($module_name, $sql, $params);

            $sql = $query_info['sql'];
            $data = $query_info['data'];

            if (count($data)<1) {
                $rows = $this->model->query($sql, 'object');
            } else {
                $rows = $this->model->query_bind($sql, $data, 'object');
            }

            $output['body'] = json_encode($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];
        }

    }

    function _process_get_with_post($module_name, $module_endpoints) {

        $endpoint_name = 'Get By Post';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];
        

        //get posted params
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);

        if (!isset($decoded)) {
            $decoded = [];
        }

        if (count($decoded)>0) {
            $params = $this->_get_params_from_post($decoded);
        } else {
            $params = [];
        }

        $sql = 'select * from '.$module_name;

        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $num_params = count($params);

        if ($num_params < 1) {

            $rows = $this->model->get('id', $module_name);
            $output['body'] = json_encode($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];

        } else {
            //params were posted
            $sql = 'select * from '.$module_name;
            $params = json_encode($params);
            $params = ltrim($params);
            $params = json_decode($params);
            $params = get_object_vars($params);
            $query_info = $this->_add_params_to_query($module_name, $sql, $params);

            $sql = $query_info['sql'];
            $data = $query_info['data'];

            if (count($data)<1) {
                $rows = $this->model->query($sql, 'object');
            } else {
                $rows = $this->model->query_bind($sql, $data, 'object');
            }

            $output['body'] = json_encode($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
        
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];
        }
    }

    function _validate_token($token_validation_data) {
        //make sure the user is allowed to be here, if YES -> return token or true
        //if NO -> display not allowed message

        $this->module('trongate_tokens');

        //attempt to fetch user token from header
        $token = (isset($_SERVER['HTTP_TRONGATETOKEN']) ? $_SERVER['HTTP_TRONGATETOKEN'] : false);

        if ((($token == '') || ($token == false)) && (segment(1) !== 'api')) {
            //attempt to fetch a token from cookie or session data
            $token = $this->trongate_tokens->_attempt_get_valid_token();
        }

        //get an array of ALL the rules for this endpoint
        $module_endpoints = $token_validation_data['module_endpoints'];
        $endpoint = $token_validation_data['endpoint'];
        $target_endpoint = $module_endpoints[$endpoint];
        $endpoint_auth_rules = $this->_fetch_endpoint_auth_rules($target_endpoint);

        if ($endpoint_auth_rules == false) {
            $error_msg = 'Endpoint not activated since no authorization rules have been declared.';
            $this->_api_manager_error(412, $error_msg);            
        } 

        if ($endpoint_auth_rules == '*') {
            //wide open endpoint - end of the road
            if (gettype($token) == 'boolean') {
                return true;
            } else {
                return $token;
            }
        }

        //attempt fetch user object from the token
        $trongate_user = $this->trongate_tokens->_get_user_obj($token);

        if ($trongate_user == false) {
            //could not find a user - but do we have a aaa token?
            if ($token == false) {
                $this->_not_allowed_msg();
            } else {
                $result = $this->_test_for_aaa_token($token);
                
                if ($result == true) {
                    return $token;
                } else {
                    $this->_not_allowed_msg();
                }
            }
        }

        //let's go through all of the authoriation rules and attempt to pass ONE of them 

        //role based authorization
        if (isset($endpoint_auth_rules['roles'])) {
            if (in_array($trongate_user->user_level, $endpoint_auth_rules['roles'])) {
                return $token; //success!
            }
        }

        //id based authorization
        if (isset($endpoint_auth_rules['userIds'])) {
            if (in_array($trongate_user->trongate_user_id, $endpoint_auth_rules['userIds'])) {
                return $token; //success!
            }
        }

        //user id segment authorization
        if (isset($endpoint_auth_rules['userIdSegment'])) {
            $target_value = segment($endpoint_auth_rules['userIdSegment']);
            if ($trongate_user->trongate_user_id == $target_value) {
                return $token; //success!
            }
        }

        //user code segment authorization
        if (isset($endpoint_auth_rules['userCodeSegment'])) {
            $target_value = segment($endpoint_auth_rules['userCodeSegment']);
            if ($trongate_user->trongate_user_code == $target_value) {
                return $token; //success!
            }
        }

        //user owned segment authorization
        if (isset($endpoint_auth_rules['userOwnedSegment'])) {
            $test_data['module_name'] = $token_validation_data['module_name'];
            $test_data['trongate_user_id'] = $trongate_user->trongate_user_id;
            $test_data['user_owned_settings'] = $endpoint_auth_rules['userOwnedSegment'];
            $result = $this->_run_user_owned_test($test_data); //true or false

            if ($result == true) {
                return $token; //success!
            } else {
                $this->_not_allowed_msg();
            }
        }

        //safety net
        $this->_not_allowed_msg();
    }

    function _test_for_aaa_token($token) {
        $token_obj = $this->model->get_one_where('token', $token, 'trongate_tokens');
        if ($token_obj == false) {
            return false;
        } else {

            if ($token_obj->code == 'aaa') {
                return true;
            } else {
                return false;
            }

        }
    }

    function _run_user_owned_test($test_data) {
        $column = $test_data['user_owned_settings']['column'];
        $value = segment($test_data['user_owned_settings']['segmentNum']);
        $target_table = $test_data['module_name'];

        $record = $this->model->get_one_where($column, $value, $target_table);

        if ($record == false) {
            return true; //might still be a legit query from a logged in user
        } else {

            $result = false; //assume failure

            if (isset($record->trongate_user_id)) {

                $trongate_user_id = $test_data['trongate_user_id'];

                if ($trongate_user_id == $record->trongate_user_id) {
                    $result = true;
                }

            }

            return $result;

        }

    }

    function _which_segment($url_segments, $identifier_str) {
        $target_segment = false;
        $segments = explode('/', $url_segments);
        foreach($segments as $key => $segment) {
            if ($segment == $identifier_str) {
                $target_segment = $key+1;
            }
        }

        return $target_segment;
    }

    function _find_one($module_name, $module_endpoints, $update_id) {
        $endpoint_name = 'Find One';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        //attempt invoke 'before' hook
        $input['params'] = [];
        $input['module_name'] = $module_name;   
        $input['endpoint'] = 'Find One'; 

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $result = $this->model->get_where($update_id, $module_name);

        $output['body'] = json_encode($result);
        $output['code'] = 200;
        $output['module_name'] = $module_name;

        $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
        
        $code = $output['code'];
        http_response_code($code);
        echo $output['body'];
        die();
    }

    function exists() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Exists';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $update_id = segment(4);

        if (!is_numeric($update_id)) {
            http_response_code(422);
            echo "Non numeric ID"; die();
        } else {

            //attempt invoke 'before' hook
            $input['token'] = $input['token'];
            $input['params'] = [];
            $input['module_name'] = $module_name;   
            $input['endpoint'] = $endpoint_name;

            $module_endpoints = $this->_fetch_endpoints($input['module_name']);
            $target_endpoint = $module_endpoints[$input['endpoint']];
            $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
            extract($input);

            $result = $this->model->get_where($update_id, $module_name);

            if ($result == false) {
                $result = 'false';
            } else {
                $result = 'true';
            }

            $output['body'] = $result;
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];            

        }

    }

    function count() {
        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->_process_count_with_get($module_name, $module_endpoints);
        } else {
            $this->_process_count_with_post($module_name, $module_endpoints);
        }

    }

    function _process_count_with_get($module_name, $module_endpoints) {

        $update_id = segment(4);
        if (is_numeric($update_id)) {
            $this->_find_one($module_name, $module_endpoints, $update_id);
            return;
        }

        $endpoint_name = 'Count';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];
        

        $params = $this->_get_params_from_url(4);

        $sql = 'select * from '.$module_name;

        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $target_endpoint = $module_endpoints[$input['endpoint']];

        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $num_params = count($params);

        if ($num_params < 1) { 
            $rows = $this->model->get('id', $module_name);
            $output['body'] = count($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];

        } else {
            //params were submitted via URL
            $sql = 'select * from '.$module_name;
            $params = json_encode($params);
            $params = ltrim($params);
            $params = json_decode($params);
            $params = get_object_vars($params);
            $query_info = $this->_add_params_to_query($module_name, $sql, $params);

            $sql = $query_info['sql'];
            $data = $query_info['data'];

            if (count($data)<1) {
                $rows = $this->model->query($sql, 'object');
            } else {
                $rows = $this->model->query_bind($sql, $data, 'object');
            }

            $output['body'] = count($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];
        }

    }

    function _process_count_with_post($module_name, $module_endpoints) {

        $endpoint_name = 'Count By Post';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);
        $output['token'] = $input['token'];
        
        //get posted params
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);

        if ($decoded == NULL) {
            $this->_api_manager_error(400, 'No posted variables!');
        }

        if (count($decoded)>0) {
            $params = $this->_get_params_from_post($decoded);
        } else {
            $params = [];
        }

        $sql = 'select * from '.$module_name;

        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $num_params = count($params);

        if ($num_params < 1) {

            $rows = $this->model->get('id', $module_name);
            $output['body'] = count($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
            
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];

        } else {
            //params were posted
            $sql = 'select * from '.$module_name;
            $params = json_encode($params);
            $params = ltrim($params);
            $params = json_decode($params);
            $params = get_object_vars($params);
            $query_info = $this->_add_params_to_query($module_name, $sql, $params);

            $sql = $query_info['sql'];
            $data = $query_info['data'];

            if (count($data)<1) {
                $rows = $this->model->query($sql, 'object');
            } else {
                $rows = $this->model->query_bind($sql, $data, 'object');
            }

            $output['body'] = count($rows);
            $output['code'] = 200;
            $output['module_name'] = $module_name;

            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
        
            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];
        }
    }

    function create() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Create';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);

        if (gettype($decoded) !== 'array') {
            http_response_code(400);
            echo 'No posted values have been received!';
            die();
        }

        if (count($decoded)>0) {
            $params = $this->_get_params_from_post($decoded);
        } else {
            $params = [];
        }
        
        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = 'Create';

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        if (count($params)>0) {
            //make sure params are valid
            $this->_make_sure_columns_exist($module_name, $params);
            $new_id = $this->model->insert($params, $module_name);
            $result = $this->model->get_where($new_id, $module_name);
            $output['code'] = 200;
            $output['body'] = json_encode($result);
        } else {
            $output['code'] = 422;
            $output['body'] = '';
        }

        $output['module_name'] = $module_name;
        $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);

        $code = $output['code'];
        http_response_code($code);
        echo $output['body'];

    }

    function batch() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Insert Batch';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);
        $data = [];

        if (count($decoded)>0) {

            foreach ($decoded as $key => $value) {
                $row_data = $this->_get_params_from_post($value);
                $data[] = $row_data;
            }

        }
        
        $input['params'] = $data;
        $input['module_name'] = $module_name;
        $input['endpoint'] = 'Insert Batch';

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        if (count($data)>0) {
            //execute batch insert and return num rows inserted
            $row_count = $this->model->insert_batch($module_name, $data);
            $output['body'] = $row_count;
            $output['code'] = 200;
        } else {
            $output['code'] = 422;
            $output['body'] = 'No rows were inserted.';
        }

        //attempt invoke 'after' hook
        $output['module_name'] = $module_name;
        $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);

        $code = $output['code'];
        http_response_code($code);
        echo $output['body'];
    }

    function update() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Update';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $update_id = segment(4);

        if (!is_numeric($update_id)) {
            http_response_code(400);
            echo 'Non numeric update id.';
            die();
        } else {
            $post = file_get_contents('php://input');
            $decoded = json_decode($post, true);
        }

        if (count($decoded)>0) {
            $params = $this->_get_params_from_post($decoded);
        } else {
            $params = [];
        }
        
        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        if (isset($params['id'])) {
            unset($params['id']); //id cannot be changed
        }

        if (count($params)>0) {
            //make sure params are valid
            $this->_make_sure_columns_exist($module_name, $params);
            $this->model->update($update_id, $params, $module_name);
            $result = $this->model->get_where($update_id, $module_name);

            if ($result == false) {
                $output['code'] = 422;
            }  else {
                $output['code'] = 200;
            }

            $output['body'] = json_encode($result);
        } else {
            $output['code'] = 422;
            $output['body'] = '';
        }

        $output['module_name'] = $module_name;
        $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);

        $code = $output['code'];
        http_response_code($code);
        echo $output['body'];
    }

    function delete() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Delete One';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);

        $output['token'] = $input['token'];

        $id = segment(4);

        //attempt invoke 'before' hook
        $data['id'] = $id;
        $input['params'] = $data;
        $input['module_name'] = $module_name;
        $input['endpoint'] = $endpoint_name;

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);
        $update_id = $input['params']['id'];

        if (!is_numeric($update_id)) {
            http_response_code(400);
            echo 'Non numeric id.';
            die();
        }

        $result = $this->model->get_where($update_id, $module_name);

        if ($result == false) {
            http_response_code(422);
            echo 'false';
            die();
        } else {

            $this->model->delete($update_id, $module_name);
            $output['body'] = 'true';
            $output['code'] = 200;
            $output['module_name'] = $module_name;
            $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);

            $code = $output['code'];
            http_response_code($code);
            echo $output['body'];

        }

    }

    function destroy() {

        $module_name = segment(3);
        $this->_make_sure_table_exists($module_name);
        $module_endpoints = $this->_fetch_endpoints($module_name);

        $endpoint_name = 'Destroy';
        $token_validation_data['endpoint'] = $endpoint_name;
        $token_validation_data['module_name'] = $module_name;
        $token_validation_data['module_endpoints'] = $module_endpoints;
        $input['token'] = $this->_validate_token($token_validation_data);
        $output['token'] = $input['token'];
        
        //get posted params (PHP doesn't differentiate btn GET and DELETE)
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);

        if (count($decoded)>0) {
            $params = $this->_get_params_from_post($decoded);
        } else {
            $params = [];
        }

        $sql = 'select * from '.$module_name;

        //attempt invoke 'before' hook
        $input['params'] = $params;
        $input['module_name'] = $module_name;
        $input['endpoint'] = 'Destroy';

        $module_endpoints = $this->_fetch_endpoints($input['module_name']);
        $target_endpoint = $module_endpoints[$input['endpoint']];
        $input = $this->_attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint);
        extract($input);

        $num_params = count($params);

        if ($num_params < 1) { 
            $rows = $this->model->get('id', $module_name);
            $num_rows_affected = count($rows);

        } else {
            //params were posted
            $sql = 'select * from '.$module_name;
            $params = json_encode($params);
            $params = ltrim($params);
            $params = json_decode($params);
            $params = get_object_vars($params);
            $query_info = $this->_add_params_to_query($module_name, $sql, $params);

            $sql = $query_info['sql'];
            $data = $query_info['data'];

            if (count($data)<1) {
                $rows = $this->model->query($sql, 'object');
            } else {
                $rows = $this->model->query_bind($sql, $data, 'object');
            }

            $num_rows_affected = count($rows);

        }   

        $msg = $num_rows_affected;

        if ($num_rows_affected>0) {

            $sql = substr($sql, 13, strlen($sql));
            $sql = 'delete from'.$sql;

            if (!isset($data)) {
                $this->model->query($sql);
            } else {
                $this->model->query_bind($sql, $data);
            }            
        }

        $output['body'] = $msg;
        $output['code'] = 200;
        $output['module_name'] = $module_name;

        $output = $this->_attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint);
        
        $code = $output['code'];
        http_response_code($code);
        echo $output['body'];
    }

    function _api_manager_error($response_status_code, $error_msg) {
        http_response_code($response_status_code);
        echo $error_msg;
        die();
    }

    function _not_allowed_msg() {
        http_response_code(401);
        echo "Invalid token."; die();
    }

    function _fetch_endpoint_auth_rules($endpoint) {
 
        if (!isset($endpoint['authorization'])) {
            $endpoint['authorization'] = false;
        }

        return $endpoint['authorization'];
    }

    function _make_sure_table_exists($table) {
        $all_tables = $this->_get_all_tables();
        if(!in_array($table, $all_tables)) {
            http_response_code(422);
            echo 'invalid table name'; die();
        }
    }

    function _make_sure_columns_exist($module_name, $params) {
        //make sure columns exists on the table
        $invalid_columns = [];
        $columns = $this->_get_all_columns($module_name);

        foreach ($params as $key => $value) {
            if (!in_array($key,$columns)) {
                $invalid_columns[] = $key;
            }
        }

        if (count($invalid_columns)) {
            $error_count = 0;
            http_response_code(422);

            $msg = "The following fields are not valid; ";

            if (count($invalid_columns)==1) {
                $msg = str_replace('fields are', 'field is', $msg);
            }

            echo $msg;

            foreach ($invalid_columns as $invalid_field) {
                $error_count++;
                echo $invalid_field;

                if ($error_count<count($invalid_columns)) {
                    echo ", ";
                }

                echo '.';
            }
            die();
        }

    }

    function explorer() {

        if (ENV !== 'dev') {
            http_response_code(403);
            echo "API Explorer disabled since not in 'dev' mode.";
            die();
        }

        $this->module('trongate_tokens');
        $target_module = segment(3);
        $this->_make_sure_table_exists($target_module);
        $this->module('trongate_tokens');

        $token_data['user_id'] = $this->trongate_tokens->_get_user_id();
        $token_data['code'] = 'aaa';
        
        $sql = 'delete from trongate_tokens where user_id = :user_id and code = :code';
        $this->model->query_bind($sql, $token_data);

        $token_data['expiry_date'] = time() + 7200; //two hours
        $data['golden_token'] = $this->trongate_tokens->_generate_token($token_data);
        $data['endpoints'] = $this->_fetch_endpoints($target_module);
        $data['endpoint_settings_location'] = '/modules/'.$target_module.'/assets/api.json';
        $columns = $this->_get_all_columns($target_module);

        //build columns as json_str
        $json_starter_str = '{';
        $count = 1;
        foreach ($columns as $column) {
            $count++;
            if ($column !== 'id') {

                if ($count == 3) {
                    $column_length = strlen($column);
                    $cursor_reset_position = $column_length + 10;
                }

                $json_starter_str.= '"'.$column.'":""';

                if ($count <= count($columns)) {
                    $json_starter_str.= ',';
                } else {
                    $json_starter_str.= '}';
                }

            }
        }

        $new_line = '\n';
        $indent = '    ';
        $json_starter_str = str_replace('{', '{'.$new_line.$indent, $json_starter_str);
        $json_starter_str = str_replace(',', ','.$new_line.$indent, $json_starter_str);
        $json_starter_str = trim(str_replace('}', $new_line.'}'.$new_line.$indent, $json_starter_str));

        $data['cursor_reset_position'] = $cursor_reset_position;
        $data['json_starter_str'] = $json_starter_str;
        $view_file = $file_path = APPPATH.'engine/views/api_explorer.php';

        extract($data);
        require_once $view_file;
    }

    function _get_all_tables() {
        $tables = [];
        $sql = 'show tables';
        $column_name = 'Tables_in_'.DATABASE;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $tables[] = $row[$column_name];
        }

        return $tables;
    }

     function _fetch_endpoints($target_module) {

        if ($target_module == '') {
            http_response_code(422);
            echo "No target module set"; die();
        }

        $file_path = APPPATH.'modules/'.$target_module.'/assets/api.json';
        $settings = file_get_contents($file_path);
        $endpoints = json_decode($settings, true);   
        return $endpoints;    
    }

     function _get_all_columns($table) {

        $columns = [];
        $sql = 'describe '.$table;
        $rows = $this->model->query($sql, 'array');
        foreach ($rows as $row) {
            $columns[] = $row['Field'];
        }

        return $columns;   
    }

    function _get_params_from_url($params_segment) {
        //params segment is where params might be passed
        $params_str = segment($params_segment);
        $first_char = substr($params_str, 0, 1);
        if ($first_char == '?') {
            $params_str = substr($params_str, 1);
        }

        $data = [];
        parse_str($params_str, $data);

        //convert into json
        $params = [];
        foreach ($data as $key => $value) {
            $key = $this->_prep_key($key); 
            $value = $this->_remove_special_characters($value);          
            $params[$key] = $value;
        }

        return $params;
    }

    function _get_params_from_post($decoded) {

        $params = [];
        foreach ($decoded as $key => $value) {
            $key = $this->_prep_key($key); 
            $value = $this->_remove_special_characters($value);          
            $params[$key] = $value;
        }

        return $params;
    }

    function _attempt_invoke_before_hook($input, $module_endpoints, $target_endpoint) {

        //check API settings & find out which method (if any) to invoke
        if (isset($target_endpoint['beforeHook'])) {
            //invoke the before hook
            $module_name = $input['module_name'];
            $target_method = $target_endpoint['beforeHook'];
            $input = Modules::run($module_name.'/'.$target_method, $input);
        }

        return $input;
    }

    function _attempt_invoke_after_hook($output, $module_endpoints, $target_endpoint) {
        //check API settings & find out which method (if any) to invoke

        if (isset($target_endpoint['afterHook'])) {
            //invoke the after hook
            $module_name = $output['module_name'];
            $code = $output['code'];
            $target_method = $target_endpoint['afterHook'];

            $output = $this->_clean_output($output);

            //ONLY output['body'] and output['code'] is being used at this point
            $output = Modules::run($module_name.'/'.$target_method, $output);

        } else {
            $output = $this->_clean_output($output);
        }

        return $output;
    }

    function _clean_output($output) {
        //remove unwanted output properties
        foreach ($output as $key => $value) {

            if (($key != 'body') && ($key != 'code') && ($key != 'token')) {
                unset($output[$key]);
            }
        }

        return $output;
    }

    function _prep_key($key) { //php convert json into URL string

        //get last char
        $key = trim($key);
        $str_len = strlen($key);
        $last_char = substr($key, $str_len-1);
        
        if ($last_char == '!') {
            $key = $key.'=';
        }

        $key = $this->_remove_special_characters($key);

        $ditch = 'OR_';
        $replace = 'OR ';
        $key = str_replace($ditch, $replace, $key);

        $ditch = '_NOT_LIKE';
        $replace = ' NOT LIKE';
        $key = str_replace($ditch, $replace, $key);

        $ditch = '_LIKE';
        $replace = ' LIKE';
        $key = str_replace($ditch, $replace, $key);

        $ditch = '_!=';
        $replace = ' !=';
        $key = str_replace($ditch, $replace, $key);

        $ditch = 'AND_';
        $replace = 'AND ';
        $key = str_replace($ditch, $replace, $key);

        $ditch = '_>';
        $replace = ' >';
        $key = str_replace($ditch, $replace, $key);

        $ditch = '_<';
        $replace = ' <';
        $key = str_replace($ditch, $replace, $key);

        return $key;
    }

    function _remove_special_characters($str) {
        $ditch = '*!underscore!*';
        $replace = '_';
        $str = str_replace($ditch, $replace, $str);

        $ditch = '*!gt!*';
        $replace = '>';
        $str = str_replace($ditch, $replace, $str);

        $ditch = '*!lt!*';
        $replace = '<';
        $str = str_replace($ditch, $replace, $str);

        $ditch = '*!equalto!*';
        $replace = '=';
        $str = str_replace($ditch, $replace, $str);

        return $str;
    }

    function _add_params_to_query($module_name, $sql, $params) {

        //variables have been posted - start from here
        $got_where = false;
        foreach ($params as $key => $value) {
            $param_type = $this->_get_param_type($module_name, $key);

            if ($param_type == 'where') {
                $where_conditions[$key] = $value;
            }
        }

        //add where conditions
        if (isset($where_conditions)) {
            $where_condition_count = 0;
            foreach ($where_conditions as $where_left_side => $where_value) {
                $where_condition_count++;
                //where_key    where_value
                //manipulate the SQL query

                $where_key = $this->_extract_where_key($where_left_side);

                //make sure this column exists on the table
                $columns = $this->_get_all_columns($module_name);
                if (!in_array($where_key, $columns)) {
                    http_response_code(422);
                    echo $where_key.' is not a valid value or column name.';
                    die();
                }

                $where_start_word = $this->_extract_where_start_word($where_left_side, $where_condition_count);
                $connective = $this->_extract_connective($where_left_side);

                $new_where_condition = $where_start_word.' '.$where_key.' '.$connective.' :'.$where_key;
                $sql = $sql.' '.$new_where_condition;
                $data[$where_key] = $where_value;

            }

        }

        //add order by
        if (isset($params['orderBy'])) {

            //make sure this column is on the table
            $columns = $this->_get_all_columns($module_name);
            $column_name = str_replace(' desc', '', $params['orderBy']);

            if (!in_array($column_name, $columns)) {
                //invalid order by
                http_response_code(422);
                echo "invalid order by"; die();
            }

            $sql = $sql.' order by '.$params['orderBy'];
            unset($params['orderBy']);
        }

        //add limit offset
        if (isset($params['limit'])) {

            $limit = $params['limit'];

            //get the offset
            if (isset($params['offset'])) {
                $offset = $params['offset'];
            } else {
                $offset = 0;
            }

            if ((!is_numeric($limit)) || (!is_numeric($offset))) {
                http_response_code(422);
                echo "non numeric limit and/or offset"; die();
            }

            settype($limit, "integer");
            settype($offset, "integer");

            $data['limit'] = $limit;
            $data['offset'] = $offset;
            $sql = $sql.= ' limit :offset, :limit';

        }

        if (!isset($data)) {
            $data = [];
        }

        $query_info['sql'] = $sql;
        $query_info['data'] = $data;
        return $query_info;
    }

    function _get_param_type($module_name, $key) {

        switch ($key) {
            case 'limit':
                $type = 'limit';
                break;
            case 'offset':
                $type = 'offset';
                break;
            case 'orderBy':
                $type = 'order by';
                break;
            default:
                $type = 'where';
                break;
        }


        return $type;
    }

    function _extract_where_key($where_left_side) {

        $where_left_side = trim($where_left_side);
        $bits = explode(' ', $where_left_side);

        $first_three = substr($where_left_side, 0, 3);
        if ($first_three == 'OR ') {
            $where_key = $bits[1];
        } else {
            $where_key = $bits[0];
        }

        return $where_key;
    }

    function _extract_where_start_word($where_left_side, $where_condition_count) {
        //return WHERE, AND or OR
        $where_start_word = 'WHERE';
        $where_left_side = trim($where_left_side);

        $first_three = substr($where_left_side, 0, 3);
        if ($first_three == 'OR ') {
            $where_start_word = 'OR';
        } elseif ($where_condition_count>1) {
            $where_start_word = 'AND';
        }

        return $where_start_word;        
    }

    function _extract_connective($where_left_side) {

        /*
            * =         { "name":"John"}
            * OR        { "OR age >" : 21}
            * !=        { "name !=": "John"}
            * >         { "age >" : 21}
            * <         { "age <" : 21}
            * LIKE      { "name LIKE" : "e"}
            * NOT LIKE  { "name NOT LIKE" : "e"}
        */   

        $where_left_side = trim($where_left_side);
        $str_len = strlen($where_left_side);
        $start = $str_len - 9;
        $last_nine = substr($where_left_side, $start, $str_len);

        if ($last_nine == ' NOT LIKE') {
            $connective = 'NOT LIKE';
        }

        $ditch = ' NOT LIKE';
        $replace = '';
        $where_left_side = str_replace($ditch, $replace, $where_left_side);

        if (!isset($connective)) {
            $start = $str_len - 5;
            $last_five = substr($where_left_side, $start, $str_len);

            if ($last_five == ' LIKE') {
                $connective = 'LIKE';
            }            
        }

        $ditch = ' LIKE';
        $replace = '';
        $where_left_side = str_replace($ditch, $replace, $where_left_side);

        if (!isset($connective)) {

            $first_three = substr($where_left_side, 0, 3);
            if ($first_three == 'OR ') {
                $where_left_side = substr($where_left_side, 3, $str_len);
            }

            $bits = explode(' ', $where_left_side);
            $num_bits = count($bits);

            if ($num_bits>1) {
                $target_index = count($bits)-1;
                $connective = $bits[$target_index];
            } else {
                $connective = '=';
            }

        }

        $connective = ltrim(trim($connective));
        return $connective;
    }

    function submit_bypass_auth() {
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);
        $this->module('trongate_tokens');
        $this->trongate_tokens->_attempt_generate_bypass_token();
    }

}