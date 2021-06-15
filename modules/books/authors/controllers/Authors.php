<?php
class Authors extends Trongate {

    function __construct() {
        $this->parent_module = 'books';
        $this->child_module = 'authors';
    }

    function hello() {
        echo 'hello from authors';
    }

}