let trongateMXOpeningModal = false;

(function(window) {
    'use strict';

    const CONFIG = {
        CORE_MX_ATTRIBUTES: ['mx-get', 'mx-post', 'mx-put', 'mx-delete', 'mx-patch', 'mx-remove'],
        REQUIRES_DATA_ATTRIBUTES: ['mx-post', 'mx-put', 'mx-patch'],
        DEFAULT_TIMEOUT: 60000,
        POLLING_INTERVALS: new WeakMap() // Tracks polling timers
    };

    let lastRequestTime = 0;
    let mousedownEl;
    let mouseupEl;

    const Utils = {
        parseAttributeValue(value) {
            value = value.trim();
            if (!value.startsWith('{') && !value.startsWith('[')) {
                return value;
            }
            try {
                return JSON.parse(value);
            } catch (e) {
                console.error('Error parsing attribute value:', e);
                return false;
            }
        },

        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        },

        getAttributeValue(element, attributeName) {
            if (element.hasAttribute(attributeName)) {
                return element.getAttribute(attributeName);
            }
            let current = element.parentElement;
            while (current) {
                if (current.hasAttribute(attributeName)) {
                    return current.getAttribute(attributeName);
                }
                current = current.parentElement;
            }
            return null;
        },

        parsePollingInterval(intervalString) {
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
        },
        canMakeRequest(throttleTime) {
            const now = Date.now();
            if (now - lastRequestTime >= throttleTime) {
                lastRequestTime = now;
                return true;
            }
            return false;
        },
        parseUrlWithPlaceholders(url, element) {
            return url.replace(/\$\{([^}]+)\}/g, (match, p1) => {
                if (p1 === 'this.value') {
                    return element.value;
                }
                return match;
            });
        },
        pushUrl(url) {
            const normalizedUrl = this.normalizeUrl(url);
            if (normalizedUrl && window.location.href !== normalizedUrl) {
                history.pushState({}, '', normalizedUrl);
            }
        },
        getRequestUrl(element) {
            for (const attr of CONFIG.CORE_MX_ATTRIBUTES) {
                let url = element.getAttribute(attr);
                if (url) {
                    url = this.processDynamicUrl(url, element);
                    return this.normalizeUrl(url);
                }
            }
            return null;
        },
        processDynamicUrl(url, element) {
            return url.replace(/\${([^}]+)}/g, (match, p1) => {
                if (p1 === 'this.value') {
                    return element.value;
                }
                return match; // Return unchanged if no match
            });
        },
        normalizeUrl(url) {
            // Check if the URL is absolute
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }

            const baseElement = document.querySelector('base');
            const baseUrl = baseElement ? baseElement.href : window.location.origin + '/';

            // Check if the url already starts with the base URL
            if (url.startsWith(baseUrl)) {
                return url;
            }

            // Remove leading slash from url if baseUrl ends with a slash
            if (baseUrl.endsWith('/') && url.startsWith('/')) {
                url = url.substring(1);
            }

            // Ensure there's exactly one slash between base URL and the path
            return baseUrl.replace(/\/?$/, '/') + url.replace(/^\//, '');
        },
        createMXEvent(element, event, type, extraDetails = {}) {
            // Always get the closest button/form element as the target
            const targetElement = element.closest('button, form') || element;

            return {
                target: targetElement,
                type: type,
                detail: {
                    element: targetElement,
                    originalEvent: event,
                    ...extraDetails
                }
            };
        },

        executeMXFunction(functionName, customEvent) {
            if (!functionName) return;

            const cleanFunctionName = functionName.replace(/\(\)$/, '');
            let func;

            if (cleanFunctionName.includes('.')) {
                // Handle object.method references
                const [objectName, methodName] = cleanFunctionName.split('.');
                const obj = window[objectName];

                if (obj && typeof obj === 'object' && obj[methodName] && typeof obj[methodName] === 'function') {
                    func = obj[methodName].bind(obj); // Bind the method to the object
                } else {
                    console.warn(`Object or method not found: ${cleanFunctionName}`);
                    return;
                }
            } else {
                // Handle global functions
                func = window[cleanFunctionName];
            }

            if (typeof func === 'function') {
                try {
                    func(customEvent); // Invoke the function with the custom event
                } catch (error) {
                    console.error(`Error executing ${cleanFunctionName}:`, error);
                }
            } else {
                console.warn(`Function ${cleanFunctionName} not found`);
            }
        }
    };

    const Http = {
        setupHttpRequest(element, httpMethodAttribute) {
            let targetUrl = element.getAttribute(httpMethodAttribute);
            targetUrl = Utils.parseUrlWithPlaceholders(targetUrl, element);
            targetUrl = Utils.normalizeUrl(targetUrl);
            const requestType = httpMethodAttribute.replace('mx-', '').toUpperCase();
            Dom.attemptActivateLoader(element);

            const http = new XMLHttpRequest();
            http.open(requestType, targetUrl);
            http.setRequestHeader('Trongate-MX-Request', 'true');
            
            // Check for custom timeout value
            const customTimeout = element.getAttribute('mx-timeout');
            if (customTimeout !== null) {
                const timeoutValue = customTimeout.toLowerCase();
                if (timeoutValue === 'none' || timeoutValue === '0') {
                    http.timeout = 0; // Disable timeout
                } else {
                    const parsedTimeout = parseInt(customTimeout, 10);
                    if (!isNaN(parsedTimeout) && parsedTimeout > 0) {
                        http.timeout = parsedTimeout;
                    } else {
                        console.warn('Invalid mx-timeout value, using default timeout');
                        http.timeout = CONFIG.DEFAULT_TIMEOUT;
                    }
                }
            } else {
                http.timeout = CONFIG.DEFAULT_TIMEOUT;
            }

            return http;
        },

        setMXHeaders(http, element) {
            const mxToken = element.getAttribute('mx-token');
            if (mxToken) {
                http.setRequestHeader('trongateToken', mxToken);
            }

            const mxHeadersStr = element.getAttribute('mx-headers');
            if (mxHeadersStr) {
                const headers = Utils.parseAttributeValue(mxHeadersStr);
                if (headers && typeof headers === 'object') {
                    Object.entries(headers).forEach(([key, value]) => {
                        http.setRequestHeader(key, value);
                    });
                } else {
                    console.error('Error parsing mx-headers attribute.');
                }
            }
        },

        setMXHandlers(http, element) {
            http.onerror = function() {
                Dom.attemptHideLoader(element);
                console.error('Request failed');
            };

            http.ontimeout = function() {
                Dom.attemptHideLoader(element);
                console.warn('HTTP request timed out.');
                
                // Execute mx-on-timeout function if specified
                const onTimeoutFunction = element.getAttribute('mx-on-timeout');
                if (onTimeoutFunction) {
                    const customEvent = Utils.createMXEvent(element, event, 'timeout', { http });
                    Utils.executeMXFunction(onTimeoutFunction, customEvent);
                }
            };
        },

        commonHttpRequest(element, httpMethodAttribute, containingForm = null) {
            const http = Http.setupHttpRequest(element, httpMethodAttribute);
            Http.setMXHeaders(http, element);
            Http.setMXHandlers(http, element);

            const isFormSubmission = containingForm !== null;
            let formData;

            // Always get form data if we have a form, regardless of where the attributes are
            if (isFormSubmission) {
                formData = new FormData(containingForm);
            } else {
                formData = new FormData();
            }

            // Process mx-vals from either the form or the triggering element
            const mxValsStr = isFormSubmission ?
                containingForm.getAttribute('mx-vals') || element.getAttribute('mx-vals') :
                element.getAttribute('mx-vals');

            if (mxValsStr) {
                const vals = Utils.parseAttributeValue(mxValsStr);
                if (vals && typeof vals === 'object') {
                    Object.entries(vals).forEach(([key, value]) => {
                        formData.append(key, value);
                    });
                }
            }

            // Process mx-dom-vals
            const domVals = Dom.processMXDomVals(element);
            Object.entries(domVals).forEach(([key, value]) => {
                formData.append(key, value);
            });

            let targetElement;
            if ((containingForm) && (!element.hasAttribute(httpMethodAttribute))) {
                targetElement = Dom.establishTargetElement(containingForm);
            } else {
                targetElement = Dom.establishTargetElement(element);
            }

            Dom.handleMxDuringRequest(element, targetElement);

            return { http, formData, targetElement };
        },

        invokeFormPost(element, triggerEvent, httpMethodAttribute, containingForm, event) {
            const { http, formData, targetElement } = Http.commonHttpRequest(element, httpMethodAttribute, containingForm);

            http.onload = function() {
                Dom.attemptHideLoader(containingForm);

                const isSuccessfulResponse = http.status >= 200 && http.status < 300;
                const shouldResetForm = isSuccessfulResponse && !element.hasAttribute(httpMethodAttribute);

                if (shouldResetForm) {
                    containingForm.reset();
                }

                const responseTarget = element.hasAttribute(httpMethodAttribute) ? element : containingForm;
                Http.handleHttpResponse(http, responseTarget, event);
            };

            try {
                http.send(formData);
            } catch (error) {
                Dom.attemptHideLoader(containingForm);
                console.error('Error sending form request:', error);
            }
        },

        initInvokeHttpRequest(element, httpMethodAttribute, event) {
            const buildModalStr = element.getAttribute('mx-build-modal');

            if (buildModalStr) {
                const modalOptions = Utils.parseAttributeValue(buildModalStr);

                if (modalOptions === false) {
                    console.warn("Invalid JSON in mx-build-modal:", buildModalStr);
                    return;
                }

                if (typeof modalOptions === "string") {
                    const modalData = {
                        id: modalOptions
                    };
                    Modal.buildMXModal(modalData, element, httpMethodAttribute, event);
                } else {
                    Modal.buildMXModal(modalOptions, element, httpMethodAttribute, event);
                }
            } else {
                Http.invokeHttpRequest(element, httpMethodAttribute, event);
            }
        },

        invokeHttpRequest(element, httpMethodAttribute, event) {
            
            const { http, formData, targetElement } = Http.commonHttpRequest(element, httpMethodAttribute);
        
            http.setRequestHeader('Accept', 'text/html');
        
            http.onload = function() {
                
                Dom.attemptHideLoader(element);
                Http.handleHttpResponse(http, element, event);
        
                // Push URL if mx-push-url is true and the request was successful
                if (element.getAttribute('mx-push-url') === 'true' && http.status >= 200 && http.status < 300) {
                    const requestUrl = Utils.getRequestUrl(element);
                    Utils.pushUrl(requestUrl);
                }
            };
        
            try {
                http.send(formData);
            } catch (error) {
                Dom.attemptHideLoader(element);
                console.error('Error sending request:', error);
            }
        },

        handleHttpResponse(http, element, event) {
            // Handle redirects first based on status and response text
            const shouldRedirectOnSuccess = element.getAttribute('mx-redirect-on-success') === 'true';
            const shouldRedirectOnError = element.getAttribute('mx-redirect-on-error') === 'true';

            const isSuccess = http.status >= 200 && http.status < 300;
            const redirectUrl = http.responseText.trim();

            // Check if we should redirect
            if ((isSuccess && shouldRedirectOnSuccess) || (!isSuccess && shouldRedirectOnError)) {
                if (redirectUrl && !redirectUrl.startsWith('{') && !redirectUrl.startsWith('[') && !redirectUrl.includes('<')) {
                    window.location.href = Utils.normalizeUrl(redirectUrl);
                    return;
                }
            }

            // Only cleanup loading states if NOT redirecting
            Dom.removeCloak();
            Dom.restoreOriginalContent();
            Dom.reEnableDisabledElements();

            element.classList.remove('blink');

            // If no redirect, continue with normal response handling
            if (isSuccess) {
                const contentType = http.getResponseHeader('Content-Type');
                const targetEl = Dom.establishTargetElement(element);
                
                // ADDITION: Process OOB swaps regardless of target
                const selectOobStr = element.getAttribute('mx-select-oob');
                if (selectOobStr) {
                    const tempFragment = document.createDocumentFragment();
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = http.responseText;
                    tempFragment.appendChild(tempDiv);
                    Dom.handleOobSwaps(tempFragment, selectOobStr);
                    
                    // This block handles mx-after-swap for OOB swaps with mx-target="none"
                    if (!targetEl && element.hasAttribute('mx-after-swap')) {
                        const functionName = element.getAttribute('mx-after-swap');
                        if (functionName) {
                            const customEvent = Utils.createMXEvent(element, event, 'afterSwap', { http });
                            Utils.executeMXFunction(functionName, customEvent);
                        }
                    }
                }

                // Handle both animation and content updates
                const successAnimateStr = element.getAttribute('mx-animate-success');
                
                if (successAnimateStr && targetEl) {
                    // Start the animation AND handle content updates
                    Animation.initAnimateSuccessWithCallback(targetEl, http, element, () => {
                        // This callback runs after animation completes
                        Modal.initAttemptCloseModal(targetEl, http, element);
                        this.updateContent(targetEl, http, element, event);
                    });
                } else if (targetEl) {
                    // No animation, proceed normally
                    Modal.initAttemptCloseModal(targetEl, http, element);
                    this.updateContent(targetEl, http, element, event);
                }

                if (element.getAttribute('mx-push-url') === 'true') {
                    const requestUrl = Utils.getRequestUrl(element);
                    Utils.pushUrl(requestUrl);
                }

                this.attemptInitOnSuccessActions(http, element);
            } else {
                this.handleErrorResponse(http, element);
            }

            Dom.attemptHideLoader(element);
        },

        updateContent(targetEl, http, element, event) {
            const contentType = http.getResponseHeader('Content-Type');
            if (contentType.includes('text/html')) {
                Dom.populateTargetEl(targetEl, http, element);
            } else if (contentType.includes('application/json')) {
                try {
                    const jsonData = JSON.parse(http.responseText);
                    targetEl.textContent = JSON.stringify(jsonData, null, 2);
                } catch (error) {
                    console.error('Error parsing JSON response:', error);
                }
            } else if (contentType.startsWith('image/')) {
                targetEl.style.backgroundImage = `url('${http.responseURL}')`;
            } else {
                targetEl.textContent = http.responseText;
            }

            // Handle mx-after-swap with standardized event handling
            const functionName = element.getAttribute('mx-after-swap');
            if (functionName) {
                const customEvent = Utils.createMXEvent(element, event, 'afterSwap', { http });
                Utils.executeMXFunction(functionName, customEvent);
            }
        },

        handleErrorResponse(http, element) {
            console.error('Request failed with status:', http.status);

            const containingForm = element.closest('form');
            if (containingForm) {
                this.attemptDisplayValidationErrors(http, element, containingForm);
            }

            const errorAnimateStr = element.getAttribute('mx-animate-error');
            if (errorAnimateStr) {
                Animation.initAnimateError(element, http, element);
            } else {
                const targetEl = Dom.establishTargetElement(element);
                Modal.initAttemptCloseModal(targetEl, http, element);
                this.attemptInitOnErrorActions(http, element);
            }
        },

        attemptInitOnErrorActions(http, element) {
            const onErrorStr = element.getAttribute('mx-on-error');
            if (onErrorStr) {
                const errorTargetEl = document.querySelector(onErrorStr);
                Dom.handlePageLoadedEvents(errorTargetEl);
            }
        },

        attemptInitOnSuccessActions(http, element) {
            const onSuccessStr = element.getAttribute('mx-on-success');
            if (onSuccessStr) {
                const successTargetEl = document.querySelector(onSuccessStr);
                Dom.handlePageLoadedEvents(successTargetEl);
            }
        },

        attemptDisplayValidationErrors(http, element, containingForm) {
            // Skip validation handling for authentication/authorization status codes
            if (http.status === 401 || http.status === 402 || http.status === 403) {
                return;
            }

            if (http.status >= 400 && http.status <= 499) {
                try {
                    if (containingForm.classList.contains('highlight-errors')) {
                        const validationErrors = JSON.parse(http.responseText);
                        Dom.handleValidationErrors(containingForm, validationErrors);
                    }
                } catch (e) {
                    console.error('Error parsing validation errors:', e);
                }
            }
        }

    };

    const Dom = {

        processedIndicators: new Set(),
        indicatorObserver: null,

        initializeIndicatorObserver() {
            if (this.indicatorObserver) return;
        
            this.indicatorObserver = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList?.contains('mx-indicator')) {
                                this.initializeSingleIndicator(node);
                            }
                            // Check children
                            node.querySelectorAll?.('.mx-indicator')?.forEach(indicator => {
                                this.initializeSingleIndicator(indicator);
                            });
                        }
                    });
        
                    mutation.removedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node

                            if (node.classList?.contains('mx-indicator')) {
                                this.processedIndicators.delete(node);
                            }
                            node.querySelectorAll?.('.mx-indicator')?.forEach(indicator => {
                                this.processedIndicators.delete(indicator);
                            });
                            // Stop polling
                            if (CONFIG.POLLING_INTERVALS.has(node)) {
                                Main.stopPolling(node); // Stop polling for removed elements
                            }
                        }
                    });
                });
            });
        
            this.indicatorObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        initializeSingleIndicator(element) {
            if (!element || this.processedIndicators.has(element)) return;
            this.hideLoader(element);
            element.style.display = '';
            this.processedIndicators.add(element);
        },

        handleMxDuringRequest(element, targetElement) {
            const mxDuringRequest = element.getAttribute('mx-target-loading');
            if (mxDuringRequest) {
                if (mxDuringRequest === 'cloak') {
                    targetElement.style.setProperty('opacity', '0', 'important');
                    targetElement.classList.add('mx-cloak');
                } else {
                    const placeholder = document.querySelector(mxDuringRequest);
                    if (placeholder) {
                        targetElement.dataset.mxOriginalContent = targetElement.innerHTML;
                        targetElement.innerHTML = placeholder.innerHTML;
                        targetElement.classList.add('mx-placeholder-active');
                    }
                }
            }

            if (element.tagName.toLowerCase() === 'form') {
                Array.from(element.elements).forEach(formElement => {
                    if (!formElement.disabled) {
                        formElement.disabled = true;
                        formElement.classList.add('mx-temp-disabled');
                    }
                });
            }
        },

        establishTargetElement(element) {
            const mxTargetStr = Utils.getAttributeValue(element, 'mx-target');

            if (!mxTargetStr || mxTargetStr === 'this') {
                return element;
            }

            if (mxTargetStr === 'none') {
                return null;
            }

            if (mxTargetStr === 'body') {
                return document.body;
            }

            if (mxTargetStr.startsWith('closest ')) {
                const selector = mxTargetStr.replace('closest ', '');
                return element.closest(selector);
            }

            if (mxTargetStr.startsWith('find ')) {
                const selector = mxTargetStr.replace('find ', '');
                return element.querySelector(selector);
            }

            return document.querySelector(mxTargetStr);
        },

        attemptActivateLoader(element) {
            // Only check the element itself for mx-indicator, not its ancestors
            const indicatorSelector = element.getAttribute('mx-indicator');
            if (indicatorSelector) {
                const loaderEl = document.querySelector(indicatorSelector);
                if (loaderEl) {
                    loaderEl.style.removeProperty('display');
                    loaderEl.classList.remove('mx-indicator-hidden');
                    loaderEl.classList.add('mx-indicator');
                }
            }
        },

        attemptHideLoader(element) {
            const indicatorSelector = Utils.getAttributeValue(element, 'mx-indicator');
            if (indicatorSelector) {
                const loaderEl = document.querySelector(indicatorSelector);
                if (loaderEl) {
                    Dom.hideLoader(loaderEl);
                }
            }
        },

        hideLoader(element) {
            if (element && element.classList.contains('mx-indicator')) {
                element.classList.remove('mx-indicator');
                element.classList.add('mx-indicator-hidden');
                // Don't remove from processedIndicators here since the element may be reused
            }
        },

        processMXDomVals(element) {
            // Try both attribute names
            const mxDomValsStr = element.getAttribute('mx-dom-vals') || element.getAttribute('mx-dom-values');
            if (!mxDomValsStr) return {};

            const domVals = Utils.parseAttributeValue(mxDomValsStr);
            if (!domVals || typeof domVals !== 'object') return {};

            const result = {};
            Object.entries(domVals).forEach(([key, value]) => {
                if (typeof value === 'object' && value.selector && value.property) {
                    const selectedElement = document.querySelector(value.selector);
                    if (selectedElement) {
                        result[key] = selectedElement[value.property];
                    }
                }
            });
            return result;
        },

        populateTargetEl(targetEl, http, element) {
            const selectStr = element.getAttribute('mx-select');
            const mxSwapStr = Dom.establishSwapStr(element);
            const selectOobStr = element.getAttribute('mx-select-oob');
            const tempFragment = document.createDocumentFragment();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = http.responseText;

            tempFragment.appendChild(tempDiv);
            try {
                Dom.handleOobSwaps(tempFragment, selectOobStr);
                Dom.handleMainSwaps(targetEl, tempFragment, selectStr, mxSwapStr);
                Modal.attemptAddModalButtons(targetEl, element);
            } catch (error) {
                console.error('Error in populateTargetEl:', error);
            } finally {
                tempDiv.innerHTML = '';
                tempFragment.textContent = '';
            }
        },

        handleMainSwaps(targetEl, tempFragment, selectStr, mxSwapStr) {
            let contents = selectStr ? tempFragment.querySelectorAll(selectStr) : [tempFragment.firstChild];
            contents.forEach(content => {
                if (content) {
                    Dom.swapContent(targetEl, content, mxSwapStr);
                }
            });
        },

        handleOobSwaps(tempFragment, selectOobStr) {
            if (!selectOobStr) return;

            const parsedValue = Utils.parseAttributeValue(selectOobStr);

            if (typeof parsedValue === 'string') {
                const swapInstructions = parsedValue.split(/,(?![^[]*\])/);
                swapInstructions.forEach(instruction => {
                    const [select, target] = instruction.trim().split(':');
                    this.performOobSwap(tempFragment, { select, target, swap: 'innerHTML' });
                });
            } else if (Array.isArray(parsedValue)) {
                parsedValue.forEach(obj => this.performOobSwap(tempFragment, obj));
            } else if (typeof parsedValue === 'object' && parsedValue !== null) {
                this.performOobSwap(tempFragment, parsedValue);
            } else {
                console.error('Invalid mx-select-oob syntax:', selectOobStr);
            }
        },

        performOobSwap(tempFragment, { select, target, swap = 'innerHTML' }) {
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

            this.swapContent(oobTarget, oobSelected.cloneNode(true), swap);
        },

        swapContent(target, source, swapMethod) {
            const sourceString = typeof source === 'string' ? source : source.outerHTML || source.innerHTML;
            const processedSource = this.removeOutermostDiv(sourceString);

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
                case 'value':
                    if ('value' in target) {  // Check if target has a value property
                        target.value = processedSource;
                    } else {
                        console.warn('Target element does not support value property');
                        target.innerHTML = processedSource;  // Fallback to innerHTML
                    }
                    break;
                case 'none':
                    // Do nothing
                    break;
                default: // 'innerHTML' is the default
                    target.innerHTML = processedSource;
            }
        },

        removeOutermostDiv(htmlString) {
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = htmlString.trim();

            if (tempContainer.firstElementChild && tempContainer.firstElementChild.tagName.toLowerCase() === 'div') {
                const firstDiv = tempContainer.firstElementChild;
                while (firstDiv.firstChild) {
                    tempContainer.insertBefore(firstDiv.firstChild, firstDiv);
                }
                tempContainer.removeChild(firstDiv);
            }

            return tempContainer.innerHTML;
        },

        handleValidationErrors(containingForm, validationErrors) {
            containingForm.querySelectorAll('.form-field-validation-error')
                .forEach(field => field.classList.remove('form-field-validation-error'));
            containingForm.querySelectorAll('.validation-error-report')
                .forEach(report => report.remove());

            let firstErrorElement = null;

            validationErrors.forEach(error => {
                const field = containingForm.querySelector(`[name="${error.field}"]`);
                if (field) {
                    field.classList.add('form-field-validation-error');

                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'validation-error-report';

                    error.messages.forEach(message => {
                        const errorDiv = document.createElement('div');
                        errorDiv.innerHTML = '● ' + Utils.escapeHtml(message);
                        errorContainer.appendChild(errorDiv);
                    });

                    // Find the closest ancestor with 'flex-row' class that's inside or is the form itself
                    const closestFlexRow = field.closest('.flex-row');

                    if (closestFlexRow && (containingForm === closestFlexRow || containingForm.contains(closestFlexRow))) {
                        // 1. Traverse back from the erroneous form field to find a 'target label'
                        let targetLabel = null;
                        let currentElement = field.previousElementSibling;

                        while (currentElement && currentElement.tagName.toLowerCase() !== 'label') {
                            // If we hit another form field or the start of the form, stop looking for a label
                            if (currentElement.tagName.toLowerCase().match(/(input|select|textarea)/) || currentElement === null) {
                                break;
                            }
                            currentElement = currentElement.previousElementSibling;
                        }

                        if (currentElement && currentElement.tagName.toLowerCase() === 'label') {
                            // If a target label is found, insert the errorContainer immediately after the form label
                            targetLabel.parentNode.insertBefore(errorContainer, targetLabel.nextSibling);
                        } else {
                            // OTHERWISE, search for a containing element that is WITHIN containingForm with a class of 'flex-row'
                            const innerFlexRow = field.closest('.flex-row');

                            if (innerFlexRow && containingForm.contains(innerFlexRow)) {
                                // If found, insert before this containing element
                                innerFlexRow.parentNode.insertBefore(errorContainer, innerFlexRow);
                            } else {
                                // OTHERWISE, insert the errorContainer before the form itself
                                containingForm.parentNode.insertBefore(errorContainer, containingForm);
                            }
                        }
                    } else {
                        // If no flex-row within or as the form, insert before the field itself
                        field.parentNode.insertBefore(errorContainer, field);
                    }

                    // Set the first error element for potential focus
                    if (!firstErrorElement) {
                        firstErrorElement = field;
                    }

                    // Special handling for checkbox or radio inputs
                    if (field.type === "checkbox" || field.type === "radio") {
                        let parentContainer = field.closest("div"); // Assuming checkbox/radio is wrapped in a div for styling
                        if (parentContainer) {
                            parentContainer.classList.add("form-field-validation-error");
                            // Note: Setting inline styles like this is usually not recommended. Consider using CSS classes.
                            parentContainer.style.textIndent = "7px";
                        }
                    }
                }
            });

            if (firstErrorElement) {
                setTimeout(() => {
                    firstErrorElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 100);
            }

            const functionName = containingForm.getAttribute('mx-after-validation');
            if (functionName) {
                const customEvent = Utils.createMXEvent(containingForm, event, 'afterValidation', {
                    validationErrors
                });
                Utils.executeMXFunction(functionName, customEvent);
            }

        },

        removeCloak() {
            document.querySelectorAll('.mx-cloak').forEach(el => {
                el.classList.remove('mx-cloak');
                if (el.style.opacity === '0') {
                    el.style.removeProperty('opacity');
                }
            });
        },

        restoreOriginalContent() {
            document.querySelectorAll('.mx-placeholder-active').forEach(el => {
                if (el.dataset.mxOriginalContent) {
                    el.innerHTML = el.dataset.mxOriginalContent;
                    delete el.dataset.mxOriginalContent;
                }
                el.classList.remove('mx-placeholder-active');
            });
        },

        reEnableDisabledElements() {
            document.querySelectorAll('.mx-temp-disabled').forEach(element => {
                element.disabled = false;
                element.classList.remove('mx-temp-disabled');
            });
        },

        establishSwapStr(element) {
            const swapStr = Utils.getAttributeValue(element, 'mx-swap');
            return swapStr || 'innerHTML';
        },

        handlePageLoadedEvents(element) {
            const attribute = CONFIG.CORE_MX_ATTRIBUTES.find(attr => element.hasAttribute(attr));
            if (attribute) {
                event.preventDefault();
                Main.initInvokeHttpRequest(element, attribute);
            }
        },

        clearExistingValidationErrors(containingForm) {
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
        },

        setChildrenOpacity(animationContainer, opacityValue) {
            const opacityNumber = parseFloat(opacityValue);
            if (isNaN(opacityNumber) || opacityNumber < 0 || opacityNumber > 1) {
                throw new Error('Invalid opacity value. It must be a number between 0 and 1.');
            }
            const children = Array.from(animationContainer.children);
            children.forEach(child => {
                child.style.opacity = opacityValue;
            });
        }

    };

    const Modal = {
        buildMXModal(modalData, element, httpMethodAttribute, event) {

            // Is the trigger element inside a modal
            const containingModal = element.closest('.modal');
            if (containingModal) {
                closeModal();
            }

            const modalId = typeof modalData === 'string' ? modalData : modalData.id;

            const existingEl = document.getElementById(modalId);
            if (existingEl) {
                existingEl.remove();
            }

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = modalId;
            modal.style.display = 'none';

            if (typeof modalData === 'object' && modalData.modalHeading) {

                const modalHeading = document.createElement('div');
                modalHeading.className = 'modal-heading';
                let renderCloseIcon = (modalData.renderCloseIcon) ? modalData.renderCloseIcon : true;

                if (renderCloseIcon === false || renderCloseIcon === 'false') {
                    modalHeading.innerHTML = modalData.modalHeading;
                } else {
                    modalHeading.classList.add('flex-row');
                    modalHeading.classList.add('align-center');
                    modalHeading.classList.add('justify-between');

                    const modalHeadingLhs = document.createElement('div');
                    modalHeadingLhs.innerHTML = modalData.modalHeading;
                    modalHeading.appendChild(modalHeadingLhs);

                    const modalHeadingRhs = document.createElement('div');

                    // Check if Font Awesome is available
                    const faIconAvailable = document.querySelector('link[href*="font-awesome"]') !== null || document.querySelector('.fa-times') !== null;

                    if (faIconAvailable) {
                        // If Font Awesome is available, use the Font Awesome icon
                        const closeIcon = document.createElement('i');
                        closeIcon.classList.add('fa', 'fa-times');
                        closeIcon.style.cursor = 'pointer'; // Add pointer cursor for clickability
                        closeIcon.setAttribute('onclick', 'closeModal()');

                        modalHeadingRhs.appendChild(closeIcon);
                    } else {
                        // If Font Awesome is not available, use SVG that mimics the fa-times icon
                        const closeIconSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        closeIconSvg.setAttribute('width', '16');
                        closeIconSvg.setAttribute('height', '16');
                        closeIconSvg.setAttribute('viewBox', '0 0 100 100');
                        closeIconSvg.setAttribute('fill', 'currentColor');
                        closeIconSvg.setAttribute('class', 'bi bi-x');
                        closeIconSvg.style.cursor = 'pointer';

                        const crossGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                        crossGroup.setAttribute('transform', 'rotate(45, 50, 50)');

                        const verticalRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        verticalRect.setAttribute('x', '38');
                        verticalRect.setAttribute('y', '0');
                        verticalRect.setAttribute('width', '24');
                        verticalRect.setAttribute('height', '100');
                        verticalRect.setAttribute('rx', '12');
                        verticalRect.setAttribute('ry', '12');

                        const horizontalRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        horizontalRect.setAttribute('x', '0');
                        horizontalRect.setAttribute('y', '38');
                        horizontalRect.setAttribute('width', '100');
                        horizontalRect.setAttribute('height', '24');
                        horizontalRect.setAttribute('rx', '12');
                        horizontalRect.setAttribute('ry', '12');

                        crossGroup.appendChild(verticalRect);
                        crossGroup.appendChild(horizontalRect);
                        closeIconSvg.appendChild(crossGroup);

                        closeIconSvg.setAttribute('onclick', 'closeModal()');
                        modalHeadingRhs.appendChild(closeIconSvg);
                    }

                    modalHeading.appendChild(modalHeadingRhs);
                }

                modal.appendChild(modalHeading);
            }

            const modalBody = document.createElement('div');
            modalBody.className = 'modal-body';

            const tempSpinner = document.createElement('div');
            tempSpinner.setAttribute('class', 'spinner mt-3 mb-3');
            modalBody.appendChild(tempSpinner);

            modal.appendChild(modalBody);

            if (typeof modalData === 'object' && modalData.modalFooter) {
                const modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer';
                modalFooter.innerHTML = modalData.modalFooter;
                modal.appendChild(modalFooter);
            }

            document.body.appendChild(modal);
            this.openModal(modalId, modalData);

            const targetModal = document.getElementById(modalId);
            if (typeof modalData === 'object' && modalData.width) {
                targetModal.style.maxWidth = modalData.width;
            }

            if (element.hasAttribute('mx-target')) {
                element.removeAttribute('mx-target');
            }
            const modalBodySelector = `#${modalId} .modal-body`;
            element.setAttribute('mx-target', modalBodySelector);

            Http.invokeHttpRequest(element, httpMethodAttribute, event);
        },

        attemptAddModalButtons(targetEl, element) {
            if (element.hasAttribute('mx-build-modal')) {
                const modalValue = element.getAttribute('mx-build-modal');
                const modalOptions = Utils.parseAttributeValue(modalValue);

                if (modalOptions === false) {
                    console.warn('Failed to parse mx-build-modal attribute:', modalValue);
                    return;
                }

                const buttonPara = document.createElement('p');
                buttonPara.setAttribute('class', 'text-center');
                let buttonsAdded = false;

                if (typeof modalOptions === 'object') {
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
                            Modal.closeModal();
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
        },

        initAttemptCloseModal(targetEl, http, element) {
            if (http.status >= 200 && http.status < 300) {
                const closeOnSuccessStr = element.getAttribute('mx-close-on-success');
                if (closeOnSuccessStr === 'true') {
                    window.closeModal();
                    return;
                }
            } else {
                const closeOnErrorStr = element.getAttribute('mx-close-on-error');
                if (closeOnErrorStr === 'true') {
                    window.closeModal();
                }
            }
        },

        openModal(modalId, modalData) {
            trongateMXOpeningModal = true;

            setTimeout(() => {
                trongateMXOpeningModal = false;
            }, 333);

            const mxPageBody = document.body;
            let mxPageOverlay = document.getElementById("overlay");

            if (typeof mxPageOverlay === "undefined" || mxPageOverlay === null) {

                // Create a modal container div and prepend it to the page.
                const mxModalContainer = document.createElement("div");
                mxModalContainer.setAttribute("id", "modal-container");
                mxModalContainer.setAttribute("class", "mx-modal-container");
                mxModalContainer.setAttribute("style", "z-index: 3;");
                mxPageBody.prepend(mxModalContainer);

                // Create an overlay element.
                const mxPageOverlay = document.createElement("div");
                mxPageOverlay.setAttribute("id", "overlay");
                mxPageOverlay.setAttribute("style", "z-index: 2");
                mxPageBody.prepend(mxPageOverlay);

                // Fetch existing modal (currently hidden and appended onto page).
                const mxModal = document.getElementById(modalId);
                mxModal.removeAttribute('style');

                mxModal.setAttribute("class", "modal");
                mxModal.setAttribute("id", modalId);
                mxModal.style.zIndex = 4;
                mxModalContainer.appendChild(mxModal);

                // Get the top margin for the new modal (attempting to read from modalData)
                const mxModalMarginTop = typeof modalData === 'object'
                  ? (modalData.marginTop || modalData['margin-top'] || '12vh')
                  : '12vh';

                // Make the new modal element appear!
                setTimeout(() => {
                    mxModal.style.opacity = 1;
                    mxModal.style.marginTop = mxModalMarginTop;
                }, 0);
            }

        }

    };

    const Animation = {
        // Animation with callback support
        initAnimateSuccessWithCallback(targetEl, http, element, callback) {
            const animationContainer = this.estAnimationContainer(targetEl, element);
            const animationContainerChildren = animationContainer.children;
            const tempContainer = document.createElement('div');
            tempContainer.setAttribute('class', 'mx-temp-container cloak');
            const bodyEl = document.body;
            bodyEl.appendChild(tempContainer);

            // Loop through all the children of animationContainer and move them to tempContainer
            while (animationContainerChildren.length > 0) {
                tempContainer.appendChild(animationContainerChildren[0]);
            }

            this.mxDrawBigTick(animationContainer);

            setTimeout(() => {
                this.mxDestroyAnimation(animationContainer);
                // Execute the callback after animation cleanup
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }, 1300);
        },

        initAnimateSuccess(targetEl, http, element) {
            this.initAnimateSuccessWithCallback(targetEl, http, element, () => {
                Modal.initAttemptCloseModal(targetEl, http, element);
            });
        },

        initAnimateError(targetEl, http, element) {
            const animationContainer = this.estAnimationContainer(targetEl, element);
            const animationContainerChildren = animationContainer.children;
            const tempContainer = document.createElement('div');
            tempContainer.setAttribute('class', 'mx-temp-container cloak');
            const bodyEl = document.body;
            bodyEl.appendChild(tempContainer);

            // Loop through all the children of animationContainer and move them to tempContainer
            while (animationContainerChildren.length > 0) {
                tempContainer.appendChild(animationContainerChildren[0]);
            }

            this.mxDrawBigCross(animationContainer);

            setTimeout(() => {
                this.mxDestroyAnimation(animationContainer);
                Modal.initAttemptCloseModal(targetEl, http, element);
            }, 1300);

        },

        estAnimationContainer(targetEl, element) {
            let animationContainer = targetEl;

            const containingModal = element.closest('.modal');
            if (containingModal) {
                const containingModalBody = containingModal.querySelector('.modal-body');
                animationContainer = containingModalBody;
            } else {
                const containingForm = element.closest('form');
                if (containingForm) {
                    animationContainer = containingForm;
                }
            }
            return animationContainer;
        },

        mxDrawBigCross(overlay) {
            const bigCross = document.createElement("div");
            bigCross.setAttribute("style", "display: none");
            const trigger = document.createElement("div");
            trigger.setAttribute("class", "mx-trigger");
            bigCross.appendChild(trigger);

            const crossSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            crossSvg.setAttribute("version", "1.1");
            crossSvg.setAttribute("id", "mx-cross");
            crossSvg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
            crossSvg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
            crossSvg.setAttribute("x", "0px");
            crossSvg.setAttribute("y", "0px");
            crossSvg.setAttribute("viewBox", "0 0 37 37");
            crossSvg.setAttribute("xml:space", "preserve");
            bigCross.appendChild(crossSvg);

            const crossPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
            crossPath.setAttribute("class", "mx-circ path");
            crossPath.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10");
            crossPath.setAttribute("d", "M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z");
            crossSvg.appendChild(crossPath);

            const polyline1 = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            polyline1.setAttribute("class", "mx-tick path");
            polyline1.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;");
            polyline1.setAttribute("points", "11.1,10 25.4,27.2");
            crossSvg.appendChild(polyline1);

            const polyline2 = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            polyline2.setAttribute("class", "mx-cross path");
            polyline2.setAttribute("style", "fill:none;stroke:#cc0000;stroke-width:3;");
            polyline2.setAttribute("points", "24.1,10 12.4,27.2");
            crossSvg.appendChild(polyline2);

            overlay.appendChild(bigCross);
            bigCross.style.display = "flex";

            setTimeout(() => {
                const things = document.getElementsByClassName("mx-trigger")[0];
                things.classList.add("mx-drawn");
            }, 100);
        },

        mxDrawBigTick(animationContainer) {
            const bigTick = document.createElement("div");
            bigTick.setAttribute("style", "display: none");
            const trigger = document.createElement("div");
            trigger.setAttribute("class", "mx-trigger");
            bigTick.appendChild(trigger);

            const tickSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            tickSvg.setAttribute("version", "1.1");
            tickSvg.setAttribute("id", "mx-tick");
            tickSvg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
            tickSvg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
            tickSvg.setAttribute("x", "0px");
            tickSvg.setAttribute("y", "0px");
            tickSvg.setAttribute("viewBox", "0 0 37 37");
            tickSvg.setAttribute("xml:space", "preserve");
            bigTick.appendChild(tickSvg);

            const tickPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
            tickPath.setAttribute("class", "mx-circ path");
            tickPath.setAttribute("style", "fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10");
            tickPath.setAttribute("d", "M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z");
            tickSvg.appendChild(tickPath);

            const polyline = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            polyline.setAttribute("class", "mx-tick path");
            polyline.setAttribute("style", "fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10;");
            polyline.setAttribute("points", "11.6,20 15.9,24.2 26.4,13.8");
            tickSvg.appendChild(polyline);

            animationContainer.appendChild(bigTick);
            bigTick.style.display = "flex";

            setTimeout(() => {
                const things = document.getElementsByClassName("mx-trigger")[0];
                things.classList.add("mx-drawn");
            }, 100);
        },

        mxDestroyAnimation(animationContainer) {
            while(animationContainer.firstChild) {
                animationContainer.removeChild(animationContainer.firstChild);
            }

            const tempContainer = document.querySelector('.mx-temp-container');

            if (tempContainer) {
                const tempContainerChildren = tempContainer.children;
                while (tempContainerChildren.length > 0) {
                    animationContainer.appendChild(tempContainerChildren[0]);
                }
                tempContainer.remove();
            }

        }
    };

    const Main = {
        initializeTrongateMX() {
            // Initialize observer for indicators
            Dom.initializeIndicatorObserver();

            // Initialize existing indicators
            document.querySelectorAll('.mx-indicator').forEach(element => {
                Dom.initializeSingleIndicator(element);
            });

            const events = ['click', 'dblclick', 'change', 'submit', 'keyup', 'keydown', 'focus', 'blur', 'input'];
            events.forEach(eventType => {
                // Avoid conflicts and stale listeners by removing and re-adding event listeners
                document.body.removeEventListener(eventType, Main.handleTrongateMXEvent);
                document.body.addEventListener(eventType, Main.handleTrongateMXEvent);
            });

            document.querySelectorAll('[mx-trigger*="load"]').forEach(Dom.handlePageLoadedEvents);
            Main.attemptInitPolling();
            window.addEventListener('popstate', Main.handlePopState);
        },

        async handleTrongateMXEvent(event) {
            const element = event.target.closest('[' + CONFIG.CORE_MX_ATTRIBUTES.join('],[') + ']');

            if (!element) return;

            const triggerEvent = Main.establishTriggerEvent(element);
            if (triggerEvent !== event.type) return;

            event.preventDefault();

            // Execute mx-on-trigger function if present
            const onTriggerFunction = element.getAttribute('mx-on-trigger');
            if (onTriggerFunction) {
                const customEvent = Utils.createMXEvent(element, event, 'trigger');
                try {
                    await Utils.executeMXFunction(onTriggerFunction, customEvent);
                } catch (error) {
                    console.error(`Error executing ${onTriggerFunction}:`, error);
                    return;
                }
            }

            // Throttle check here
            const throttleTime = parseInt(element.getAttribute('mx-throttle')) || 0;
            if (throttleTime > 0 && !Utils.canMakeRequest(throttleTime)) {
                console.log('Request throttled');
                return;
            }

            const attribute = CONFIG.CORE_MX_ATTRIBUTES.find(attr => element.hasAttribute(attr));

            // Start HTTP request processing before handling mx-remove
            let httpRequestStarted = false;
            if (attribute && attribute !== 'mx-remove') {
                httpRequestStarted = true;
                if ((element.tagName.toLowerCase() === 'form' || element.closest('form')) &&
                    (attribute !== 'mx-get')) {
                    Main.mxSubmitForm(element, triggerEvent, attribute, event);
                } else if (element.tagName.toLowerCase() === 'select' &&
                           attribute === 'mx-get' &&
                           element.getAttribute('mx-trigger') === 'change') {
                    Main.initInvokeHttpRequest(element, attribute, event);
                } else {
                    Main.initInvokeHttpRequest(element, attribute, event);
                }
            }

            // Handle mx-remove after initiating HTTP request
            if (element.hasAttribute('mx-remove')) {
                const value = element.getAttribute('mx-remove');

                // Use setTimeout to ensure HTTP request gets processed first
                setTimeout(() => {
                    if (value === 'true') {
                        const parent = element.parentElement;
                        if (parent) {
                            parent.remove();
                        }
                    } else {
                        const ancestor = element.closest(value);
                        if (ancestor) {
                            ancestor.remove();
                        }
                    }
                }, 0);
            }

            // If no HTTP request was started and no mx-remove was handled, process as normal
            if (!httpRequestStarted && !element.hasAttribute('mx-remove')) {
                if ((element.tagName.toLowerCase() === 'form' || element.closest('form')) &&
                    (attribute !== 'mx-get')) {
                    Main.mxSubmitForm(element, triggerEvent, attribute, event);
                } else if (element.tagName.toLowerCase() === 'select' &&
                           attribute === 'mx-get' &&
                           element.getAttribute('mx-trigger') === 'change') {
                    Main.initInvokeHttpRequest(element, attribute, event);
                } else {
                    Main.initInvokeHttpRequest(element, attribute, event);
                }
            }
        },

        establishTriggerEvent(element) {
            const triggerEventStr = element.getAttribute('mx-trigger');

            if (triggerEventStr === 'activate') {
                return '__ACTIVATE__';  // A special value that will never match a real event type
            }

            if (triggerEventStr) {
                return triggerEventStr;
            }

            const tagName = element.tagName.toLowerCase();
            switch (tagName) {
                case 'form':
                    return 'submit';
                case 'input':
                    return element.type === 'text' ? 'input' : 'change';
                case 'textarea':
                case 'select':
                    return 'change';
                default:
                    return 'click';
            }
        },

        mxSubmitForm(element, triggerEvent, httpMethodAttribute, event) {
            const containingForm = element.closest('form');
            if (!containingForm) {
                console.error('No containing form found');
                return;
            }

            Dom.clearExistingValidationErrors(containingForm);

            if (CONFIG.REQUIRES_DATA_ATTRIBUTES.includes(httpMethodAttribute)) {
                Http.invokeFormPost(element, triggerEvent, httpMethodAttribute, containingForm, event);
            } else {
                Main.initInvokeHttpRequest(containingForm, httpMethodAttribute, event);
            }
        },

        initInvokeHttpRequest(element, httpMethodAttribute) {
            const buildModalStr = element.getAttribute('mx-build-modal');

            if (buildModalStr) {
                const modalOptions = Utils.parseAttributeValue(buildModalStr);

                if (modalOptions === false) {
                    console.warn("Invalid JSON in mx-build-modal:", buildModalStr);
                    return;
                }

                if (typeof modalOptions === "string") {
                    const modalData = {
                        id: modalOptions
                    };
                    Modal.buildMXModal(modalData, element, httpMethodAttribute);
                } else {
                    Modal.buildMXModal(modalOptions, element, httpMethodAttribute);
                }
            } else {
                Http.invokeHttpRequest(element, httpMethodAttribute);
            }
        },

        attemptInitPolling() {
            const pollingElements = document.querySelectorAll('[mx-trigger]');
            pollingElements.forEach(element => {
                const triggerAttr = element.getAttribute('mx-trigger');
                Main.setupPolling(element, triggerAttr);
            });
        },

        setupPolling(element, triggerAttr) {
            Main.stopPolling(element); // Clear existing polling first
        
            const basicPollingMatch = triggerAttr.match(/^every\s+(\d+[smhd])$/);
            if (basicPollingMatch) {
                const interval = Utils.parsePollingInterval(basicPollingMatch[1]);
                if (interval) {
                    const intervalId = setInterval(() => Main.pollElement(element), interval);
                    CONFIG.POLLING_INTERVALS.set(element, intervalId);
                }
                return;
            }
        
            const loadPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd])$/);
            if (loadPollingMatch) {
                const delay = Utils.parsePollingInterval(loadPollingMatch[1]);
                if (delay) {
                    const timeoutId = setTimeout(() => {
                        Main.pollElement(element);
                        const intervalId = setInterval(() => Main.pollElement(element), delay);
                        CONFIG.POLLING_INTERVALS.set(element, intervalId);
                    }, delay);
                    CONFIG.POLLING_INTERVALS.set(element, { timeoutId });
                }
                return;
            }
        
            const delayedPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd]),\s*every\s+(\d+[smhd])$/);
            if (delayedPollingMatch) {
                const initialDelay = Utils.parsePollingInterval(delayedPollingMatch[1]);
                const interval = Utils.parsePollingInterval(delayedPollingMatch[2]);
                if (initialDelay && interval) {
                    const timeoutId = setTimeout(() => {
                        Main.pollElement(element);
                        const intervalId = setInterval(() => Main.pollElement(element), interval);
                        CONFIG.POLLING_INTERVALS.set(element, intervalId);
                    }, initialDelay);
                    CONFIG.POLLING_INTERVALS.set(element, { timeoutId });
                }
                return;
            }
        },
        startPolling(element, intervalStr = '5s') {
            if (!element) {
                console.error('startPolling: No element provided');
                return false;
            }
            
            Main.stopPolling(element); // Clear any existing polling
            const interval = Utils.parsePollingInterval(intervalStr);
            if (!interval) {
                console.error(`startPolling: Invalid interval format: ${intervalStr}`);
                return false;
            }
            
            // Store all original attributes that need to be preserved
            const attributesToPreserve = ['mx-get', 'mx-select-oob', 'mx-target'];
            attributesToPreserve.forEach(attr => {
                if (element.hasAttribute(attr)) {
                    element.setAttribute(`data-original-${attr}`, element.getAttribute(attr));
                }
            });
            
            // Set mx-trigger to 'polling' to prevent user events
            element.setAttribute('mx-trigger', 'polling');
            const intervalId = setInterval(() => Main.pollElement(element), interval);
            CONFIG.POLLING_INTERVALS.set(element, intervalId);
            element.setAttribute('data-polling-active', 'true');
            
            return true;
        },
        stopPolling(element) {
            
            const timer = CONFIG.POLLING_INTERVALS.get(element);
            if (timer) {
                if (typeof timer === 'number') {
                    clearInterval(timer);
                } else if (timer && typeof timer === 'object' && timer.timeoutId) {
                    clearTimeout(timer.timeoutId);
                }
                CONFIG.POLLING_INTERVALS.delete(element);
                
                // Restore all original attributes
                const attributesToRestore = ['mx-get', 'mx-select-oob', 'mx-target'];
                attributesToRestore.forEach(attr => {
                    const originalAttrName = `data-original-${attr}`;
                    if (element.hasAttribute(originalAttrName)) {
                        element.setAttribute(attr, element.getAttribute(originalAttrName));
                        element.removeAttribute(originalAttrName);
                    }
                });
                
                if (element.hasAttribute('data-original-mx-trigger')) {
                    element.setAttribute('mx-trigger', element.getAttribute('data-original-mx-trigger'));
                    element.removeAttribute('data-original-mx-trigger');
                } else {
                    element.removeAttribute('mx-trigger');
                }
                element.removeAttribute('data-polling-active');
                
                return true;
            }
            return false;
        },
        stopAllPolling() {
            CONFIG.POLLING_INTERVALS.forEach((timer, element) => {
                if (typeof timer === 'number') {
                    clearInterval(timer);
                } else if (timer && typeof timer === 'object' && timer.timeoutId) {
                    clearTimeout(timer.timeoutId);
                }
                element.removeAttribute('mx-trigger');
            });
            CONFIG.POLLING_INTERVALS = new WeakMap(); // Reset the WeakMap
        },
        pollElement(element) {
            
            const attribute = CONFIG.CORE_MX_ATTRIBUTES.find(attr => element.hasAttribute(attr));
            if (attribute) {
                
                // Throttle check for polling
                const throttleTime = parseInt(element.getAttribute('mx-throttle')) || 0;
                if (throttleTime > 0 && !Utils.canMakeRequest(throttleTime)) {
                    console.log('Polling request throttled');
                    return;
                }
                
                Main.initInvokeHttpRequest(element, attribute);
            } else {
                console.log('No polling attribute found on element');
            }
        },
        handlePopState(event) {
            const currentPath = window.location.pathname;
            const element = document.querySelector(`[mx-get^="${currentPath}"]`);

            if (element) {
                // Simulate a click on the matching element
                const clickEvent = new Event('click');
                element.dispatchEvent(clickEvent);
            } else {
                // If no matching element is found, reload the page
                window.location.reload();
            }
        }
    };

    function handleEscapeKey(event) {
        if (event.key === "Escape") {
            const modalContainer = document.getElementById("modal-container");
            if (modalContainer) {
                closeModal();
            }
        }
    }

    function handleMxModalClick(event) {

        if (trongateMXOpeningModal) {
            return;
        }

        const preExistingMxModalContainer = document.querySelector('.mx-modal-container');

        if (preExistingMxModalContainer) {
            const preExistingMxModal = preExistingMxModalContainer.querySelector(".modal");
            const clickedOutside = preExistingMxModal && !preExistingMxModal.contains(event.target);
            const mousedownInsideModal = preExistingMxModal && preExistingMxModal.contains(mousedownEl);
            const mouseupInsideModal = preExistingMxModal && preExistingMxModal.contains(mouseupEl);

            if ((clickedOutside) && (!mousedownInsideModal) && (!mouseupInsideModal)) {
                closeModal();
            }
        }
    }

    // Initialize Trongate MX when the DOM is loaded
    document.addEventListener('DOMContentLoaded', Main.initializeTrongateMX);

    // Initialize closing of modals upon pressing 'Escape' key.
    document.addEventListener("keydown", handleEscapeKey);


    document.addEventListener("click", (event) => {
        handleMxModalClick(event);
    });

    // Establish the target element when mouse down event happens.
    document.addEventListener("mousedown", (event) => {
        mousedownEl = event.target;
    });

    // Establish the target element when mouse down event happens.
    document.addEventListener("mouseup", (event) => {
        mouseupEl = event.target;
    });

    // Expose necessary functions to the global scope
    window.TrongateMX = {
        init: Main.initializeTrongateMX,
        openModal: Modal.openModal,
        startPolling: Main.startPolling.bind(Main),
        stopPolling: Main.stopPolling.bind(Main),
        stopAllPolling: Main.stopAllPolling.bind(Main)
    };

})(window);

