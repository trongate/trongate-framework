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
 * Retrieve a segment from the current URL path, with optional type casting.
 *
 * This function fetches a specific segment from the URL based on its
 * 1-based position. URL segments are not implicitly injected into controller
 * methods; they must be explicitly retrieved using this helper.
 *
 * Example URL:
 * example.com/users/profile/123
 *
 * Segment values:
 * - segment(1) returns "users"
 * - segment(2) returns "profile"
 * - segment(3) returns "123"
 *
 * Optional type casting is performed using PHP's native settype() function,
 * allowing input to be coerced at the point of retrieval. This encourages
 * explicit handling, improves clarity, and strengthens security by ensuring
 * predictable data types.
 *
 * Supported types include:
 * "int", "integer", "float", "double",
 * "string", "bool", "boolean",
 * "array", "object", "null"
 *
 * @param int $num
 * The 1-based index of the URL segment to retrieve.
 *
 * @param string|null $var_type
 * Optional. A PHP data type to cast the segment value to.
 * If null, the segment is returned as a string.
 *
 * @return mixed
 * The value of the requested URL segment, cast to the specified type
 * if provided. Returns an empty string if the segment does not exist.
 */
function segment(int $num, ?string $var_type = null): mixed {
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
 * * If the provided target URL does not start with 'http', it is assumed
 * to be an internal route, and the BASE_URL is automatically prepended.
 *
 * @param string $target_url The URL or internal route to redirect to.
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
 * @return string The URL of the previous page or an empty string.
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
 * Generates an HTML anchor tag with optional URL resolution and XSS protection.
 *
 * This function creates an anchor tag (<a>). If the URL is relative (does not 
 * start with 'http://', 'https://', or '//'), the BASE_URL constant is 
 * prepended to ensure the link points to the correct internal route.
 *
 * Attributes and URLs are automatically escaped to prevent XSS attacks.
 *
 * @param string $url The destination URL. Can be relative or absolute.
 * @param string|null $text The visible link text. If null, the URL is used.
 * @param array $attributes Associative array of HTML attributes (e.g., ['class' => 'btn']).
 * @return string The generated HTML anchor tag.
 */
function anchor(string $url, ?string $text = null, array $attributes = []): string {
    // Determine if the URL is absolute or relative
    if (preg_match('/^(https?:\/\/|\/\/)/i', $url)) {
        $full_url = $url;
    } else {
        $full_url = BASE_URL . $url;
    }

    // Escape the full URL for attribute safety
    $escaped_url = htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8');

    // Use provided text or fallback to URL
    $text_to_use = $text ?? $url;

    // Build the attributes string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $escaped_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $attr_string .= ' ' . $key . '="' . $escaped_value . '"';
    }

    $tag = '<a href="' . $escaped_url . '"' . $attr_string . '>' . $text_to_use . '</a>';
    return $tag;
}