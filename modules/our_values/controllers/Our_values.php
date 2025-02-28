<?php

class Our_values extends Trongate {

    public function index(): void
    {
        $this->template('public', [
            'view_module' => 'our_values',
            'view_file' => 'our_values'
        ]);
    }
}