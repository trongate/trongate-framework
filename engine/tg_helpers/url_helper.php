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
 * Generates an anchor (<a>) HTML tag.
 *
 * This function creates a complete HTML anchor tag with the given URL, optional link text, optional attributes,
 * and additional code to be inserted inside the anchor tag. If no text is provided, the URL is used as the text.
 * If $text is explicitly false, an empty anchor text is used.
 *
 * @param string $target_url The target URL for the anchor tag. This is the destination for the link.
 * @param string|null|false $text The text to display for the anchor tag. If empty or not provided, the URL is used as the text.
 *                               If false, no text is displayed.
 * @param array|null $attributes Optional associative array of HTML attributes (e.g., ['class' => 'my-class', 'onclick' => 'return confirm("Are you sure?")']).
 * @param string|null $additional_code Optional additional HTML code to insert inside the anchor tag.
 *
 * @return string The generated anchor (<a>) HTML tag or an empty string if the URL is invalid.
 *                If an invalid URL is provided, it will be logged using PHP's error_log.
 */
function anchor(string $target_url, string|null|false $text = '', ?array $attributes = null, ?string $additional_code = null): string {
    // Sanitize and validate URL
    $target_url = filter_var(
        trim($target_url),
        FILTER_SANITIZE_URL
    );

    // Check if URL is valid or internal path
    if (!filter_var($target_url, FILTER_VALIDATE_URL) && !str_starts_with($target_url, '/')) {
        error_log("Invalid URL provided in anchor function: " . $target_url);
        return ''; // or '<!-- Invalid URL: ' . htmlspecialchars($target_url) . ' -->' for debugging
    }

    // Check if URL is absolute
    $is_absolute = preg_match('#^([a-z]+:)?//#i', $target_url);
    
    // Normalize URL
    if (!$is_absolute && !str_starts_with($target_url, '/')) {
        $target_url = BASE_URL . ltrim($target_url, '/');
    }

    // Handle link text
    $link_text = ($text === false) ? '' : 
                 (empty($text) ? $target_url : 
                  htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8'));

    // Start building the anchor tag
    $html = '<a href="' . htmlspecialchars($target_url, ENT_QUOTES, 'UTF-8') . '"';

    // Add security attributes for external links
    if ($is_absolute) {
        $external_attrs = ['rel' => 'noopener noreferrer', 'target' => '_blank'];
        $attributes = is_array($attributes) 
            ? array_merge($external_attrs, $attributes)
            : $external_attrs;
    }

    // Add attributes if they are provided
    if (is_array($attributes)) {
        foreach ($attributes as $key => $value) {
            $html .= ' ' . htmlspecialchars(trim($key), ENT_QUOTES, 'UTF-8') . 
                    '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    // Close the opening tag, add the link text, and any additional code
    $html .= '>' . $link_text;
    
    if ($additional_code !== null) {
        $html .= trim($additional_code);
    }

    $html .= '</a>';
    return $html;
}