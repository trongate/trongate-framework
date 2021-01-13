<?php
class Api extends Trongate {

    function __construct() {
        $this->parent_module = 'books';
        $this->child_module = 'api';
    }

    function hello() {
        echo 'hello from api';
    }

    function goodbye($output) {
        echo 'goodbye from API';
    }

    function __destruct() {
        $this->parent_module = '';
        $this->child_module = '';
    }

}