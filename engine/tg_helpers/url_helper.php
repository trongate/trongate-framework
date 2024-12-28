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
 * Generates an anchor (<a>) tag with optional attributes and XSS protection.
 *
 * This function creates an anchor tag (`<a>`) with a given URL and text. 
 * The text is HTML-escaped unless it contains HTML content (e.g., Font Awesome icons).
 * The URL is always escaped to prevent XSS. Optional attributes (such as `rel` for external links) can be provided.
 *
 * @param string $url The URL to link to. This is a required parameter.
 * @param string|null $text The link's inner text (optional). If null, the URL is used as the text.
 * @param array $attributes An optional associative array of attributes (e.g., ['rel' => 'noopener noreferrer']).
 *
 * @return string The complete anchor (<a>) tag as a string.
 */
function anchor(string $url, ?string $text = null, array $attributes = []): string {
    // Default empty text is the same as URL
    if ($text === null) {
        $text = $url;
    }

    // Escape the URL to prevent XSS
    $escaped_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

    // If the text contains HTML (e.g., Font Awesome icons), don't escape it
    if (strpos($text, '<') !== false) {
        $escaped_text = $text; // Leave HTML content (icons) as is
    } else {
        $escaped_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    // Add optional attributes (e.g., rel="noopener noreferrer" for external links)
    $attr_str = '';
    if (!empty($attributes)) {
        foreach ($attributes as $key => $value) {
            $attr_str .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    // Construct the anchor tag
    return '<a href="' . $escaped_url . '"' . $attr_str . '>' . $escaped_text . '</a>';
}