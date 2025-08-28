<?php
class Trongate_security extends Trongate {

    /**
     * Ensures the user is allowed access for the specified scenario.
     *
     * @param string $scenario The scenario for access control. Default is 'admin panel'.
     * @param array $params (Optional) Additional parameters for more granular control.
     * @return mixed The return type and value depend on the scenario and implementation.
     *               May include tokens, user objects, arrays, booleans, or other data types.
     * @note Some scenarios may terminate script execution (via die/exit) instead of returning.
     */
    public function _make_sure_allowed(string $scenario = 'admin panel', array $params = []): mixed {
        switch ($scenario) {
            case 'fetch comments':
                $this->module('trongate_comments');
                $result = $this->trongate_comments->_make_sure_allowed('fetch comments', $params);
                break;
            case 'upsert comment':
                $this->module('trongate_comments');
                $result = $this->trongate_comments->_make_sure_allowed('upsert comment', $params);
                break;
            case 'delete comment':
                $this->module('trongate_comments');
                $result = $this->trongate_comments->_make_sure_allowed('delete comment', $params);
                break;
            default:
                // Default case - delegate to trongate_administrators
                $this->module('trongate_administrators');
                $result = $this->trongate_administrators->_make_sure_allowed($scenario, $params);
                break;
        }
        
        return $result;
    }

}