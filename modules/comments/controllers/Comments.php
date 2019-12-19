<?php
class Comments extends Trongate {

    function _pre_insert($input) {
        //establish user_id, date_created and code before doing an insert
        $this->module('trongate_tokens');
        $token = $input['token'];
        $user = $this->trongate_tokens->_fetch_token_obj($token);

        $input['params']['user_id'] = $user->user_id;
        $input['params']['date_created'] = time();
        $input['params']['code'] = make_rand_str(6);

        return $input;
    }

    function _prep_comments($output) {
        //return comments with nicely formatted date
        $body = $output['body'];

        $comments = json_decode($body);
        $data = [];
        foreach ($comments as $key=>$value) {

            $row_data['comment'] = nl2br($value->comment);
            $row_data['date_created'] = date('l jS \of F Y \a\t h:i:s A', $value->date_created);
            $row_data['user_id'] = $value->user_id;
            $row_data['target_table'] = $value->target_table;
            $row_data['update_id'] = $value->update_id;
            $row_data['code'] = $value->code;
            $data[] = $row_data;

        }

        $output['body'] = json_encode($data);
        return $output;
    }

    function _display_comments_block($token) {
        $target_table = $this->url->segment(1);
        $update_id = $this->url->segment(3);
        $data['target_table'] = $target_table;
        $data['update_id'] = $update_id;
        $data['token'] = $token;
        $this->view('comments_block', $data);
    }

    function submit() {

        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);

        $token = $decoded['token'];
        $data['comment'] = $decoded['comment'];
        $data['target_table'] = $decoded['target_table'];
        $data['update_id'] = $decoded['update_id'];
        $data['date_created'] = time();

        $this->module('trongate_tokens');
        $token_obj = $this->trongate_tokens->_fetch_token_obj($token);

        if ($token_obj == false) {
            die(); //invalid token
        } else {

            $data['code'] = make_rand_str(6);
            $information = json_decode($token_obj->information);
            $data['user_id'] = $token_obj->user_id;

            if (isset($information->tables)) {
                $tables = get_object_vars($information->tables);

                if (isset($tables['comments'])) {
                    $table_permissions = $tables['comments'];

                    if (($table_permissions == '*') || ($table_permissions == 'w')) {
                        //we have permission to insert this comment - let's do it!
                        $this->model->insert($data, 'comments');

                        //let's now refresh the token so that it cannot be reused
                        $new_token = $this->_refresh_token($token_obj->token);
                        echo $new_token;

                    }
                }

            }

        }

    }

    function _refresh_token($old_token) {
        //generate a new token string
        $this->module('trongate_tokens');
        $data['old_token'] = $old_token;
        $data['token'] = $this->trongate_tokens->_generate_rand_str();
        $data['expiry_date'] = $this->_calc_expiry_date();
        $sql = 'update trongate_tokens set token = :token, expiry_date = :expiry_date where token = :old_token';
        $this->model->query_bind($sql, $data);
        return $data['token'];
    }

    function _calc_expiry_date() {
        $expiry_date = time()+3600; //token expires in one hour
        return $expiry_date;        
    }

    function _insert_comment($comment) {
        $data['comment'] = $comment;
        $data['date_created'] = time();
        $this->model->insert($data, 'comments');
        echo 'Finished.';
    }

    function get() {
        $post = file_get_contents('php://input');
        $decoded = json_decode($post, true);
        $token = $decoded['token'];
        
        $this->module('trongate_tokens');
        $token_obj = $this->trongate_tokens->_fetch_token_obj($token);

        if ($token_obj == false) {
            die(); //invalid token
        } else {

            $information = json_decode($token_obj->information);

            if (isset($information->tables)) {
                $tables = get_object_vars($information->tables);

                if (isset($tables['comments'])) {
                    $table_permissions = $tables['comments'];

                    $sql = 'select * from comments where target_table = :target_table and update_id = :update_id order by date_created';
                    $query_data['target_table'] = $decoded['target_table'];
                    $query_data['update_id'] = $decoded['update_id'];
                    $comments = $this->model->query_bind($sql, $query_data, 'object');

                    foreach ($comments as $comment) {
                        $row_data['comment'] = $comment->comment;
                        $row_data['date_created'] = date('l jS \of F Y \a\t h:i:s A', $comment->date_created);
                        $data[] = $row_data;
                    }

                    echo json_encode($data);
                }

            }

        }        
    }

}