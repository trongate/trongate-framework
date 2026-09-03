<div class="container">
    <h1>Module Details</h1>

    <?php
    $form_attr = [
      'mx-post' => $form_location,
      'mx-after-swap' => 'afterValidation',
      'class' => 'highlight-errors sm'
    ];
    echo form_open('#', $form_attr);
    
    // Row 1: Module Directory and Record Name Singular
    echo '<div class="form-row">';
    
    echo '<div class="form-column">';
    echo form_label('Module Directory');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'moduleDir-input'
    ];
    echo form_input('moduleDir', '', $attributes);
    echo '</div>';
    
    echo '<div class="form-column">';
    echo form_label('Record Name Singular');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'recordNameSingular-input'
    ];
    echo form_input('recordNameSingular', '', $attributes);
    echo '</div>';
    
    echo '</div>';
    
    // Row 2: Record Name Plural and Nav Label
    echo '<div class="form-row">';
    
    echo '<div class="form-column">';
    echo form_label('Record Name Plural');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'recordNamePlural-input'
    ];
    echo form_input('recordNamePlural', '', $attributes);
    echo '</div>';
    
    echo '<div class="form-column">';
    echo form_label('Nav Label');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'navLabel-input'
    ];
    echo form_input('navLabel', '', $attributes);
    echo '</div>';
    
    echo '</div>';
    
    // Row 3: Properties (full width)
    echo form_label('Properties');
    $attributes = [
        'id' => 'properties-input',
        'rows' => '12'
    ];
    echo form_textarea('properties', '', $attributes);
    
    // Row 4: URL Column and Order By
    echo '<div class="form-row">';
    
    echo '<div class="form-column">';
    echo form_label('URL Column');
    $attributes = [
        'id' => 'urlColumn-input'
    ];
    echo form_dropdown('urlColumn', [], '', $attributes);
    echo '</div>';
    
    echo '<div class="form-column">';
    echo form_label('Order By');
    $attributes = [
        'id' => 'orderBy-input'
    ];
    echo form_dropdown('orderBy', [], '', $attributes);
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="text-center form-buttons">';
    
    $attributes = [
      'class'   => 'close-btn alt',
      'onclick' => "window.parent.postMessage('reload_iframe:' + '{$after_close_url}' + '|' + '{$after_close_width}' + '|' + '{$after_close_height}', '*')"
    ];
    echo form_button('close-btn', 'Close Window', $attributes);
    
    echo form_submit('submit', 'Update Details', array('class' => 'submit-btn'));
    
    echo '</div>';
    
    echo form_close();
    ?>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-column {
    display: flex;
    flex-direction: column;
}

.form-column label {
    margin-bottom: 5px;
}

.form-column input,
.form-column select,
.form-column textarea {
    width: 100%;
}

.form-buttons {
    margin-top: 2em;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
}

.form-buttons button {
    margin-left: 1em;
}
</style>


<script>
// Field mappings for this form — feature-specific data.
// The mechanics (populateFormFromLocalStorage, populateSelectDropdowns,
// afterValidation) live in flo.js so every Flo feature shares them.
window.floFieldMapping = {
    'module_folder_name': 'moduleDir-input',
    'record_name_singular': 'recordNameSingular-input',
    'record_name_plural': 'recordNamePlural-input',
    'nav_label': 'navLabel-input',
    'properties': 'properties-input',
    'urlColumn': 'urlColumn-input',
    'orderBy': 'orderBy-input'
};

window.floPostedToStorageMap = {
    'moduleDir': 'module_folder_name',
    'recordNameSingular': 'record_name_singular',
    'recordNamePlural': 'record_name_plural',
    'navLabel': 'nav_label',
    'properties': 'properties',
    'urlColumn': 'urlColumn',
    'orderBy': 'orderBy'
};

// Run the function when the DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    populateFormFromLocalStorage(window.floFieldMapping);
});
</script>
