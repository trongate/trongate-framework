<?php
/**
 * Initializes the default date format constant if not already defined.
 *
 * If DEFAULT_DATE_FORMAT is not defined, sets it to 'mm/dd/yyyy' (US date format).
 *
 * @return void
 */
function get_default_date_format(): void {
    if (!defined('DEFAULT_DATE_FORMAT')) {
        define('DEFAULT_DATE_FORMAT', 'mm/dd/yyyy'); // US date format.
    }

    // No need to return DEFAULT_DATE_FORMAT since it's already global.
}

/**
 * Initializes the default locale string constant if not already defined.
 *
 * If DEFAULT_LOCALE_STR is not defined, sets it to 'en-US' (US English).
 *
 * @return void
 */
function get_default_locale_str(): void {
    if (!defined('DEFAULT_LOCALE_STR')) {
        define('DEFAULT_LOCALE_STR', 'en-US'); // US English.
    }

    // No need to return DEFAULT_LOCALE_STR since it's already global.
}

/**
 * Formats a date string.
 *
 * Accepts a date string ($stored_date_str) expected in 'yyyy-mm-dd' format and formats it
 * according to the DEFAULT_DATE_FORMAT constant.
 *
 * @param string $stored_date_str The date string to be formatted (expected format: 'yyyy-mm-dd').
 * @return string The formatted date string as per the DEFAULT_DATE_FORMAT or the original string if an error occurs.
 */
function format_date_str(string $stored_date_str): string {
    try {
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

/**
 * Formats a date-time string.
 *
 * Accepts a date-time string ($stored_datetime_str) expected in 'yyyy-mm-dd HH:ii:ss' format and formats it
 * according to the DEFAULT_DATE_FORMAT constant.
 *
 * @param string $stored_datetime_str The date-time string to be formatted (expected format: 'yyyy-mm-dd HH:ii:ss').
 * @return string The formatted date-time string as per the DEFAULT_DATE_FORMAT or the original string if an error occurs.
 */
function format_datetime_str(string $stored_datetime_str): string {
    try {
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

/**
 * Formats a time string.
 *
 * Accepts a time string ($stored_time_str) expected in 'HH:ii' format and formats it
 * according to the 'h:i' or 'HH:ii' format based on the provided time.
 *
 * @param string $stored_time_str The time string to be formatted (expected format: 'HH:ii').
 * @return string The formatted time string as per the 'h:i' or 'HH:ii' format or the original string if an error occurs.
 */
function format_time_str(string $stored_time_str): string {
    try {
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

/**
 * Converts a string representation of a date or date-time to a DateTime object.
 * 
 * Accepts date strings in formats 'dd/mm/yyyy', 'dd-mm-yyyy', 'mm/dd/yyyy', or 'mm-dd-yyyy',
 * and date-time strings in the format 'mm/dd/yyyy, HH:ii'.
 * 
 * @param string $input_str A string representing a date or date-time.
 * @return DateTime|null Returns a DateTime object if successful, otherwise returns null.
 */
function convert_to_date_obj(string $input_str): ?DateTime {
    get_default_date_format();
    $possible_formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'm-d-Y'];
    $default_date_format = DEFAULT_DATE_FORMAT;

    // Pattern to match date-time string 'mm/dd/yyyy, HH:ii'
    $datetime_pattern = '/^(\d{2}[\/-]\d{2}[\/-]\d{4}),\s(\d{2}:\d{2})$/';

    if (preg_match($datetime_pattern, $input_str, $matches)) {
        $date_time = DateTime::createFromFormat('m/d/Y, H:i', $input_str);

        if ($date_time instanceof DateTime) {
            return $date_time;
        }
    } else {
        foreach ($possible_formats as $format) {
            $date_obj = DateTime::createFromFormat($format, $input_str);

            if ($date_obj instanceof DateTime) {
                return $date_obj;
            }
        }
    }

    return null; // Returning null if date object cannot be created
}

/**
 * Converts a string representation of time to a DateTime object.
 * 
 * Accepts time strings in the format 'HH:mm'.
 * 
 * @param string $input_time_str A string representing time in 'HH:mm' format.
 * @return DateTime|null Returns a DateTime object if successful, otherwise returns null.
 */
function convert_time_str(string $input_time_str): ?DateTime {
    $time_pattern = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';

    if (preg_match($time_pattern, $input_time_str)) {
        $time_obj = DateTime::createFromFormat('H:i', $input_time_str);
        return $time_obj !== false ? $time_obj : null;
    }

    return null; // Returning null if time object cannot be created
}