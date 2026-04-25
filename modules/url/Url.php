<?php

/**
 * Url Module - Framework Service for URL Manipulation and Routing
 * 
 * This module provides URL-related functionality as a framework service.
 * It is accessed exclusively via Service Routing from helper functions.
 * 
 * Security: Uses BASE_URL check instead of block_url() to minimize dependencies
 * and maximize performance for core framework services.
 */

// Prevent direct script access - lightweight security check
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class Url extends Trongate {

    /**
     * Get the current URL of the web page.
     *
     * @return string The current URL as a string.
     */
    public function current_url(): string {
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
     * @param array $data Array containing 'num' and 'var_type' keys.
     * @return mixed The value of the requested URL segment.
     */
    public function segment(array $data = []): mixed {
        $num = $data['num'] ?? 0;
        $var_type = $data['var_type'] ?? null;

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
    public function remove_query_string(string $string): string {
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
    public function get_last_segment(): string {
        $current_url = $this->current_url();
        // Remove query string first
        $url_without_query = $this->remove_query_string($current_url);
        // Get the last part of the path
        $last_segment = $this->get_last_part($url_without_query, '/');
        return $last_segment;
    }

    /**
     * Perform an HTTP redirect to the specified URL.
     *
     * If the provided target URL does not start with 'http', it is assumed
     * to be an internal route, and the BASE_URL is automatically prepended.
     *
     * @param string $target_url The URL or internal route to redirect to.
     * @return void
     */
    public function redirect(string $target_url): void {
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
    public function previous_url(): string {
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
     * @param array $data Array containing 'url', 'text', and 'attributes' keys.
     * @return string The generated HTML anchor tag.
     */
    public function anchor(array $data = []): string {
        $url = $data['url'] ?? '';
        $text = $data['text'] ?? null;
        $attributes = $data['attributes'] ?? [];

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

    /**
     * Helper method to get the last part of a string using a delimiter.
     * 
     * @param string $str The input string.
     * @param string $delimiter The delimiter to use.
     * @return string The last part of the string.
     */
    private function get_last_part(string $str, string $delimiter = '-'): string {
        if (strpos($str, $delimiter) !== false) {
            $parts = explode($delimiter, $str);
            return end($parts);
        }
        return $str;
    }
}
