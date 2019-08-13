<?php
class Security extends Trongate {

    function _make_sure_allowed() {
        return true;
    }

    function _get_user_id() {
        $user_id = 1; //replace this with your own authentication code
        return $user_id;
    }

    function _get_user_level($user_id) {
        //fetch the user_level for this user
        $this->module('trongate_users-trongate_user_levels');
        $user_level = $this->trongate_user_levels->_get_user_level($user_id);
        return $user_level;
    }

    function _generate_random_string($length) {
        $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

}