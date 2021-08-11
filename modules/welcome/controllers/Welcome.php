<?php
class Welcome extends Trongate {
 
    function index() {
        $this->view('welcome');
    }

    function greeting() {
        $data['name'] = 'David Connelly';
        $this->view('greeting', $data);
    }

}