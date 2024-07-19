const methodAttributes = ['mx-get', 'mx-post', 'mx-put', 'mx-delete', 'mx-patch'];

function invokeFormPost(containingForm, triggerEvent, httpMethodAttribute) {
    
    // Establish the target URL.
    const targetUrl = containingForm.getAttribute(httpMethodAttribute);

    // Establish the request type.
    const requestType = httpMethodAttribute.replace('mx-', '').toUpperCase();

    // Attempt to display 'loading' element (indicator).
    attemptActivateLoader(containingForm);

    const formData = new FormData(containingForm);

    const http = new XMLHttpRequest();
    http.open('POST', targetUrl);

    // Attach Trongate MX header to identify the request
    http.setRequestHeader('Trongate-MX-Request', 'true');

    // No need to set Content-Type header when sending FormData
    http.send(formData);

    // Check if 'mx-token' attribute exists
    const mxToken = containingForm.getAttribute('mx-token');
    if (mxToken) {
        // Attach Trongate Token as a custom header
        http.setRequestHeader('trongateToken', mxToken);
    }

    // Check for mx-headers attribute
    const mxHeadersAttr = containingForm.getAttribute('mx-headers');
    if (mxHeadersAttr) {
        try {
            // Use makeValidJsonString to fix the input string
            const fixedJsonStr = makeValidJsonString(mxHeadersAttr);
            const headersArray = JSON.parse(fixedJsonStr);

            if (Array.isArray(headersArray)) {
                headersArray.forEach(header => {
                    if (header.key && header.value) {
                        http.setRequestHeader(header.key, header.value);
                    }
                });
            } else {
                console.error('mx-headers attribute should be an array of objects.');
            }
        } catch (error) {
            console.error('Error parsing or fixing mx-headers attribute:', error);
        }
    }

    http.onload = function() {
        attemptHideLoader(containingForm);
        
        // Only reset the form if the request was successful
        if (http.status >= 200 && http.status < 300) {
            containingForm.reset();
        }

        handleHttpResponse(http, containingForm);
    };

    http.onerror = function() {
        attemptHideLoader(containingForm);
        console.error('Form request failed');
        // Handle error (e.g., show error message to user)
    };

    http.ontimeout = function() {
        attemptHideLoader(containingForm);
        console.error('Form request timed out');
        // Handle timeout (e.g., show timeout message to user)
    };
}

function mxSubmitForm(element, triggerEvent, httpMethodAttribute) {
    const containingForm = element.closest('form');
    if (!containingForm) {
        console.error('No containing form found');
        return;
    }

    const submitButton = containingForm.querySelector('button[type="submit"]');
    if (submitButton) {
        // Clear existing validation errors
        clearExistingValidationErrors(containingForm);

        submitButton.disabled = true; // Disable submit button

        // The following three attribute types require an attempt to collect form data.
        const requiresDataAttributes = ['mx-post', 'mx-put', 'mx-patch'];

        if (requiresDataAttributes.includes(httpMethodAttribute)) {
            invokeFormPost(containingForm, triggerEvent, httpMethodAttribute);
        } else {
            initInvokeHttpRequest(containingForm, httpMethodAttribute);
        }
    } else {
        console.log('no submit button found');
    }
}

function clearExistingValidationErrors(containingForm) {
    // Remove the 'validation-error-alert' element if it exists
    const validationErrorsAlert = document.querySelector('.validation-error-alert');
    if (validationErrorsAlert) {
        validationErrorsAlert.remove();
    }
    
    // Remove 'validation-error-report' elements within the form
    containingForm.querySelectorAll('.validation-error-report')
        .forEach(el => el.remove());

    // Remove the 'form-field-validation-error' class from form fields
    containingForm.querySelectorAll('.form-field-validation-error')
        .forEach(el => el.classList.remove('form-field-validation-error'));
}

function initInvokeHttpRequest(element, httpMethodAttribute) {

    const buildModalStr = element.getAttribute('mx-build-modal');

    if (buildModalStr) {
        const modalOptions = parseModalOptions(buildModalStr);

        if (typeof modalOptions === "string") {
            const modalData = {
                id: modalOptions
            }

            buildMXModal(modalData, element, httpMethodAttribute);

        } else {
            buildMXModal(modalOptions, element, httpMethodAttribute);
        }

    } else {
        invokeHttpRequest(element, httpMethodAttribute);
    }

}

function parseModalOptions(value) {
    // Trim whitespace from the value
    value = value.trim();

    // Check if the value starts and ends with '{' and '}', or '[' and ']'
    if ((value.startsWith('{') && value.endsWith('}')) || 
        (value.startsWith('[') && value.endsWith(']'))) {
        try {
            // Attempt to parse as JSON
            return JSON.parse(value);
        } catch (e) {
            // If parsing fails, return the trimmed string
            console.warn("Invalid JSON in mx-build-modal:", value);
            return value;
        }
    } else {
        // If it's not enclosed in curly braces or square brackets, return the trimmed string
        return value;
    }
}

