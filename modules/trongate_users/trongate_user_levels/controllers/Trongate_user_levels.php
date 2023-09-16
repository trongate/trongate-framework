<?php

declare(strict_types=1);

class Trongate_user_levels extends Trongate
{
    public function __construct()
    {
        parent::__construct();
        $this->parent_module = 'trongate_users';
        $this->child_module = 'trongate_user_levels';
    }

    public function __destruct()
    {
        $this->parent_module = '';
        $this->child_module = '';
    }

    public function _get_user_level($user_id)
    {
        $sql = 'SELECT
                    trongate_user_levels.level_title
                FROM
                    trongate_users
                JOIN trongate_user_levels ON trongate_users.user_level_id = trongate_user_levels.id 
                where trongate_users.id = :user_id';

        $data['user_id'] = $user_id;
        $result = $this->model->query_bind($sql, $data, 'array');

        return $result[0]['level_title'] ?? '';
    }
}