const _mxOpenModal = function(modalId) {
    trongateMXOpeningModal = true;
    setTimeout(() => {
        trongateMXOpeningModal = false;
    }, 333);

    const mxPageBody = document.body;
    let mxPageOverlay = document.getElementById("overlay");

    if (typeof mxPageOverlay === "undefined" || mxPageOverlay === null) {
        const mxModalContainer = document.createElement("div");
        mxModalContainer.setAttribute("id", "modal-container");
        mxModalContainer.setAttribute("class", "mx-modal-container");
        mxModalContainer.setAttribute("style", "z-index: 3;");
        mxPageBody.prepend(mxModalContainer);

        const mxPageOverlay = document.createElement("div");
        mxPageOverlay.setAttribute("id", "overlay");
        mxPageOverlay.setAttribute("style", "z-index: 2");
        mxPageBody.prepend(mxPageOverlay);

        const mxModal = document.getElementById(modalId);
        mxModal.removeAttribute('style');

        mxModal.setAttribute("class", "modal");
        mxModal.setAttribute("id", modalId);
        mxModal.style.zIndex = 4;
        mxModalContainer.appendChild(mxModal);

        setTimeout(() => {
            mxModal.style.opacity = 1;
            mxModal.style.marginTop = '12vh';
        }, 0);
    }
};

const _mxCloseModal = function () {
    const mxModalContainer = document.getElementById("modal-container");
    if (mxModalContainer) {
        const openModal = mxModalContainer.firstChild;
        openModal.style.zIndex = -4;
        openModal.style.opacity = 0;
        openModal.style.marginTop = '12vh';
        openModal.style.display = "none";
        const tmxBody = document.querySelector('body');
        tmxBody.appendChild(openModal);
        mxModalContainer.remove();

        const overlay = document.getElementById("overlay");
        if (overlay) {
            overlay.remove();
        }

        const event = new Event("modalClosed", { bubbles: true, cancelable: true });
        document.dispatchEvent(event);
    }
};

window.openModal = window.openModal || _mxOpenModal;
window.closeModal = window.closeModal || _mxCloseModal;