function buildMXModal(modalData, element, httpMethodAttribute) {
    const modalId = modalData.id;

    // Remove any existing elements that have this 'id' to prevent duplicate elements.
    const existingEl = document.getElementById(modalId);
    if (existingEl) {
        existingEl.remove();
    }

    // Create the modal container
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = modalId;
    modal.style.display = 'none';

    // Conditionally create the modal heading
    if (modalData.modalHeading) {
        const modalHeading = document.createElement('div');
        modalHeading.className = 'modal-heading';
        modalHeading.innerHTML = modalData.modalHeading;
        modal.appendChild(modalHeading);
    }

    // Create the modal body
    const modalBody = document.createElement('div');
    modalBody.className = 'modal-body';

    // Create a spinner div
    const tempSpinner = document.createElement('div');
    tempSpinner.setAttribute('class', 'spinner mt-3 mb-3');
    modalBody.appendChild(tempSpinner);

    // Append the modal body to the modal container
    modal.appendChild(modalBody);

    // Append the modal to the body
    document.body.appendChild(modal);

    // Open the modal
    openModal(modalId);

    // Adjust modal width if specified
    const targetModal = document.getElementById(modalId);
    if (modalData.width) {
        targetModal.style.maxWidth = modalData.width;
    }

    // Update mx-target attribute on element
    if (element.hasAttribute('mx-target')) {
        element.removeAttribute('mx-target');
    }
    const modalBodySelector = `#${modalId} .modal-body`;
    element.setAttribute('mx-target', modalBodySelector);

    // Invoke HTTP request
    invokeHttpRequest(element, httpMethodAttribute);
}

function invokeHttpRequest(element, httpMethodAttribute) {
    // Establish the target URL.
    const targetUrl = element.getAttribute(httpMethodAttribute);
    
    // Establish the request type.
    const requestType = httpMethodAttribute.replace('mx-', '').toUpperCase();
    
    // Attempt to display 'loading' element (indicator).
    attemptActivateLoader(element);

    const http = new XMLHttpRequest();
    http.open(requestType, targetUrl);
    http.setRequestHeader('Accept', 'text/html');
    http.timeout = 10000; // 10 seconds timeout

    // Check if 'mx-token' attribute exists
    const mxToken = element.getAttribute('mx-token');
    if (mxToken) {
        // Attach Trongate Token as a custom header
        http.setRequestHeader('trongateToken', mxToken);
    }

    // Set Trongate MX header to identify the request
    http.setRequestHeader('Trongate-MX-Request', 'true');

    // Check for mx-headers attribute
    const mxHeadersAttr = element.getAttribute('mx-headers');
    if (mxHeadersAttr) {
        try {
            // Use makeValidJsonString to fix the input string
            const fixedJsonStr = makeValidJsonString(mxHeadersAttr);
            const headersArray = JSON.parse(fixedJsonStr);

            if (Array.isArray(headersArray)) {
                headersArray.forEach(header => {
                    if (header.key && header.value) {
                        http.setRequestHeader(header.key, header.value);
                    }
                });
            } else {
                console.error('mx-headers attribute should be an array of objects.');
            }
        } catch (error) {
            console.error('Error parsing or fixing mx-headers attribute:', error);
        }
    }

    http.onload = function() {
        attemptHideLoader(element);
        handleHttpResponse(http, element);
    };

    http.onerror = function() {
        attemptHideLoader(element);
        console.error('Request failed');
        // Handle error (e.g., show error message to user)
    };

    http.ontimeout = function() {
        attemptHideLoader(element);
        console.error('Request timed out');
        // Handle timeout (e.g., show timeout message to user)
    };

    try {
        http.send();
    } catch (error) {
        attemptHideLoader(element);
        console.error('Error sending request:', error);
        // Handle error (e.g., show error message to user)
    }
}

function populateTargetEl(targetEl, http, element) {

    const selectStr = element.getAttribute('mx-select');
    if(!selectStr) {
        console.log('we have no select string')
    }

    const mxSwapStr = establishSwapStr(element);
    const selectOobStr = element.getAttribute('mx-select-oob');

    // Create a document fragment to hold the response
    const tempFragment = document.createDocumentFragment();
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = http.responseText;

    tempFragment.appendChild(tempDiv);

    try {
        // Handle out-of-band swaps first
        handleOobSwaps(tempFragment, selectOobStr);

        // Handle the main target swap(s)
        handleMainSwaps(targetEl, tempFragment, selectStr, mxSwapStr);

        // Attempt add modal buttons
        attemptAddModalButtons(targetEl, element);
    } catch (error) {
        console.error('Error in populateTargetEl:', error);
    } finally {
        // Clean up
        tempDiv.innerHTML = '';
        tempFragment.textContent = '';
    }
}

