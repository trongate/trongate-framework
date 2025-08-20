<?php

/**
 * Module_path Helper Class
 * 
 * Centralized logic for resolving module paths and handling parent-child module structures.
 */
class Module_path {

    /**
     * Resolve module path information from a module token.
     *
     * @param string $module_token The module identifier (e.g., 'users' or 'shop-products').
     * @return array Associative array with resolved path information.
     */
    public static function resolve(string $module_token): array {
        $controller_class = ucfirst($module_token);
        $standard_path = '../modules/' . $module_token . '/controllers/' . $controller_class . '.php';

        // Check if it's a standard module first
        if (file_exists($standard_path)) {
            return [
                'type' => 'standard',
                'module_name' => $module_token,
                'controller_class' => $controller_class,
                'controller_path' => $standard_path,
                'access_key' => $module_token
            ];
        }

        // Try child module parsing
        $bits = explode('-', $module_token);
        if (count($bits) === 2 && strlen($bits[1]) > 0) {
            $parent_module = strtolower($bits[0]);
            $child_module = strtolower($bits[1]);
            $child_controller_class = ucfirst($child_module);
            $child_path = '../modules/' . $parent_module . '/' . $child_module . '/controllers/' . $child_controller_class . '.php';

            if (file_exists($child_path)) {
                return [
                    'type' => 'child',
                    'parent_module' => $parent_module,
                    'child_module' => $child_module,
                    'module_name' => $module_token,
                    'controller_class' => $child_controller_class,
                    'controller_path' => $child_path,
                    'access_key' => $child_module
                ];
            }
        }

        // Module not found
        return [
            'type' => 'not_found',
            'module_name' => $module_token,
            'attempted_paths' => [
                $standard_path,
                isset($child_path) ? $child_path : null
            ]
        ];
    }

    /**
     * Check if a module token represents a child module format.
     *
     * @param string $module_token The module identifier.
     * @return bool True if it's a child module format.
     */
    public static function is_child_module_format(string $module_token): bool {
        $bits = explode('-', $module_token);
        return count($bits) === 2 && strlen($bits[0]) > 0 && strlen($bits[1]) > 0;
    }

    /**
     * Extract access key from module token (for property access).
     *
     * @param string $module_token The module identifier.
     * @return string The key to use for property access.
     */
    public static function get_access_key(string $module_token): string {
        if (self::is_child_module_format($module_token)) {
            return explode('-', $module_token)[1];
        }
        return $module_token;
    }

}