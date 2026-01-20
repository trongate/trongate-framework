<?php
class Pagination_model extends Model {

    // Required properties with their expected types
    private static $required_properties = [
        "total_rows" => "int",
        "limit" => "int",
        "record_name_plural" => "string"
    ];

    // Optional properties with their default values and expected types
    private static $optional_properties = [
        "include_showing_statement" => ["default" => false, "type" => "bool"],
        "page_num_segment" => ["default" => null, "type" => "int"],
        "pagination_root" => ["default" => null, "type" => "string"],
        "settings" => ["default" => [], "type" => "array"],
        "include_css" => ["default" => false, "type" => "bool"],
        "showing_statement" => ["default" => null, "type" => "string"],  // NEW
        "num_links_per_page" => ["default" => 7, "type" => "int"]  // NEW (moved from hardcoded)
    ];

    // Map PHP types to expected types
    private static $type_map = [
        "integer" => "int",
        "string" => "string",
        "boolean" => "bool",
        "double" => "float"
    ];

    // Default settings for pagination HTML output
    private static $default_settings = [
        'pagination_open' => '<div class="pagination">',
        'pagination_close' => '</div>',
        'cur_link_open' => '<a class="active">',
        'cur_link_close' => '</a>',
        'num_link_open' => '',
        'num_link_close' => '',
        'first_link' => 'First',
        'first_link_open' => '',
        'first_link_close' => '',
        'first_link_aria_label' => 'First page',
        'last_link' => 'Last',
        'last_link_open' => '',
        'last_link_close' => '',
        'last_link_aria_label' => 'Last page',
        'prev_link' => '&laquo;',
        'prev_link_open' => '',
        'prev_link_close' => '',
        'prev_link_aria_label' => 'Previous page',
        'next_link' => '&raquo;',
        'next_link_open' => '',
        'next_link_close' => '',
        'next_link_aria_label' => 'Next page'
    ];

    /**
     * Prepare and validate pagination data.
     *
     * @param array $pagination_data Raw pagination configuration
     * @return array|null Processed data ready for rendering, or null if pagination not needed
     */
    public function prepare_pagination_data(array $pagination_data): ?array {
        // Validate required properties
        self::check_for_required_properties($pagination_data);
        
        // Initialize optional properties with defaults
        $pagination_data = self::init_optional_properties($pagination_data);
        
        // Auto-detect pagination_root if not provided
        if ($pagination_data['pagination_root'] === null || $pagination_data['pagination_root'] === '') {
            $pagination_data['pagination_root'] = $this->auto_detect_pagination_root();
        } else {
            // Ensure provided pagination_root ends with a slash
            if (substr($pagination_data['pagination_root'], -1) !== '/') {
                $pagination_data['pagination_root'] .= '/';
            }
        }
        
        // Early return if no pagination needed
        if ($pagination_data['total_rows'] <= $pagination_data['limit']) {
            return null;
        }

        // Calculate total pages first
        $pagination_data['total_pages'] = (int) ceil($pagination_data['total_rows'] / $pagination_data['limit']);
        
        // Calculate current page
        $pagination_data['current_page'] = $this->get_current_page($pagination_data['page_num_segment']);
        
        // VALIDATE: Ensure current_page doesn't exceed total_pages
        if ($pagination_data['current_page'] > $pagination_data['total_pages']) {
            $pagination_data['current_page'] = $pagination_data['total_pages'];
        }
        
        return $pagination_data;
    }

    /**
     * Checks for the presence and type of required properties in the pagination data array.
     *
     * @param array $pagination_data The pagination data array.
     * @return void
     */
    private static function check_for_required_properties(array $pagination_data): void {
        $type_map = self::$type_map;
        $required_properties = self::$required_properties;

        // Loop through required properties
        foreach ($required_properties as $property => $expected_type) {
            // Check if property exists in $pagination_data
            if (isset($pagination_data[$property])) {
                // Check if the type matches
                $actual_type = gettype($pagination_data[$property]);
                if (isset($type_map[$actual_type]) && $type_map[$actual_type] === $expected_type) {
                    // Property exists and type is correct
                    unset($required_properties[$property]);
                }
            }
        }

        // Check if any required properties are missing or have wrong type
        if (!empty($required_properties)) {
            $missing_properties = [];
            foreach ($required_properties as $property => $expected_type) {
                $missing_properties[] = "$property (expected type: $expected_type)";
            }
            $error_message = 'Pagination Error: ';
            if (count($missing_properties) === 1) {
                $error_message .= 'Missing or invalid required property: ';
            } else {
                $error_message .= 'Missing or invalid required properties: ';
            }
            $error_message .= implode(', ', $missing_properties);
            die($error_message);
        }
    }

