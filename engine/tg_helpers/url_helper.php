<?php
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
 * Retrieves the last part of a string based on a delimiter.
 *
 * @param string $str The input string to retrieve the last part from.
 * @param string $delimiter The delimiter used to split the string (default: '-').
 * @return string The last part of the input string.
 */
function get_last_part(string $str, string $delimiter = '-'): string {
    if (strpos($str, $delimiter) !== false) {
        $parts = explode($delimiter, $str);
        $last_part = end($parts);
    } else {
        $last_part = $str;
    }
    return $last_part;
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

    $text_type = gettype($text);

    if ($text_type === 'boolean') {
        return $target_url;
    }

    $extra = '';
    if (isset($attributes)) {

        if (isset($attributes['rewrite_url'])) {
            unset($attributes['rewrite_url']);
        } else {
            $target_url = attempt_return_nice_url($target_url);
        }

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
