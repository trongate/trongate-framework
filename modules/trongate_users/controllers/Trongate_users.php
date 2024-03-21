<?php
class Trongate_users extends Trongate {

    /**
     * Creates a new trongate user record with a specified user level ID.
     *
     * @param int $user_level_id The user level ID for the new user.
     * @return int The Trongate user ID of the newly created user.
     */
    function _create_user(int $user_level_id): int {
        $params['code'] = make_rand_str(32);
        $params['user_level_id'] = $user_level_id;
        $trongate_user_id = $this->model->insert($params, 'trongate_users');
        return $trongate_user_id;
    }
}
