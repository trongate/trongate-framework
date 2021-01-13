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
        $username = input('username');
        $username = str_replace(' ', '[SPACE]', $username);
        echo $username;
    }

}