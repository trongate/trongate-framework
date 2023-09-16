<?php

declare(strict_types=1);

class Trongate_users extends Trongate
{
    public function _create_user($user_level_id)
    {
        $params['code'] = make_rand_str(32);
        $params['user_level_id'] = $user_level_id;
        return $this->model->insert($params, 'trongate_users');
    }
}
