<?php
class Trongate_administrators extends Trongate {

    //NOTE: the default username and password is 'admin' and 'admin'
    //private $secret_login_segment = 'tg-admin';
    private $dashboard_home = 'trongate_pages/manage'; //where to go after login


    /**
     * Initiates the login process for administrators.
     * 
     * @return void
     */
    function login(): void {

        if (isset($this->secret_login_segment)) {

            if (is_numeric(strpos(current_url(), 'trongate_administrators'))) {
                $this->template('error_404');
                die();
            }

            $data['form_location'] = BASE_URL.$this->secret_login_segment.'/submit_login';
        } else {
            $data['form_location'] = BASE_URL.'trongate_administrators/submit_login';
        }

        $data['username'] = post('username');
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'login_form'; 
        $this->load_template($data);
    }

    /**
     * Handles the submission of login forms, validating user input and logging users in if validation passes.
     * Redirects to the login form on validation failure or redirects to the base URL on 'Cancel' submission.
     *
     * @return void
     */
    function submit_login(): void {

        if (isset($this->secret_login_segment)) {
            if (is_numeric(strpos(current_url(), 'trongate_administrators'))) {
                $this->template('error_404');
                die();
            }
        }

        $submit = post('submit'); 

        if ($submit == 'Submit') {
            $this->validation_helper->set_rules('username', 'username', 'required|callback_login_check');
            $this->validation_helper->set_rules('password', 'password', 'required|min_length[5]');
            $result = $this->validation_helper->run();

            if ($result == true) {
                $this->_log_user_in(post('username'));
            } else {
                $this->login();
            }
        } elseif($submit == 'Cancel') {
            redirect(BASE_URL);
        }
    }

    /**
     * Handles form submission for user data, validates input, updates existing records or creates new ones accordingly.
     * Redirects to management view or the creation form based on form submission.
     *
     * @return void
     */
    function submit(): void {
        $data['token'] = $this->_make_sure_allowed();
        $submit = post('submit');

        if ($submit == 'Submit') {
            $this->validation_helper->set_rules('username', 'username', 'required|min_length[5]|callback_username_check');
            $this->validation_helper->set_rules('password', 'password', 'required|min_length[5]');
            $this->validation_helper->set_rules('repeat_password', 'repeat password', 'matches[password]');

            $result = $this->validation_helper->run();

            if ($result == true) {
                $update_id =  segment(3);
                $data = $this->_get_data_from_post();
                unset($data['repeat_password']);
                $data['password'] = $this->_hash_string($data['password']);

                if (is_numeric($update_id)) {
                    $this->model->update($update_id, $data);
                    set_flashdata('The record was successfully updated');
                } else {
                    //create new trongate_administrators/users records 
                    $this->module('trongate_users');
                    $data['trongate_user_id'] = $this->trongate_users->_create_user(1);
                    $this->model->insert($data);
                    set_flashdata('The record was successfully created');
                }

                redirect('trongate_administrators/manage');

            } else {
                $this->create();
            }
        } elseif($submit == 'Cancel') {
            redirect('trongate_administrators/manage');
        }
    }

    /**
     * Handles the deletion of a specific user record and related entries based on the given update ID.
     * Performs the deletion of related records from 'trongate_users' and 'trongate_administrators' tables.
     * Redirects to the management page after successful deletion.
     *
     * @return void
     */
    function submit_delete(): void {
        $this->_make_sure_allowed();
        $update_id =  segment(3);
        $submit = post('submit');

        if (($submit == 'Delete Record Now') && (is_numeric($update_id))) {
            //get the trongate_user_id 
            $user_obj = $this->model->get_where($update_id, 'trongate_administrators');
            $trongate_user_id = $user_obj->trongate_user_id;
            $this->model->delete($trongate_user_id, 'trongate_users');
            $this->model->delete($update_id, 'trongate_administrators');
            set_flashdata('The record was successfully deleted');
        }

        redirect('trongate_administrators/manage');
    }

    /**
     * Manages the display of the administrator records within the 'trongate_administrators' table.
     * Retrieves necessary data such as admin ID, username rows from the model, and loads the management view.
     *
     * @return void
     */
    function manage(): void {
        $token = $this->_make_sure_allowed();
        $data['my_admin_id'] = $this->_get_my_id($token);
        $data['rows'] = $this->model->get('username', 'trongate_administrators');
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'manage';
        $this->load_template($data);
    }

