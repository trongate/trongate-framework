<?php
class Trongate_tokens extends Trongate {

	private $default_token_lifespan = 86400; //one day

    function auth() {

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'no token'; die();
        } else {
            $token = $_SERVER['HTTP_TRONGATETOKEN'];
            $result = $this->model->get_one_where('token', $token, 'trongate_tokens');

            if ($result == false) {
                http_response_code(401);
                echo 'false';
            } else {
                http_response_code(200);
                echo $token;
            }

        }

    }

    function id() {

        //fetch the trongate_user_id via HTTP POST request

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'no token'; die();
        } else {
            $token = $_SERVER['HTTP_TRONGATETOKEN'];
            $result = $this->model->get_one_where('token', $token, 'trongate_tokens');

            if ($result == false) {
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

    function user() {

        //fetch the trongate user object via HTTP POST request 

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
            if (isset($rows[0])) {
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

    function destroy() {

        if (!isset($_SERVER['HTTP_TRONGATETOKEN'])) {
            http_response_code(422);
            echo 'No token found in here!';
            die();
        } else {
            $params['token'] = $_SERVER['HTTP_TRONGATETOKEN'];
            $sql = 'delete from trongate_tokens where token = :token';
            $this->model->query_bind($sql, $params);
            http_response_code(200);        
            echo 'Token deleted.';
            die();
        }

    }

    function _get_user_id($token=null) {

    	if (isset($token)) {
    		$params['cookie_token'] = $token;
    		$params['session_token'] = $token;
    	} else {
	        //attempt to fetch Trongate user object from sessions or cookie
	        $params['cookie_token'] = (isset($_COOKIE['trongatetoken']) ? $_COOKIE['trongatetoken'] : '');
	        $params['session_token'] = (isset($_SESSION['trongatetoken']) ? $_SESSION['trongatetoken'] : '');
    	}

        $sql = 'select user_id from trongate_tokens where token=:cookie_token or token=:session_token';
        $rows = $this->model->query_bind($sql, $params, 'object');
        $trongate_user_id = (isset($rows[0]) ? $rows[0]->user_id : false);
        return $trongate_user_id;
    }

    function _get_user_obj($token=null) {

    	if (isset($token)) {

            if (gettype($token) !== 'string') {
                settype($token, 'string');
            }
            
    		$params['cookie_token'] = $token;
    		$params['session_token'] = $token;
    	} else {
	        //attempt to fetch Trongate user object from sessions or cookie
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
                    trongate_tokens.token =:session_token';
        $rows = $this->model->query_bind($sql, $params, 'object');
        $trongate_user_obj = (isset($rows[0]) ? $rows[0] : false);
        return $trongate_user_obj;
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

    function generate() {

        /*
             generate token by POST (for devs who like JavaScript)
             $posted data may contain:
                                       user_id ~ int(11) : required
                                       expiry_date ~ int(10) : optional 
        */

        if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
            http_response_code(403);
            echo 'Forbidden';
            die();
        } else {
            //fetch posted data
            $posted_data = file_get_contents('php://input');
            $input = (array) json_decode($posted_data);
            $data = $this->_pre_token_validation($input);
        }

        $token = $this->_generate_token($data);
        http_response_code(200);
        echo $token;
    }

    function _pre_token_validation($input) {

        //validation tests that happen before a new token is issued

        if (ENV !== 'dev') {
            //add your own validation code here!
            echo 'Forbidden (no validation tests available)';
            http_response_code(403);
            die();
        }

        if (!isset($input['user_id'])) {
            http_response_code(400);
            echo 'No user_id submitted!';
            die();
        } elseif(!is_numeric($input['user_id'])) {
            http_response_code(400);
            echo 'Non-numeric user_id submitted!';
            die();
        }

        return $input;
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
        $random_string = make_rand_str();

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
            $new_token = make_rand_str();

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

        $this->_delete_old_tokens();

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

}