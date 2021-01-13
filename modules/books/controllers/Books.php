<?php
class Books extends Trongate {

    function test() {
        $name = segment(3);
        echo $name;
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