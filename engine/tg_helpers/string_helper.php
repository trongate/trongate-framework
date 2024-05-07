<?php

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
 * Truncates a string to a specified maximum number of words.
 *
 * @param string $value The input string to be truncated.
 * @param int $max_words The maximum number of words in the truncated string.
 * @return string The truncated string with an ellipsis (...) if necessary.
 */
function truncate_words(string $value, int $max_words): string {
    $words = explode(' ', $value);

    if (count($words) <= $max_words) {
        return $value;
    } else {
        $truncated = implode(' ', array_slice($words, 0, $max_words));
        return $truncated . '...';
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
 * Converts a string into a URL-friendly slug format.
 *
 * This function will transliterate the string if the 'intl' extension is loaded and transliteration is set to true.
 * It then converts any non-alphanumeric characters to dashes, trims them from the start and end, and converts everything to lowercase.
 *
 * @param string $value The string to be converted into a slug.
 * @param bool $transliteration Whether to transliterate characters to ASCII.
 * @return string The slugified version of the input string.
 */
function url_title(string $value, bool $transliteration = true): string {
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
 * Safely escape and format a string for various output contexts.
 *
 * @param string $input The string to be escaped.
 * @param string $encoding (Optional) The character encoding to use for escaping. Defaults to 'UTF-8'.
 * @param string $output_format (Optional) The desired output format: 'html' (default), 'xml', 'json', 'javascript', or 'attribute'.
 * 
 * @return string The escaped and formatted string ready for safe inclusion in the specified context.
 * @throws Exception if an unsupported output format is provided.
 */
function out(?string $input, string $encoding = 'UTF-8', string $output_format = 'html'): string {
    $flags = ENT_QUOTES;

    if ($input === null) {
        $input = '';
    }

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
 * Convert HTML content based on given specifications.
 *
 * @param string $content The original HTML content to be converted.
 * @param array $specifications An array containing specifications for conversion.
 *                             Should include opening and closing strings.
 *                             Example: [
 *                                 'opening_string_before' => '<span class="whatever">',
 *                                 'close_string_before' => '</span>',
 *                                 'opening_string_after' => '<div>',
 *                                 'close_string_after' => '</div>'
 *                             ]
 * @return string The modified HTML content.
 */
function replace_html_tags(string $content, array $specifications): string {
    $opening_string_before = $specifications['opening_string_before'];
    $close_string_before = $specifications['close_string_before'];
    $opening_string_after = $specifications['opening_string_after'];
    $close_string_after = $specifications['close_string_after'];

    $pattern = '/' . preg_quote($opening_string_before, '/') . '(.*?)' . preg_quote($close_string_before, '/') . '/';
    $replacement = $opening_string_after . '$1' . $close_string_after;

    return preg_replace($pattern, $replacement, $content);
}

/**
 * Remove code sections from HTML content based on specified start and end patterns.
 *
 * @param string $content The original HTML content to be processed.
 * @param string $opening_pattern The pattern specifying the start of the code section to remove.
 * @param string $closing_pattern The pattern specifying the end of the code section to remove.
 * @return string The HTML content with specified code sections removed.
 */
function remove_html_code(string $content, string $opening_pattern, string $closing_pattern): string {
    $pattern = '/(' . preg_quote($opening_pattern, '/') . ')(.*?)(\s*?' . preg_quote($closing_pattern, '/') . ')/is';
    return preg_replace($pattern, '', $content);
}

/**
 * Filter and sanitize a string.
 *
 * @param string $string The input string to be filtered and sanitized.
 * @param string[] $allowed_tags An optional array of allowed HTML tags (default is an empty array).
 * @return string The filtered and sanitized string.
 */
function filter_string(string $string, array $allowed_tags = []) {
    // Remove HTML & PHP tags
    $string = strip_tags($string, implode('', $allowed_tags));

    // Convert multiple consecutive whitespaces to a single space, except for line breaks
    $string = preg_replace('/[^\S\r\n]+/', ' ', $string);

    // Trim leading and trailing white space
    $string = trim($string);

    return $string;
}

/**
 * Filter and sanitize a name.
 *
 * @param string $name The input name to be filtered and sanitized.
 * @param string[] $allowed_chars An optional array of allowed characters.
 * @return string The filtered and sanitized name.
 */
function filter_name(string $name, array $allowed_chars = []) {
    // Similar to filter_string() but better suited for usernames, etc.

    // Remove HTML & PHP tags (please read note above for more!)
    $name = strip_tags($name);

    // Apply XSS filtering
    $name = htmlspecialchars($name);

    // Create a regex pattern that includes the allowed characters
    $pattern = '/[^a-zA-Z0-9\s';
    $pattern .= !empty($allowed_chars) ? '[' . implode('', $allowed_chars) . ']' : ']';
    $pattern .= '/';

    // Replace any characters that are not in the allowed list
    $name = preg_replace($pattern, '', $name);

    // Convert double spaces to single spaces
    $name = preg_replace('/\s+/', ' ', $name);

    // Trim leading and trailing white space
    $name = trim($name);

    return $name;
}