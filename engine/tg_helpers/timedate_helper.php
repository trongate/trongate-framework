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
    try {
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
    } catch (Exception $e) {
        return $stored_date_str;
    }
}

function format_datetime_str($stored_datetime_str) {
    try {
        // Function to format a date-time string ($stored_datetime_str) expected in 'yyyy-mm-dd HH:ii:ss' format
        get_default_date_format(); // Ensuring DEFAULT_DATE_FORMAT is set
        
        // Constructing DateTime object from the provided date-time string
        $date_time = DateTime::createFromFormat('Y-m-d H:i:s', $stored_datetime_str);

        // Check if the DateTime object was created successfully
        if ($date_time === false) {
            throw new Exception('Invalid date-time format');
        }

        // Format date according to DEFAULT_DATE_FORMAT
        $formatted_date = $date_time->format(str_replace(['mm', 'dd', 'yyyy'], ['m', 'd', 'Y'], DEFAULT_DATE_FORMAT));

        // Format time in 24-hour clock format
        $formatted_time = $date_time->format('H:i');

        // Combine date and time based on DEFAULT_DATE_FORMAT
        $formatted_datetime = $formatted_date . ', ' . $formatted_time;

        return $formatted_datetime;
    } catch (Exception $e) {
        return $stored_datetime_str; // Return the original string in case of error
    }
}

function format_time_str($stored_time_str) {
    try {
        // Function to format a time string ($stored_time_str) expected in 'HH:ii' format
        $time_parts = explode(':', $stored_time_str);
    
        $hours = (int)$time_parts[0];
        $minutes = (int)$time_parts[1];
    
        $formatted_time = '';
    
        if (count($time_parts) >= 2) {
            if ($hours > 12) {
                $formatted_time = sprintf('%02d:%02d', $hours, $minutes);
            } else {
                $formatted_time = date('h:i', strtotime($stored_time_str));
            }
        }
        
        return $formatted_time;

    } catch (Exception $e) {
        return $stored_time_str; // Return the original string in case of error
    }
}