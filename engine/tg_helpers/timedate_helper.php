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
 * Attempts to create a DateTime object from provided date and time components.
 *
 * @param array $day_vars An array containing 'day', 'month', 'year', 'hours', and 'minutes'.
 *
 * @return DateTime|false Returns a DateTime object if successful, otherwise returns false.
 */
function create_date_from_array(array $day_vars) {
    $required_keys = ['day', 'month', 'year', 'hours', 'minutes'];

    // Ensure all required keys are present in the array
    if (count(array_intersect_key(array_flip($required_keys), $day_vars)) !== count($required_keys)) {
        return false; // Missing required keys, unable to create a date object
    }

    // Convert day, month, and year strings to integers
    $day = intval($day_vars['day']);
    $month = intval($day_vars['month']);
    $year = intval($day_vars['year']);
    $hours = intval($day_vars['hours']);
    $minutes = intval($day_vars['minutes']);

    // Check if the provided values are valid for a date
    if (!checkdate($month, $day, $year)) {
        return false; // Invalid date, unable to create a date object
    }

    // Check if the provided values are valid for time
    if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
        return false; // Invalid time, unable to create a date object
    }

    // Create the date string with zero-padded values for consistency
    $date_str = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hours, $minutes);

    // Attempt to create a DateTime object
    $date_object = DateTime::createFromFormat('Y-m-d H:i:s', $date_str);

    if ($date_object === false) {
        return false; // Unable to create a date object
    }

    return $date_object; // Return the DateTime object
}

/**
 * Converts a time string into a date object or returns false.
 *
 * @param string $time_str The time string to parse (format: "HH:MM").
 *
 * @return \DateTime|false Returns a \DateTime object representing the parsed time or false if invalid.
 */
function parse_time(string $time_str): \DateTime|false {
    $time_bits = explode(':', $time_str);

    if (count($time_bits) === 2 && strlen($time_str) === 5) {
        $hour_str = trim($time_bits[0]);
        $minute_str = trim($time_bits[1]);

        if (is_numeric($hour_str) && is_numeric($minute_str)) {
            $hour_value = intval($hour_str);
            $minute_value = intval($minute_str);

            if ($hour_value < 0 || $hour_value > 23 || $minute_value < 0 || $minute_value > 59) {
                return false; // Invalid time components
            }

            // Use the current date components
            $day_vars = [
                'hours' => $hour_value,
                'minutes' => $minute_value,
                'day' => intval(date('d')),
                'month' => intval(date('m')),
                'year' => intval(date('Y')),
            ];

            // Attempt to create a date object from the time components
            return create_date_from_array($day_vars);
        }
    }

    return false;
}

/**
 * Converts a date string into a date object or returns false.
 *
 * @param string $date_str The date string to parse (format: "mm/dd/yyyy" or "mm-dd-yyyy").
 *
 * @return \DateTime|false Returns a \DateTime object representing the parsed date or false if invalid.
 */
function parse_date(string $date_str): \DateTime|false {
    get_default_date_format();
    if (strlen($date_str) === 10) {

        if (strpos($date_str, '-') !== false) {
            $delimiter = '-';
        } else {
            $delimiter = '/';
        }

        $date_bits = explode($delimiter, $date_str);
        if (count($date_bits) === 3) {

            $day_vars['hours'] = date('G');
            $day_vars['minutes'] = date('i');
            $day_vars['year'] = $date_bits[2];

            if ((DEFAULT_DATE_FORMAT === 'mm/dd/yyyy') || (DEFAULT_DATE_FORMAT === 'mm-dd-yyyy')) {
                $day_vars['month'] = $date_bits[0];
                $day_vars['day'] = $date_bits[1];
            } else {
                $day_vars['day'] = $date_bits[0];
                $day_vars['month'] = $date_bits[1];
            }

            // Attempt to create a date object from the date components
            return create_date_from_array($day_vars);
        }
    }
    return false;
}

/**
 * Parses a datetime string into a date object or returns false.
 *
 * @param string $datetime_str The datetime string to parse (format: "mm/dd/yyyy HH:MM" or "mm-dd-yyyy HH:MM").
 *
 * @return \DateTime|false Returns a \DateTime object representing the parsed datetime or false if invalid.
 */
function parse_datetime(string $datetime_str): \DateTime|false {
    get_default_date_format();

    if (strlen($datetime_str) === 17) {

        if (strpos($datetime_str, '-') !== false) {
            $delimiter = '-';
        } else {
            $delimiter = '/';
        }

        // Extract the $date_str from the $datetime_str.
        $datetime_bits = explode(',', $datetime_str);
        if (count($datetime_bits) !== 2) {
            return false; // Invalid datetime string.
        }

        $date_str = trim($datetime_bits[0]);
        $time_str = trim($datetime_bits[1]);
        $time_obj = parse_time($time_str);

        if ($time_obj === false) {
            return false; // Could not extract valid time data.
        }

        $date_bits = explode($delimiter, $date_str);
        if (count($date_bits) === 3) {

            $day_vars['hours'] = $time_obj->format('G');
            $day_vars['minutes'] = $time_obj->format('i');
            $day_vars['year'] = $date_bits[2];

            if ((DEFAULT_DATE_FORMAT === 'mm/dd/yyyy') || (DEFAULT_DATE_FORMAT === 'mm-dd-yyyy')) {
                $day_vars['month'] = $date_bits[0];
                $day_vars['day'] = $date_bits[1];
            } else {
                $day_vars['day'] = $date_bits[0];
                $day_vars['month'] = $date_bits[1];
            }

            // Attempt to create a date object from the date components
            return create_date_from_array($day_vars);
        }
    }
    return false;
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
