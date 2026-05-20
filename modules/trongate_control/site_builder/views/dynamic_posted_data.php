return [
<?php
    $counter = 0;
    $total = count($posted_properties);
    foreach($posted_properties as $posted_property) {
        $counter++;
        $form_field_name = build_form_field_name($posted_property);
        if ($posted_property->property_type === 'textarea') {
            $row_str = '            \''.$form_field_name.'\' => trim(post(\''.$form_field_name.'\'))';
        } elseif ($posted_property->property_type === 'boolean') {
            $row_str = '            \''.$form_field_name.'\' => (int) (bool) post(\''.$form_field_name.'\', true)';
        } else {
            $row_str = '            \''.$form_field_name.'\' => post(\''.$form_field_name.'\', true)';
        }
        $row_str_end = ($counter === $total) ? PHP_EOL.'        ' : ','.PHP_EOL;
        echo $row_str.$row_str_end;
    }
        echo '];'.PHP_EOL;