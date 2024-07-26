const methodAttributes = ['mx-get', 'mx-post', 'mx-put', 'mx-delete', 'mx-patch'];

/**
 * Parses the value of a Trongate MX attribute.
 * 
 * This function handles two types of attribute values:
 * 1. Simple strings (e.g., "user-info-modal")
 * 2. JSON-like structures (e.g., '{"id": "add-element-modal", "width": "760px"}')
 * 
 * For simple strings, it returns the value as-is.
 * For JSON-like structures, it attempts to parse them into JavaScript objects.
 * 
 * @param {string} value - The attribute value to parse.
 * @returns {string|object|boolean} 
 *          - String: if the input is a simple string.
 *          - Object: if the input is a valid JSON-like structure.
 *          - false: if the input appears to be JSON-like but fails to parse.
 */
function parseAttributeValue(value) {
    // Trim any whitespace
    value = value.trim();

    // Check if the value is a simple string (no JSON-like structure)
    if (!value.startsWith('{') && !value.startsWith('[')) {
        return value;
    }

    // The value appears to have a JSON-like structure
    try {
        // Attempt to parse as JSON
        return JSON.parse(value);
    } catch (e) {
        // If parsing fails, return false
        return false;
    }
}

/**
 * Parses the value of a Trongate MX attribute.
 * 
 * This function handles two types of attribute values:
 * 1. Simple strings (e.g., "user-info-modal")
 * 2. JSON-like structures (e.g., '{"id": "add-element-modal", "width": "760px"}')
 * 
 * For simple strings, it returns the value as-is.
 * For JSON-like structures, it attempts to parse them into JavaScript objects.
 * 
 * @param {string} value - The attribute value to parse.
 * @returns {string|object|boolean} 
 *          - String: if the input is a simple string.
 *          - Object: if the input is a valid JSON-like structure.
 *          - false: if the input appears to be JSON-like but fails to parse.
 */
function parseAttributeValue(value) {
    // Trim any whitespace
    value = value.trim();

    // Check if the value is a simple string (no JSON-like structure)
    if (!value.startsWith('{') && !value.startsWith('[')) {
        return value;
    }

    // The value appears to have a JSON-like structure
    try {
        // Attempt to parse as JSON
        return JSON.parse(value);
    } catch (e) {
        // If parsing fails, return false
        return false;
    }
}

function setupHttpRequest(element, httpMethodAttribute) {
    const targetUrl = element.getAttribute(httpMethodAttribute);
    const requestType = httpMethodAttribute.replace('mx-', '').toUpperCase();
    attemptActivateLoader(element);
    
    const http = new XMLHttpRequest();
    http.open(requestType, targetUrl);
    http.setRequestHeader('Trongate-MX-Request', 'true');
    
    return http;
}

function setMXHeaders(http, element) {
    const mxToken = element.getAttribute('mx-token');
    if (mxToken) {
        http.setRequestHeader('trongateToken', mxToken);
    }

    const mxHeadersAttr = element.getAttribute('mx-headers');
    if (mxHeadersAttr) {
        const headersArray = parseAttributeValue(mxHeadersAttr);
        if (headersArray && Array.isArray(headersArray)) {
            headersArray.forEach(header => {
                if (header.key && header.value) {
                    http.setRequestHeader(header.key, header.value);
                }
            });
        } else if (headersArray === false) {
            console.error('Error parsing mx-headers attribute as JSON.');
        } else {
            console.error('mx-headers attribute should be an array of objects.');
        }
    }
}

/**
 * Sets error and timeout handlers for XMLHttpRequest.
 * @param {XMLHttpRequest} http - The XMLHttpRequest object.
 * @param {HTMLElement} element - The element associated with the request.
 */
function setMXHandlers(http, element) {
    http.onerror = function() {
        attemptHideLoader(element);
        console.error('Request failed');
    };

    http.ontimeout = function() {
        attemptHideLoader(element);
        console.error('Request timed out');
    };
}

function invokeFormPost(containingForm, triggerEvent, httpMethodAttribute) {
    const http = setupHttpRequest(containingForm, httpMethodAttribute);
    setMXHeaders(http, containingForm);
    setMXHandlers(http, containingForm);

    const formData = new FormData(containingForm);

    http.onload = function() {
        attemptHideLoader(containingForm);
        if (http.status >= 200 && http.status < 300) {
            containingForm.reset();
        }
        handleHttpResponse(http, containingForm);
    };

    http.send(formData);
}

