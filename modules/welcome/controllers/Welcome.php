<?php
class Welcome extends Trongate {
 
    function index() {
        $data['view_module'] = 'welcome';
        $data['view_file'] = 'welcome';
        $this->template('public', $data);
    }

}