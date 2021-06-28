<?php
class Trongate_tokens extends Trongate {

    /*
     * This a utility class that can assist you with things like securing API endpoints
     * The class is associated with a database table called trongate_tokens. 
     * Therefore, a database connection is required for this class to work.
     *
     * The default expiry time for tokens is set to one day.  However, you can 
     * easily change that to suit your needs.
     */

    private $default_token_lifespan = 86400; //one day

    function _validate_token() {

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            $this->_not_allowed_msg();
        } else {
            $token = $_SERVER['HTTP_TRONGATETOKEN'];
            $valid = $this->_is_token_valid($token);

            if ($valid == false) {
                $this->_not_allowed_msg();
            }
        }

        return $token;
    }

    function _not_allowed_msg() {
        http_response_code(401);
        echo "Invalid token."; die();
    }

    function _fetch_token($user_id) {

        $this->_delete_old_tokens();
        $result = $this->model->get_one_where('user_id', $user_id, 'trongate_tokens');

        if ($result == false) {
            //generate new token
            $token_data['user_id'] = $user_id;
            $token = $this->_generate_token($token_data);
        } else {
            $user_id = $result->user_id;
            $token = $result->token;
        }

        return $token;

    }

    function _generate_token($data) {

        /*
         * $data array may contain:
         *                         user_id ~ int(11) : required
         *                         expiry_date ~ int(10) : optional 
         *                         set_cookie ~ bool(true) : optional 
         *                         code ~ varchar(4) : optional 
         *
         * 'expiry_date' (if submitted) should be a unix timestamp, set to some future date.
         * 
         * How you choose to use this class is entirely up to you.
         */

        //generate 32 digit random string
        $random_string = $this->_generate_rand_str();

        //build data array variables (required for table insert)
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

    function _generate_rand_str() {
        $token_length = 32;
        $characters = '-_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';
        for ($i = 0; $i < $token_length; $i++) {
            $random_string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $random_string;
    }

    function regenerate() {
        $old_token = segment(3);
        $expiry_date = segment(4);

        if (!is_numeric($expiry_date)) {
            die();
        } elseif ($expiry_date<time()) {
            die();
        }

        $data['token'] = $old_token;
        $sql = 'select * from trongate_tokens where token = :token';
        $tokens = $this->model->query_bind($sql, $data, 'object');
        $num_rows = count($tokens);

        if ($num_rows>0) {
            $this_token = $tokens[0];
            $update_id = $this_token->id;
            $new_token = $this->_generate_rand_str();

            $new_data['user_id'] = $this_token->user_id;
            $new_data['code'] = $this_token->code;
            $new_data['expiry_date'] = $expiry_date;
            $new_data['token'] = $new_token;
            $this->model->update($update_id, $new_data, 'trongate_tokens');
            echo $new_token;
        } else {
            echo 'false';
        }

    }

    function _fetch_token_obj($token) {
        $data['token'] = $token;
        $sql = 'select * from trongate_tokens where token = :token';
        $token_objs = $this->model->query_bind($sql, $data, 'object');

        if ($token_objs == false) {
            return false; //token not found
        } else {
            $token_obj = $token_objs[0];
            return $token_obj;
        }

    }

    function _fetch_endpoint_auth_rules($endpoint, $module_endpoints) {

        if (gettype($module_endpoints) !== 'array') {
            $module_endpoints_json = json_decode($module_endpoints);
            $module_endpoints = (array) $module_endpoints_json;

            if (isset($module_endpoints[$endpoint])) {
                $target = $module_endpoints[$endpoint];
                $module_endpoints[$endpoint] = (array) $target;
            }
            
        }

        $data['url_segments'] = $module_endpoints[$endpoint]["url_segments"];

        if (isset($module_endpoints[$endpoint]["authorization"])) {
            $data['authorization_rules'] = $module_endpoints[$endpoint]["authorization"];
            return $data;
        } else {
            return false; //no authorization rules could be found
        }

    }

    function _is_token_valid($token_validation_data) {
        //this method gets used by the API manager - do not mess with this!
        extract($token_validation_data);

        if ($authorization_rules == false) {
            //invalid token
            return false;            
        }

        if ($authorization_rules == '*') {
            return true; //open endpoint
        }

        //If we have reached here then the endpoint requires authorization
        $this->_delete_old_tokens(); //housekeeping

        //try admin ('aaa') authentication
        $result = $this->model->get_one_where('token', $token, 'trongate_tokens');

        if ($result == false) {
            //invalid token
            return false;
        } else {

            $user_id = $result->user_id;
            $code = $result->code;

            if ($code == 'aaa') {
                return true;  //must be admin
            }

            //from here, the validation MUST require EITHER a certain role OR a certain user_id

            //test for user role
            if (isset($authorization_rules['roles'])) {
                $test_result = $this->_test_for_user_role($authorization_rules['roles'], $user_id);

                if ($test_result == true) {
                    return true; //user has the correct role
                }

            }

            //test for user ID
            if (isset($authorization_rules['userIds'])) {
                $test_result = $this->_test_for_user_id($authorization_rules['userIds'], $user_id);

                if ($test_result == true) {
                    return true; //user has an allowed correct id
                }

            }

        }

        return $user_id; //token has failed all of the tests

    }

    function _test_for_user_role($role_rules, $user_id) {
  
        //fetch the role (user_level) for this user
        $this->module('trongate_users-trongate_user_levels');
        $user_level = $this->trongate_user_levels->_get_user_level($user_id);

        if (in_array($user_level, $role_rules)) {
            return true;
        } else {
            return false;
        }

    }

    function _test_for_user_id($id_rules, $user_id) {

        if (in_array($user_id, $id_rules)) {
            return true;
        } else {

            //test for posted ID
            foreach ($id_rules as $param) {
                if (!is_numeric($param)) {

                    //fetch params from URL
                    $bits = explode('?', current_url());

                    if (count($bits)>1) {
                        $params = [];
                        parse_str($bits[1], $params);
                    } elseif (!isset($params)) {
                        $post = file_get_contents('php://input');
                        $params = json_decode($post, true);
                    }

                    if (isset($params[$param])) {
                        
                        if ($params[$param] == $user_id) {
                            return true;
                        }

                    }

                }
            }

            return false;
        }
    }

    function _destroy() {

        if (isset($_SESSION['trongatetoken'])) {
            $tokens_to_delete[] = $_SESSION['trongatetoken'];
            $_SESSION['trongatetoken'] = 'x'; //fallback
            unset($_SESSION['trongatetoken']);
        }

        if (isset($_COOKIE['trongatetoken'])) {
            //destroy the cookie
            $tokens_to_delete[] = $_COOKIE['trongatetoken'];
            $past_date = time()-86400;
            setcookie('trongatetoken', 'x', $past_date, '/');
        }

        if (isset($tokens_to_delete)) {
            foreach ($tokens_to_delete as $token) {
                $params['token'] = $token;
                $sql = 'delete from trongate_tokens where token = :token';
                $this->model->query_bind($sql, $params);
            }
        }

    }

    function _delete_old_tokens($user_id=NULL) {

        $sql = 'delete from trongate_tokens where expiry_date < :nowtime';
        $data['nowtime'] = time();

        if (isset($user_id)) {
            $sql.= ' or user_id = :user_id';
            $data['user_id'] = $user_id;
        }
        
        $this->model->query_bind($sql, $data);
    }

    function _delete_one_token($token) {
        $sql = 'delete from trongate_tokens where token = ?';
        $data[] = $token;
        $this->model->query_bind($sql, $data);        
    }

    function _get_user_level($token) {

        $sql = '
                SELECT
                    trongate_user_levels.level_title
                FROM
                    trongate_tokens
                JOIN trongate_users ON trongate_tokens.user_id = trongate_users.id
                JOIN trongate_user_levels ON trongate_users.user_level_id = trongate_user_levels.id 
                WHERE
                    trongate_tokens.token = :token 
        ';

        $data['token'] = $token;
        $result = $this->model->query_bind($sql, $data, 'object');

        if (count($result)>0) {
            $user_level = $result[0]->level_title;
        } else {
            $user_level = '';
        }

        return $user_level;
    }

    function _attempt_get_valid_token($user_levels=NULL) {

        //$user_levels can be; NULL, int or array (of ints)

        if (isset($_COOKIE['trongatetoken'])) {
            $user_tokens[] = $_COOKIE['trongatetoken'];
        }

        if (isset($_SESSION['trongatetoken'])) {
            $user_tokens[] = $_SESSION['trongatetoken'];
        }

        if (!isset($user_tokens)) {
            return false;
        } else {

            if (!isset($user_levels)) {
                $user_levels_type = '';
            } else {
                $user_levels_type = gettype($user_levels);
            }

            switch ($user_levels_type) {
                case 'integer':
                    //allow access for ONE user level type
                    $token = $this->_execute_sql_single($user_tokens, $user_levels);
                    break;
                case 'array':
                    //allow access for MORE THAN ONE user level type
                    $token = $this->_execute_sql_multi($user_tokens, $user_levels);
                    break;
                default:
                    //allow access for AND user level type
                    $token = $this->_execute_sql_default($user_tokens);
                    break;
            }

            return $token;

        }

    }

    function _execute_sql_single($user_tokens, $user_levels) {
        //allow access for ONE user level type
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
                    '.$where_condition.' 
                    AND 
                            trongate_users.user_level_id = :user_level_id';
            $sql.= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

                if (count($rows)>0) {
                    $token = $rows[0]->token;
                    return $token;
                }

        }

        return false;
    }

    function _execute_sql_multi($user_tokens, $user_levels) {
        //allow access for MORE THAN ONE user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

        $and_condition = ' AND (';
        $count = 0;
        foreach ($user_levels as $user_level) { 
            $count++;

            $this_property = 'user_level_'.$count;
            $params[$this_property] = $user_level;

            if ($count>1) {
                $and_condition.= ' OR';
            }

            $and_condition.= ' trongate_users.user_level_id = :'.$this_property;
        }
        $and_condition.= ')';
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
                    '.$where_condition.' 
                    '.$and_condition;  
            $sql.= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

                if (count($rows)>0) {
                    $token = $rows[0]->token;
                    return $token;
                }

        }

        return false;
    }

    function _execute_sql_default($user_tokens) {
        //allow access for ANY user level type
        $where_condition = ' WHERE trongate_tokens.token = :token ';
        $params['nowtime'] = time();

        foreach ($user_tokens as $token) {
            $params['token'] = $token;
            $sql = 'SELECT 
                            trongate_tokens.token 
                    FROM 
                            trongate_tokens 
                    '.$where_condition;  
            $sql.= ' AND expiry_date > :nowtime ';
            $rows = $this->model->query_bind($sql, $params, 'object');

                if (count($rows)>0) {
                    $token = $rows[0]->token;
                    return $token;
                }

        }

        return false;
    }

}