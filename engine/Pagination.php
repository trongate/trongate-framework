<?php

/**
 * Class Pagination - Handles pagination functionality for displaying paginated data.
 */
class Pagination {

    // Required properties with their expected types
    private static $required_properties = [
        "total_rows" => "int",
        "page_num_segment" => "int",
        "limit" => "int",
        "pagination_root" => "string",
        "record_name_plural" => "string"
    ];

    // Optional properties with their default values and expected types
    private static $optional_properties = [
        "include_showing_statement" => ["default" => false, "type" => "bool"],
        "include_css" => ["default" => false, "type" => "bool"],
        "num_links_per_page" => ["default" => 10, "type" => "int"],
        "settings" => ["default" => [], "type" => "array"],
        "enable_infinite_scrolling" => ["default" => true, "type" => "bool"]
    ];

    // Derived properties with their default values
    private static $derived_properties = [];

    // Map PHP types to expected types
    private static $type_map = [
        "integer" => "int",
        "string" => "string",
        "boolean" => "bool",
        "double" => "float"
    ];

    // Default pagination settings
    private static $default_settings = [
        'pagination_open' => '<div class="pagination">',
        'pagination_close' => '</div>',
        'cur_link_open' => '<a href="#" class="active">',
        'cur_link_close' => '</a>',
        'num_link_open' => '',
        'num_link_close' => '',
        'first_link' => 'First',
        'first_link_open' => '',
        'first_link_close' => '',
        'last_link' => 'Last',
        'last_link_open' => '',
        'last_link_close' => '',
        'prev_link' => '&laquo;',
        'prev_link_open' => '',
        'prev_link_close' => '',
        'next_link' => '&raquo;',
        'next_link_open' => '',
        'next_link_close' => ''
    ];

    /**
     * Display pagination links based on provided pagination data.
     *
     * @param array $pagination_data The pagination data array.
     * @return void
     */
    public static function display(array $pagination_data): void {

        // If 'showing_statement' property exists, bypass any errors caused by empty/missing 'record_name_plural'
        if (isset($pagination_data['showing_statement']) && !isset($pagination_data['record_name_plural'])) {
            $pagination_data['record_name_plural'] = 'x'; // Placeholder (since 'record_name_plural' won't be used)
        }

        // Make sure we have all of the required properties
        self::check_for_required_properties($pagination_data);

        // Initialize optional properties
        $pagination_data = self::init_optional_properties($pagination_data);

        // Initialize derived properties:
        // "root","current_page","start","end","num_links_to_side","num_pages","prev","next","showing_statement"
        $pagination_data = self::init_derived_properties($pagination_data);

        // Check if pagination is necessary
        if ($pagination_data['total_rows'] <= $pagination_data['limit']) {
            // No need for pagination, exit early
            return;
        }

        // Output the showing statement
        if (!empty($pagination_data['showing_statement'])) {
            echo '<p class="tg-showing-statement">' . $pagination_data['showing_statement'] . '</p>';
        }

        self::render_pagination($pagination_data);
    }