    /**
     * Renders a template file with provided data.
     *
     * @param array $data The data to be passed into the template.
     * @return void
     */
    function load_template(array $data): void {
        $file_path = APPPATH.'modules/trongate_administrators/views/tg_admin_template.php';
        require_once($file_path);
    }

    /**
     * Redirects to the 'create' route for the Trongate administrators module based on the user's token.
     *
     * @return void
     */
    function account(): void {
        $token = $this->_make_sure_allowed();
        $update_id = $this->_get_my_id($token);
        redirect('trongate_administrators/create/'.$update_id);
    }

    /**
     * Manages the creation or updating of Trongate administrator records based on provided data.
     * Redirects to appropriate routes for the record management.
     *
     * @return void
     */
    function create(): void {
        $token = $this->_make_sure_allowed();
        $update_id = segment(3);
        $submit = post('submit');

        if ((is_numeric($update_id)) && ($submit == '')) {
            $data = $this->_get_data_from_db($update_id);
        } else {
            $data = $this->_get_data_from_post();
        }

        $data['my_admin_id'] = $this->_get_my_id($token);

        if (is_numeric($update_id)) {
            $data['headline'] = 'Update Record';

            if ($data['my_admin_id'] == $update_id) {
                $data['headline'] = str_replace('Record', 'Your Account', $data['headline']);
            }

        } else {
            $data['headline'] = 'Create Record';
        }

        $data['form_location'] = str_replace('/create', '/submit', current_url());
        $data['conf_delete_url'] = str_replace('/create', '/conf_delete', current_url());
        $data['token'] = $this->_make_sure_allowed();
        $data['view_module'] = 'trongate_administrators';
        $data['view_file'] = 'create';
        $this->load_template($data);
    }

    /**
     * Manages the confirmation process for deleting a Trongate administrator record.
     * Validates the deletion request and loads the confirmation template if the record exists.
     * Redirects to the management page if the record doesn't exist.
     *
     * @return void
     */
    function conf_delete(): void {
        $token = $this->_make_sure_allowed();
        $update_id =  segment(3);

        if (!is_numeric($update_id)) {
            redirect('trongate_administrators/manage');
        } else {
            $data['my_admin_id'] = $this->_get_my_id($token);
            $data['form_location'] = str_replace('/conf_delete', '/submit_delete', current_url());
            $data['view_module'] = 'trongate_administrators';
            $data['view_file'] = 'conf_delete';
            $this->load_template($data);
        }
    }

    /**
     * Redirects to the designated dashboard home page.
     *
     * @return void
     */
    function go_home(): void {
        redirect($this->dashboard_home);
    }

    /**
     * Retrieves the user ID associated with the given token.
     *
     * @param string $token The token associated with the user.
     * @return mixed Returns the user ID if found, otherwise false.
     */
    function _get_my_id(string $token) {
        $params['token'] = $token;
        $sql = 'SELECT trongate_administrators.id
                FROM trongate_users
                INNER JOIN trongate_administrators
                       ON trongate_users.id = trongate_administrators.trongate_user_id
                INNER JOIN trongate_tokens
                       ON trongate_tokens.user_id = trongate_users.id 
                WHERE trongate_tokens.token = :token 
                ORDER BY trongate_tokens.id DESC LIMIT 0,1';
        $result = $this->model->query_bind($sql, $params, 'object');
        if (gettype($result) == 'array') {
            $id = $result[0]->id;
        } else {
            $id = false;
        }
        return $id;
    }

