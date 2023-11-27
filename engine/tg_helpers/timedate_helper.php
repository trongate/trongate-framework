<?php
function get_default_date_format() {
    if (!defined('DEFAULT_DATE_FORMAT')) {
        define('DEFAULT_DATE_FORMAT', 'mm/dd/yyyy'); // US date format.
    }

    // No need to return DEFAULT_DATE_FORMAT since it's already global.
}

function get_default_locale_str() {
    if (!defined('DEFAULT_LOCALE_STR')) {
        define('DEFAULT_LOCALE_STR', 'en-US'); // US English.
    }

    // No need to return DEFAULT_LOCALE_STR since it's already global.
}

function format_date_str($stored_date_str) {
    // Function to format a date string ($stored_date_str) expected in 'yyyy-mm-dd' format
    get_default_date_format(); // Ensuring DEFAULT_DATE_FORMAT is set
    
    // Constructing DateTime object from the provided date string
    $date = DateTime::createFromFormat('Y-m-d', $stored_date_str);
    if ($date === false) {
        throw new Exception('Invalid date format');
    }

    // Extracting day, month, and year components from the DateTime object
    $day = $date->format('d');
    $month = $date->format('m');
    $year = $date->format('Y');

    // Retaining separators in DEFAULT_DATE_FORMAT and replacing placeholders
    $formatted_date = str_replace(['mm', 'dd', 'yyyy'], [$month, $day, $year], DEFAULT_DATE_FORMAT);

    return $formatted_date;
}