    /**
     * Render pagination links based on provided pagination data.
     *
     * @param array $pagination_data The pagination data array.
     * @return void
     */
    private static function render_pagination(array $pagination_data): void {

        $html = PHP_EOL.'<div class="pagination">';

        // Attempt 'first/prev' buttons if not on the first page.
        if ($pagination_data['current_page'] > 1) {
            $html .= $pagination_data['settings']['first_link_open'];
            $html .= self::attempt_build_link('first_link', $pagination_data);
            $html .= $pagination_data['settings']['first_link_close'] . PHP_EOL;

            $html .= $pagination_data['settings']['prev_link_open'];
            $html .= self::attempt_build_link('prev_link', $pagination_data);
            $html .= $pagination_data['settings']['prev_link_close'] . PHP_EOL;
        }

        // Calculate the range of links to display based on num_links_to_side
        $start_range = max(1, $pagination_data['current_page'] - $pagination_data['num_links_to_side']);
        $end_range = min($pagination_data['num_pages'], $pagination_data['current_page'] + $pagination_data['num_links_to_side']);

        // Loop through the range of links to display
        for ($i = $start_range; $i <= $end_range; $i++) {
            // Numbered links
            if ($i == $pagination_data['current_page']) {
                $html .= $pagination_data['settings']['cur_link_open'];
                $html .= $i;
                $html .= $pagination_data['settings']['cur_link_close'] . PHP_EOL;
            } else {
                $html .= $pagination_data['settings']['num_link_open'];
                $html .= self::attempt_build_link($i, $pagination_data);
                $html .= $pagination_data['settings']['num_link_close'] . PHP_EOL;
            }
        }

        // Attempt 'next/last' buttons if not on the last page.
        if ($pagination_data['current_page'] < $pagination_data['num_pages']) {
            $html .= $pagination_data['settings']['next_link_open'];
            $html .= self::attempt_build_link('next_link', $pagination_data);
            $html .= $pagination_data['settings']['next_link_close'] . PHP_EOL;

            $html .= $pagination_data['settings']['last_link_open'];
            $html .= self::attempt_build_link('last_link', $pagination_data);
            $html .= $pagination_data['settings']['last_link_close'] . PHP_EOL;
        }

        $html .= '</div>';

        if ($pagination_data['include_css'] === true) {
            // CSS code
            $css_code = '
                .pagination {
                    display: inline-block;
                    margin: 0 0 1em 0;
                }
                .pagination:last-of-type {
                    margin-top: 1em;
                }
                .pagination a {
                    color: black;
                    float: left;
                    padding: 8px 16px;
                    text-decoration: none;
                    border: 1px solid #ddd;
                }
                .pagination a.active {
                    background-color: var(--primary);
                    color: white;
                    border: 1px solid var(--primary);
                }
                .pagination a:hover:not(.active) {
                    background-color: #ddd;
                }
                .pagination a:first-child {
                    border-top-left-radius: 5px;
                    border-bottom-left-radius: 5px;
                }
                .pagination a:last-child {
                    border-top-right-radius: 5px;
                    border-bottom-right-radius: 5px;
                }';

            // Concatenate CSS code into HTML string
            $html .= PHP_EOL . '<style>' . $css_code . '</style>' . PHP_EOL;
        }

        $enable_infinite_scrolling = (bool) $pagination_data['enable_infinite_scrolling'] ?? true;
        if ($enable_infinite_scrolling === true) {

            $scroll_js_file_path = APPPATH . 'public/js/infinite-scroll.js';

            if (file_exists($scroll_js_file_path)) {
                echo '<script id="init-infinite-scroll">' . PHP_EOL;
                echo 'if (!document.getElementById(\'infinite-scroll-script\')) {' . PHP_EOL;
                echo '    const infiniteScrollEl = document.createElement(\'script\');' . PHP_EOL;
                echo '    infiniteScrollEl.src = \'' . BASE_URL . 'js/infinite-scroll.js\';' . PHP_EOL;
                echo '    infiniteScrollEl.id = \'infinite-scroll-script\';' . PHP_EOL;
                echo '    document.body.appendChild(infiniteScrollEl);' . PHP_EOL;
                echo '}' . PHP_EOL;
                echo '</script>' . PHP_EOL;
            }

        }

        echo $html;
    }

