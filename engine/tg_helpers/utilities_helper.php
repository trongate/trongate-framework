<?php
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
 * Authenticate API requests and validate access based on API rules.
 *
 * This function validates API requests and ensures access based on defined API rules in 'api.json' files.
 *
 * @return void
 */
function api_auth(): void {
    //find out where the api.json file lives
    $validation_complete = false;
    $target_url = str_replace(BASE_URL, '', current_url());
    $segments = explode('/', $target_url);

    if ((isset($segments[0])) && (isset($segments[1]))) {
        $current_module_bits = explode('-', $segments[0]);
        $current_module = $current_module_bits[0];
        $filepath = APPPATH . 'modules/' . $current_module . '/assets/api.json';

        if (file_exists($filepath)) {
            //extract the rules for the current path
            $target_method = $segments[1];
            $settings = file_get_contents($filepath);
            $endpoints = json_decode($settings, true);

            $current_uri_path = str_replace(BASE_URL, '', current_url());
            $current_uri_bits = explode('/', $current_uri_path);

            foreach ($endpoints as $rule_name => $api_rule_value) {

                if (isset($api_rule_value['url_segments'])) {

                    //make sure the current URL segments match against the required segments
                    $target_url_segments = $api_rule_value['url_segments'];
                    $bits = explode('/', $target_url_segments);
                    $required_segments = [];

                    foreach ($bits as $key => $value) {
                        if (!is_numeric(strpos($value, '{'))) {
                            $required_segments[$key] = $value;
                        }
                    }

                    $num_required_segments = count($required_segments);

                    foreach ($current_uri_bits as $key => $value) {
                        if (isset($required_segments[$key])) {
                            if ($value === $required_segments[$key]) {
                                $num_required_segments--;
                            }
                        }
                    }

                    if ($num_required_segments === 0) {
                        $token_validation_data['endpoint'] = $rule_name;
                        $token_validation_data['module_name'] = $current_module;
                        $token_validation_data['module_endpoints'] = $endpoints;

                        $api_class_location = APPPATH . 'engine/Api.php';

                        if (file_exists($api_class_location)) {
                            include_once $api_class_location;
                            $api_helper = new Api;
                            $api_helper->validate_token($token_validation_data);
                            $validation_complete = true;
                        }
                    }

                    if (isset($required_segments)) {
                        unset($required_segments);
                    }
                }
            }
        }
    }

    if ($validation_complete === false) {
        http_response_code(401);
        echo "Invalid token.";
        die();
    }
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
 * Loads a template file with optional data for use within the template.
 *
 * @param string $template_file The filename of the template to load.
 * @param array|null $data (Optional) The data to be passed to the template as an associative array. Defaults to null.
 * 
 * @return void
 */
function load(string $template_file, ?array $data = null): void {
    // Attempt load template view file
    if (isset(THEMES[$template_file])) {
        $theme_dir = THEMES[$template_file]['dir'];
        $template = THEMES[$template_file]['template'];
        $file_path = APPPATH . 'public/themes/' . $theme_dir . '/' . $template;
        define('THEME_DIR', BASE_URL . 'themes/' . $theme_dir . '/');
    } else {
        $file_path = APPPATH . 'templates/views/' . $template_file . '.php';
    }

    if (file_exists($file_path)) {
        // Extract data if provided
        if (isset($data)) {
            extract($data);
        }

        require_once($file_path);
    } else {
        die('<br><b>ERROR:</b> View file does not exist at: ' . $file_path);
    }
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