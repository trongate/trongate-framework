<?php
class Security extends Trongate {

    function _make_sure_allowed($scenario='admin panel') {
        //returns EITHER (trongate)token OR initialises 'not allowed' procedure

        switch ($scenario) {
            // case 'members area':
            //     $this->module('members');
            //     $token = $this->members->_make_sure_allowed();
            //     break;
            default:
                $this->module('trongate_administrators');
                $token = $this->trongate_administrators->_make_sure_allowed();
                break;
        }

        return $token;
    }

    function _get_user_id() {
        //attempt fetch trongate_user_id (this gets called by the API explorer)
        $trongate_user_id = 0;

        if (isset($_COOKIE[TRONGATE_COOKIE_NAME])) {
            $trongate_user_id = $this->_is_token_valid($_COOKIE[TRONGATE_COOKIE_NAME], true);

            if ($trongate_user_id == 0) {
                //user has an invalid cookie - destroy it
                setcookie(TRONGATE_COOKIE_NAME, '', time() - 3600);
            }
        }

        if ((isset($_SESSION[TRONGATE_COOKIE_NAME])) && ($trongate_user_id == 0)) {
            $trongate_user_id = $this->_is_token_valid($_SESSION[TRONGATE_COOKIE_NAME], true);
        }

        return $trongate_user_id;
    }

    function _is_token_valid($token, $return_id=false) {
        $params['token'] = $token;
        $params['nowtime'] = time();
        $sql = 'select * from trongate_tokens where token = :token and expiry_date > :nowtime';
        $rows = $this->model->query_bind($sql, $params, 'object');

        if (count($rows)!==1) {

            if ($return_id == true) {
                return 0;
            } else {
                return false;
            }

        } else {

            if ($return_id == true) {
                $user_obj = $rows[0];
                return $user_obj->user_id;
            } else {
                return true;
            }

        }
    }

    function _redirect_https(){
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
            $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $location);
            exit;
        } 
    }    

}