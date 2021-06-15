<?php
class Books extends Trongate {

    function test() {
        $name = segment(3);
        $rows = $this->model->get('id');
        //var_dump($rows);
    }

    function goodbye($output) {
        echo 'goodbye from books';
        return $output;
    }

    function create() {
        $this->view('create');
    }

    function submit() {
        $username = input('username', true);
        $username = str_replace(' ', '[SPACE]', $username);
        echo $username;
    }

}