function invokeHttpRequest(element, httpMethodAttribute) {
    const http = setupHttpRequest(element, httpMethodAttribute);
    http.setRequestHeader('Accept', 'text/html');
    http.timeout = 10000; // 10 seconds timeout

    setMXHeaders(http, element);
    setMXHandlers(http, element);

    http.onload = function() {
        attemptHideLoader(element);
        handleHttpResponse(http, element);
    };

    try {
        http.send();
    } catch (error) {
        attemptHideLoader(element);
        console.error('Error sending request:', error);
    }
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
        const modalOptions = parseAttributeValue(buildModalStr);

        if (modalOptions === false) {
            console.warn("Invalid JSON in mx-build-modal:", buildModalStr);
            return;
        }

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

function buildMXModal(modalData, element, httpMethodAttribute) {
    const modalId = typeof modalData === 'string' ? modalData : modalData.id;

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
    if (typeof modalData === 'object' && modalData.modalHeading) {
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
    if (typeof modalData === 'object' && modalData.width) {
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

function populateTargetEl(targetEl, http, element) {

    const selectStr = element.getAttribute('mx-select');
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

function handleMainSwaps(targetEl, tempFragment, selectStr, mxSwapStr) {
    let contents = selectStr ? tempFragment.querySelectorAll(selectStr) : [tempFragment.firstChild];
    contents.forEach(content => {
        if (content) {
            swapContent(targetEl, content, mxSwapStr);
        }
    });
}

function handleOobSwaps(tempFragment, selectOobStr) {
    if (!selectOobStr) return;

    const parsedValue = parseAttributeValue(selectOobStr);

    if (typeof parsedValue === 'string') {
        // Handle comma-separated string syntax
        const swapInstructions = parsedValue.split(/,(?![^[]*\])/);
        swapInstructions.forEach(instruction => {
            const [select, target] = instruction.trim().split(':');
            performOobSwap(tempFragment, { select, target, swap: 'innerHTML' });
        });
    } else if (Array.isArray(parsedValue)) {
        // Handle JSON-like array syntax
        parsedValue.forEach(obj => performOobSwap(tempFragment, obj));
    } else if (typeof parsedValue === 'object' && parsedValue !== null) {
        // Handle single object case (though this is less likely to be used)
        performOobSwap(tempFragment, parsedValue);
    } else {
        console.error('Invalid mx-select-oob syntax:', selectOobStr);
    }
}

function performOobSwap(tempFragment, { select, target, swap = 'innerHTML' }) {
    const oobSelected = tempFragment.querySelector(select);
    if (!oobSelected) {
        console.error(`Source element not found: ${select}`);
        return;
    }

    const oobTarget = document.querySelector(target);
    if (!oobTarget) {
        console.error(`Target element not found: ${target}`);
        return;
    }

    swapContent(oobTarget, oobSelected.cloneNode(true), swap);
}

function handleOobSwapsXXX(tempFragment, selectOobStr) {
    if (!selectOobStr) return;

    // Split the string by commas, but ignore commas within brackets
    const swapInstructions = selectOobStr.split(/,(?![^[]*\])/);

    swapInstructions.forEach(instruction => {
        const trimmedInstruction = instruction.trim();
        const parsedValue = parseAttributeValue(trimmedInstruction);

        if (typeof parsedValue === 'string') {
            // Handle simple string case (e.g., "h1:h3")
            const [select, target] = parsedValue.split(':');
            performOobSwap(tempFragment, { select, target, swap: 'innerHTML' });
        } else if (typeof parsedValue === 'object' && parsedValue !== null) {
            // Handle object case (for advanced syntax)
            performOobSwap(tempFragment, parsedValue);
        } else {
            console.error('Invalid mx-select-oob instruction:', trimmedInstruction);
        }
    });
}

function performOobSwapXXX(tempFragment, { select, target, swap = 'innerHTML' }) {
    const oobSelected = tempFragment.querySelector(select) || tempFragment.firstChild;
    const oobTarget = document.querySelector(target);
    if (oobTarget) {
        swapContent(oobTarget, oobSelected.cloneNode(true), swap);
    } else {
        console.error(`Target element not found: ${target}`);
    }
}

function swapContent(target, source, swapMethod) {
    // Ensure source is a string
    const sourceString = typeof source === 'string' ? source : source.outerHTML || source.innerHTML;
    
    // Remove the outermost div from the source string
    const processedSource = removeOutermostDiv(sourceString);
    
    switch (swapMethod) {
        case 'outerHTML':
            target.outerHTML = processedSource;
            break;
        case 'textContent':
            target.textContent = processedSource;
            break;
        case 'beforebegin':
            target.insertAdjacentHTML('beforebegin', processedSource);
            break;
        case 'afterbegin':
            target.insertAdjacentHTML('afterbegin', processedSource);
            break;
        case 'beforeend':
            target.insertAdjacentHTML('beforeend', processedSource);
            break;
        case 'afterend':
            target.insertAdjacentHTML('afterend', processedSource);
            break;
        case 'delete':
            target.remove();
            break;
        case 'none':
            // Do nothing
            break;
        default: // 'innerHTML' is the default
            target.innerHTML = processedSource;
    }
}

function removeOutermostDiv(htmlString) {
    // Create a temporary container element
    const tempContainer = document.createElement('div');
    
    // Set the innerHTML of the temporary container to the provided HTML string
    tempContainer.innerHTML = htmlString.trim();
    
    // If the first child is a div, replace it with its children
    if (tempContainer.firstElementChild && tempContainer.firstElementChild.tagName.toLowerCase() === 'div') {
        const firstDiv = tempContainer.firstElementChild;
        while (firstDiv.firstChild) {
            tempContainer.insertBefore(firstDiv.firstChild, firstDiv);
        }
        tempContainer.removeChild(firstDiv);
    }
    
    // Return the HTML content of the temporary container
    return tempContainer.innerHTML;
}

function attemptAddModalButtons(targetEl, element) {
    if (element.hasAttribute('mx-build-modal')) {
        const modalValue = element.getAttribute('mx-build-modal');
        const modalOptions = parseAttributeValue(modalValue);

        if (modalOptions === false) {
            console.warn('Failed to parse mx-build-modal attribute:', modalValue);
            return;
        }

        const buttonPara = document.createElement('p');
        buttonPara.setAttribute('class', 'text-center');
        let buttonsAdded = false;

        if (typeof modalOptions === 'string') {
            // Handle the simple string case
            const closeBtn = document.createElement('button');
            closeBtn.setAttribute('class', 'alt');
            closeBtn.innerText = 'Close';
            closeBtn.setAttribute('onclick', 'closeModal()');
            buttonPara.appendChild(closeBtn);
            buttonsAdded = true;
        } else if (typeof modalOptions === 'object') {
            // Handle the object case
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
        }

        if (buttonsAdded) {
            targetEl.appendChild(buttonPara);
        }
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

    // Attempt to start polling.
    attemptInitPolling();

}

function attemptInitPolling() {
    const pollingElements = document.querySelectorAll('[mx-trigger]');
    pollingElements.forEach(element => {
        const triggerAttr = element.getAttribute('mx-trigger');
        setupPolling(element, triggerAttr);
    });
}

function parsePollingInterval(intervalString) {
    const match = intervalString.match(/^(\d+)(s|m|h|d)$/);
    if (!match) return null;

    const value = parseInt(match[1], 10);
    const unit = match[2];

    switch (unit) {
        case 's': return value * 1000;
        case 'm': return value * 60 * 1000;
        case 'h': return value * 60 * 60 * 1000;
        case 'd': return value * 24 * 60 * 60 * 1000;
    }
}

function setupPolling(element, triggerAttr) {
    // Basic Polling
    const basicPollingMatch = triggerAttr.match(/^every\s+(\d+[smhd])$/);
    if (basicPollingMatch) {
        const interval = parsePollingInterval(basicPollingMatch[1]);
        if (interval) {
            setInterval(() => pollElement(element), interval);
        }
        return;
    }

    // Load Polling
    const loadPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd])$/);
    if (loadPollingMatch) {
        const delay = parsePollingInterval(loadPollingMatch[1]);
        if (delay) {
            setTimeout(() => {
                pollElement(element);
                setInterval(() => pollElement(element), delay);
            }, delay);
        }
        return;
    }

    // Polling with Initial Delay
    const delayedPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd]),\s*every\s+(\d+[smhd])$/);
    if (delayedPollingMatch) {
        const initialDelay = parsePollingInterval(delayedPollingMatch[1]);
        const interval = parsePollingInterval(delayedPollingMatch[2]);
        if (initialDelay && interval) {
            setTimeout(() => {
                pollElement(element);
                setInterval(() => pollElement(element), interval);
            }, initialDelay);
        }
        return;
    }
}