function attemptAddModalButtons(targetEl, element) {
    if (element.hasAttribute('mx-build-modal')) {
        const modalValue = element.getAttribute('mx-build-modal');

        try {
            const modalOptions = JSON.parse(modalValue);
            const buttonPara = document.createElement('p');
            buttonPara.setAttribute('class', 'text-center');
            let buttonsAdded = false;

            if (modalOptions.hasOwnProperty('showCloseButton')) {
                const closeBtn = document.createElement('button');
                closeBtn.setAttribute('class', 'alt');
                closeBtn.innerText = 'Close';
                closeBtn.setAttribute('onclick', 'closeModal()');
                buttonPara.appendChild(closeBtn);
                buttonsAdded = true;
            }

            if (modalOptions.hasOwnProperty('showDestroyButton')) {
                const destroyBtn = document.createElement('button');
                destroyBtn.setAttribute('class', 'alt');
                destroyBtn.innerText = 'Close';
                destroyBtn.addEventListener('click', function() {
                    closeModal();
                    let targetModal = this.closest('.modal');
                    if (targetModal) {
                        targetModal.remove();
                    }
                });
                buttonPara.appendChild(destroyBtn);
                buttonsAdded = true;
            }

            if (buttonsAdded) {
                targetEl.appendChild(buttonPara);
            }
        } catch (e) {
            console.warn('Failed to parse mx-build-modal attribute:', e.message);
        }
    }
}

function handleOobSwaps(tempFragment, selectOobStr) {
    if (!selectOobStr) {
        return;
    }

    // Evaluate the 'mx-select-oob' value to determine what technique to use for handling oob swaps.
    const methodology = determineOobMethodology(selectOobStr);

    if (!methodology) {
        return;
    }

    const oobDataObjs = [];

    if (methodology === 1) {
        oobDataObjs.push(executeOobMethodology1(selectOobStr));
    } else if (methodology === 2) {
        oobDataObjs.push(executeOobMethodology2(selectOobStr));
    } else if (methodology === 3) {
        oobDataObjs.push(...executeOobMethodology3(selectOobStr));
    }

    // Loop through the oobDataObjs array and perform the swaps
    oobDataObjs.forEach(obj => {
        //swapElements(obj.select, obj.target, obj.swap);

        let oobSelected;
        oobSelected = tempFragment.querySelector(obj.select);

        if(!oobSelected) {
            oobSelected = tempFragment.firstChild;
        }

        const oobTarget = document.querySelector(obj.target);
        swapContent(oobTarget, oobSelected.cloneNode(true), obj.swap);
    });
}

function executeOobMethodology1(selectOobStr) {
    /*
    Example use case:
    For an attribute like:
    mx-select-oob="#source-element:#destination-element"
    
    This function will return:
    {
        select: '#source-element',
        target: '#destination-element',
        swap: 'innerHTML'
    }

    This could be used in any scenario where content needs to be moved or copied:
    - '#source-element' is the ID of the element containing the original content
    - '#destination-element' is the ID of the element where content should be placed
    - 'innerHTML' specifies that the entire content should be swapped
    */

    const [select, target] = selectOobStr.split(':');

    const oobDataObj = {
        select,
        target,
        swap: "innerHTML"
    }
    return oobDataObj;
}

function executeOobMethodology2(selectOobStr) {
    /*
    Example use case:
    For an attribute like:
    mx-select-oob="select:#source-element,target:#destination-element,swap:innerHTML"
    
    This function will return:
    {
        select: '#source-element',
        target: '#destination-element',
        swap: 'innerHTML'
    }

    This could be used in any scenario where content needs to be moved or copied:
    - '#source-element' is the ID of the element containing the original content
    - '#destination-element' is the ID of the element where content should be placed
    - 'innerHTML' specifies that the entire content should be swapped

    If 'swap' is omitted, it defaults to 'innerHTML'
    */

    // Split the string into key-value pairs
    const pairs = selectOobStr.split(',');
    
    // Initialize the oobDataObj with default swap value
    const oobDataObj = {
        select: '',
        target: '',
        swap: 'innerHTML'  // Default value
    };

    // Process each key-value pair
    pairs.forEach(pair => {
        const [key, value] = pair.split(':').map(item => item.trim());
        
        // Assign values to oobDataObj based on the key
        switch(key) {
            case 'select':
                oobDataObj.select = value;
                break;
            case 'target':
                oobDataObj.target = value;
                break;
            case 'swap':
                oobDataObj.swap = value;
                break;
            // Ignore any other keys
        }
    });

    // Validate that we have at least select and target
    if (!oobDataObj.select || !oobDataObj.target) {
        throw new Error('Invalid mx-select-oob syntax. Both "select" and "target" must be specified.');
    }

    return oobDataObj;
}

