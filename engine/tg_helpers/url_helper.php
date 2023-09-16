<?php

declare(strict_types=1);

function segment($num, $var_type = null)
{
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

function remove_query_string($string)
{
    $parts = explode('?', $string, 2);

    return $parts[0];
}

function previous_url()
{
    if (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = '';
    }

    return $url;
}

function current_url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

function redirect($target_url): void
{
    $str = substr($target_url, 0, 4);
    if ($str !== 'http') {
        $target_url = BASE_URL.$target_url;
    }

    header('location: '.$target_url);
    exit;
}

function anchor($target_url, $text, $attributes = null, $additional_code = null)
{
    $str = substr($target_url, 0, 4);
    if ($str !== 'http') {
        $target_url = BASE_URL.$target_url;
    }

    $target_url = attempt_return_nice_url($target_url);

    $text_type = gettype($text);

    if ($text_type === 'boolean') {
        return $target_url;
    }

    $extra = '';
    if (isset($attributes)) {
        foreach ($attributes as $key => $value) {
            $extra .= ' '.$key.'="'.$value.'"';
        }
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<a href="'.$target_url.'"'.$extra.'>'.$text.'</a>';
}

/**
 * Truncates a string to a specified maximum length.
 *
 * @param  string  $value The input string to be truncated.
 * @param  int  $max_length The maximum length of the truncated string.
 *
 * @return string The truncated string with an ellipsis (...) if necessary.
 */
function truncate_str(string $value, int $max_length): string
{
    if (strlen($value) <= $max_length) {
        return $value;
    }
    return substr($value, 0, $max_length).'...';
}

/**
 * Format a number as a price with commas and optional currency symbol.
 *
 * @param  float  $num The number to be formatted.
 * @param  string|null  $currency_symbol The optional currency symbol to be added.
 *
 * @return string|float The formatted nice price.
 */
function nice_price(float $num, ?string $currency_symbol = null): string|float
{
    $num = number_format($num, 2);
    $nice_price = str_replace('.00', '', $num);

    if (isset($currency_symbol)) {
        $nice_price = $currency_symbol.$nice_price;
    }

    return $nice_price;
}

/**
 * It takes a string, converts it to lowercase, replaces all non-alphanumeric characters with a dash,
 * and trims any leading or trailing dashes.
 *
 * @author Special thanks to framex who posted this fix on the help-bar
 *
 * @see https://trongate.io/help_bar/thread/h7W9QyPcsx69
 *
 * @param string value The string to be converted.
 * @param bool transliteration If you want to transliterate the string, set this to true.
 *
 * @return string The slugified version of the string.
 */
function url_title($value, $transliteration = true): string
{
    if (extension_loaded('intl') && $transliteration === true) {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
        $value = $transliterator->transliterate($value);
    }
    $slug = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
    $slug = trim($slug, '- ');
    return strtolower($slug);
}

function return_file_info($file_string)
{
    // Get the file extension
    $file_extension = pathinfo($file_string, PATHINFO_EXTENSION);
    // Get the file name without the extension
    $file_name = str_replace('.'.$file_extension, '', $file_string);

    // Return an array containing the file name and file extension
    return ['file_name' => $file_name, 'file_extension' => '.'.$file_extension];
}

function api_auth(): void
{
    //find out where the api.json file lives
    $validation_complete = false;
    $target_url = str_replace(BASE_URL, '', current_url());
    $segments = explode('/', $target_url);

    if (isset($segments[0]) && (isset($segments[1]))) {
        $current_module_bits = explode('-', $segments[0]);
        $current_module = $current_module_bits[0];
        $filepath = APPPATH.'modules/'.$current_module.'/assets/api.json';

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
                        if (! is_numeric(strpos($value, '{'))) {
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

                        $api_class_location = APPPATH.'engine/Api.php';

                        if (file_exists($api_class_location)) {
                            include_once $api_class_location;
                            $api_helper = new Api();
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
        echo 'Invalid token.';
        exit;
    }
}

function make_rand_str($length = 32, $uppercase = false)
{
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

function json($data, $kill_script = null): void
{
    echo '<pre>'.json_encode($data, JSON_PRETTY_PRINT).'</pre>';

    if (isset($kill_script)) {
        exit;
    }
}

function ip_address()
{
    return $_SERVER['REMOTE_ADDR'];
}