function pollElement(element) {
    const attribute = methodAttributes.find(attr => element.hasAttribute(attr));
    if (attribute) {
        initInvokeHttpRequest(element, attribute);
    }
}

function mxDrawBigTick(element, overlay, targetEl) {

    let bigTick = document.createElement("div");
    bigTick.setAttribute("style", "display: none");
    let trigger = document.createElement("div");
    trigger.setAttribute("class", "mx-trigger");
    bigTick.appendChild(trigger);

    let tickSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    tickSvg.setAttribute("version", "1.1");
    tickSvg.setAttribute("id", "mx-tick");
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
    bigTick.style.display = "flex";

    setTimeout(() => {
        let things = document.getElementsByClassName("mx-trigger")[0];
        things.classList.add("mx-drawn");
    }, 100);

}

function mxDrawBigCross(overlay) {

    let bigCross = document.createElement("div");
    bigCross.setAttribute("style", "display: none");
    let trigger = document.createElement("div");
    trigger.setAttribute("class", "mx-trigger");
    bigCross.appendChild(trigger);

    let crossSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    crossSvg.setAttribute("version", "1.1");
    crossSvg.setAttribute("id", "mx-cross");
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

    bigCross.style.display = "flex";

    setTimeout(() => {
        let things = document.getElementsByClassName("mx-trigger")[0];
        things.classList.add("mx-drawn");
    }, 100);

}

