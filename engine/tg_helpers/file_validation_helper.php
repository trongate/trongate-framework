<?php
$file_validation_rules = explode('|', $rules);

//figure out which validation tests need to be carried out
foreach ($file_validation_rules as $file_validation_rule) {
    $rule_content = $this->_extract_content($file_validation_rule, '[', ']');
    $file_validation_test = str_replace('['.$rule_content.']', '', $file_validation_rule);
    $file_checks_to_run[$file_validation_test] = $rule_content;
}

$target_file = get_target_file();
$target_file = $_FILES[$target_file];
$temp_file_name = $target_file['tmp_name'];
$file_size = $target_file['size']/1000; //kilobytes)

$filetype = filetype($temp_file_name);

foreach ($file_checks_to_run as $file_check_key => $file_check_value) {
    switch ($file_check_key) {
        case 'allowed_types': //make sure the file is one of the allowed types
            $result = check_is_allowed_type($target_file['name'], $file_check_value);

            if ($result !='') {
                $this->form_submission_errors[$key][] = $result;
            } 

            break;
        case 'max_size': //make sure the file is no greater than this file size 
            $result = check_file_size($file_size, $file_check_value);

            if ($result !='') {
                $this->form_submission_errors[$key][] = $result;
            } 
            break;
        case 'max_height': 
            
            if (!isset($dimension_data)) {
                $dimension_data = getimagesize($temp_file_name);
            }

            $image_height = $dimension_data[1];

            if ((!is_numeric($image_height)) || ($image_height>$file_check_value)) {
                $result = 'The file exceeds the maximum allowed height ('.$file_check_value.' pixels).';
                $this->form_submission_errors[$key][] = $result;
            }

            break;
        case 'max_width':

            if (!isset($dimension_data)) {
                $dimension_data = getimagesize($temp_file_name);
            }

            $image_width = $dimension_data[0];

            if ((!is_numeric($image_width)) || ($image_width>$file_check_value)) {
                $result = 'The file exceeds the maximum allowed width ('.$file_check_value.' pixels).';
                $this->form_submission_errors[$key][] = $result;
            }
            
            break;
        case 'min_height': 
            
            if (!isset($dimension_data)) {
                $dimension_data = getimagesize($temp_file_name);
            }

            $image_height = $dimension_data[1];

            if ((!is_numeric($image_height)) || ($image_height<$file_check_value)) {
                $result = 'The image height falls below the minimum allowed height ('.$file_check_value.' pixels).';
                $this->form_submission_errors[$key][] = $result;
            }

            break;
        case 'min_width':

            if (!isset($dimension_data)) {
                $dimension_data = getimagesize($temp_file_name);
            }

            $image_width = $dimension_data[0];

            if ((!is_numeric($image_width)) || ($image_width<$file_check_value)) {
                $result = 'The image width falls below the minimum allowed width ('.$file_check_value.' pixels).';
                $this->form_submission_errors[$key][] = $result;
            }
            
            break;
        case 'square':

            if (!isset($dimension_data)) {
                $dimension_data = getimagesize($temp_file_name);
            }

            $image_width = $dimension_data[0];
            $image_height = $dimension_data[1];

            if ($image_width !== $image_height) {
                $result = 'The image width does not match the image height.';
                $this->form_submission_errors[$key][] = $result;            
            }
 
            break;
        default:
            die('ERROR: Invalid validation rule.');  
            break;
    }
}

function check_is_allowed_type($target_file_name, $file_check_value) {

    $allowed_types = explode(',', $file_check_value);
    $bits = explode('.', $target_file_name);
    $file_type = $bits[count($bits)-1];

    if (!in_array($file_type, $allowed_types)) {
        $result = 'The file type that you submitted is not allowed.';
    } else {
        $result = '';
    }

    return $result;

}

function check_file_size($file_size, $file_check_value) {

    if ((!is_numeric($file_check_value)) || ($file_size>$file_check_value)) {
        $result = 'The file that you attempted to upload exceeds the maximum allowed file size ('.$file_check_value.' kilobytes).';
    } else {
        $result = '';
    }

    return $result;
}

function get_target_file() {
    $userfile = array_keys($_FILES)[0];
    return $userfile;
}