<?php

declare(strict_types=1);

class Welcome extends Trongate
{
    public function index(): void
    {
        $data['view_module'] = 'welcome';
        $data['view_file'] = 'welcome';
        $this->template('public', $data);
    }
}
