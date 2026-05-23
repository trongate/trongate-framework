<?php
foreach($posted_properties as $posted_property) {
    $property_type = $posted_property->property_type;
    $form_field_name = build_form_field_name($posted_property);
    if ($property_type === 'varchar' || $property_type === 'textarea') {
        echo '        $record_obj->'.$form_field_name.' = trim($record_obj->'.$form_field_name.');'.PHP_EOL;
    } elseif ($property_type === 'boolean') {
        echo '        $record_obj->'.$form_field_name.' = ($record_obj->'.$form_field_name.' == 1) ? \'yes\' : \'no\';'.PHP_EOL;
    }
}