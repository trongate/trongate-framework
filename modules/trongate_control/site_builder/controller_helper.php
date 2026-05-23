<?php
function build_dynamic_validation_tests($data) {
        $posted_validation_tests = $data['properties'];
        $posted_properties = $data['posted_properties'];
        $validation_tests = [];
        foreach($posted_properties as $posted_property) {
            switch ($posted_property->property_type) {
                case 'varchar':
                    $validation_tests_row = build_validation_tests_row_varchar($posted_property);
                    break;
                case 'textarea':
                    $validation_tests_row = build_validation_tests_row_varchar($posted_property);
                    break;
                case 'int':
                    $validation_tests_row = build_validation_tests_row_int($posted_property);
                    break;
                case 'decimal':
                    $validation_tests_row = build_validation_tests_row_decimal($posted_property);
                    break;
                case 'boolean':
                    $validation_tests_row = '';
                    break;
                case 'email':
                    $validation_tests_row = build_validation_tests_row_email($posted_property);
                    break;
                case 'date':
                    $validation_tests_row = build_validation_tests_row_date($posted_property);
                    break;
                case 'time':
                    $validation_tests_row = build_validation_tests_row_time($posted_property);
                    break;
                case 'date and time':
                    $validation_tests_row = build_validation_tests_row_datetime_local($posted_property);
                    break;
                
                default:
                    $validation_tests_row = '';
                    break;
            }

            $validation_tests[] = $validation_tests_row; 
        }

        return $validation_tests;
}

function build_validation_tests_row_varchar($posted_property) {
    $form_field_name = build_form_field_name($posted_property);
    $validation_label = trim(strtolower($posted_property->property_name));

    $num_validation_rules = count($posted_property->validation_rules);
    if ($num_validation_rules < 1) {
        return '';
    }

    $validation_tests_str = build_validation_tests_str($posted_property);
    $code = "\$this->validation->set_rules('$form_field_name', '$validation_label', '$validation_tests_str');";
    return $code;
}

function build_validation_tests_row_int($posted_property) {
    $posted_property->validation_rules = array_merge(['integer'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_row_decimal($posted_property) {
    $posted_property->validation_rules = array_merge(['decimal'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_row_email($posted_property) {
    $posted_property->validation_rules = array_merge(['valid_email'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_row_date($posted_property) {
    $posted_property->validation_rules = array_merge(['valid_date'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_row_time($posted_property) {
    $posted_property->validation_rules = array_merge(['valid_time'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_row_datetime_local($posted_property) {
    $posted_property->validation_rules = array_merge(['valid_datetime_local'], $posted_property->validation_rules);
    $code = build_validation_tests_row_varchar($posted_property);
    return $code;
}

function build_validation_tests_str($posted_property) {
    $num_validation_rules = count($posted_property->validation_rules);
    $validation_tests_str = '';
    $counter = 0;
    foreach($posted_property->validation_rules as $validation_rule) {
        $counter++;
        $validation_tests_str.= $validation_rule;
        if ($counter < $num_validation_rules) {
            $validation_tests_str.= '|';
        }
    }

    $validation_tests_str = str_replace('min length[', 'min_length[', $validation_tests_str);
    $validation_tests_str = str_replace('max length[', 'max_length[', $validation_tests_str);
    $validation_tests_str = str_replace('greater than[', 'greater_than[', $validation_tests_str);
    $validation_tests_str = str_replace('less than[', 'less_than[', $validation_tests_str);
    if ($posted_property->property_type === 'date and time') {
        $validation_tests_str = str_replace('in the past', 'callback_datetime_in_past', $validation_tests_str);
        $validation_tests_str = str_replace('in the future', 'callback_datetime_in_future', $validation_tests_str);
    } elseif ($posted_property->property_type === 'time') {
        $validation_tests_str = str_replace('in the past', 'callback_time_in_past', $validation_tests_str);
        $validation_tests_str = str_replace('in the future', 'callback_time_in_future', $validation_tests_str);
    } else {
        $validation_tests_str = str_replace('in the past', 'callback_date_in_past', $validation_tests_str);
        $validation_tests_str = str_replace('in the future', 'callback_date_in_future', $validation_tests_str);
    }
    return $validation_tests_str;
}

function build_form_field_name($posted_property) {
    $form_field_name = url_title($posted_property->property_name);
    $form_field_name = str_replace('-', '_', $form_field_name);
    return $form_field_name;
}

function build_dynamic_callback_methods($data) {
    $posted_properties = $data['posted_properties'];
    $needs_date_past = false;
    $needs_date_future = false;
    $needs_datetime_past = false;
    $needs_datetime_future = false;
    $needs_time_past = false;
    $needs_time_future = false;

    foreach($posted_properties as $property) {
        if ($property->property_type === 'date') {
            if (in_array('in the past', $property->validation_rules)) {
                $needs_date_past = true;
            }
            if (in_array('in the future', $property->validation_rules)) {
                $needs_date_future = true;
            }
        }
        if ($property->property_type === 'date and time') {
            if (in_array('in the past', $property->validation_rules)) {
                $needs_datetime_past = true;
            }
            if (in_array('in the future', $property->validation_rules)) {
                $needs_datetime_future = true;
            }
        }
        if ($property->property_type === 'time') {
            if (in_array('in the past', $property->validation_rules)) {
                $needs_time_past = true;
            }
            if (in_array('in the future', $property->validation_rules)) {
                $needs_time_future = true;
            }
        }
    }

    $callback_methods = [];

    if ($needs_date_past) {
        $callback_methods[] = generate_callback_date_in_past($data);
    }
    if ($needs_date_future) {
        $callback_methods[] = generate_callback_date_in_future($data);
    }
    if ($needs_datetime_past) {
        $callback_methods[] = generate_callback_datetime_in_past($data);
    }
    if ($needs_datetime_future) {
        $callback_methods[] = generate_callback_datetime_in_future($data);
    }
    if ($needs_time_past) {
        $callback_methods[] = generate_callback_time_in_past($data);
    }
    if ($needs_time_future) {
        $callback_methods[] = generate_callback_time_in_future($data);
    }

    return $callback_methods;
}

function generate_callback_date_in_past($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a date is in the past (strictly before today).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $date The date value (YYYY-MM-DD).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function date_in_past(string $date): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/date_in_past\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_date_in_past($date);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_callback_date_in_future($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a date is in the future (strictly after today).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $date The date value (YYYY-MM-DD).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function date_in_future(string $date): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/date_in_future\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_date_in_future($date);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_callback_datetime_in_past($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a datetime is in the past (strictly before now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $datetime The datetime value (YYYY-MM-DDTHH:MM).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function datetime_in_past(string $datetime): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/datetime_in_past\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_datetime_in_past($datetime);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_callback_datetime_in_future($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a datetime is in the future (strictly after now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $datetime The datetime value (YYYY-MM-DDTHH:MM).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function datetime_in_future(string $datetime): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/datetime_in_future\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_datetime_in_future($datetime);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_callback_time_in_past($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a time is in the past (strictly before now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $time The time value (HH:MM or HH:MM:SS).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function time_in_past(string $time): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/time_in_past\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_time_in_past($time);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_callback_time_in_future($data) {
    $module_name = $data['module_folder_name'];
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a time is in the future (strictly after now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $time The time value (HH:MM or HH:MM:SS).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function time_in_future(string $time): string|bool {'.PHP_EOL;
    $code.= $indent.'    block_url(\''.$module_name.'/time_in_future\');'.PHP_EOL;
    $code.= $indent.'    return $this->model->validate_time_in_future($time);'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}