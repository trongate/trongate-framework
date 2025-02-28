<?php

class About extends Trongate {
    public function index(): void
    {
        $this->template('public', [
            'view_module' => 'about',
            'view_file' => 'about'
        ]);
    }
}