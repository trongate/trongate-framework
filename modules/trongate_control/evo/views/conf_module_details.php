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
function populateFormFromLocalStorage() {
    // Map of localStorage keys to form input IDs
    const fieldMapping = {
        'module_folder_name': 'moduleDir-input',
        'record_name_singular': 'recordNameSingular-input',
        'record_name_plural': 'recordNamePlural-input',
        'nav_label': 'navLabel-input',
        'properties': 'properties-input',
        'urlColumn': 'urlColumn-input',
        'orderBy': 'orderBy-input'
    };

    // Populate each form field from localStorage
    Object.entries(fieldMapping).forEach(([storageKey, fieldId]) => {
        const value = localStorage.getItem(storageKey);
        
        if (value !== null) {
            const field = document.getElementById(fieldId);
            if (field) {
                if (fieldId === 'properties-input') {
                    // Beautify JSON for properties textarea
                    try {
                        const parsedJSON = JSON.parse(value);
                        field.value = JSON.stringify(parsedJSON, null, 2);
                    } catch (e) {
                        field.value = value;
                    }
                } else {
                    field.value = value;
                }
            }
        }
    });

    // Populate URL Column and Order By dropdowns based on properties
    populateSelectDropdowns();
}

function populateSelectDropdowns() {
    const propertiesValue = localStorage.getItem('properties');
    
    if (!propertiesValue) return;

    let properties = [];
    try {
        properties = JSON.parse(propertiesValue);
    } catch (e) {
        return;
    }

    // Populate URL Column dropdown
    const urlColumnSelect = document.getElementById('urlColumn-input');
    if (urlColumnSelect) {
        urlColumnSelect.innerHTML = '';
        
        // Add 'No URL Column' option first
        const noUrlOption = document.createElement('option');
        noUrlOption.value = '';
        noUrlOption.textContent = '-- No URL Column --';
        urlColumnSelect.appendChild(noUrlOption);
        
        properties.forEach(prop => {
            const option = document.createElement('option');
            option.value = prop.propertyName;
            option.textContent = prop.propertyName;
            urlColumnSelect.appendChild(option);
        });

        // Set the stored value if it exists, otherwise default to empty string
        const storedUrlColumn = localStorage.getItem('urlColumn');
        if (storedUrlColumn && storedUrlColumn.trim() !== '') {
            urlColumnSelect.value = storedUrlColumn;
        } else {
            urlColumnSelect.value = '';
        }
    }

    // Populate Order By dropdown
    const orderBySelect = document.getElementById('orderBy-input');
    if (orderBySelect) {
        orderBySelect.innerHTML = '';
        
        // Add 'id' option first
        const idOption = document.createElement('option');
        idOption.value = 'id';
        idOption.textContent = 'id';
        orderBySelect.appendChild(idOption);
        
        const idDescOption = document.createElement('option');
        idDescOption.value = 'id DESC';
        idDescOption.textContent = 'id DESC';
        orderBySelect.appendChild(idDescOption);

        // Add property options (using column names, not display names)
        properties.forEach(prop => {
            const columnName = prop.propertyName.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '_');
            const option = document.createElement('option');
            option.value = columnName;
            option.textContent = prop.propertyName;
            orderBySelect.appendChild(option);

            const descOption = document.createElement('option');
            descOption.value = columnName + ' DESC';
            descOption.textContent = prop.propertyName + ' DESC';
            orderBySelect.appendChild(descOption);
        });

        // Set the stored value if it exists, otherwise default to 'id'
        const storedOrderBy = localStorage.getItem('orderBy');
        if (storedOrderBy && storedOrderBy.trim() !== '') {
            orderBySelect.value = storedOrderBy;
        } else {
            orderBySelect.value = 'id';
        }
    }
}

function afterValidation() {
    
    try {
        // Find the posted-items-container div
        const container = document.querySelector('.posted-items-container');
        
        if (!container) {
            console.error('posted-items-container div not found');
            alert("Validation was successful");
            return;
        }
        
        // Extract the JSON text content
        const jsonText = container.textContent.trim();
        
        // Parse the JSON
        const postedData = JSON.parse(jsonText);
        
        // Map of posted field names to localStorage keys
        const fieldMapping = {
            'moduleDir': 'module_folder_name',
            'recordNameSingular': 'record_name_singular',
            'recordNamePlural': 'record_name_plural',
            'navLabel': 'nav_label',
            'properties': 'properties',
            'urlColumn': 'urlColumn',
            'orderBy': 'orderBy'
        };
        
        // Update localStorage with posted values
        Object.entries(fieldMapping).forEach(([postedKey, storageKey]) => {
            if (postedData.hasOwnProperty(postedKey)) {
                localStorage.setItem(storageKey, postedData[postedKey]);
            }
        });
        
        const closeBtn = document.querySelector('.close-btn');
        closeBtn.click();
        
    } catch (e) {
        console.error('Error extracting or parsing posted data:', e);
        alert("Error processing response");
    }
}

// Run the function when the DOM is ready
document.addEventListener('DOMContentLoaded', populateFormFromLocalStorage);
</script>
