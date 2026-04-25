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
    Modules::run('utilities/block_url', $block_path);
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
    $params = [
        'data' => $data,
        'kill_script' => $kill_script
    ];
    Modules::run('utilities/json', $params);
}

/**
 * Get the client's IP address.
 *
 * @return string The client's IP address.
 */
function ip_address(): string {
    return Modules::run('utilities/ip_address');
}

/**
 * Display content view within a template
 *
 * @param array $data Data containing view_module and view_file
 * @return void
 */
function display(array $data): void {
    Modules::run('utilities/display', $data);
}

/**
 * Extract file name and extension from a given file path.
 *
 * @param string $file_string The file path from which to extract information.
 * @return array An associative array containing the 'file_name' and 'file_extension'.
 */
function return_file_info(string $file_string): array {
    return Modules::run('utilities/return_file_info', $file_string);
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
    $params = [
        'array' => $array,
        'property' => $property,
        'direction' => $direction
    ];
    return Modules::run('utilities/sort_by_property', $params);
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
    $params = [
        'array' => $array,
        'property' => $property,
        'direction' => $direction
    ];
    return Modules::run('utilities/sort_rows_by_property', $params);
}

/**
 * Checks if the HTTP request has been invoked by Trongate MX.
 *
 * @return bool True if the request has the X-Trongate-MX header set to 'true', otherwise false.
 */
function from_trongate_mx(): bool {
    return Modules::run('utilities/from_trongate_mx');
}
