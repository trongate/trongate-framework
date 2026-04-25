<?php
/**
 * Truncates a string to a specified maximum length.
 *
 * @param string $value The input string to be truncated.
 * @param int $max_length The maximum length of the truncated string.
 * @return string The truncated string with an ellipsis (...) if necessary.
 */
function truncate_str(string $value, int $max_length): string {
    $data = ['value' => $value, 'max_length' => $max_length];
    return Modules::run('string_service/truncate_str', $data);
}

/**
 * Truncates a string to a specified maximum number of words.
 *
 * @param string $value The input string to be truncated.
 * @param int $max_words The maximum number of words in the truncated string.
 * @return string The truncated string with an ellipsis (...) if necessary.
 */
function truncate_words(string $value, int $max_words): string {
    $data = ['value' => $value, 'max_words' => $max_words];
    return Modules::run('string_service/truncate_words', $data);
}

/**
 * Retrieves the last part of a string based on a delimiter.
 *
 * @param string $str The input string to retrieve the last part from.
 * @param string $delimiter The delimiter used to split the string (default: '-').
 * @return string The last part of the input string.
 */
function get_last_part(string $str, string $delimiter = '-'): string {
    $data = ['str' => $str, 'delimiter' => $delimiter];
    return Modules::run('string_service/get_last_part', $data);
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
    $data = ['string' => $string, 'start_delim' => $start_delim, 'end_delim' => $end_delim];
    return Modules::run('string_service/extract_content', $data);
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
    $data = ['start' => $start, 'end' => $end, 'haystack' => $haystack, 'remove_all' => $remove_all];
    return Modules::run('string_service/remove_substr_between', $data);
}

/**
 * Format a number as a price with commas and optional currency symbol.
 *
 * @param float $num The number to be formatted.
 * @param string|null $currency_symbol The optional currency symbol to be added.
 * @return string|float The formatted nice price.
 */
function nice_price(float $num, ?string $currency_symbol = null): string {
    $data = ['num' => $num, 'currency_symbol' => $currency_symbol];
    return Modules::run('string_service/nice_price', $data);
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
    $data = ['value' => $value, 'transliteration' => $transliteration];
    return Modules::run('string_service/url_title', $data);
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
    $data = ['filename' => $filename, 'transliteration' => $transliteration, 'max_length' => $max_length];
    return Modules::run('string_service/sanitize_filename', $data);
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
    $data = ['input' => $input, 'output_format' => $output_format, 'encoding' => $encoding];
    return Modules::run('string_service/out', $data);
}

/**
 * Generate a random string of characters.
 *
 * @param int $length (Optional) The length of the random string. Default is 32.
 * @param bool $uppercase (Optional) Whether to use uppercase characters. Default is false.
 * @return string The randomly generated string.
 */
function make_rand_str(int $length = 32, bool $uppercase = false): string {
    $data = ['length' => $length, 'uppercase' => $uppercase];
    return Modules::run('string_service/make_rand_str', $data);
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
    $data = ['content' => $content, 'specifications' => $specifications];
    return Modules::run('string_service/replace_html_tags', $data);
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
    $data = ['content' => $content, 'opening_pattern' => $opening_pattern, 'closing_pattern' => $closing_pattern];
    return Modules::run('string_service/remove_html_code', $data);
}

/**
 * Filter and sanitize a string.
 *
 * @param string $str The input string to be filtered and sanitized.
 * @param string[] $allowed_tags An optional array of allowed HTML tags (default is an empty array).
 * @return string The filtered and sanitized string.
 */
function filter_str(string $str, array $allowed_tags = []): string {
    $data = ['str' => $str, 'allowed_tags' => $allowed_tags];
    return Modules::run('string_service/filter_str', $data);
}

/**
 * Alias for filter_str function for backward compatibility.
 * @deprecated This function is deprecated and will be removed from the Trongate framework on June 3, 2026.
 * Developers are encouraged to globally replace instances of 'filter_string(' with 'filter_str(' throughout their site or application.
 */
function filter_string(string $string, array $allowed_tags = []): string {
    $data = ['string' => $string, 'allowed_tags' => $allowed_tags];
    return Modules::run('string_service/filter_string', $data);
}

/**
 * Filter and sanitize a name.
 *
 * @param string $name The input name to be filtered and sanitized.
 * @param string[] $allowed_chars An optional array of allowed characters.
 * @return string The filtered and sanitized name.
 */
function filter_name(string $name, array $allowed_chars = []) {
    $data = ['name' => $name, 'allowed_chars' => $allowed_chars];
    return Modules::run('string_service/filter_name', $data);
}
