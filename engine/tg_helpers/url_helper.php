<?php
/**
 * Get a specific URL segment.
 *
 * @param int $num The segment number to retrieve.
 * @param string|null $var_type (Optional) The desired data type of the segment value. Default is null.
 * @return mixed The value of the specified URL segment.
 */
function segment(int $num, ?string $var_type = null) {
    $segments = SEGMENTS;
    if (isset($segments[$num])) {
        $value = $segments[$num];
    } else {
        $value = '';
    }

    if (isset($var_type)) {
        settype($value, $var_type);
    }

    return $value;
}

/**
 * Remove query string from a URL.
 *
 * @param string $string The URL with a query string to be processed.
 * @return string The URL without the query string.
 */
function remove_query_string(string $string): string {
    $parts = explode("?", $string, 2);
    return $parts[0];
}

/**
 * Get the URL of the previous page, if available.
 *
 * @return string The URL of the previous page as a string.
 */
function previous_url(): string {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = '';
    }
    return $url;
}

/**
 * Get the current URL of the web page.
 *
 * @return string The current URL as a string.
 */
function current_url(): string {
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];
    return $current_url;
}

/**
 * Perform an HTTP redirect to the specified URL.
 *
 * @param string $target_url The URL to which the redirect should occur.
 * @return void
 */
function redirect(string $target_url): void {
    $str = substr($target_url, 0, 4);
    if ($str != 'http') {
        $target_url = BASE_URL . $target_url;
    }

    header('location: ' . $target_url);
    die();
}

/**
 * Generate an HTML anchor (link) element.
 *
 * @param string $target_url The URL to link to.
 * @param mixed $text The link text or boolean value to indicate no link.
 * @param array|null $attributes (Optional) An associative array of HTML attributes for the anchor element.
 * @param string|null $additional_code (Optional) Additional HTML code to append to the anchor element.
 * @return string The HTML anchor element as a string.
 */
function anchor(string $target_url, $text, ?array $attributes = null, ?string $additional_code = null): string {
    $str = substr($target_url, 0, 4);
    if ($str != 'http') {
        $target_url = BASE_URL . $target_url;
    }

    $target_url = attempt_return_nice_url($target_url);

    $text_type = gettype($text);

    if ($text_type === 'boolean') {
        return $target_url;
    }

    $extra = '';
    if (isset($attributes)) {
        foreach ($attributes as $key => $value) {
            $extra .= ' ' . $key . '="' . $value . '"';
        }
    }

    if (isset($additional_code)) {
        $extra .= ' ' . $additional_code;
    }

    $link = '<a href="' . $target_url . '"' . $extra . '>' . $text . '</a>';
    return $link;
}

/**
 * Truncates a string to a specified maximum length.
 *
 * @param string $value The input string to be truncated.
 * @param int $max_length The maximum length of the truncated string.
 * @return string The truncated string with an ellipsis (...) if necessary.
 */
function truncate_str(string $value, int $max_length): string {
    if (strlen($value) <= $max_length) {
        return $value;
    } else {
        return substr($value, 0, $max_length) . '...';
    }
}

/**
 * Format a number as a price with commas and optional currency symbol.
 *
 * @param float $num The number to be formatted.
 * @param string|null $currency_symbol The optional currency symbol to be added.
 * @return string|float The formatted nice price.
 */
function nice_price(float $num, ?string $currency_symbol = null): string|float {
    $num = number_format($num, 2);
    $nice_price = str_replace('.00', '', $num);

    if (isset($currency_symbol)) {
        $nice_price = $currency_symbol . $nice_price;
    }

    return $nice_price;
}

/**
 * It takes a string, converts it to lowercase, replaces all non-alphanumeric characters with a dash,
 * and trims any leading or trailing dashes.
 * 
 * @author Special thanks to framex who posted this fix on the help-bar
 * @see https://trongate.io/help_bar/thread/h7W9QyPcsx69
 * 
 * @param string value The string to be converted.
 * @param bool transliteration If you want to transliterate the string, set this to true.
 * 
 * @return string The slugified version of the string.
 */
function url_title($value, $transliteration = true) {
    if (extension_loaded('intl') && $transliteration === true) {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
        $value = $transliterator->transliterate($value);
    }
    $slug = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
    $slug = trim($slug, '- ');
    $slug = strtolower($slug);
    return $slug;
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
 * Generate a random string of characters.
 *
 * @param int $length (Optional) The length of the random string. Default is 32.
 * @param bool $uppercase (Optional) Whether to use uppercase characters. Default is false.
 * @return string The randomly generated string.
 */
function make_rand_str(int $length = 32, bool $uppercase = false): string {
    $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomByte = random_bytes(1);
        $randomInt = ord($randomByte) % $charactersLength;
        $randomString .= $characters[$randomInt];
    }
    return $uppercase ? strtoupper($randomString) : $randomString;
}

/**
 * Safely escape and format a string for various output contexts.
 *
 * @param string $input The string to be escaped.
 * @param string $encoding (Optional) The character encoding to use for escaping. Defaults to 'UTF-8'.
 * @param string $output_format (Optional) The desired output format: 'html' (default), 'xml', 'json', 'javascript', or 'attribute'.
 * 
 * @return string The escaped and formatted string ready for safe inclusion in the specified context.
 * @throws Exception if an unsupported output format is provided.
 */
function out(string $input, string $encoding = 'UTF-8', string $output_format = 'html'): string {
    $flags = ENT_QUOTES;
    
    if ($output_format === 'xml') {
        $flags = ENT_XML1;
    } elseif ($output_format === 'json') {
        // Customize JSON escaping as needed
        $input = json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $flags = ENT_NOQUOTES;
    } elseif ($output_format === 'javascript') {
        // JavaScript-encode the input
        $input = json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    } elseif ($output_format === 'attribute') {
        // Escape for HTML attributes
        $flags = ENT_QUOTES;
    } else {
        // Dynamically choose the right function
        $input = ($output_format === 'html') ? htmlspecialchars($input, $flags, $encoding) : htmlentities($input, $flags, $encoding);
        return $input;
    }
    
    return htmlspecialchars($input, $flags, $encoding);
}

/**
 * Encode data as JSON and optionally display it with preformatted HTML.
 *
 * @param mixed $data The data to be encoded as JSON.
 * @param bool|null $kill_script (Optional) If true, terminate the script after displaying the JSON. Default is null.
 * @return void
 */
function json($data, ?bool $kill_script = null): void {
    echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre';

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