<?php
function build_dynamic_form_fields($data) {
    $posted_validation_tests = $data['properties'];
    $posted_properties = $data['posted_properties'];
    $form_field_rows = [];
    foreach($posted_properties as $posted_property) {
        switch ($posted_property->property_type) {
            case 'varchar':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'textarea':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'int':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'decimal':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'boolean':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'email':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'date':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'time':
                $form_field_row = build_form_field_row($posted_property);
                break;
            case 'date and time':
                $form_field_row = build_form_field_row($posted_property);
                break;
            default:
                $form_field_row = '';
                break;
        }
        $form_field_rows[] = $form_field_row; 
    }
    return $form_field_rows;
}
function build_form_field_row($posted_property) {
    $form_field_name = build_form_field_name($posted_property);
    $indent = '        ';
    $num_validation_rules = count($posted_property->validation_rules);
    $form_field_label = $posted_property->property_name;
    $form_field_name = str_replace('-', '_', url_title($posted_property->property_name));

    // Boolean checkboxes use a completely different rendering pattern
    if ($posted_property->property_type === 'boolean') {
        $row_str = $indent.'echo \'<label>\';'.PHP_EOL;
        $row_str.= $indent.'echo form_checkbox(\''.$form_field_name.'\', 1, $'.$form_field_name.');'.PHP_EOL;
        $row_str.= $indent.'echo \' '.$form_field_label.'\';'.PHP_EOL;
        $row_str.= $indent.'echo \'</label>\';';
        return $row_str;
    }

    $row_str = $indent.'echo form_label(\''.$form_field_label.'\');'.PHP_EOL;
    switch ($posted_property->property_type) {
        case 'varchar':
            $method_start = 'form_input';
            break;
        case 'textarea':
            $method_start = 'form_textarea';
            break;
        case 'int':
            $method_start = 'form_number';
            break;
        case 'decimal':
            $method_start = 'form_number';
            break;
        case 'email':
            $method_start = 'form_email';
            break;
        case 'date':
            $method_start = 'form_date';
            break;
        case 'time':
            $method_start = 'form_time';
            break;
        case 'date and time':
            $method_start = 'form_datetime_local';
            break;
        default:
            $method_start = 'form_input';
            break;
    }
    if ($num_validation_rules < 1) {
        $row_str.= $indent.'echo '.$method_start.'(\''.$form_field_name.'\', $'.$form_field_name.', [\'placeholder\' => \'Enter '.$form_field_label.'\']);'.PHP_EOL;
    } else {
        $row_str.= $indent.'$'.$form_field_name.'_attr = ['.PHP_EOL;
        $row_str.= $indent.'    \'placeholder\' => \'Enter '.$form_field_label.'\','.PHP_EOL;
        $counter = 0;
        foreach($posted_property->validation_rules as $validation_rule) {
            $counter++;
            $attr_validation_code= build_attr_validation_code($validation_rule, $indent);
            if ($attr_validation_code !== '') {
                $row_str.= $attr_validation_code;
                if ($counter < $num_validation_rules) {
                    $row_str.= ',';
                }
                $row_str.= PHP_EOL;
            }
        }
        if ($posted_property->property_type === 'decimal') {
            $row_str.= $indent.'    \'step\' => \'any\','.PHP_EOL;
        }
        $row_str.= $indent.'];'.PHP_EOL;
        $row_str.= $indent.'echo '.$method_start.'(\''.$form_field_name.'\', $'.$form_field_name.', $'.$form_field_name.'_attr);'.PHP_EOL;
    }
    $row_str = rtrim($row_str);
    return $row_str;
}
function build_attr_validation_code($validation_rule, $indent) {
    $rule_name = extract_rule_name($validation_rule);
    switch ($rule_name) {
        case 'required':
            $code = $indent.'    \'required\'  => true';
            break;
        case 'min length':
            $rule_value = extract_rule_value($validation_rule);
            $code = $indent.'    \'minlength\' => '.$rule_value;
            break;
        case 'max length':
            $rule_value = extract_rule_value($validation_rule);
            $code = $indent.'    \'maxlength\' => '.$rule_value;
            break;
        case 'greater than':
            $rule_value = extract_rule_value($validation_rule);
            $code = $indent.'    \'min\' => '.$rule_value;
            break;
        case 'less than':
            $rule_value = extract_rule_value($validation_rule);
            $code = $indent.'    \'max\' => '.$rule_value;
            break;
        case 'in the past':
            $code = $indent.'    \'data-date-constraint\' => \'past\'';
            break;
        case 'in the future':
            $code = $indent.'    \'data-date-constraint\' => \'future\'';
            break;
        default:
            $code = '';
            break;
    }
    return $code;
}
function extract_rule_value($validation_rule) {
    $rule_value = extract_content($validation_rule, '[', ']');
    return $rule_value;
}
function extract_rule_name($rule) {
    $pos = strpos($rule, '[');
    if ($pos !== false) {
        return substr($rule, 0, $pos);
    }
    return $rule;
}