    /**
     * Attempt to build a pagination link.
     *
     * @param string|int $value The value to determine the type of link.
     * @param array $pagination_data The pagination data array.
     * @return string The HTML code for the pagination link.
     */
    private static function attempt_build_link($value, array $pagination_data): string {

        $pagination_root_url = BASE_URL.$pagination_data['pagination_root'];
        $settings = $pagination_data['settings'];
        $current_page = $pagination_data['current_page'];

        switch ($value) {
            case 'first_link':
                $html = '<a href="' . $pagination_root_url . '">' . $settings['first_link'] . '</a>';
                break;
            case 'last_link':
                $html = '<a href="' . $pagination_root_url . $pagination_data['num_pages'] . '">' . $settings['last_link'] . '</a>';
                break;
            case 'prev_link':
                $html = '<a href="' . $pagination_root_url . $current_page-1 . '">' . $settings['prev_link'] . '</a>';
                break;
            case 'next_link':
                $html = '<a href="' . $pagination_root_url . $current_page+1 . '">' . $settings['next_link'] . '</a>';
                break;
            default:
                $html = '<a href="' . $pagination_root_url . $value . '">' . $value . '</a>';
                break;
        }

        return $html;

    }

    /**
     * Checks for the presence and type of required properties in the pagination data array.
     *
     * @param array $pagination_data The pagination data array.
     * @return void
     */
    private static function check_for_required_properties(array $pagination_data): void {

        // Accessing static properties using self::$required_properties;
        $type_map = self::$type_map;
        $required_properties = self::$required_properties;

        // Loop through required properties
        foreach ($required_properties as $property => $expected_type) {
            // Check if property exists in $pagination_data
            if (isset($pagination_data[$property])) {
                // Check if the type matches
                if (isset($type_map[gettype($pagination_data[$property])]) && $type_map[gettype($pagination_data[$property])] === $expected_type) {
                    // Unset the property from the required properties array
                    unset($required_properties[$property]);
                }
            }
        }

        // Check if any required properties are missing
        if (!empty($required_properties)) {
            $missing_properties = [];
            foreach ($required_properties as $property => $expected_type) {
                $missing_properties[] = "$property (expected type: $expected_type)";
            }
            $error_message = 'Pagination Error: ';
            if (count($missing_properties) === 1) {
                $error_message .= 'Missing required property: ';
            } else {
                $error_message .= 'Missing required properties: ';
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

        // Accessing static properties using self::$optional_properties
        $type_map = self::$type_map;
        $optional_properties = self::$optional_properties;

        // Loop through optional properties
        foreach ($optional_properties as $property => $options) {
            // Check if property exists in $pagination_data
            if (!isset($pagination_data[$property])) {
                // If property does not exist, set it to the default value
                $pagination_data[$property] = $options['default'];
            } else {
                // If property exists, check if the type matches
                $expected_type = $options['type'];
                if (isset($type_map[gettype($pagination_data[$property])]) && $type_map[gettype($pagination_data[$property])] !== $expected_type) {
                    // If the type of the property doesn't match the expected type, set it to the default value
                    $pagination_data[$property] = $options['default'];
                }
            }
        }

        if (empty($pagination_data['settings'])) {
            $pagination_data['settings'] = self::$default_settings;
        } else {
            // Custom settings have been submitted, let's validate them...
            $pagination_data['settings'] = self::validate_settings($pagination_data['settings']);
        }

        return $pagination_data;
    }

    /**
     * Validate custom pagination settings.
     *
     * @param array $submitted_settings The custom pagination settings submitted for validation.
     * @return array|null The validated custom pagination settings, or null if validation fails.
     */
    private static function validate_settings(array $submitted_settings): ?array {
        $settings = [];
        $required_settings = array_keys(self::$default_settings);

        foreach($required_settings as $rs_key => $required_setting) {
            if (isset($submitted_settings[$required_setting])) {
                $settings[$required_setting] = $submitted_settings[$required_setting];
                unset($required_settings[$rs_key]);
            }
        }

        // Check if any required settings are missing
        if ((count($settings)) !== (count($required_settings))) {
            $error_message = 'Pagination Error: ';

            if (count($required_settings) === 1) {
                $error_message .= 'Missing required settings property: ';
            } else {
                $error_message .= 'Missing required settings properties; ';
            }

            $error_message .= implode(', ', $required_settings);
            die($error_message);
        }

        return $settings;
    }

    /**
     * Initializes derived properties in the pagination data array with default values or calculates them based on other properties.
     *
     * This method calculates and initializes the following derived properties in the pagination data array:
     * - 'root' (string): The pagination root URL.
     * - 'current_page' (int): The current page number.
     * - 'start' (int): The starting page number in pagination links.
     * - 'end' (int): The ending page number in pagination links.
     * - 'num_links_to_side' (int): The number of links to show on each side.
     * - 'num_pages' (int): The total number of pages.
     * - 'prev' (mixed): The previous page number, or an empty string if not applicable.
     * - 'next' (mixed): The next page number, or the last page number if not applicable.
     * - 'showing_statement' (string|null): The showing statement, or null if not applicable.
     *
     * @param array $pagination_data The pagination data array.
     * @return array The pagination data array with derived properties initialized.
     */
    private static function init_derived_properties(array $pagination_data): array {

        // Make sure last character of 'pagination_root' is a '/'
        if (substr($pagination_data['pagination_root'], -1) !== '/') {
            // If not, append a '/' to the string
            $pagination_data['pagination_root'] .= '/';
        }

        $pagination_root = rtrim(BASE_URL, '/') . '/' . ltrim($pagination_data['pagination_root'], '/');

        $page_num_segment = $pagination_data['page_num_segment'] ?? null; // Ensure page_num_segment is set
        $limit = $pagination_data['limit'] ?? null; // Ensure limit is set
        $total_rows = $pagination_data['total_rows'] ?? null; // Ensure total_rows is set

        // Check if required properties are set
        if ($page_num_segment === null || $limit === null || $total_rows === null) {
            // Throw an exception or handle the error appropriately
            throw new InvalidArgumentException('Required properties (page_num_segment, limit, total_rows) are missing.');
        }

        // Calculate num_pages
        $num_pages = ceil($total_rows / $limit);

        // Calculate other derived properties
        $current_page = max(segment($pagination_data['page_num_segment'], 'int'), 1);
        $start = 1;
        $end = min($pagination_data['num_links_per_page'] ?? 10, $num_pages);
        $num_links_to_side = ($pagination_data['num_links_per_page'] ?? 10) / 2;

        $prev = ($current_page > 1) ? $current_page - 1 : '';
        $next = ($current_page < $num_pages) ? $current_page + 1 : $num_pages;

        // Initialize derived properties in the pagination data array
        $derived_properties = [
            'root' => $pagination_root,
            'current_page' => $current_page,
            'start' => $start,
            'end' => $end,
            'num_links_to_side' => $num_links_to_side,
            'num_pages' => $num_pages,
            'prev' => $prev,
            'next' => $next
        ];

        if (!isset($pagination_data['showing_statement'])) {
            // Get showing statement if include_showing_statement is true
            $showing_statement = self::get_showing_statement($pagination_data, $current_page);
            if ($showing_statement !== null) {
                $derived_properties['showing_statement'] = $showing_statement;
            }
        }

        // Merge derived properties with pagination data
        return array_merge($pagination_data, $derived_properties);
    }

    /**
     * Generate the showing statement for pagination.
     *
     * @param array $pagination_data The pagination data array.
     * @return string|null The showing statement if it should be included, otherwise null.
     */
    private static function get_showing_statement(array $pagination_data, int $current_page): ?string {

        if (!$pagination_data['include_showing_statement']) {
            return null;
        }

        $limit = $pagination_data['limit'];
        $total_rows = $pagination_data['total_rows'];
        $record_name_plural = $pagination_data['record_name_plural'];

        // Calculate offset
        $offset = ($current_page - 1) * $limit;

        // Calculate values for showing statement
        $value1 = $offset + 1;
        $value2 = min($offset + $limit, $total_rows);
        $value3 = $total_rows;

        $showing_statement = "Showing " . $value1 . " to " . $value2 . " of " . number_format($value3) . " $record_name_plural.";
        return $showing_statement;
    }

}