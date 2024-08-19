(function(window) {
    'use strict';

    const CONFIG = {
        CORE_MX_ATTRIBUTES: ['mx-get', 'mx-post', 'mx-put', 'mx-delete', 'mx-patch', 'mx-remove'],
        REQUIRES_DATA_ATTRIBUTES: ['mx-post', 'mx-put', 'mx-patch'],
        DEFAULT_TIMEOUT: 10000
    };

    let lastRequestTime = 0;

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
                .replace(/'/g, "&#039;");
        },

        getAttributeValue(element, attributeName) {
            let current = element;
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
        }
    };

    const Http = {
        setupHttpRequest(element, httpMethodAttribute) {
            const targetUrl = element.getAttribute(httpMethodAttribute);
            const requestType = httpMethodAttribute.replace('mx-', '').toUpperCase();
            Dom.attemptActivateLoader(element);
            
            const http = new XMLHttpRequest();
            http.open(requestType, targetUrl);
            http.setRequestHeader('Trongate-MX-Request', 'true');
            http.timeout = CONFIG.DEFAULT_TIMEOUT;
            
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
                console.error('Request timed out');
            };
        },

        commonHttpRequest(element, httpMethodAttribute, containingForm = null) {
            const http = Http.setupHttpRequest(element, httpMethodAttribute);
            Http.setMXHeaders(http, element);
            Http.setMXHandlers(http, element);
        
            const isForm = containingForm !== null;
            let formData = isForm ? new FormData(containingForm) : new FormData();
        
            // Process mx-vals
            const mxValsStr = element.getAttribute('mx-vals');
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

        invokeFormPost(element, triggerEvent, httpMethodAttribute, containingForm) {
            const { http, formData, targetElement } = Http.commonHttpRequest(element, httpMethodAttribute, containingForm);
        
            http.onload = function() {
                Dom.attemptHideLoader(containingForm);
                
                const isSuccessfulResponse = http.status >= 200 && http.status < 300;
                const shouldResetForm = isSuccessfulResponse && !element.hasAttribute(httpMethodAttribute);
                
                if (shouldResetForm) {
                    containingForm.reset();
                }
        
                const responseTarget = element.hasAttribute(httpMethodAttribute) ? element : containingForm;
                Http.handleHttpResponse(http, responseTarget);
            };
        
            try {
                http.send(formData);
            } catch (error) {
                Dom.attemptHideLoader(containingForm);
                console.error('Error sending form request:', error);
            }
        },

        invokeHttpRequest(element, httpMethodAttribute) {
            const { http, formData, targetElement } = Http.commonHttpRequest(element, httpMethodAttribute);
            
            http.setRequestHeader('Accept', 'text/html');
        
            http.onload = function() {
                Dom.attemptHideLoader(element);
                Http.handleHttpResponse(http, element);
            };
        
            try {
                http.send(formData);
            } catch (error) {
                Dom.attemptHideLoader(element);
                console.error('Error sending request:', error);
            }
        },

        handleHttpResponse(http, element) {
            Dom.removeCloak();
            Dom.restoreOriginalContent();
            Dom.reEnableDisabledElements();
        
            element.classList.remove('blink');
        
            if (http.status >= 200 && http.status < 300) {
                if (http.getResponseHeader('Content-Type').includes('text/html')) {
                    const targetEl = Dom.establishTargetElement(element);
        
                    if (targetEl) {
                        const successAnimateStr = element.getAttribute('mx-animate-success');
        
                        if (successAnimateStr) {
                            Animation.initAnimateSuccess(targetEl, http, element);
                        } else {
                            Modal.initAttemptCloseModal(targetEl, http, element);
                            Dom.populateTargetEl(targetEl, http, element);
                        }
                    }
        
                    Http.attemptInitOnSuccessActions(http, element);
        
                } else {
                    console.log('Response is not HTML. Handle accordingly.');
                }
            } else {
                console.error('Request failed with status:', http.status);
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
        
                const containingForm = element.closest('form');
                if (containingForm) {
                    Http.attemptDisplayValidationErrors(http, element, containingForm);
                }
        
                const errorAnimateStr = element.getAttribute('mx-animate-error');
        
                if (errorAnimateStr) {
                    Animation.initAnimateError(element, http, element);
                } else {
                    const targetEl = element ?? targetEl;
                    Modal.initAttemptCloseModal(targetEl, http, element);
                    Http.attemptInitOnErrorActions(http, element);
                }
            }
        
            Dom.attemptHideLoader(element);
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
        handleMxDuringRequest(element, targetElement) {
            const mxDuringRequest = element.getAttribute('mx-during-request');
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
            const indicatorSelector = Utils.getAttributeValue(element, 'mx-indicator');
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
            }
        },

        processMXDomVals(element) {
            const mxDomValsStr = element.getAttribute('mx-dom-vals');
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
                Dom.executeAfterSwap(element);
        
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

        executeAfterSwap(element) {
            const functionName = element.getAttribute('mx-after-swap');
            if (functionName) {
                const cleanFunctionName = functionName.replace(/\(\)$/, '');
                
                if (typeof window[cleanFunctionName] === 'function') {
                    try {
                        window[cleanFunctionName]();
                    } catch (error) {
                        console.error(`Error executing ${cleanFunctionName}:`, error);
                    }
                } else {
                    console.warn(`Function ${cleanFunctionName} not found`);
                }
            }
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
                        errorDiv.innerHTML = '&#9679; ' + Utils.escapeHtml(message);
                        errorContainer.appendChild(errorDiv);
                    });
        
                    let label = containingForm.querySelector(`label[for="${field.id}"]`);
                    if (!label) {
                        label = field.previousElementSibling;
                        while (label && label.tagName.toLowerCase() !== 'label') {
                            label = label.previousElementSibling;
                        }
                    }
        
                    if (label) {
                        label.parentNode.insertBefore(errorContainer, label.nextSibling);
                        if (!firstErrorElement) firstErrorElement = label;
                    } else {
                        field.parentNode.insertBefore(errorContainer, field.nextSibling);
                        if (!firstErrorElement) firstErrorElement = field;
                    }
        
                    if (field.type === "checkbox" || field.type === "radio") {
                        let parentContainer = field.closest("div");
                        if (parentContainer) {
                            parentContainer.classList.add("form-field-validation-error");
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

        setChildrenOpacity(overlayTargetEl, opacityValue) {
            const opacityNumber = parseFloat(opacityValue);
            if (isNaN(opacityNumber) || opacityNumber < 0 || opacityNumber > 1) {
                throw new Error('Invalid opacity value. It must be a number between 0 and 1.');
            }
            const children = Array.from(overlayTargetEl.children);
            children.forEach(child => {
                child.style.opacity = opacityValue;
            });
        }
        
    };

    const Modal = {
        buildMXModal(modalData, element, httpMethodAttribute) {
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
                modalHeading.innerHTML = modalData.modalHeading;
                modal.appendChild(modalHeading);
            }

            const modalBody = document.createElement('div');
            modalBody.className = 'modal-body';

            const tempSpinner = document.createElement('div');
            tempSpinner.setAttribute('class', 'spinner mt-3 mb-3');
            modalBody.appendChild(tempSpinner);

            modal.appendChild(modalBody);
            document.body.appendChild(modal);

            this.openModal(modalId);

            const targetModal = document.getElementById(modalId);
            if (typeof modalData === 'object' && modalData.width) {
                targetModal.style.maxWidth = modalData.width;
            }

            if (element.hasAttribute('mx-target')) {
                element.removeAttribute('mx-target');
            }
            const modalBodySelector = `#${modalId} .modal-body`;
            element.setAttribute('mx-target', modalBodySelector);

            Http.invokeHttpRequest(element, httpMethodAttribute);
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

                if (typeof modalOptions === 'string') {
                    const closeBtn = document.createElement('button');
                    closeBtn.setAttribute('class', 'alt');
                    closeBtn.innerText = 'Close';
                    closeBtn.setAttribute('onclick', 'closeModal()');
                    buttonPara.appendChild(closeBtn);
                    buttonsAdded = true;
                } else if (typeof modalOptions === 'object') {
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
                    this.closeModal();
                    return;
                }
            } else {
                const closeOnErrorStr = element.getAttribute('mx-close-on-error');
                if (closeOnErrorStr === 'true') {
                    this.closeModal();
                }
            }
        },

        openModal(modalId) {
            var body = document.querySelector("body");
            var pageOverlay = document.getElementById("overlay");

            if (typeof pageOverlay === "undefined" || pageOverlay === null) {
                var modalContainer = document.createElement("div");
                modalContainer.setAttribute("id", "modal-container");
                modalContainer.setAttribute("style", "z-index: 3;");
                body.prepend(modalContainer);

                var overlay = document.createElement("div");
                overlay.setAttribute("id", "overlay");
                overlay.setAttribute("style", "z-index: 2");

                body.prepend(overlay);

                var targetModal = document.getElementById(modalId);
                var targetModalContent = targetModal.innerHTML;
                targetModal.remove();

                var newModal = document.createElement("div");
                newModal.setAttribute("class", "modal");
                newModal.setAttribute("id", modalId);

                newModal.style.zIndex = 4;
                newModal.innerHTML = targetModalContent;
                modalContainer.appendChild(newModal);

                setTimeout(() => {
                    newModal.style.opacity = 1;
                    newModal.style.marginTop = "12vh";
                }, 0);
            }
        },

        closeModal() {
            var modalContainer = document.getElementById("modal-container");
            if (modalContainer) {
                var openModal = modalContainer.firstChild;

                openModal.style.zIndex = -4;
                openModal.style.opacity = 0;
                openModal.style.marginTop = "12vh";
                openModal.style.display = "none";
                document.body.appendChild(openModal);

                modalContainer.remove();

                var overlay = document.getElementById("overlay");
                if (overlay) {
                    overlay.remove();
                }
                var event = new Event('modalClosed', { bubbles: true, cancelable: true });
                document.dispatchEvent(event);
            }
        }
    };

    const Animation = {
        initAnimateError(targetEl, http, element) {
            const overlayTargetEl = this.estOverlayTargetEl(targetEl, element);
            const overlay = this.mxCreateOverlay(overlayTargetEl);

            this.mxDrawBigCross(overlay);

            setTimeout(() => {
                const targetEl = element ?? targetEl;
                this.mxDestroyAnimation(targetEl, http, element);
                Dom.setChildrenOpacity(overlayTargetEl, 1);

                if (overlayTargetEl.style.minHeight) {
                    overlayTargetEl.style.minHeight = '';
                }

                Modal.initAttemptCloseModal(targetEl, http, element);
                Http.attemptInitOnErrorActions(http, element);

            }, 1300);
        },

        initAnimateSuccess(targetEl, http, element) {
            const overlayTargetEl = this.estOverlayTargetEl(targetEl, element);
            const overlay = this.mxCreateOverlay(overlayTargetEl);

            this.mxDrawBigTick(element, overlay, targetEl);

            setTimeout(() => {
                this.mxDestroyAnimation(targetEl, http, element);
                Dom.setChildrenOpacity(overlayTargetEl, 1);

                if (overlayTargetEl.style.minHeight) {
                    overlayTargetEl.style.minHeight = '';
                }

                Modal.initAttemptCloseModal(targetEl, http, element);
                Dom.populateTargetEl(targetEl, http, element);
            }, 1300);
        },

        estOverlayTargetEl(targetEl, element) {
            let overlayTargetEl = targetEl;
            const containingModalBody = element.closest('.modal-body');
            if (containingModalBody) {
                overlayTargetEl = containingModalBody;
            } else {
                const containingForm = element.closest('form');
                if (containingForm) {
                    overlayTargetEl = containingForm;
                }
            }
            return overlayTargetEl;
        },

        mxCreateOverlay(overlayTargetEl) {
            const rect = overlayTargetEl.getBoundingClientRect();
            const overlay = document.createElement('div');
            overlay.style.position = 'absolute';
            overlay.style.top = `${rect.top + window.scrollY}px`;
            overlay.style.left = `${rect.left + window.scrollX}px`;
            overlay.style.width = `${rect.width}px`;
            overlay.style.minHeight = `${rect.height}px`;
            overlay.style.display = 'flex';
            overlay.style.flexDirection = 'column';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.classList.add('mx-animation');
            overlay.style.zIndex = '9999';
            document.body.appendChild(overlay);
            Dom.setChildrenOpacity(overlayTargetEl, 0);
            setTimeout(() => {
                const overlayHeight = overlay.offsetHeight;
                if (overlayHeight > overlayTargetEl.offsetHeight) {
                    overlayTargetEl.style.minHeight = overlayHeight + 'px';
                }
            }, 1);
            return overlay;
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

        mxDrawBigTick(element, overlay, targetEl) {
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

            overlay.appendChild(bigTick);
            bigTick.style.display = "flex";

            setTimeout(() => {
                const things = document.getElementsByClassName("mx-trigger")[0];
                things.classList.add("mx-drawn");
            }, 100);
        },

        mxDestroyAnimation() {
            const mxAnimationEl = document.querySelector('.mx-animation');
            if (mxAnimationEl) {
                mxAnimationEl.remove();
            }
        }
    };

    const Main = {
        initializeTrongateMX() {
            document.querySelectorAll('.mx-indicator').forEach(element => {
                Dom.hideLoader(element);
                element.style.display = '';
            });

            const events = ['click', 'dblclick', 'change', 'submit', 'keyup', 'keydown', 'focus', 'blur', 'input'];
            events.forEach(eventType => {
                document.body.addEventListener(eventType, Main.handleTrongateMXEvent);
            });

            document.querySelectorAll('[mx-trigger*="load"]').forEach(Dom.handlePageLoadedEvents);

            Main.attemptInitPolling();
        },

        handleTrongateMXEvent(event) {
            const element = event.target.closest('[' + CONFIG.CORE_MX_ATTRIBUTES.join('],[') + ']');

            if (!element) return;

            const triggerEvent = Main.establishTriggerEvent(element);

            if (triggerEvent !== event.type) return;

            event.preventDefault();

            // Add throttle check here
            const throttleTime = parseInt(element.getAttribute('mx-throttle')) || 0;
            if (throttleTime > 0 && !Utils.canMakeRequest(throttleTime)) {
                console.log('Request throttled');
                return;
            }

            if (element.hasAttribute('mx-remove')) {
                const parent = element.closest('.category-level');
                if (parent) {
                    parent.remove();
                }
                return;
            }

            const attribute = CONFIG.CORE_MX_ATTRIBUTES.find(attr => element.hasAttribute(attr));

            if (
                (element.tagName.toLowerCase() === 'form' || element.closest('form')) && 
                (attribute !== 'mx-get') &&
                !element.hasAttribute('mx-vals')
            ) {
                Main.mxSubmitForm(element, triggerEvent, attribute);
            } else {
                Main.initInvokeHttpRequest(element, attribute);
            }
        },

        establishTriggerEvent(element) {
            const triggerEventStr = element.getAttribute('mx-trigger');

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

        mxSubmitForm(element, triggerEvent, httpMethodAttribute) {
            const containingForm = element.closest('form');
            if (!containingForm) {
                console.error('No containing form found');
                return;
            }

            Dom.clearExistingValidationErrors(containingForm);

            if (CONFIG.REQUIRES_DATA_ATTRIBUTES.includes(httpMethodAttribute)) {
                Http.invokeFormPost(element, triggerEvent, httpMethodAttribute, containingForm);
            } else {
                Main.initInvokeHttpRequest(containingForm, httpMethodAttribute);
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
            const basicPollingMatch = triggerAttr.match(/^every\s+(\d+[smhd])$/);
            if (basicPollingMatch) {
                const interval = Utils.parsePollingInterval(basicPollingMatch[1]);
                if (interval) {
                    setInterval(() => Main.pollElement(element), interval);
                }
                return;
            }

            const loadPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd])$/);
            if (loadPollingMatch) {
                const delay = Utils.parsePollingInterval(loadPollingMatch[1]);
                if (delay) {
                    setTimeout(() => {
                        Main.pollElement(element);
                        setInterval(() => Main.pollElement(element), delay);
                    }, delay);
                }
                return;
            }

            const delayedPollingMatch = triggerAttr.match(/^load\s+delay:(\d+[smhd]),\s*every\s+(\d+[smhd])$/);
            if (delayedPollingMatch) {
                const initialDelay = Utils.parsePollingInterval(delayedPollingMatch[1]);
                const interval = Utils.parsePollingInterval(delayedPollingMatch[2]);
                if (initialDelay && interval) {
                    setTimeout(() => {
                        Main.pollElement(element);
                        setInterval(() => Main.pollElement(element), interval);
                    }, initialDelay);
                }
                return;
            }
        },
        pollElement(element) {
            const attribute = CONFIG.CORE_MX_ATTRIBUTES.find(attr => element.hasAttribute(attr));
            if (attribute) {
                // Add throttle check for polling
                const throttleTime = parseInt(element.getAttribute('mx-throttle')) || 0;
                if (throttleTime > 0 && !Utils.canMakeRequest(throttleTime)) {
                    console.log('Polling request throttled');
                    return;
                }
                Main.initInvokeHttpRequest(element, attribute);
            }
        }
    };

    // Initialize Trongate MX when the DOM is loaded
    document.addEventListener('DOMContentLoaded', Main.initializeTrongateMX);

    // Expose necessary functions to the global scope
    window.TrongateMX = {
        init: Main.initializeTrongateMX,
        openModal: Modal.openModal,
        closeModal: Modal.closeModal
    };

})(window);