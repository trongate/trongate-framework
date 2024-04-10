<?php
// Get file validation rules
$fileValidationRules = explode('|', $rules);

// Extract rule content and determine which validation tests need to be carried out
$fileChecksToRun = [];
foreach ($fileValidationRules as $fileValidationRule) {
    $ruleContent = _extract_content($fileValidationRule, '[', ']');
    $fileValidationTest = str_replace('[' . $ruleContent . ']', '', $fileValidationRule);
    $fileChecksToRun[$fileValidationTest] = $ruleContent;
}

// Get target file and its information
$targetFileKey = get_target_file();
$targetFile = $_FILES[$targetFileKey];
$tempFileName = $targetFile['tmp_name'];
$fileSize = $targetFile['size'] / 1000; // Convert size to kilobytes
$fileType = filetype($tempFileName);

// Perform file validation checks
foreach ($fileChecksToRun as $fileCheckKey => $fileCheckValue) {
    switch ($fileCheckKey) {
        case 'allowed_types':
            validate_allowed_types($targetFile['name'], $fileCheckValue);
            break;
        case 'max_size':
            validate_max_size($fileSize, $fileCheckValue);
            break;
        case 'max_height':
            validate_dimension('height', $tempFileName, $fileCheckValue);
            break;
        case 'max_width':
            validate_dimension('width', $tempFileName, $fileCheckValue);
            break;
        case 'min_height':
            validate_dimension('min_height', $tempFileName, $fileCheckValue);
            break;
        case 'min_width':
            validate_dimension('min_width', $tempFileName, $fileCheckValue);
            break;
        case 'square':
            validate_square($tempFileName);
            break;
        default:
            die('ERROR: Invalid validation rule.');
            break;
    }
}

// Function to validate allowed file types
function validate_allowed_types($fileName, $allowedTypes) {
    $allowedTypesArr = explode(',', $allowedTypes);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    if (!in_array($fileExtension, $allowedTypesArr)) {
        $result = 'The file type that you submitted is not allowed.';
        $this->form_submission_errors[$key][] = $result;
    }
}

// Function to validate maximum file size
function validate_max_size($fileSize, $maxSize) {
    if ($fileSize > $maxSize) {
        $result = 'The file that you attempted to upload exceeds the maximum allowed file size (' . $maxSize . ' kilobytes).';
        $this->form_submission_errors[$key][] = $result;
    }
}

// Function to validate image dimensions (height or width)
function validate_dimension($dimensionType, $tempFileName, $limitValue) {
    $dimensionData = getimagesize($tempFileName);
    $dimension = ($dimensionType === 'height') ? $dimensionData[1] : $dimensionData[0];
    $comparison = ($dimensionType === 'min_height' || $dimensionType === 'min_width') ? '<' : '>';
    if (!is_numeric($dimension) || ($dimension $comparison $limitValue)) {
        $result = 'The ' . $dimensionType . ' falls ' . (($comparison === '<') ? 'below' : 'exceeds') . ' the allowed limit (' . $limitValue . ' pixels).';
        $this->form_submission_errors[$key][] = $result;
    }
}

// Function to validate square image
function validate_square($tempFileName) {
    $dimensionData = getimagesize($tempFileName);
    $imageWidth = $dimensionData[0];
    $imageHeight = $dimensionData[1];
    if ($imageWidth !== $imageHeight) {
        $result = 'The image width does not match the image height.';
        $this->form_submission_errors[$key][] = $result;
    }
}

/**
 * Check if the file type is allowed based on the given allowed types.
 *
 * @param string $target_file_name The name of the target file.
 * @param string $file_check_value A comma-separated list of allowed file types.
 * @return string Returns an error message if the file type is not allowed; otherwise, returns an empty string.
 */
function check_is_allowed_type($target_file_name, $file_check_value) {

    $allowed_types = explode(',', $file_check_value);
    $bits = explode('.', $target_file_name);
    $file_type = $bits[count($bits) - 1];

    if (!in_array($file_type, $allowed_types)) {
        $result = 'The file type that you submitted is not allowed.';
    } else {
        $result = '';
    }

    return $result;
}

/**
 * Check if the file size is within the allowed limit.
 *
 * @param float $file_size The size of the file in kilobytes.
 * @param float $file_check_value The maximum allowed file size in kilobytes.
 * @return string Returns an error message if the file size exceeds the limit; otherwise, returns an empty string.
 */
function check_file_size($file_size, $file_check_value) {

    if ((!is_numeric($file_check_value)) || ($file_size > $file_check_value)) {
        $result = 'The file that you attempted to upload exceeds the maximum allowed file size (' . $file_check_value . ' kilobytes).';
    } else {
        $result = '';
    }

    return $result;
}

/**
 * Get the name of the target file from the $_FILES array.
 *
 * @return string The name of the target file.
 */
function get_target_file() {
    $userfile = array_keys($_FILES)[0];
    return $userfile;
}
