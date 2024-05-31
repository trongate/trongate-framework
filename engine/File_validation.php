<?php

/**
 * File_validation Class
 *
 * This class provides internal file validation services for the Trongate framework and is 
 * specifically designed to be invoked by the Validation class (Validation.php). It handles
 * various file validation criteria such as file type, size, and dimensions.
 * 
 * The class is not intended for direct use by framework users and is not included in the 
 * public documentation to avoid misuse. It is an integral part of the framework's validation 
 * mechanism, especially in the handling of file inputs.
 *
 * Modifications to this class should be handled with caution, as changes can affect
 * the core file validation functionalities that are crucial for file input operations
 * throughout the framework. Developers are encouraged to consult with the framework's 
 * maintainers or refer to the developer guidelines before altering this class.
 *
 * Usage:
 * This class is invoked through the 'run_file_validation()' method by the Validation class
 * when file validation is required. It is not intended to be accessed directly by other 
 * parts of the application or by end users.
 */

class File_validation {
    private $form_submission_errors = [];

    /**
     * Validates file input based on provided rules.
     * This function handles the validation of file inputs by applying specific rules
     * such as file type, size, and dimensions, and records any validation errors.
     *
     * @param string $key The key in the form data used to identify the file input.
     * @param string $rules The validation rules separated by '|'. Each rule can optionally include parameters inside brackets.
     * @return void Stores any validation errors in the form_submission_errors array.
     */
    public function run_file_validation(string $key, string $rules): void {
        $file_validation_rules = explode('|', $rules);
        $file_checks_to_run = [];

        foreach ($file_validation_rules as $file_validation_rule) {
            $rule_content = $this->extract_content($file_validation_rule, '[', ']');
            $file_validation_test = str_replace('[' . $rule_content . ']', '', $file_validation_rule);
            $file_checks_to_run[$file_validation_test] = $rule_content;
        }

        $target_file = $this->get_target_file();
        $target_file = $_FILES[$target_file];
        $temp_file_name = $target_file['tmp_name'];
        $file_size = $target_file['size'] / 1000; // Convert size to kilobytes

        foreach ($file_checks_to_run as $file_check_key => $file_check_value) {
            $result = $this->perform_check($file_check_key, $file_check_value, $target_file, $temp_file_name, $file_size);
            if ($result !== '') {
                $this->form_submission_errors[$key][] = $result;
            }
        }
    }

    /**
     * Performs specific file validation checks based on the given key and value.
     * This method is part of a file validation process that handles various checks such as file type,
     * file size, and image dimensions based on predefined rules.
     *
     * @param string $file_check_key The type of check to perform (e.g., 'allowed_types', 'max_size').
     * @param mixed $file_check_value The value or parameter associated with the check (e.g., allowed extensions, maximum size).
     * @param array $target_file The file array from $_FILES that contains file details like name and size.
     * @param string $temp_file_name The temporary filename of the file being uploaded.
     * @param float $file_size The size of the file in kilobytes.
     * @return mixed Returns the result of the validation check, which could be a boolean or a string containing an error message.
     * @throws Exception If an invalid validation rule key is provided.
     */
    private function perform_check(string $file_check_key, $file_check_value, array $target_file, string $temp_file_name, float $file_size) {
        $dimension_data = null;
        switch ($file_check_key) {
            case 'allowed_types':
                return $this->check_is_allowed_type($target_file['name'], $file_check_value);
            case 'max_size':
                return $this->check_file_size($file_size, $file_check_value);
            case 'max_height':
            case 'max_width':
            case 'min_height':
            case 'min_width':
            case 'square':
                if (!isset($dimension_data)) {
                    $dimension_data = getimagesize($temp_file_name);
                }
                return $this->check_dimensions($dimension_data, $file_check_key, $file_check_value);
            default:
                throw new Exception('ERROR: Invalid validation rule.');
        }
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
    private function extract_content(string $string, string $start_delim, string $end_delim): string {
        if (($start_pos = strpos($string, $start_delim)) !== false) {
            $start_pos += strlen($start_delim);
            if (($end_pos = strpos($string, $end_delim, $start_pos)) !== false) {
                return substr($string, $start_pos, $end_pos - $start_pos);
            }
        }
        return '';
    }

    /**
     * Checks if the file extension of the given file name is within the allowed types.
     * This function parses the file name to extract its extension and compares it against
     * a list of allowed file types. If the file's extension is not in the allowed list,
     * an error message is returned.
     *
     * @param string $file_name The name of the file to check.
     * @param string $types A comma-separated string listing the allowed file extensions.
     * @return string An error message if the file type is not allowed; otherwise, an empty string.
     */
    private function check_is_allowed_type(string $file_name, string $types): string {
        $allowed_types = explode(',', $types);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            return 'The file type that you submitted is not allowed.';
        }
        return '';
    }

    /**
     * Checks if the given file size exceeds the specified maximum size.
     * This function compares the size of a file against a predefined maximum size limit.
     * If the file size is greater than the allowed maximum, an error message is returned.
     *
     * @param float $file_size The size of the file in kilobytes.
     * @param float $max_size The maximum file size allowed in kilobytes.
     * @return string An error message if the file size exceeds the maximum allowed size; otherwise, an empty string.
     */
    private function check_file_size(float $file_size, float $max_size): string {
        if ($file_size > $max_size) {
            return 'The file that you attempted to upload exceeds the maximum allowed file size (' . $max_size . ' kilobytes).';
        }
        return '';
    }

    /**
     * Checks the dimensions of an image against specified criteria.
     * This function assesses whether an image's dimensions comply with given requirements
     * such as maximum or minimum height and width, or if the image must be square.
     * Returns an error message if the image fails to meet the specified criteria.
     *
     * @param array $dimension_data Array containing the image's width at index 0 and height at index 1.
     * @param string $check_type The type of dimension check to perform ('max_height', 'max_width', 'min_height', 'min_width', 'square').
     * @param int $value The value to compare against the image dimension, which could be max/min height or width in pixels.
     * @return string An error message if the dimension check fails; otherwise, an empty string.
     */
    private function check_dimensions(array $dimension_data, string $check_type, int $value): string {
        $image_width = $dimension_data[0];
        $image_height = $dimension_data[1];

        switch ($check_type) {
            case 'max_height':
                if ($image_height > $value) {
                    return 'The file exceeds the maximum allowed height (' . $value . ' pixels).';
                }
                break;
            case 'max_width':
                if ($image_width > $value) {
                    return 'The file exceeds the maximum allowed width (' . $value . ' pixels).';
                }
                break;
            case 'min_height':
                if ($image_height < $value) {
                    return 'The image height falls below the minimum allowed height (' . $value . ' pixels).';
                }
                break;
            case 'min_width':
                if ($image_width < $value) {
                    return 'The image width falls below the minimum allowed width (' . $value . ' pixels).';
                }
                break;
            case 'square':
                if ($image_width !== $image_height) {
                    return 'The image width does not match the image height.';
                }
                break;
        }
        return '';
    }

    /**
     * Retrieves the key of the first file in the global $_FILES array.
     * This function is typically used to access the file data of the first uploaded file
     * when dealing with single file uploads. It assumes that the $_FILES array is not empty.
     *
     * @return string The key (name) of the first file in the $_FILES superglobal array.
     */
    public function get_target_file(): string {
        return array_keys($_FILES)[0];
    }
}
