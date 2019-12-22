<?php
class Trongate_administrators extends Trongate {

    //NOTE: the default username and password is 'admin' and 'admin'

    function manage() {
        $data['token'] = $this->_make_sure_allowed();
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'manage';
        $this->view('trongate_administrators_template', $data);
    }

    function login() {
        $data['form_location'] = str_replace('/login', '/submit_login', current_url());
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'login_form'; 
        $this->view('trongate_administrators_template', $data);
    }

    function missing_tbl_msg() {
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'missing_tbl_msg'; 
        $this->view('trongate_administrators_template', $data);        
    }

    function submit_login() {

        $submit = $this->input('submit');
        $error_msg = 'You did not enter a correct username and/or password.';

        if ($submit == 'Submit') {

            $submitted_username = $this->input('username');
            $submitted_password = $this->input('password');

            //fetch record from trongate_administrators table
            $user_obj = $this->model->get_one_where('username', $submitted_username, 'trongate_administrators');
            
            if ($user_obj == false) {
                //submitted username was not correct
                $validation_errors[] = $error_msg;
                $_SESSION['form_submission_errors'] = $validation_errors;
                $this->login();
            } else {
                //check submitted password against hashed password on db
                $hashed_password = $user_obj->password;
                $is_password_good = $this->_verify_hash($submitted_password, $hashed_password);

                if ($is_password_good == true) {

                    //user has submitted correct details
                    $this->module('trongate_tokens');
                    $remember = $this->input('remember');
                    $token_data['user_id'] = $user_obj->trongate_user_id;

                    if ($remember == 1) {
                        //set a cookie and remember for 30 days
                        $token_data['expiry_date'] = time() + (86400*30);
                        $token = $this->trongate_tokens->_generate_token($token_data);
                        setcookie('trongatetoken', $token, $token_data['expiry_date'], '/');

                    } else {
                        //user did not select 'remember me' checkbox
                        $_SESSION['trongatetoken'] = $this->trongate_tokens->_generate_token($token_data);
                    }

                    redirect('trongate_administrators/manage');

                } else {
                    //user entered incorrect password
                    $validation_errors[] = $error_msg;
                    $_SESSION['form_submission_errors'] = $validation_errors;
                    $this->login();
                }

            }

        }

    }

    function _make_sure_allowed() {

        //let's assume that only users with a valid token 
        //who are user_level_id = 1 can view
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token(1);

        if ($token == false) {

            if (ENV == 'dev') {
                //automatically give token to user when in dev mode

                //generate trongatetoken for 1st trongate_administrator record on tbl
                $sql = 'select * from trongate_administrators order by id limit 0,1';
                $rows = $this->model->query($sql, 'object');

                if ($rows == false) {
                    redirect(BASE_URL.'trongate_administrators/missing_tbl_msg');
                } else {
                    $token_params['user_id'] = $rows[0]->trongate_user_id;

                    //start off by clearing all tokens for this user
                    $this->_delete_tokens_for_user($token_params['user_id']);

                    //now generate the new token
                    $token_params['expiry_date'] = 86400+time();
                    $this->module('trongate_tokens');
                    $_SESSION['trongatetoken'] = $this->trongate_tokens->_generate_token($token_params);
                    return $_SESSION['trongatetoken'];
                }

            } else {
                redirect('trongate_administrators/login');
            }

        } else {
            return $token;
        }

    }

    function logout() {
        $this->module('trongate_tokens');
        $this->trongate_tokens->_destroy();
        redirect('trongate_administrators/login');
    }

    function _pre_insert_actions($input) {
        $this->module('trongate_users');
        $params['code'] = make_rand_str(32);
        $params['user_level_id'] = 1;
        $input['params']['trongate_user_id'] = $this->model->insert($params, 'trongate_users');
        $input = $this->_prep_password($input);
        return $input;
    }

    function _prep_password($input) {
        //a beforeHook that gets called when updating via JavaScript POST/PUT request
        if (isset($input['params']['password'])) {
            //hash baby hash!
            $input['params']['password'] = $this->_hash_string($input['params']['password']);
        }
        
        return $input;
    }

    function _delete_trongate_user($input) {
        //a beforeHook that gets called from deleting via JavaScript HTTP request
        if (isset($input['params']['id'])) {

            //delete the trongate user record
            $user_obj = $this->model->get_where($input['params']['id'], 'trongate_administrators');
            $params['trongate_user_id'] = $user_obj->trongate_user_id;
            $sql = 'delete from trongate_users where id=:trongate_user_id';
            $this->model->query_bind($sql, $params);

            //delete trongate_tokens for this user
            $this->_delete_tokens_for_user($params['trongate_user_id']);
        }

        return $input;
    }

    function _delete_tokens_for_user($trongate_user_id) {
        $params['user_id'] = $trongate_user_id;
        $sql = 'delete from trongate_tokens where user_id = :user_id';
        $this->model->query_bind($sql, $params);

        //let's delete expired tokens too
        $this->_delete_expired_tokens();
    }

    function _delete_expired_tokens() {
        $params['nowtime'] = time();
        $sql = 'delete from trongate_tokens where expiry_date<:nowtime';
        $this->model->query_bind($sql, $params);        
    }

    function _hash_string($str) {
        $hashed_string = password_hash($str, PASSWORD_BCRYPT, array(
            'cost' => 11
        ));
        return $hashed_string;
    }

    function _verify_hash($plain_text_str, $hashed_string) {
        $result = password_verify($plain_text_str, $hashed_string);
        return $result; //TRUE or FALSE
    }

}