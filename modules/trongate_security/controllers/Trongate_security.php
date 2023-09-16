<?php

declare(strict_types=1);

class Trongate_security extends Trongate
{
    public function _make_sure_allowed($scenario = 'admin panel')
    {
        //returns EITHER (trongate)token OR initialises 'not allowed' procedure

        switch ($scenario) {
            // case 'members area':
            //     $this->module('members');
            //     $token = $this->members->_make_sure_allowed();
            //     break;
            default:
                $this->module('trongate_administrators');
                $token = $this->trongate_administrators->_make_sure_allowed();
                break;
        }

        return $token;
    }
}
