<?php
/**
 * String Module - Framework Service for String Manipulation
 * 
 * This module provides string-related functionality as a framework service.
 * It is accessed exclusively via Service Routing from helper functions.
 * 
 * Security: Uses BASE_URL check instead of block_url() to minimize dependencies
 * and maximize performance for core framework services.
 */

// Prevent direct script access - lightweight security check
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class String_service extends Trongate {

    /**
     * Truncates a string to a specified maximum length.
     *
     * @param array $data Array containing 'value' and 'max_length' keys.
     * @return string The truncated string with an ellipsis (...) if necessary.
     */
    public function truncate_str(array $data): string {
        $value = $data['value'] ?? '';
        $max_length = $data['max_length'] ?? 0;

        if (mb_strlen($value, 'UTF-8') <= $max_length) {
            return $value;
        } else {
            return mb_substr($value, 0, $max_length, 'UTF-8') . '...';
        }
    }

    /**
     * Truncates a string to a specified maximum number of words.
     *
     * @param array $data Array containing 'value' and 'max_words' keys.
     * @return string The truncated string with an ellipsis (...) if necessary.
     */
    public function truncate_words(array $data): string {
        $value = $data['value'] ?? '';
        $max_words = $data['max_words'] ?? 0;

        // Use preg_split so multiple consecutive spaces don't create phantom empty-string "words".
        $words = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

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
     * @param array $data Array containing 'str' and 'delimiter' keys.
     * @return string The last part of the input string.
     */
    public function get_last_part(array $data): string {
        $str = $data['str'] ?? '';
        $delimiter = $data['delimiter'] ?? '-';
        
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
     *
     * @param array $data Array containing 'string', 'start_delim', and 'end_delim' keys.
     * @return string The content found between the specified delimiters or an empty string if no content is found.
     */
    public function extract_content(array $data): string {
        $string = $data['string'] ?? '';
        $start_delim = $data['start_delim'] ?? '';
        $end_delim = $data['end_delim'] ?? '';
        
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
     * @param array $data Array containing 'start', 'end', 'haystack', and 'remove_all' keys.
     * @return string The modified string.
     */
    public function remove_substr_between(array $data): string {
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $haystack = $data['haystack'] ?? '';
        $remove_all = $data['remove_all'] ?? false;
        
        if (!$remove_all) {
            $start_pos = strpos($haystack, $start);
            if ($start_pos === false) {
                return $haystack;
            }
            $end_pos = strpos($haystack, $end, $start_pos + strlen($start));
            if ($end_pos === false) {
                return $haystack;
            }
            $result = substr($haystack, 0, $start_pos) . substr($haystack, $end_pos + strlen($end));
            // If the removed block sat between two spaces, one trailing space remains — trim one leading space after the cut point.
            if ($start_pos > 0 && $haystack[$start_pos - 1] === ' ' && isset($result[$start_pos]) && $result[$start_pos] === ' ') {
                $result = substr($result, 0, $start_pos) . substr($result, $start_pos + 1);
            }
            return $result;
        } else {
            while (($start_pos = strpos($haystack, $start)) !== false) {
                $end_pos = strpos($haystack, $end, $start_pos + strlen($start));
                if ($end_pos === false) {
                    break;
                }
                $removed = substr($haystack, 0, $start_pos) . substr($haystack, $end_pos + strlen($end));
                // Collapse double-space left behind when block was between spaces.
                if ($start_pos > 0 && $haystack[$start_pos - 1] === ' ' && isset($removed[$start_pos]) && $removed[$start_pos] === ' ') {
                    $removed = substr($removed, 0, $start_pos) . substr($removed, $start_pos + 1);
                }
                $haystack = $removed;
            }
            return $haystack;
        }
    }

    /**
     * Format a number as a price with commas and optional currency symbol.
     *
     * @param array $data Array containing 'num' and 'currency_symbol' keys.
     * @return string|float The formatted nice price.
     */
    public function nice_price(array $data): string {
        $num = $data['num'] ?? 0.0;
        $currency_symbol = $data['currency_symbol'] ?? null;
        
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
     * @param array $data Array containing 'value' and 'transliteration' keys.
     * @return string The slugified version of the input string.
     */
    public function url_title(array $data): string {
        $value = $data['value'] ?? '';
        $transliteration = $data['transliteration'] ?? true;
        
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
     * @param array $data Array containing 'filename', 'transliteration', and 'max_length' keys.
     * @return string The sanitized filename with preserved extension.
     * @throws InvalidArgumentException If filename contains null bytes.
     */
    public function sanitize_filename(array $data): string {
        $filename = $data['filename'] ?? '';
        $transliteration = $data['transliteration'] ?? true;
        $max_length = $data['max_length'] ?? 200;
        
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
        $clean_basename = $this->url_title(['value' => $basename, 'transliteration' => $transliteration]);
        
        // Fallback if url_title() returns empty (very rare edge case)
        if (empty($clean_basename)) {
            $clean_basename = 'file_' . uniqid();
        }
        
        // Limit length to prevent filesystem issues
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
     * @param array $data Array containing 'input', 'output_format', and 'encoding' keys.
     * @return string The escaped and formatted string ready for safe inclusion in the specified context.
     * @throws InvalidArgumentException if an unsupported output format is provided.
     * @throws RuntimeException if encoding fails due to invalid character encoding.
     * @throws JsonException if JSON encoding fails (requires PHP 7.3+).
     */
    public function out(array $data): string {
        $input = $data['input'] ?? null;
        $output_format = $data['output_format'] ?? 'html';
        $encoding = $data['encoding'] ?? 'UTF-8';
        
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
     * @param array $data Array containing 'length' and 'uppercase' keys.
     * @return string The randomly generated string.
     */
    public function make_rand_str(array $data): string {
        $length = $data['length'] ?? 32;
        $uppercase = $data['uppercase'] ?? false;
        
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
     * @param array $data Array containing 'content' and 'specifications' keys.
     * @return string The modified HTML content.
     */
    public function replace_html_tags(array $data): string {
        $content = $data['content'] ?? '';
        $specifications = $data['specifications'] ?? [];
        
        $opening_string_before = $specifications['opening_string_before'] ?? '';
        $close_string_before = $specifications['close_string_before'] ?? '';
        $opening_string_after = $specifications['opening_string_after'] ?? '';
        $close_string_after = $specifications['close_string_after'] ?? '';

        $pattern = '/' . preg_quote($opening_string_before, '/') . '(.*?)' . preg_quote($close_string_before, '/') . '/';
        $replacement = $opening_string_after . '$1' . $close_string_after;

        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * Remove code sections from HTML content based on specified start and end patterns.
     *
     * @param array $data Array containing 'content', 'opening_pattern', and 'closing_pattern' keys.
     * @return string The HTML content with specified code sections removed.
     */
    public function remove_html_code(array $data): string {
        $content = $data['content'] ?? '';
        $opening_pattern = $data['opening_pattern'] ?? '';
        $closing_pattern = $data['closing_pattern'] ?? '';
        
        // Use \s* (greedy) — lazy \s*? had no practical effect and was misleading.
        $pattern = '/(' . preg_quote($opening_pattern, '/') . ')(.*?)(\s*' . preg_quote($closing_pattern, '/') . ')/is';
        return preg_replace($pattern, '', $content);
    }

    /**
     * Filter and sanitize a string.
     *
     * @param array $data Array containing 'str' and 'allowed_tags' keys.
     * @return string The filtered and sanitized string.
     */
    public function filter_str(array $data): string {
        $str = $data['str'] ?? '';
        $allowed_tags = $data['allowed_tags'] ?? [];
        
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
    public function filter_string(array $data): string {
        $string = $data['string'] ?? '';
        $allowed_tags = $data['allowed_tags'] ?? [];
        
        return $this->filter_str(['str' => $string, 'allowed_tags' => $allowed_tags]);
    }

    /**
     * Filter and sanitize a name.
     *
     * @param array $data Array containing 'name' and 'allowed_chars' keys.
     * @return string The filtered and sanitized name.
     */
    public function filter_name(array $data): string {
        $name = $data['name'] ?? '';
        $allowed_chars = $data['allowed_chars'] ?? [];
        
        // Similar to filter_string() but better suited for usernames, etc.

        // Remove HTML & PHP tags (please read note above for more!)
        $name = strip_tags($name);

        // Apply XSS filtering
        $name = htmlspecialchars($name);

        // Create a regex character class that includes any explicitly allowed characters.
        // preg_quote() prevents regex injection from special characters in $allowed_chars.
        $extra = '';
        foreach ($allowed_chars as $char) {
            $extra .= preg_quote($char, '/');
        }
        $pattern = '/[^a-zA-Z0-9\s' . $extra . ']/u';

        // Replace any characters that are not in the allowed list
        $name = preg_replace($pattern, '', $name);

        // Convert double spaces to single spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Trim leading and trailing white space
        $name = trim($name);

        return $name;
    }
}