function executeOobMethodology3(selectOobStr) {

    /*
    Example use case:
    For an attribute like:
    mx-select-oob="[{select:#source-element1,target:#destination-element1,swap:outerHTML},{select:#source-element2,target:#destination-element2,swap:innerText}]"
    
    This function will return:
    [
        {
            select: '#source-element1',
            target: '#destination-element1',
            swap: 'outerHTML'
        },
        {
            select: '#source-element2',
            target: '#destination-element2',
            swap: 'innerText'
        }
    ]

    This could be used in any scenario where multiple content swaps need to be performed:
    - '#source-element1' and '#source-element2' are the IDs of the elements containing the original content
    - '#destination-element1' and '#destination-element2' are the IDs of the elements where content should be placed
    - 'outerHTML' and 'innerText' specify how the content should be swapped
    */

    try {
        // Use makeValidJsonString to fix the input string
        const fixedJsonStr = makeValidJsonString(selectOobStr);

        // Parse the fixed JSON string
        const oobDataObjs = JSON.parse(fixedJsonStr);

        // Validate that the input is an array
        if (!Array.isArray(oobDataObjs)) {
            throw new Error('Invalid mx-select-oob syntax. Expected an array of objects.');
        }

        // Process each object in the array
        return oobDataObjs.map(obj => {
            // Ensure that the required properties are present
            if (!obj.select || !obj.target) {
                throw new Error('Invalid mx-select-oob syntax. Both "select" and "target" must be specified.');
            }

            // Set a default swap value if not provided
            obj.swap = obj.swap || 'innerHTML';

            return obj;
        });
    } catch (e) {
        // Handle any errors
        console.error('Error processing mx-select-oob:', e);
        return [];
    }
}

