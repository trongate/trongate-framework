<div class="container">
    <h1>Relation Details</h1>

    <?php
    // Read-only summary rows (decided in the wizard).
    $type = $wizard['relation_type'] ?? '';
    $parent = $wizard['parent_module'] ?? '';
    $child = $wizard['child_module'] ?? '';
    $singular_a = $wizard['singular_a'] ?? '';
    $singular_b = $wizard['singular_b'] ?? '';
    $bridging = ($wizard['bridging_table'] ?? false) ? 'Yes' : 'No';

    $summary_rows = [
        'Relation Type' => ucwords($type),
        'Module A' => $parent . ' (' . $singular_a . ')',
        'Module B' => $child . ' (' . $singular_b . ')',
        'Bridging Table' => $bridging
    ];

    echo '<table class="relation-summary">';
    foreach ($summary_rows as $label => $value) {
        echo '<tr><td class="row-key"><strong>' . out($label) . '</strong></td><td>' . out($value) . '</td></tr>';
    }
    echo '</table>';

    $form_attr = [
      'mx-post' => $form_location,
      'mx-after-swap' => 'afterValidation',
      'class' => 'highlight-errors sm'
    ];
    echo form_open('#', $form_attr);

    echo '<div class="form-row">';

    echo '<div class="form-column">';
    echo form_label('Identifier Column A');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'identifierColumnA-input'
    ];
    echo form_input('identifierColumnA', '', $attributes);
    echo '</div>';

    echo '<div class="form-column">';
    echo form_label('Identifier Column B');
    $attributes = [
        'autocomplete' => 'off',
        'id' => 'identifierColumnB-input'
    ];
    echo form_input('identifierColumnB', '', $attributes);
    echo '</div>';

    echo '</div>';

    echo '<p class="hint-text">Identifier columns are used to describe a record in dropdowns and association panels. Comma-separated multi-column values are allowed.</p>';

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
.relation-summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5em;
}

.relation-summary td {
    border: 1px solid #ccc;
    padding: 8px 12px;
}

.relation-summary .row-key {
    width: 35%;
    background: #f4f4f4;
}

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

.form-column input {
    width: 100%;
}

.hint-text {
    font-size: .85em;
    color: #555;
    margin-top: 1.5em;
    text-align: left;
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
// The mechanics (populateFormFromLocalStorage, afterValidation)
// live in flo.js so every Flo feature shares them.
window.floFieldMapping = {
    'identifier_column_a': 'identifierColumnA-input',
    'identifier_column_b': 'identifierColumnB-input'
};

window.floPostedToStorageMap = {
    'identifierColumnA': 'identifier_column_a',
    'identifierColumnB': 'identifier_column_b'
};

document.addEventListener('DOMContentLoaded', function () {
    populateFormFromLocalStorage(window.floFieldMapping);
});
</script>
