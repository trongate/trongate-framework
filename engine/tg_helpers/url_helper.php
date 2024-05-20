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
 * Get the number of segments in the current URL after the base URL.
 *
 * @return int The number of URL segments after the base URL.
 */
function get_num_segments(): int {
    $url_path = str_replace(BASE_URL, '', current_url());
    $url_segments = explode('/', $url_path);
    return count($url_segments);
}

/**
 * Get the value of the last segment of the current URL.
 *
 * @return string The last segment of the URL.
 */
function get_last_segment(): string {
    $last_segment = get_last_part(current_url(), '/');
    return $last_segment;
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
            //takes an assumed_url and returns the nice_url
            foreach (CUSTOM_ROUTES as $key => $value) {
                $pos = strpos($target_url, $value);
                if (is_numeric($pos)) {
                    $target_url = str_replace($value, $key, $target_url);
                }
            }
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
