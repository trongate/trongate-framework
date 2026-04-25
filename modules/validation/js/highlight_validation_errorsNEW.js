document.addEventListener('DOMContentLoaded', function() {

    if (typeof window.trongateValidationErrors === 'undefined') return;

    const forms = document.querySelectorAll('form.highlight-errors');
    const validationErrors = window.trongateValidationErrors;

    forms.forEach(form => {
        highlightErrorFields(form, validationErrors);

        const errorDisplayEndpoint = form.getAttribute('data-error-display');
        if (errorDisplayEndpoint) {
            fetchAndDisplayErrorMessage(form, errorDisplayEndpoint);
        }
        
        // Optional: Add a subtle shake to the form for modern feedback
        form.classList.add('shake'); 
    });

    function highlightErrorFields(form, errors) {

        for (const fieldName in errors) {
            const field = form.elements[fieldName]; // Direct access is faster
            if (!field) continue;

            // Handle multi-element fields (like radio groups)
            const elements = field instanceof RadioNodeList ? field : [field];

            elements.forEach(el => {
                const fieldType = el.type;
                if (fieldType === 'checkbox' || fieldType === 'radio') {
                    const parentDiv = el.closest('div');
                    if (parentDiv) {
                        parentDiv.classList.add('form-field-validation-error');
                        // No inline styles here! CSS will handle the rest.
                    }
                } else {
                    el.classList.add('form-field-validation-error');
                }
            });
        }
    }

    function fetchAndDisplayErrorMessage(form, endpoint) {

        // Use a template literal for cleaner URL construction
        fetch(`{{BASE_URL}}${endpoint}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                form.insertAdjacentHTML('beforebegin', html);
            })
            .catch(error => console.error('Error:', error));
    }
});