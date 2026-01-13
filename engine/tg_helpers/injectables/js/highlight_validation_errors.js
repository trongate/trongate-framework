document.addEventListener('DOMContentLoaded', function() {
    // Only proceed if validation errors exist
    if (typeof window.trongateValidationErrors === 'undefined') {
        return;
    }

    const forms = document.querySelectorAll('form.highlight-errors');
    const validationErrors = window.trongateValidationErrors;

    forms.forEach(function(form) {
        // Add CSS classes to error fields
        highlightErrorFields(form, validationErrors);

        // Check for data-error-display attribute
        const errorDisplayEndpoint = form.getAttribute('data-error-display');
        
        // If found, fetch and insert error message
        if (errorDisplayEndpoint) {
            fetchAndDisplayErrorMessage(form, errorDisplayEndpoint);
        }
    });

    function highlightErrorFields(form, errors) {
        const formFields = form.elements;
        
        for (const fieldName in errors) {
            for (let i = 0; i < formFields.length; i++) {
                if (formFields[i].name === fieldName) {
                    const field = formFields[i];
                    const fieldType = field.type;
                    
                    if (fieldType === 'checkbox' || fieldType === 'radio') {
                        const parentDiv = field.closest('div');
                        if (parentDiv) {
                            parentDiv.classList.add('form-field-validation-error');
                            parentDiv.style.textIndent = '7px';
                            
                            const prevSibling = parentDiv.previousElementSibling;
                            if (prevSibling && prevSibling.classList.contains('validation-error-report')) {
                                prevSibling.style.marginTop = '21px';
                            }
                        }
                    } else {
                        field.classList.add('form-field-validation-error');
                    }
                }
            }
        }
    }

    function fetchAndDisplayErrorMessage(form, endpoint) {
        fetch('{{BASE_URL}}' + endpoint)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Failed to fetch validation message');
                }
                return response.text();
            })
            .then(function(html) {
                // Insert the HTML immediately before the form
                form.insertAdjacentHTML('beforebegin', html);
            })
            .catch(function(error) {
                console.error('Validation error display failed:', error);
            });
    }
});