function makeValidJsonString(inputString) {
    // Helper function to check if a string is a valid JSON
    function isValidJson(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            return false;
        }
    }

    // If the input string is already a valid JSON, return it unmodified
    if (isValidJson(inputString)) {
        return inputString;
    }

    // Attempt to modify the string to make it a valid JSON
    try {
        // Use a regular expression to find keys and values that are not quoted and quote them
        const fixedString = inputString.replace(/([{,]\s*)([^:{}\[\],\s"']+)(\s*:)/g, '$1"$2"$3')
                                       .replace(/(:\s*)([^,\[\]{}"\s]+)(\s*[}\],])/g, '$1"$2"$3');

        // Check if the fixed string is a valid JSON
        if (isValidJson(fixedString)) {
            return fixedString;
        } else {
            throw new Error('Unable to fix the string to a valid JSON format.');
        }
    } catch (e) {
        throw new Error('Failed to process the input string. Please ensure it is in a proper format.');
    }
}

function determineOobMethodology(attributeValue) {
    // Trim the attribute value to remove any leading or trailing whitespace
    attributeValue = attributeValue.trim();

    // Check for Methodology 3 (JSON array)
    if (attributeValue.startsWith('[') && attributeValue.endsWith(']')) {
        console.log(attributeValue)
        try {
            return 3;
        } catch (e) {
            // If it's not valid JSON, it's not methodology 3
        }
    }

    // Check for Methodology 2 (key-value pairs)
    if (attributeValue.includes(',') && attributeValue.includes(':')) {
        const parts = attributeValue.split(',');
        const hasAllRequiredKeys = parts.some(part => part.includes('select:')) &&
                                   parts.some(part => part.includes('target:')) &&
                                   parts.some(part => part.includes('swap:'));
        if (hasAllRequiredKeys) {
            return 2;
        }
    }

    // Check for Methodology 1 (basic syntax)
    if (attributeValue.includes(':') && attributeValue.split(':').length === 2) {
        return 1;
    }

    // If none of the above conditions are met, return null or throw an error
    return null; // or throw new Error('Invalid mx-select-oob syntax');
}

function handleMainSwaps(targetEl, tempFragment, selectStr, mxSwapStr) {
    let contents = selectStr ? tempFragment.querySelectorAll(selectStr) : [tempFragment.firstChild];
    contents.forEach(content => {
        if (content) {
            swapContent(targetEl, content, mxSwapStr);
        }
    });
}

function swapContent(target, source, swapMethod) {
    switch (swapMethod) {
        case 'outerHTML':
            target.outerHTML = source.outerHTML;
            break;
        case 'textContent':
            target.textContent = source.textContent;
            break;
        case 'beforebegin':
            target.insertAdjacentHTML('beforebegin', source.outerHTML);
            break;
        case 'afterbegin':
            target.insertAdjacentHTML('afterbegin', source.outerHTML);
            break;
        case 'beforeend':
            target.insertAdjacentHTML('beforeend', source.outerHTML);
            break;
        case 'afterend':
            target.insertAdjacentHTML('afterend', source.outerHTML);
            break;
        case 'delete':
            target.remove();
            break;
        case 'none':
            // Do nothing
            break;
        default: // 'innerHTML' is the default
            target.innerHTML = source.outerHTML || source.innerHTML;
    }
}

function handleHttpResponse(http, element) {
    const containingForm = element.closest('form');

    if (containingForm) {
        const submitButton = containingForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.removeAttribute('disabled');
        }
    }

    element.classList.remove('blink');

    if (http.status >= 200 && http.status < 300) {
        if (http.getResponseHeader('Content-Type').includes('text/html')) {
            const mxTargetStr = getAttributeValue(element, 'mx-target');

            let targetEl;

            if (mxTargetStr === 'none') {
                // If mx-target is 'none', do not replace any content
                targetEl = null;
            } else if (mxTargetStr === 'this') {
                // Target the element that triggered the request
                targetEl = element;
            } else if (mxTargetStr && mxTargetStr.startsWith('closest ')) {
                // Find the closest ancestor matching the selector
                const selector = mxTargetStr.replace('closest ', '');
                targetEl = element.closest(selector);
            } else if (mxTargetStr && mxTargetStr.startsWith('find ')) {
                // Find the first descendant matching the selector
                const selector = mxTargetStr.replace('find ', '');
                targetEl = element.querySelector(selector);
            } else if (mxTargetStr === 'body') {
                // Target the body element
                targetEl = document.body;
            } else if (mxTargetStr) {
                // If a valid CSS selector is provided
                targetEl = document.querySelector(mxTargetStr);
            } else {
                // If no mx-target is specified, use the invoking element as the target
                targetEl = element;
            }

            if (targetEl) {
                // Check to see if we are required to do a success animation.
                const successAnimateStr = element.getAttribute('mx-animate-success');

                if (successAnimateStr) {
                    initAnimateSuccess(targetEl, http, element);
                } else {
                    populateTargetEl(targetEl, http, element);
                }
            }

            // Perform 'mx-on-success' actions based on the response
            // For example, refetch a list of records etc.
            attemptInitOnSuccessActions(http, element);

        } else {
            console.log('Response is not HTML. Handle accordingly.');
            // Handle non-HTML responses (e.g., JSON)
        }
    } else {
        console.error('Request failed with status:', http.status);
        // Handle different types of errors
        switch (http.status) {
            case 404:
                console.error('Resource not found');
                break;
            case 500:
                console.error('Server error');
                break;
            default:
                console.error('An error occurred');
        }

        if (containingForm) {
            attemptDisplayValidationErrors(http, element, containingForm);
        }

        // Check to see if we are required to do an error animation.
        const errorAnimateStr = element.getAttribute('mx-animate-error');

        if (errorAnimateStr) {
            initAnimateError(element, http, element);
        } else {
            attemptInitOnErrorActions(http, element);
        }
    }

    // Remove the loader if present
    attemptHideLoader(element);
}

function attemptInitOnErrorActions(http, element) {

    const onErrorStr = element.getAttribute('mx-on-error');

    if (onErrorStr) {
        const errorTargetEl = document.querySelector(onErrorStr);
        handlePageLoadedEvents(errorTargetEl);
    }
}

function attemptInitOnSuccessActions(http, element) {
    const onSuccessStr = element.getAttribute('mx-on-success');

    if (onSuccessStr) {
        const successTargetEl = document.querySelector(onSuccessStr);
        handlePageLoadedEvents(successTargetEl);
    }
}

function handleValidationErrors(containingForm, validationErrors) {
    // First, remove any existing validation error classes
    containingForm.querySelectorAll('.form-field-validation-error')
        .forEach(field => field.classList.remove('form-field-validation-error'));

    // Loop through the validation errors
    validationErrors.forEach(error => {
        // Find the form field with the name matching the error field
        const field = containingForm.querySelector(`[name="${error.field}"]`);
        if (field) {
            // Add the validation error class to the field
            field.classList.add('form-field-validation-error');

            // Optionally, you can also display the error message
            // This assumes there's a container for error messages next to each field
            const errorContainer = field.nextElementSibling;
            if (errorContainer && errorContainer.classList.contains('error-message')) {
                errorContainer.textContent = error.messages.join(' ');
            }
        }
    });
}

function attemptDisplayValidationErrors(http, element, containingForm) {
    if (http.status >= 400 && http.status <= 499) {

        try {            

            // Check to see if the containingForm has a class of 'highlight-errors'
            if (containingForm.classList.contains('highlight-errors')) {
                // Parse the content of the validation-errors div
                const validationErrors = JSON.parse(http.responseText);

                // Loop through the validation errors
                validationErrors.forEach(error => {
                    const field = containingForm.querySelector(`[name="${error.field}"]`);
                    if (field) {
                        field.classList.add('form-field-validation-error');

                        // Create error container
                        const errorContainer = document.createElement('div');
                        errorContainer.classList.add('validation-error-report');
                        errorContainer.innerHTML = error.messages.map(msg => `<div>&#9679; ${msg}</div>`).join('');

                        // Find the appropriate place to insert the error message
                        let insertBeforeElement = field;
                        let label = field.previousElementSibling;

                        // Insert the error message
                        insertBeforeElement.parentNode.insertBefore(errorContainer, insertBeforeElement);

                        // Special handling for checkboxes and radios
                        if (field.type === "checkbox" || field.type === "radio") {
                            let parentContainer = field.closest("div");
                            if (parentContainer) {
                                parentContainer.classList.add("form-field-validation-error");
                                parentContainer.style.textIndent = "7px";
                            }
                        }
                    }
                });                
            }

        } catch (e) {
            console.error('Error parsing validation errors:', e);
        }
    }
}

function attemptDisplayValidationErrorsXXXX(http, element, containingForm) {
    if (http.status >= 400 && http.status <= 499) {

        try {
            // Create a temporary DOM element to parse the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = http.responseText;

            // Look for the validation-errors div
            const validationErrorsDiv = tempDiv.querySelector('#validation-errors');

            if (!validationErrorsDiv) {
                // If the validation-errors div doesn't exist, exit the function
                return;
            }

            // Parse the content of the validation-errors div
            const validationErrors = JSON.parse(validationErrorsDiv.textContent);

            // Clear existing validation errors
            containingForm.querySelectorAll('.form-field-validation-error, .validation-error-report')
                .forEach(el => el.remove());

            // Loop through the validation errors
            validationErrors.forEach(error => {
                const field = containingForm.querySelector(`[name="${error.field}"]`);
                if (field) {
                    field.classList.add('form-field-validation-error');

                    // Create error container
                    const errorContainer = document.createElement('div');
                    errorContainer.classList.add('validation-error-report');
                    errorContainer.innerHTML = error.messages.map(msg => `<div>&#9679; ${msg}</div>`).join('');

                    // Find the appropriate place to insert the error message
                    let insertBeforeElement = field;
                    let label = field.previousElementSibling;
                    if (label && label.tagName.toLowerCase() === 'label') {
                        insertBeforeElement = field;
                    } else {
                        // If there's no label, insert at the start of the parent container
                        insertBeforeElement = field.parentNode.firstChild;
                    }

                    // Insert the error message
                    insertBeforeElement.parentNode.insertBefore(errorContainer, insertBeforeElement);

                    // Special handling for checkboxes and radios
                    if (field.type === "checkbox" || field.type === "radio") {
                        let parentContainer = field.closest("div");
                        if (parentContainer) {
                            parentContainer.classList.add("form-field-validation-error");
                            parentContainer.style.textIndent = "7px";
                        }
                    }
                }
            });

            // Scroll to the first error
            const firstError = containingForm.querySelector('.validation-error-report');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });

        } catch (e) {
            console.error('Error parsing validation errors:', e);
        }
    }
}

function addErrorClasses(key, allFormFields) {
    for (let i = 0; i < allFormFields.length; i++) {
        if (allFormFields[i].name === key) {
            let formFieldType = allFormFields[i].type;
            if (formFieldType === "checkbox" || formFieldType === "radio") {
                let parentContainer = allFormFields[i].closest("div");
                parentContainer.classList.add("form-field-validation-error");
                parentContainer.style.textIndent = "7px";

                let previousSibling = parentContainer.previousElementSibling;
                if (previousSibling && previousSibling.classList.contains("validation-error-report")) {
                    previousSibling.style.marginTop = "21px";
                }
            } else {
                allFormFields[i].classList.add("form-field-validation-error");
            }
        }
    }
}

function findCss(fileName) {
    var finderRe = new RegExp(fileName + ".*?.css", "i");
    var linkElems = document.getElementsByTagName("link");
    for (var i = 0, il = linkElems.length; i < il; i++) {
        if (linkElems[i].href && finderRe.test(linkElems[i].href)) {
            return true;
        }
    }
    return false;
}

// Function to handle standard DOM events based on MX attributes
function handleStandardEvents(element, triggerEvent, httpMethodAttribute) {

    element.addEventListener(triggerEvent, event => {
        event.preventDefault(); // Prevent default behavior

        // Is the element either a 'form' tag or an element within a form?
        const containingForm = element.closest('form');
        if (containingForm) {

            if (triggerEvent === 'submit') {
                mxSubmitForm(element, triggerEvent, httpMethodAttribute);
            }

        } else {
            // This does not belong to a form!
            initInvokeHttpRequest(element, httpMethodAttribute);
        }

    });
}

// Function to establish the trigger event based on element type and mx-trigger attribute
function establishTriggerEvent(element) {

    const tagName = element.tagName;
    const triggerEventStr = element.getAttribute('mx-trigger');

    if (triggerEventStr) {
        return triggerEventStr; // Return mx-trigger attribute value if provided
    }

    // Determine natural trigger event based on HTMX conventions
    switch (tagName.toLowerCase()) {
        case 'form':
            return 'submit';
        case 'button':
            return 'click';
        case 'input':
            return (tagName === 'input' && element.type === 'submit') ? 'click' : 'change';
        case 'textarea':
        case 'select':
            return 'change';
        default:
            return 'click'; // Default to click for other elements
    }
}

function establishSwapStr(element) {
    const swapStr = getAttributeValue(element, 'mx-swap');
    return swapStr || 'innerHTML'; // Default to 'innerHTML' if not specified
}

function getAttributeValue(element, attributeName) {
    let current = element;
    while (current) {
        if (current.hasAttribute(attributeName)) {
            return current.getAttribute(attributeName);
        }
        current = current.parentElement;
    }
    return null;
}

function attemptActivateLoader(element) {
    const indicatorSelector = getAttributeValue(element, 'mx-indicator');
    if (indicatorSelector) {
        const loaderEl = document.querySelector(indicatorSelector);
        if (loaderEl) {
            loaderEl.style.removeProperty('display');  // Removes any inline display property
            loaderEl.classList.remove('mx-indicator-hidden');
            loaderEl.classList.add('mx-indicator');
        }
    }
}

function attemptHideLoader(element) {
    const indicatorSelector = getAttributeValue(element, 'mx-indicator');
    if (indicatorSelector) {
        const loaderEl = document.querySelector(indicatorSelector);
        if (loaderEl) {
            hideLoader(loaderEl);  // Pass loaderEl instead of element
        }
    }
}

// Function to hide loader on a specified element
function hideLoader(element) {
    if (element && element.classList.contains('mx-indicator')) {
        element.classList.remove('mx-indicator');
        element.classList.add('mx-indicator-hidden');
    }
}

// Immediately invoke the HTTP request for (page) 'load' events
function handlePageLoadedEvents(element) {
    // Find which out which kind of HTTP request should be invoked
    const attribute = methodAttributes.find(attr => element.hasAttribute(attr));

    event.preventDefault(); // Prevent default behavior
    initInvokeHttpRequest(element, attribute);
}

function handleTrongateMXEvent(event) {
    
    const element = event.target.closest('[' + methodAttributes.join('],[') + ']');

    if (!element) return; // If no matching element found, exit the function

    const triggerEvent = establishTriggerEvent(element);

    if (triggerEvent !== event.type) return; // If the event doesn't match the trigger, exit the function

    // Find which out which kind of HTTP request should be invoked
    const attribute = methodAttributes.find(attr => element.hasAttribute(attr));

    event.preventDefault(); // Prevent default behavior

    if (element.tagName.toLowerCase() === 'form' || element.closest('form')) {
        mxSubmitForm(element, triggerEvent, attribute);
    } else {
        initInvokeHttpRequest(element, attribute);
    }
}

function initializeTrongateMX() {
    // Hide all loader elements
    document.querySelectorAll('.mx-indicator').forEach(element => {
        hideLoader(element);
        element.style.display = ''; // Remove inline style "display: none;"
    });

    // Add the central event listeners
    const events = ['click', 'dblclick', 'change', 'submit', 'keyup', 'keydown', 'focus', 'blur'];
    events.forEach(eventType => {
        document.body.addEventListener(eventType, handleTrongateMXEvent);
    });

    // Handle load events (mx-trigger="load")
    const loadTriggerElements = document.querySelectorAll('[mx-trigger*="load"]');

    loadTriggerElements.forEach(element => {
        // Handle elements with 'load' in their mx-trigger attribute
        handlePageLoadedEvents(element);
    });

}

function mxDrawBigTick(element, overlay, targetEl) {

    targetEl.classList.add('mx-animation-container-tick');
    overlay.classList.add("text-center");

    let bigTick = document.createElement("div");
    bigTick.setAttribute("id", "mx-big-tick");
    bigTick.setAttribute("style", "display: none");
    let trigger = document.createElement("div");
    trigger.setAttribute("class", "mx-trigger");
    bigTick.appendChild(trigger);

    let tickSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    tickSvg.setAttribute("version", "1.1");
    tickSvg.setAttribute("id", "mx-tick");
    tickSvg.setAttribute("style", "margin: 0 auto; display: block;");
    tickSvg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    tickSvg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
    tickSvg.setAttribute("x", "0px");
    tickSvg.setAttribute("y", "0px");
    tickSvg.setAttribute("viewBox", "0 0 37 37");
    tickSvg.setAttribute("xml:space", "preserve");
    bigTick.appendChild(tickSvg);

    let tickPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
    tickPath.setAttribute("class", "mx-circ path");
    tickPath.setAttribute(
        "style",
        "fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10"
    );
    tickPath.setAttribute(
        "d",
        "M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z"
    );
    tickSvg.appendChild(tickPath);

    let polyline = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
    polyline.setAttribute("class", "mx-tick path");
    polyline.setAttribute(
        "style",
        "fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10;"
    );
    polyline.setAttribute("points", "11.6,20 15.9,24.2 26.4,13.8");
    tickSvg.appendChild(polyline);

    overlay.appendChild(bigTick);

    bigTick = document.getElementById("mx-big-tick");
    bigTick.style.display = "flex";

    setTimeout(() => {
        let things = document.getElementsByClassName("mx-trigger")[0];
        things.classList.add("mx-drawn");
    }, 100);

}

function mxDrawBigCross(overlay, targetEl) {

    targetEl.classList.add('mx-animation-container-tick');
    overlay.classList.add("text-center");

    let bigCross = document.createElement("div");
    bigCross.setAttribute("id", "mx-big-cross");
    bigCross.setAttribute("style", "display: none");
    let trigger = document.createElement("div");
    trigger.setAttribute("class", "mx-trigger");
    bigCross.appendChild(trigger);

    let crossSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    crossSvg.setAttribute("version", "1.1");
    crossSvg.setAttribute("id", "mx-cross");
    crossSvg.setAttribute("style", "margin: 0 auto; width: 53.7%; transform: scale(0.5)");
    crossSvg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    crossSvg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
    crossSvg.setAttribute("x", "0px");
    crossSvg.setAttribute("y", "0px");
    crossSvg.setAttribute("viewBox", "0 0 37 37");
    crossSvg.setAttribute("xml:space", "preserve");
    bigCross.appendChild(crossSvg);

    let crossPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
    crossPath.setAttribute("class", "mx-circ path");
    crossPath.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10");
    crossPath.setAttribute("d", "M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z");
    crossSvg.appendChild(crossPath);

    let polyline1 = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
    polyline1.setAttribute("class", "mx-tick path");
    polyline1.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;");
    polyline1.setAttribute("points", "11.1,10 25.4,27.2");
    crossSvg.appendChild(polyline1);

    let polyline2 = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
    polyline2.setAttribute("class", "mx-cross path");
    polyline2.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;");
    polyline2.setAttribute("points", "24.1,10 12.4,27.2");
    crossSvg.appendChild(polyline2);

    overlay.appendChild(bigCross);

    bigCross = document.getElementById("mx-big-cross");
    bigCross.style.display = "flex";

    setTimeout(() => {
        let things = document.getElementsByClassName("mx-trigger")[0];
        things.classList.add("mx-drawn");
    }, 100);

}

function mxDestroyAnimation() {
    // Find all elements with class .mx-animation-container-tick
    let elementsTick = document.querySelectorAll('.mx-animation-container-tick');

    // Loop through each element with class .mx-animation-container-tick and remove the class
    elementsTick.forEach(element => {
        element.classList.remove('mx-animation-container-tick');
    });

    // Find all elements with class .mx-animation-container-cross
    let elementsCross = document.querySelectorAll('.mx-animation-container-cross');

    // Loop through each element with class .mx-animation-container-cross and remove the class
    elementsCross.forEach(element => {
        element.classList.remove('mx-animation-container-cross');
    });

    const mxAnimationEl = document.querySelector('.mx-animation');
    mxAnimationEl.remove();
}

function mxCreateOverlay(targetEl) {
    const overlay = document.createElement('div');
    overlay.setAttribute('class', 'mx-animation');

    overlay.style.position = 'absolute';
    overlay.style.top = targetEl.offsetTop + 'px';
    overlay.style.left = targetEl.offsetLeft + 'px';
    overlay.style.width = targetEl.offsetWidth + 'px';
    overlay.style.minHeight = targetEl.offsetHeight + 'px';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'flex-start';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '9999';

    if (getComputedStyle(targetEl).position === 'static') {
        targetEl.style.position = 'relative';
    }

    targetEl.style.opacity = 0;
    targetEl.parentNode.appendChild(overlay);
    return overlay;
}

function initAnimateError(targetEl, http, element) {
    targetEl.classList.add('mx-animation-container-cross');

    const containingModalBody = targetEl.closest('.modal-body');
    if (containingModalBody) {
        targetEl = containingModalBody;
    }

    const overlay = mxCreateOverlay(targetEl);

    mxDrawBigCross(overlay, targetEl);

    setTimeout(() => {
        mxDestroyAnimation(targetEl, http, element);
        targetEl.style.opacity = 1;
        initAttemptCloseModal(targetEl, http, element);
    }, 1300);
}

function initAnimateSuccess(targetEl, http, element) {
    targetEl.classList.add('mx-animation-container-tick');

    // Is this inside a modal body?
    const containingModalBody = element.closest('.modal-body');
    if (containingModalBody) {
        targetEl = containingModalBody;
    }

    const overlay = mxCreateOverlay(targetEl);

    mxDrawBigTick(element, overlay, targetEl);

    setTimeout(() => {
        mxDestroyAnimation(targetEl, http, element);
        targetEl.style.opacity = 1;

        initAttemptCloseModal(targetEl, http, element);

        populateTargetEl(targetEl, http, element);
    }, 1300);
}

function initAttemptCloseModal(targetEl, http, element) {
    // Check if we should close on success
    const closeOnSuccessStr = element.getAttribute('mx-close-on-success');
    if (closeOnSuccessStr === 'true') {
        closeModal();
        return; // Exit function early if closing modal on success
    }

    // Check if we should close on error
    const closeOnErrorStr = element.getAttribute('mx-close-on-error');
    if (closeOnErrorStr === 'true') {
        closeModal();
    }
}

// Call this function when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeTrongateMX);