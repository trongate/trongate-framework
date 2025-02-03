<?php
class Trongate_security extends Trongate {

    /**
     * Ensures the user is allowed access for the specified scenario.
     *
     * @param string $scenario The scenario for access control. Default is 'admin panel'.
     * @param array $params (Optional) Additional parameters for more granular control.
     * @return string Returns a Trongate token or initializes the 'not allowed' procedure.
     */
    public function _make_sure_allowed(string $scenario = 'admin panel', array $params = []): string {
        // Returns either a Trongate token or initializes the 'not allowed' procedure.

        switch ($scenario) {
                // case 'members area':
                //     $this->module('members');
                //     $token = $this->members->_make_sure_allowed();
                //     break;
            default:
                $this->module('trongate_administrators');
                $token = $this->trongate_administrators->_make_sure_allowed($scenario, $params);
                break;
        }

        return $token;
    }
}