    /**
     * Ensures that access is allowed.
     *
     * @return string|null Returns a token if access is allowed, otherwise null.
     */
    function _make_sure_allowed(): ?string {

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

    /**
     * Retrieves data from the database based on the provided update ID.
     *
     * @param int $update_id The ID used to fetch data from the database.
     * @return array|false Returns an array containing fetched data or false if no data is found.
     */
    function _get_data_from_db(int $update_id) {
        $result_obj = $this->model->get_where($update_id);
        if (gettype($result_obj) == 'object') {
            $data = (array) $result_obj;

        } else {
            $data = false;
        }
        return $data;
    }

    /**
     * Retrieves and organizes data from the POST request.
     *
     * @return array Contains username, password, and repeated password data from the POST request.
     */
    function _get_data_from_post(): array {
        $data['username'] = post('username');
        $data['password'] = post('password');
        $data['repeat_password'] = post('repeat_password');
        return $data;
    }

    /**
     * Logs in the user based on the provided username and handles token generation for session or cookie-based authentication.
     *
     * @param string $username The username used for login.
     * @return void
     */
    function _log_user_in(string $username): void {
        $this->module('trongate_tokens');
        $user_obj = $this->model->get_one_where('username', $username);
        $trongate_user_id = $user_obj->trongate_user_id;
        $token_data['user_id'] = $trongate_user_id;

        $remember = post('remember');
        if (($remember === '1') || ($remember === 1)) {
            //set a cookie and remember for 30 days
            $token_data['expiry_date'] = time() + (86400*30);
            $token = $this->trongate_tokens->_generate_token($token_data);
            setcookie('trongatetoken', $token, $token_data['expiry_date'], '/');            
        } else {
            //user did not select 'remember me' checkbox
            $_SESSION['trongatetoken'] = $this->trongate_tokens->_generate_token($token_data);            
        }

        redirect($this->dashboard_home);
    }

    /**
     * Handles user logout by destroying tokens and redirects based on existence of the secret login segment.
     *
     * @return void
     */
    function logout(): void {
        $this->module('trongate_tokens');
        $this->trongate_tokens->_destroy();

        if (isset($this->secret_login_segment)) {
            redirect(BASE_URL);
        } else {
            redirect('trongate_administrators/login');
        }
    }

    /**
     * Deletes tokens associated with a specific Trongate user and removes expired tokens.
     *
     * @param int $trongate_user_id The ID of the Trongate user.
     * @return void
     */
    function _delete_tokens_for_user(int $trongate_user_id): void {
        $params['user_id'] = $trongate_user_id;
        $sql = 'delete from trongate_tokens where user_id = :user_id';
        $this->model->query_bind($sql, $params);

        //let's delete expired tokens too
        $this->_delete_expired_tokens();
    }

    /**
     * Deletes expired tokens from the Trongate tokens table.
     *
     * @return void
     */
    function _delete_expired_tokens(): void {
        $params['nowtime'] = time();
        $sql = 'delete from trongate_tokens where expiry_date<:nowtime';
        $this->model->query_bind($sql, $params);        
    }

    /**
     * Hashes a string using the Bcrypt algorithm.
     *
     * @param string $str The string to be hashed.
     * @return string The hashed string.
     */
    function _hash_string(string $str): string {
        $hashed_string = password_hash($str, PASSWORD_BCRYPT, array(
            'cost' => 11
        ));
        return $hashed_string;
    }

    /**
     * Verifies a plain text string against a hashed string.
     *
     * @param string $plain_text_str The plain text string to verify.
     * @param string $hashed_string The hashed string to compare against.
     * @return bool Returns TRUE if the verification is successful, otherwise FALSE.
     */
    function _verify_hash(string $plain_text_str, string $hashed_string): bool {
        $result = password_verify($plain_text_str, $hashed_string);
        return $result; //TRUE or FALSE
    }


    /**
     * Checks the availability of a username and validates it against existing usernames.
     *
     * @param string $str The username to be checked.
     * @return string|bool Returns an error message if the username is not available, otherwise returns TRUE.
     */
    function username_check(string $str): string|bool {
        //NOTE: You may wish to add other rules of your own here! 
        $update_id =  (int) segment(3);
        $result = $this->model->get_one_where('username', $str, 'trongate_administrators');
        $error_msg = 'The username that you submitted is not available.';

        if (gettype($result) == 'object') {
            if (!is_numeric($update_id)) {
                return $error_msg;
            } else {
                $register_id = $result->id;
                if ($update_id !== $register_id) {
                    return $error_msg;
                }
            }
        }

        return true;
    }

    /**
     * Validates the submitted username and password for login authentication.
     *
     * @param string $submitted_username The username submitted for login.
     * @return string|bool Returns an error message (string) if authentication fails, otherwise returns TRUE.
     */
    function login_check(string $submitted_username): string|bool {
        $submitted_password = post('password');
        $error_msg = 'You did not enter a correct username and/or or password.';
    
        $result = $this->model->get_one_where('username', $submitted_username, 'trongate_administrators');
        if (gettype($result) == 'object') {
            $hashed_password = $result->password;
            $is_password_good = $this->_verify_hash($submitted_password, $hashed_password);
            if ($is_password_good == true) {
                return true;
            }
        }

        return $error_msg;
    }

}