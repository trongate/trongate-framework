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
 * Extracts a substring from a given string, defined by start and end delimiters.
 * This function searches for the first occurrence of the start delimiter and the first subsequent
 * occurrence of the end delimiter, and returns the text found between them. If either delimiter
 * is not found, or if they are in the wrong order, an empty string is returned.
 *
 * @param string $string The full string from which to extract content.
 * @param string $start_delim The starting delimiter of the content to extract.
 * @param string $end_delim The ending delimiter of the content to extract.
 * @return string The content found between the specified delimiters or an empty string if no content is found.
 */
function extract_content(string $string, string $start_delim, string $end_delim): string {
    if (($start_pos = strpos($string, $start_delim)) !== false) {
        $start_pos += strlen($start_delim);
        if (($end_pos = strpos($string, $end_delim, $start_pos)) !== false) {
            return substr($string, $start_pos, $end_pos - $start_pos);
        }
    }
    return '';
}

/**
 * Removes a portion of a string between two given substrings.
 *
 * @param string $start The starting substring.
 * @param string $end The ending substring.
 * @param string $haystack The string from which to remove the substring.
 * @param bool $remove_all Optional argument to remove all matching portions. Defaults to false.
 * @return string The modified string.
 */
function remove_substr_between(string $start, string $end, string $haystack, bool $remove_all = false): string {
    if (!$remove_all) {
        $start_pos = strpos($haystack, $start);
        if ($start_pos === false) {
            return $haystack;
        }
        $end_pos = strpos($haystack, $end, $start_pos + strlen($start));
        if ($end_pos === false) {
            return $haystack;
        }
        return substr($haystack, 0, $start_pos) . substr($haystack, $end_pos + strlen($end));
    } else {
        while (($start_pos = strpos($haystack, $start)) !== false) {
            $end_pos = strpos($haystack, $end, $start_pos + strlen($start));
            if ($end_pos === false) {
                break;
            }
            $haystack = substr($haystack, 0, $start_pos) . substr($haystack, $end_pos + strlen($end));
        }
        return $haystack;
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
 * Sanitizes a filename for safe storage and usage.
 *
 * This function leverages url_title() to handle international characters, special characters,
 * and whitespace while preserving the file extension. The result is a clean, URL-safe filename
 * that's safe to store on the filesystem and serve via HTTP.
 *
 * Features:
 * - Transliterates international characters (e.g., "Москва.jpg" → "moskva.jpg")
 * - Removes or replaces special characters and whitespace
 * - Preserves and normalizes file extensions
 * - Prevents null byte attacks
 * - Generates fallback names for edge cases
 * - Limits filename length to prevent filesystem issues
 *
 * Examples:
 *   sanitize_filename('My Photo (1).jpg')        → 'my-photo-1.jpg'
 *   sanitize_filename('Москва 2024.png')         → 'moskva-2024.png' (with intl)
 *   sanitize_filename('café résumé.pdf')         → 'cafe-resume.pdf' (with intl)
 *   sanitize_filename('北京_beijing.jpg')         → 'bei-jing-beijing.jpg' (with intl)
 *   sanitize_filename('file@#$%.txt')            → 'file.txt'
 *   sanitize_filename('my   multiple   spaces')  → 'my-multiple-spaces'
 *
 * @param string $filename The filename to sanitize.
 * @param bool $transliteration Whether to transliterate international characters to ASCII (default: true).
 * @param int $max_length Maximum length for the base filename, excluding extension (default: 200).
 * @return string The sanitized filename with preserved extension.
 * @throws InvalidArgumentException If filename contains null bytes.
 */
function sanitize_filename(string $filename, bool $transliteration = true, int $max_length = 200): string {
    // Security: Prevent null byte attacks
    if (strpos($filename, "\0") !== false) {
        throw new InvalidArgumentException("Filename contains null bytes");
    }
    
    // Extract components
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    
    // Handle empty basename (e.g., hidden files like ".htaccess")
    if (empty($basename)) {
        $basename = 'file_' . uniqid();
    }
    
    // Use url_title() for robust string cleaning
    // This handles international characters, HTML entities, special chars, etc.
    $clean_basename = url_title($basename, $transliteration);
    
    // Fallback if url_title() returns empty (very rare edge case)
    if (empty($clean_basename)) {
        $clean_basename = 'file_' . uniqid();
    }
    
    // Limit length to prevent filesystem issues
    // Most filesystems support 255 bytes, but leave room for extension
    if (strlen($clean_basename) > $max_length) {
        $clean_basename = substr($clean_basename, 0, $max_length);
        // Remove any trailing dashes created by the substring
        $clean_basename = rtrim($clean_basename, '-');
    }
    
    // Clean and normalize extension (alphanumeric only, lowercase)
    $clean_extension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));
    
    // Reconstruct filename
    return $clean_extension ? "{$clean_basename}.{$clean_extension}" : $clean_basename;
}

/**
 * Safely escape and format a string for various output contexts.
 *
 * @param string|null $input The string to be escaped. Null values are converted to empty strings.
 * @param string $output_format The desired output format: 'html' (default), 'xml', 'json', 'javascript', or 'attribute'.
 * @param string $encoding The character encoding to use for escaping. Defaults to 'UTF-8'.
 * 
 * @return string The escaped and formatted string ready for safe inclusion in the specified context.
 * @throws InvalidArgumentException if an unsupported output format is provided.
 * @throws RuntimeException if encoding fails due to invalid character encoding.
 * @throws JsonException if JSON encoding fails (requires PHP 7.3+).
 */
function out(?string $input, string $output_format = 'html', string $encoding = 'UTF-8'): string {
    if ($input === null) {
        return '';
    }
    
    switch ($output_format) {
        case 'html':
        case 'attribute':
            $result = htmlspecialchars($input, ENT_QUOTES, $encoding);
            if ($result === false) {
                throw new RuntimeException("Failed to encode string with encoding: {$encoding}");
            }
            return $result;
            
        case 'xml':
            $result = htmlspecialchars($input, ENT_XML1, $encoding);
            if ($result === false) {
                throw new RuntimeException("Failed to encode string with encoding: {$encoding}");
            }
            return $result;
            
        case 'json':
            return json_encode(
                $input, 
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | 
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            
        case 'javascript':
            $encoded = json_encode(
                $input, 
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | 
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            // Remove surrounding quotes for JS string content
            if (strlen($encoded) >= 2 && $encoded[0] === '"' && $encoded[strlen($encoded)-1] === '"') {
                return substr($encoded, 1, -1);
            }
            return $encoded;
            
        default:
            throw new InvalidArgumentException("Unsupported output format: '{$output_format}'");
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
 * @param string $str The input string to be filtered and sanitized.
 * @param string[] $allowed_tags An optional array of allowed HTML tags (default is an empty array).
 * @return string The filtered and sanitized string.
 */
function filter_str(string $str, array $allowed_tags = []): string {
    // Remove HTML & PHP tags
    $str = strip_tags($str, implode('', $allowed_tags));

    // Convert multiple consecutive whitespaces to a single space, except for line breaks
    $str = preg_replace('/[^\S\r\n]+/', ' ', $str);

    // Trim leading and trailing white space
    $str = trim($str);

    return $str;
}

/**
 * Alias for filter_str function for backward compatibility.
 * @deprecated This function is deprecated and will be removed from the Trongate framework on June 3, 2026.
 * Developers are encouraged to globally replace instances of 'filter_string(' with 'filter_str(' throughout their site or application.
 */
function filter_string(string $string, array $allowed_tags = []): string {
    return filter_str($string, $allowed_tags);
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