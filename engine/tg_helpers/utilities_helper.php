<?php
/**
 * Blocks direct browser access to a module or a specific module-method combination.
 *
 * This helper function prevents certain code from being invoked via URL access while 
 * allowing unrestricted internal PHP calls. It compares the current URL segments 
 * against the supplied target path and returns a 403 Forbidden response if a match is found.
 *
 * Usage:
 *  - block_url('module') → blocks all methods in the module
 *  - block_url('module/method') → blocks only that specific method
 *
 * @param string $block_path The module or module/method string to block.
 * @return void
 */
function block_url(string $block_path = ''): void {
    if ($block_path === '') {
        return; // Nothing to block
    }

    // Split into module and optional method
    $bits = explode('/', $block_path);
    $target_module = $bits[0];
    $target_method = $bits[1] ?? '';

    if ($target_method === '') {
        // Block entire module
        if (segment(1) === $target_module) {
            http_response_code(403);
            die('403 Forbidden - Direct URL access not permitted');
        }
    } else {
        // Block specific method
        if (segment(1) === $target_module && segment(2) === $target_method) {
            http_response_code(403);
            die('403 Forbidden - Direct URL access not permitted');
        }
    }
}

/**
 * Outputs the given data as JSON in a prettified format, suitable for debugging and visualization.
 * This function is especially useful during development for inspecting data structures in a readable JSON format directly in the browser. 
 * It optionally allows terminating the script immediately after output, useful in API development for stopping further processing.
 *
 * @param mixed $data The data (e.g., array or object) to encode into JSON format. The data can be any type that is encodable into JSON.
 * @param bool|null $kill_script Optionally, whether to terminate the script after outputting the JSON. 
 *                                If true, the script execution is halted immediately after the JSON output.
 *                                Default is null, which means the script continues running unless specified otherwise.
 * @return void Does not return any value; the output is directly written to the output buffer.
 */
function json($data, ?bool $kill_script = null): void {
    echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';

    if (isset($kill_script)) {
        die();
    }
}

/**
 * Get the client's IP address.
 *
 * @return string The client's IP address.
 */
function ip_address(): string {
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Display content view within a template
 * 
 * @param array $data Data containing view_module and view_file
 * @return void
 */
function display(array $data): void {
    // Auto-detect view_module from URL if not provided
    if (!isset($data['view_module'])) {
        $data['view_module'] = segment(1) ?? 'welcome';
    }
    
    // Default view_file if not provided
    if (!isset($data['view_file'])) {
        $data['view_file'] = 'index';
    }
    
    // Build path to content view
    $content_view_path = APPPATH . "modules/{$data['view_module']}/views/{$data['view_file']}.php";
    
    // Check if view exists
    if (!file_exists($content_view_path)) {
        echo "<div style='color: red; padding: 1rem; border: 2px solid red;'>";
        echo "<h2>View Not Found</h2>";
        echo "<p>Looking for: <code>{$content_view_path}</code></p>";
        echo "</div>";
        return;
    }
    
    // Extract data and include view
    extract($data);
    require $content_view_path;
}

/**
 * Extract file name and extension from a given file path.
 *
 * @param string $file_string The file path from which to extract information.
 * @return array An associative array containing the 'file_name' and 'file_extension'.
 */
function return_file_info(string $file_string): array {
    // Get the file extension
    $file_extension = pathinfo($file_string, PATHINFO_EXTENSION);
    // Get the file name without the extension
    $file_name = str_replace("." . $file_extension, "", $file_string);
    // Return an array containing the file name and file extension
    return array("file_name" => $file_name, "file_extension" => "." . $file_extension);
}

/**
 * Sorts an array of associative arrays by a specified property.
 *
 * @param array $array The array to be sorted.
 * @param string $property The property by which to sort the array.
 * @param string $direction The direction to sort ('asc' for ascending, 'desc' for descending). Default is 'asc'.
 * @return array The sorted array.
 */
function sort_by_property(array &$array, string $property, string $direction = 'asc'): array {
    usort($array, function($a, $b) use ($property, $direction) {
        // Determine the comparison method based on the property type
        if (is_string($a[$property])) {
            $result = strcasecmp($a[$property], $b[$property]);
        } else {
            $result = $a[$property] <=> $b[$property];
        }
        
        return ($direction === 'desc') ? -$result : $result;
    });
    return $array;
}

/**
 * Sorts an array of objects by a specified property.
 *
 * @param array $array The array of objects to be sorted.
 * @param string $property The property by which to sort the objects.
 * @param string $direction (Optional) The direction of sorting ('asc' or 'desc'). Defaults to 'asc'.
 * @return array The sorted array of objects.
 */
function sort_rows_by_property(array $array, string $property, string $direction = 'asc'): array {
    usort($array, function($a, $b) use ($property, $direction) {
        // Determine the comparison method based on the property type
        if (is_string($a->$property)) {
            $result = strcasecmp($a->$property, $b->$property);
        } else {
            $result = $a->$property <=> $b->$property;
        }
        
        return ($direction === 'desc') ? -$result : $result;
    });
    return $array;
}

/**
 * Checks if the HTTP request has been invoked by Trongate MX.
 *
 * @return bool True if the request has the X-Trongate-MX header set to 'true', otherwise false.
 */
function from_trongate_mx(): bool {
    if (isset($_SERVER['HTTP_TRONGATE_MX_REQUEST'])) {
        return true;
    } else {
        return false;
    }
}