function needs_date_constraint_js($data) {
    $posted_properties = $data['posted_properties'];
    $temporal_types = ['date', 'date and time', 'time'];
    foreach($posted_properties as $property) {
        if (in_array($property->property_type, $temporal_types)) {
            if (in_array('in the past', $property->validation_rules) ||
                in_array('in the future', $property->validation_rules)) {
                return true;
            }
        }
    }
    return false;
}

function build_dynamic_validation_methods($data) {
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

    $validation_methods = [];

    if ($needs_date_past) {
        $validation_methods[] = generate_model_validate_date_in_past();
    }
    if ($needs_date_future) {
        $validation_methods[] = generate_model_validate_date_in_future();
    }
    if ($needs_datetime_past) {
        $validation_methods[] = generate_model_validate_datetime_in_past();
    }
    if ($needs_datetime_future) {
        $validation_methods[] = generate_model_validate_datetime_in_future();
    }
    if ($needs_time_past) {
        $validation_methods[] = generate_model_validate_time_in_past();
    }
    if ($needs_time_future) {
        $validation_methods[] = generate_model_validate_time_in_future();
    }

    return $validation_methods;
}

function generate_model_validate_date_in_past() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a date is in the past (strictly before today).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $date The date value (YYYY-MM-DD).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_date_in_past(string $date): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($date === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat(\'Y-m-d\', $date);'.PHP_EOL;
    $code.= $indent.'    $today = new DateTime(\'today\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input >= $today) {'.PHP_EOL;
    $code.= $indent.'        return \'The date must be in the past.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_model_validate_date_in_future() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a date is in the future (strictly after today).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $date The date value (YYYY-MM-DD).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_date_in_future(string $date): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($date === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat(\'Y-m-d\', $date);'.PHP_EOL;
    $code.= $indent.'    $today = new DateTime(\'today\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input <= $today) {'.PHP_EOL;
    $code.= $indent.'        return \'The date must be in the future.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_model_validate_datetime_in_past() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a datetime is in the past (strictly before now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $datetime The datetime value (YYYY-MM-DDTHH:MM).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_datetime_in_past(string $datetime): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($datetime === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat(\'Y-m-d\\TH:i\', $datetime);'.PHP_EOL;
    $code.= $indent.'    $now = new DateTime(\'now\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input >= $now) {'.PHP_EOL;
    $code.= $indent.'        return \'The datetime must be in the past.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_model_validate_datetime_in_future() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a datetime is in the future (strictly after now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $datetime The datetime value (YYYY-MM-DDTHH:MM).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_datetime_in_future(string $datetime): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($datetime === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat(\'Y-m-d\\TH:i\', $datetime);'.PHP_EOL;
    $code.= $indent.'    $now = new DateTime(\'now\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input <= $now) {'.PHP_EOL;
    $code.= $indent.'        return \'The datetime must be in the future.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_model_validate_time_in_past() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a time is in the past (strictly before now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $time The time value (HH:MM or HH:MM:SS).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_time_in_past(string $time): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($time === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $format = (strlen($time) > 5) ? \'H:i:s\' : \'H:i\';'.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat($format, $time);'.PHP_EOL;
    $code.= $indent.'    $now = new DateTime(\'now\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input >= $now) {'.PHP_EOL;
    $code.= $indent.'        return \'The time must be in the past.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}

function generate_model_validate_time_in_future() {
    $indent = '    ';
    $code = $indent.'/**'.PHP_EOL;
    $code.= $indent.' * Validates that a time is in the future (strictly after now).'.PHP_EOL;
    $code.= $indent.' *'.PHP_EOL;
    $code.= $indent.' * @param string $time The time value (HH:MM or HH:MM:SS).'.PHP_EOL;
    $code.= $indent.' * @return string|bool Error message if validation fails, true if passes.'.PHP_EOL;
    $code.= $indent.' */'.PHP_EOL;
    $code.= $indent.'public function validate_time_in_future(string $time): string|bool {'.PHP_EOL;
    $code.= $indent.'    if ($time === \'\') {'.PHP_EOL;
    $code.= $indent.'        return true;'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    $format = (strlen($time) > 5) ? \'H:i:s\' : \'H:i\';'.PHP_EOL;
    $code.= $indent.'    $input = DateTime::createFromFormat($format, $time);'.PHP_EOL;
    $code.= $indent.'    $now = new DateTime(\'now\');'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    if ($input <= $now) {'.PHP_EOL;
    $code.= $indent.'        return \'The time must be in the future.\';'.PHP_EOL;
    $code.= $indent.'    }'.PHP_EOL;
    $code.= $indent.PHP_EOL;
    $code.= $indent.'    return true;'.PHP_EOL;
    $code.= $indent.'}';
    return $code;
}