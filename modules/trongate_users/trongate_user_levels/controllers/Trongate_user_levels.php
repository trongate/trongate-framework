<?php
class Trongate_user_levels extends Trongate {

    /**
     * Initializes the Trongate_user_levels class.
     */
    function __construct() {
        parent::__construct();
        $this->parent_module = 'trongate_users';
        $this->child_module = 'trongate_user_levels';
    }

    /**
     * Retrieves the user level title for a given user ID.
     *
     * @param int $user_id The ID of the user.
     * @return string The title of the user level.
     */
    function _get_user_level(int $user_id): string {
        $sql = 'SELECT
                    trongate_user_levels.level_title
                FROM
                    trongate_users
                JOIN trongate_user_levels ON trongate_users.user_level_id = trongate_user_levels.id 
                WHERE trongate_users.id = :user_id';

        $data['user_id'] = $user_id;
        $result = $this->model->query_bind($sql, $data, 'array');

        $user_level = $result[0]['level_title'] ?? '';

        return $user_level;
    }

    /**
     * Clean up resources and reset class properties.
     */
    function __destruct() {
        $this->parent_module = '';
        $this->child_module = '';
    }
}