    /**
     * Initializes optional properties in the pagination data array with default values if they are not already set.
     *
     * @param array $pagination_data The pagination data array.
     * @return array The pagination data array with optional properties initialized.
     */
    private static function init_optional_properties(array $pagination_data): array {
        $type_map = self::$type_map;
        $optional_properties = self::$optional_properties;

        // Loop through optional properties
        foreach ($optional_properties as $property => $options) {
            // Check if property exists in $pagination_data
            if (!isset($pagination_data[$property])) {
                // If property does not exist, set it to the default value
                $pagination_data[$property] = $options['default'];
            } else {
                // If property exists, check if the type matches (skip null check for nullable properties)
                $expected_type = $options['type'];
                $actual_type = gettype($pagination_data[$property]);
                
                // Allow null for properties with null default
                if ($pagination_data[$property] === null && $options['default'] === null) {
                    continue;
                }
                
                if (isset($type_map[$actual_type]) && $type_map[$actual_type] !== $expected_type) {
                    // If the type doesn't match, set it to the default value
                    $pagination_data[$property] = $options['default'];
                }
            }
        }

        // Handle settings validation
        if (empty($pagination_data['settings'])) {
            $pagination_data['settings'] = self::$default_settings;
        } else {
            // Custom settings have been submitted, validate them
            $pagination_data['settings'] = self::validate_settings($pagination_data['settings']);
        }

        return $pagination_data;
    }

    /**
     * Validate custom pagination settings.
     *
     * @param array $submitted_settings The custom pagination settings submitted for validation.
     * @return array The validated custom pagination settings.
     */
    private static function validate_settings(array $submitted_settings): array {
        $settings = [];

        // Fill in submitted settings and use defaults for missing ones
        foreach (self::$default_settings as $key => $default_value) {
            $settings[$key] = $submitted_settings[$key] ?? $default_value;
        }

        return $settings;
    }

    /**
     * Get the current page number from URL segment or auto-detect from last segment.
     *
     * @param int|null $segment URL segment position (null for auto-detection)
     * @return int Current page number (minimum 1)
     */
    private function get_current_page(?int $segment): int {
        if ($segment === null) {
            // Auto-detect: check if last URL segment is numeric
            $last_segment = get_last_segment();
            if (is_numeric($last_segment)) {
                return max(1, (int) $last_segment);
            }
            return 1; // Default to page 1 if not numeric
        }
        
        // Use specified segment position with 'int' parameter to force integer type
        $page = segment($segment, 'int');
        return max(1, $page);
    }

    /**
     * Auto-detect pagination root from current URL.
     *
     * @return string The detected pagination root
     */
    private function auto_detect_pagination_root(): string {
        $current_url = current_url();
        $last_segment = get_last_segment();
        
        if (is_numeric($last_segment)) {
            // If last segment is numeric, remove it to get the base URL
            $last_segment_pos = strrpos($current_url, '/' . $last_segment);
            $base_url = ($last_segment_pos !== false) ? substr($current_url, 0, $last_segment_pos) : $current_url;
        } else {
            // If not numeric, use current URL as base
            $base_url = $current_url;
        }
        
        // Remove query string if present
        $base_url = remove_query_string($base_url);
        
        // Convert to relative URL by removing BASE_URL
        $pagination_root = str_replace(BASE_URL, '', $base_url);
        
        // Ensure it ends with a slash
        if (substr($pagination_root, -1) !== '/') {
            $pagination_root .= '/';
        }
        
        return $pagination_root;
    }
}