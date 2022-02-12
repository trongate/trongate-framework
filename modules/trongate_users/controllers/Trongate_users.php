<?php
class Trongate_users extends Trongate {

    function _create_user($user_level_id) {
    	$params['code'] = make_rand_str(32);
        $params['user_level_id'] = $user_level_id;
        $trongate_user_id = $this->model->insert($params, 'trongate_users');
        return $trongate_user_id;
    }

}