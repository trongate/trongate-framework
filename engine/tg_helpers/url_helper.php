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
 * Generates an HTML anchor tag with the given URL, text, and attributes.
 *
 * This function creates an anchor tag (<a>) with the specified URL and text.
 * If the URL is relative (does not start with 'http://', 'https://', or '//'),
 * it prepends the BASE_URL constant to make it absolute.
 * The URL is escaped using htmlspecialchars to prevent XSS attacks.
 * The text is not escaped, allowing for HTML content.
 * Additional attributes can be provided as an associative array.
 *
 * Note: This function assumes that the BASE_URL constant is defined.
 *
 * @param string $url The URL for the anchor tag. Can be relative or absolute.
 * @param string|null $text The inner HTML of the anchor tag. If null, the original $url is used.
 * @param array $attributes Associative array of additional attributes (e.g., ['class' => 'btn']).
 * @return string The generated HTML anchor tag.
 */
function anchor(string $url, ?string $text = null, array $attributes = []): string {
    // Determine if the URL is absolute (starts with http://, https://, or //)
    if (preg_match('/^(https?:\/\/|\/\/)/i', $url)) {
        $full_url = $url; // Use the URL as is
    } else {
        $full_url = BASE_URL . $url; // Prepend BASE_URL for relative paths
    }

    // Escape the full URL for safe use in HTML attributes
    $escaped_url = htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8');

    // Use provided text, or fall back to the original $url if text is null
    $text_to_use = $text ?? $url;

    // Build the attributes string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $escaped_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $attr_string .= ' ' . $key . '="' . $escaped_value . '"';
    }

    // Construct and return the anchor tag
    $tag = '<a href="' . $escaped_url . '"' . $attr_string . '>' . $text_to_use . '</a>';
    return $tag;
}