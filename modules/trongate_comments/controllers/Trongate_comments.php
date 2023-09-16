<?php

declare(strict_types=1);

class Trongate_comments extends Trongate
{
    /**
     * @var string The default system'username for comments from a user id of 0.
     */
    private string $zero_id_username = 'System';

    /**
     * Prepare comments data with formatted dates and user information.
     * This method is typically called via admin.js.
     *
     * @param  array  $output The output data containing comments.
     *
     * @return array Processed output data with formatted comments.
     */
    public function _prep_comments(array $output): array
    {
        //return comments with nicely formatted date
        $body = $output['body'];

        //get an array of all trongate_administrators
        $sql = 'SELECT trongate_users.id, trongate_administrators.username 
                FROM trongate_comments 
                INNER JOIN trongate_users ON trongate_comments.user_id = trongate_users.id 
                INNER JOIN trongate_administrators ON trongate_users.id = trongate_administrators.trongate_user_id';
        $all_admins = $this->model->query($sql, 'object');

        $admin_users = [];
        foreach ($all_admins as $admin_user) {
            $admin_users[$admin_user->id] = $admin_user->username;
        }

        $comments = json_decode($body);
        $data = [];
        foreach ($comments as $key => $value) {
            $row_data['comment'] = nl2br($value->comment);

            if (isset($admin_users[$value->user_id])) {
                $posted_by = $admin_users[$value->user_id];
            } else {
                if (($value->user_id === 0) && (isset($this->zero_id_username))) {
                    $posted_by = $this->zero_id_username;
                } else {
                    $posted_by = 'an unknown user';
                }
            }

            $date_created = date('l jS \of F Y \a\t h:i:s A', $value->date_created);
            $row_data['date_created'] = 'Posted by '.$posted_by.' on '.$date_created;
            $row_data['user_id'] = $value->user_id;
            $row_data['target_table'] = $value->target_table;
            $row_data['update_id'] = $value->update_id;
            $row_data['code'] = $value->code;
            $data[] = $row_data;
        }

        $output['body'] = json_encode($data);

        return $output;
    }

    /**
     * Pre-insert hook to be invoked by API manager before inserting a comment
     *
     * @param  array  $input The input data for insertion.
     *                     Expected structure:
     *                     [
     *                         'token' => string, // The token associated with the user.
     *                         'params' => array[ // Additional parameters for insertion.
     *                             'user_id' => int,      // ID of the user.
     *                             'date_created' => int, // Unix timestamp of creation date.
     *                             'code' => string,      // Randomly generated code.
     *                         ],
     *                     ]
     *
     * @return array Processed input data with additional parameters.
     */
    public function _pre_insert(array $input): array
    {
        // Establish user_id, date_created, and code before doing an insert.
        $this->module('trongate_tokens');
        $token = $input['token'];
        $user = $this->trongate_tokens->_fetch_token_obj($token);

        $input['params']['user_id'] = $user->user_id;
        $input['params']['date_created'] = time();
        $input['params']['code'] = make_rand_str(6);

        return $input;
    }
}
