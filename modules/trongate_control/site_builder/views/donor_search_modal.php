&lt;?php
/**
 * Search modal form for filtering <?= strtolower($record_name_plural) ?>.
 *
 * Loaded via MX into a modal when the Search button is clicked.
 */
$searchable_columns = [<?php
$properties_arr = json_decode($properties);
$posted_props = $this->model->est_posted_properties($properties_arr);
$first = true;
foreach ($posted_props as $prop) {
    if (($prop->is_searchable ?? 'no') === 'yes') {
        echo PHP_EOL . "    '" . $prop->form_field_name . "' => '" . addslashes($prop->property_name) . "',";
    }
}
?>

];

$form_attr = ['id' => 'search-form'];
echo form_open('<?= $module_folder_name ?>/submit_search', $form_attr);
?&gt;
    <div class="form-group">
        &lt;?= form_label('Search Query') ?&gt;
        &lt;?= form_input('search_query', '', ['placeholder' => 'Enter search term...', 'autocomplete' => 'off']) ?&gt;
    </div>
    <div class="form-group">
        &lt;?= form_label('Search In') ?&gt;
        &lt;?= form_dropdown('search_column', $searchable_columns) ?&gt;
    </div>
&lt;?= form_close() ?&gt;
