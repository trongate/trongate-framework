<?php
/**
 * Security access control class for managing scenario-based authorization.
 * Routes authorization requests to appropriate modules with token validation.
 * Provides centralized access control with configurable security scenarios.
 */
class Trongate_security extends Trongate {
    
    /**
     * Class constructor.
     *
     * Prevents direct URL access to the security module while allowing
     * internal security checks via application code.
     *
     * @param string|null $module_name The module name (auto-provided by framework)
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url($this->module_name);
    }
    
    /**
     * Ensures the user is allowed access for the specified scenario.
     *
     * @param string $scenario The scenario for access control. Default is 'admin panel'.
     * @param array $params (Optional) Additional parameters for more granular control.
     * @return mixed The return type and value depend on the scenario and implementation.
     *               May include tokens, user objects, arrays, booleans, or other data types.
     * @note Some scenarios may terminate script execution (via die/exit) instead of returning.
     */
    public function make_sure_allowed(string $scenario = 'admin panel', array $params = []): mixed {

        switch ($scenario) {
            // case 'members area':
            //     $result = $this->members->make_sure_allowed($scenario, $params);
            //     break;
            default:
                // Admin panel access (default scenario)
                $result = $this->trongate_administrators->make_sure_allowed();
                break;
        }
        
        return $result;
    }

}