function mxDestroyAnimation() {
    const mxAnimationEl = document.querySelector('.mx-animation');
    mxAnimationEl.remove();
}

function mxCreateOverlay(overlayTargetEl) {
    // Get the bounding rectangle of the target element
    const rect = overlayTargetEl.getBoundingClientRect();

    // Create a new div element for the overlay
    const overlay = document.createElement('div');

    // Set the overlay's styles to match the target element
    overlay.style.position = 'absolute';
    overlay.style.top = `${rect.top + window.scrollY}px`;
    overlay.style.left = `${rect.left + window.scrollX}px`;
    overlay.style.width = `${rect.width}px`;
    overlay.style.minHeight = `${rect.height}px`;
    overlay.classList.add('mx-animation');
    overlay.style.zIndex = '9999';

    // Append the overlay to the body
    document.body.appendChild(overlay);

    // Hide the contents of the area that is below the animation.
    setChildrenOpacity(overlayTargetEl, 0);

    setTimeout(() => {

        const overlayTargetElHeight = overlayTargetEl.offsetHeight;
        const overlayHeight = overlay.offsetHeight;

        // Adjust height of area below animation so that it has enough height.
        if (overlayHeight > overlayTargetElHeight) {
            overlayTargetEl.style.minHeight = overlayHeight + 'px';
        }
    }, 1);

    return overlay;
}

function setChildrenOpacity(overlayTargetEl, opacityValue) {
    // Ensure opacityValue is a number between 0 and 1
    const opacityNumber = parseFloat(opacityValue);
    if (isNaN(opacityNumber) || opacityNumber < 0 || opacityNumber > 1) {
        throw new Error('Invalid opacity value. It must be a number between 0 and 1.');
    }

    // Get all children of overlayTargetEl and convert to an array
    const children = Array.from(overlayTargetEl.children);

    // Iterate over each child and set its opacity
    children.forEach(child => {
        child.style.opacity = opacityValue;
    });
}

function initAnimateError(targetEl, http, element) {

    // Establish where to create an overlay (for our animation).
    const overlayTargetEl = estOverlayTargetEl(targetEl, element);
    const overlay = mxCreateOverlay(overlayTargetEl);

    // Draw default cross animation.
    mxDrawBigCross(overlay);

    setTimeout(() => {
        mxDestroyAnimation(targetEl, http, element);
        setChildrenOpacity(overlayTargetEl, 1);

        if (overlayTargetEl.style.minHeight) {
            overlayTargetEl.style.minHeight = '';
        }

        initAttemptCloseModal(targetEl, http, element);
        populateTargetEl(targetEl, http, element);
    }, 1300);
}

function estOverlayTargetEl(targetEl, element) {
    let overlayTargetEl = targetEl;

    // Is this inside a modal body?
    const containingModalBody = element.closest('.modal-body');
    if (containingModalBody) {
        overlayTargetEl = containingModalBody;
    } else {
        // Is this inside a form element?
        const containingForm = element.closest('form');
        if (containingForm) {
            overlayTargetEl = containingForm;
        }
    }
    return overlayTargetEl;
}

function initAnimateSuccess(targetEl, http, element) {

    // Establish where to create an overlay (for our animation).
    const overlayTargetEl = estOverlayTargetEl(targetEl, element);
    const overlay = mxCreateOverlay(overlayTargetEl);

    // Draw default tick animation.
    mxDrawBigTick(element, overlay, targetEl);

    setTimeout(() => {
        mxDestroyAnimation(targetEl, http, element);
        setChildrenOpacity(overlayTargetEl, 1);

        if (overlayTargetEl.style.minHeight) {
            overlayTargetEl.style.minHeight = '';
        }

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