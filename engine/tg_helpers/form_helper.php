<?php

declare(strict_types=1);

function form_open($location, $attributes = null, $additional_code = null)
{
    $extra = '';

    if (isset($attributes['method'])) {
        $method = $attributes['method'];
        unset($attributes['method']);
    } else {
        $method = 'post';
    }

    if (isset($attributes)) {
        foreach ($attributes as $key => $value) {
            $extra .= ' '.$key.'="'.$value.'"';
        }
    }

    if (filter_var($location, FILTER_VALIDATE_URL) === false) {
        $location = BASE_URL.$location;
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<form action="'.$location.'" method="'.$method.'"'.$extra.'>';
}

function form_open_upload($location, $attributes = null, $additional_code = null)
{
    $html = form_open($location, $attributes, $additional_code);
    return str_replace('>', ' enctype="multipart/form-data">', $html);
}

function form_close()
{
    $csrf_token = password_hash(session_id(), PASSWORD_BCRYPT, [
        'cost' => 11,
    ]);

    $html = '<input type="hidden" name="csrf_token" value="'.$csrf_token.'">';
    $html .= '</form>';

    if (isset($_SESSION['form_submission_errors'])) {
        $errors_json = json_encode($_SESSION['form_submission_errors']);
        $inline_validation_js = highlight_validation_errors($errors_json);
        $html .= $inline_validation_js;
        unset($_SESSION['form_submission_errors']);
    }

    return $html;
}

function build_output_str()
{
    return file_get_contents(APPPATH.'engine/views/highlight_errors.txt');
}

function highlight_validation_errors($errors_json)
{
    $code = '<div class="inline-validation-builder">';
    $output_str = build_output_str();
    $code .= '<script>let validationErrorsJson  = '.json_encode($errors_json).'</script>';
    $code .= '<script>';
    $code .= $output_str;
    $code .= '</script></div>';

    return $code;
}

function get_attributes_str($attributes)
{
    $attributes_str = '';
    if (! isset($value)) {
        $value = '';
    }

    if (isset($attributes)) {
        foreach ($attributes as $a_key => $a_value) {
            $attributes_str .= ' '.$a_key.'="'.$a_value.'"';
        }
    }

    return $attributes_str;
}

function form_label($label_text, $attributes = null, $additional_code = null)
{
    $extra = '';

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<label'.$extra.'>'.$label_text.'</label>';
}

function form_input($name, $value = null, $attributes = null, $additional_code = null)
{
    $extra = '';
    if (! isset($value)) {
        $value = '';
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<input type="text" name="'.$name.'" value="'.$value.'"'.$extra.'>';
}

function form_number($name, $value = null, $attributes = null, $additional_code = null)
{
    $html = form_input($name, $value, $attributes, $additional_code);
    return str_replace('type="text"', 'type="number"', $html);
}

function form_password($name, $value = null, $attributes = null, $additional_code = null)
{
    $html = form_input($name, $value, $attributes, $additional_code);
    return str_replace(' type="text" ', ' type="password" ', $html);
}

function form_email($name, $value = null, $attributes = null, $additional_code = null)
{
    $html = form_input($name, $value, $attributes, $additional_code);
    return str_replace(' type="text" ', ' type="email" ', $html);
}

function form_hidden($name, $value = null, $additional_code = null)
{
    $html = form_input($name, $value, $additional_code);
    return str_replace(' type="text" ', ' type="hidden" ', $html);
}

function form_textarea($name, $value = null, $attributes = null, $additional_code = null)
{
    $extra = '';
    if (! isset($value)) {
        $value = '';
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<textarea name="'.$name.'"'.$extra.'>'.$value.'</textarea>';
}

function form_submit($name, $value = null, $attributes = null, $additional_code = null)
{
    $extra = '';
    if (! isset($value)) {
        $value = $name;
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<button type="submit" name="'.$name.'" value="'.$value.'"'.$extra.'>'.$value.'</button>';
}

function form_button($name, $value = null, $attributes = null, $additional_code = null)
{
    $html = form_submit($name, $value, $attributes, $additional_code);
    return str_replace(' type="submit" ', ' type="button" ', $html);
}

function form_radio($name, $value = null, $checked = null, $attributes = null, $additional_code = null)
{
    $extra = '';

    if (! isset($value)) {
        $value = '1';
    }

    if (! isset($checked)) {
        $checked = false;
    }

    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if ($checked === true) {
        $extra .= ' checked';
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    return '<input type="radio" name="'.$name.'" value="'.$value.'"'.$extra.'>';
}

function form_checkbox($name, $value = null, $checked = null, $attributes = null, $additional_code = null)
{
    $html = form_radio($name, $value, $checked, $attributes, $additional_code);
    return str_replace(' type="radio" ', ' type="checkbox" ', $html);
}

function form_dropdown($name, $options, $selected_key = null, $attributes = null, $additional_code = null)
{
    $extra = '';
    if (isset($attributes)) {
        $extra = get_attributes_str($attributes);
    }

    if (isset($additional_code)) {
        $extra .= ' '.$additional_code;
    }

    $html = '<select name="'.$name.'"'.$extra.'>
';

    if (isset($options[$selected_key])) {
        $selected_value = $options[$selected_key];
        $html .= '<option value="'.$selected_key.'" selected>'.$selected_value.'</option>
';
    }

    if (isset($options[$selected_key])) {
        unset($options[$selected_key]);
    }

    foreach ($options as $option_key => $option_value) {
        $html .= '<option value="'.$option_key.'">'.$option_value.'</option>
';
    }

    $html .= '</select>';

    return $html;
}

function form_file_select($name, $attributes = null, $additional_code = null)
{
    $value = null;
    $html = form_input($name, $value, $attributes, $additional_code);
    return str_replace(' type="text" ', ' type="file" ', $html);
}

function post($field_name, $clean_up = null)
{
    if (! isset($_POST[$field_name])) {
        $value = '';
    } else {
        $value = $_POST[$field_name];

        if (isset($clean_up)) {
            $value = filter_string($value);

            if (is_numeric($value)) {
                $var_type = is_numeric(strpos($value, '.')) ? 'double' : 'int';
                settype($value, $var_type);
            }
        }
    }

    return $value;
}

/*
    IMPORTANT NOTE REGARDING STRIP_TAGS():

    It's possible that you may have to write and use your own, unique
    string filter methods depending on your specific use case. With this
    being the case, please note that strip_tags function has an optional
    second argument, which is a string of allowed HTML tags and attributes.
    If you want to allow certain HTML tags or attributes in the string,
    you can pass a list of allowed tags and attributes as the second argument.

    Example 1:

                $string = '<p>This is a <strong>test</strong> string.</p>';
                $filtered_string = strip_tags($string, '<strong>');
                echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string."

    Example 2:
                In this example, we allow both 'strong' tags and 'em' tags...

                $string = '<p>This is a <strong>test</strong> string.</p>';
                $filtered_string = strip_tags($string, '<strong><em>');
                echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string."

    Example 3:
                In this example, we allow the style attribute for the <em> tag...

                $string = '<p>This is a <strong>test</strong> string.</p><em style="color:red">Emphasis</em>';
                $filtered_string = strip_tags($string, '<strong><em style>');
                echo $filtered_string;  // Outputs: "This is a <strong>test</strong> string.<em style="color:red">Emphasis</em>"

    FINALLY
               If you pass an array of allowed tags into strip_tags, before a database insert,
               use html_entity_decode() when displaying the stored string in the browser.
*/

function filter_string($string, $allowed_tags = [])
{
    //Potentially suitable for filtering data submitted via textarea.

    //remove HTML & PHP tags (please read note above for more!)
    $string = strip_tags($string, $allowed_tags);

    // Apply XSS filtering
    $string = htmlspecialchars($string);

    // Convert multiple consecutive whitespaces to a single space, except for line breaks
    $string = preg_replace('/[^\S\r\n]+/', ' ', $string);

    // Trim leading and trailing white space
    return trim($string);
}

function filter_name($name, $allowed_chars = [])
{
    //Similar to filter_string() but better suited for usernames etc

    //remove HTML & PHP tags (please read note above for more!)
    $name = strip_tags($name);

    // Apply XSS filtering
    $name = htmlspecialchars($name);

    // Create a regex pattern that includes the allowed characters
    $pattern = '/[^a-zA-Z0-9\s';
    $pattern .= ! empty($allowed_chars) ? '['.implode('', $allowed_chars).']' : ']';
    $pattern .= '/';

    // Replace any characters that are not in the allowed list
    $name = preg_replace($pattern, '', $name);

    // Convert double spaces to single spaces
    $name = preg_replace('/\s+/', ' ', $name);

    // Trim leading and trailing white space
    return trim($name);
}
