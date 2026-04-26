const CodeGenerator = {
    // ============================================
    // Configuration
    // ============================================
    MODAL_ID: 'codegen-iframe-modal',
    DEFAULT_WIDTH: 800,
    DEFAULT_HEIGHT: 600,
    templateName: 'c64',
    apiBaseUrl: null, // Will be set when trigger is clicked

    // ============================================
    // Initialization
    // ============================================
    activateTriggers() {
        const cgTriggers = document.querySelectorAll('.code-generator-trigger');
        for (let i = 0; i < cgTriggers.length; i++) {
            cgTriggers[i].classList.remove('cloak');
            cgTriggers[i].addEventListener('click', (ev) => {
                // Capture the API base URL from the clicked trigger
                CodeGenerator.apiBaseUrl = ev.currentTarget.dataset.apiBaseUrl;
                CodeGenerator.openCodeGenerator();
            });
        }

        // Listen for postMessage from cross-origin iframes
        window.addEventListener('message', (event) => {

            // Only accept messages from trusted origins
            const trustedOrigins = [
                CodeGenerator.apiBaseUrl ? CodeGenerator.apiBaseUrl.replace(/\/+$/, '') : null,
                'https://trongate.io'
            ].filter(Boolean);

            if (!trustedOrigins.includes(event.origin)) {
                return;
            }

            // Handle fetch proxy requests from the iframe
            if (event.data && event.data.type === 'FLO_FETCH') {
                CodeGenerator._handleFetchProxy(event);
                return;
            }

            var action = event.data.action || event.data;

            switch (action) {
                case 'open_query_builder':
                    CodeGenerator.openQueryBuilder();
                    break;
                case 'reset':
                    CodeGenerator.reset();
                    break;
                case 'close':
                    CodeGenerator.close();
                    break;
                case 'close_query_builder':
                    CodeGenerator.closeQueryBuilder();
                    break;
                case 'go_back':
                    CodeGenerator.reset();
                    break;
                default:
                    if (typeof action === 'string' && action.startsWith('open_url:')) {
                        var url = action.substring('open_url:'.length);
                        var newTab = url.indexOf('new_tab') > -1;
                        CodeGenerator.openUrl(url.replace('|new_tab', ''), newTab);
                    } else if (typeof action === 'string' && action.startsWith('reload_iframe:')) {
                        var parts = action.substring('reload_iframe:'.length).split('|');
                        var url = parts[0] || '';
                        var width = parts[1] ? parseInt(parts[1]) : null;
                        var height = parts[2] ? parseInt(parts[2]) : null;
                        var template = parts[3] || null;
                        CodeGenerator.reloadIframe(url, width, height, template);
                    }
                    break;
            }
        });
    },

    openCodeGenerator() {
        const targetUrl = this.apiBaseUrl + 'evo/home';
        this._openIframeModal(targetUrl, this.DEFAULT_WIDTH, this.DEFAULT_HEIGHT, this.templateName);
    },

    openQueryBuilder() {
        // Derive the parent website's base URL from the current page
        var parentBaseUrl = document.querySelector('base').getAttribute('href');
        var webhookUrl = parentBaseUrl + 'trongate_control-webhooks/inbound';
        const targetUrl = this.apiBaseUrl + 'evo/query_builder?webhook_url=' + encodeURIComponent(webhookUrl);
        this._openIframeModal(targetUrl, null, null, this.templateName);
    },

    // ============================================
    // Modal Management
    // ============================================
    _openIframeModal(targetUrl, width, height, templateName = null) {
        // Remove any existing modals first to prevent stacking
        this.close();

        const { overlay, iframe, spinnerContainer } = this._createModal(width, height);

        // Remove spinner when iframe loads
        iframe.addEventListener('load', () => {
            spinnerContainer.remove();
        });

        iframe.src = targetUrl;

        // Append embedder origin for CSP frame-ancestors support
        var embedderOrigin = window.location.origin;
        var sep = (targetUrl.indexOf('?') > -1) ? '&' : '?';
        iframe.src += sep + 'embedder=' + encodeURIComponent(embedderOrigin);

        if (templateName) {
            iframe.src += '&template=' + templateName;
        }

        this._attachModalEventListeners(overlay);
        document.body.appendChild(overlay);
    },

    _createModal(width, height) {
        const isFullViewport = (width === null || height === null);
        const iframeModalOverlay = document.createElement("div");
        iframeModalOverlay.setAttribute("id", this.MODAL_ID);
        iframeModalOverlay.style.cssText = `
            display: block;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        `;

        const modalContent = document.createElement("div");
        modalContent.className = "codegen-iframe-modal-content";
        if (isFullViewport) {
            modalContent.style.cssText = `
                background-color: transparent;
                margin: 0;
                padding: 0;
                border: none;
                border-radius: 0;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
            `;
        } else {
            modalContent.style.cssText = `
                background-color: transparent;
                margin: 0;
                padding: 0;
                border: none;
                border-radius: 12px;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                width: 94%;
                max-width: ${width}px;
                height: 94vh;
                max-height: ${height}px;
            `;
        }

        const spinnerContainer = document.createElement("div");
        spinnerContainer.className = "codegen-spinner-container";
        spinnerContainer.style.cssText = `
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        `;

        const spinner = document.createElement("div");
        spinner.className = "spinner";
        spinnerContainer.appendChild(spinner);

        const modalIframe = document.createElement("iframe");
        modalIframe.style.cssText = `
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
            display: block;
            background-color: #000;
        `;
        modalIframe.title = "Code Generator";

        modalContent.appendChild(spinnerContainer);
        modalContent.appendChild(modalIframe);
        iframeModalOverlay.appendChild(modalContent);

        return { overlay: iframeModalOverlay, iframe: modalIframe, spinnerContainer: spinnerContainer };
    },

    _attachModalEventListeners(overlay) {
        const modalContent = overlay.querySelector('.codegen-iframe-modal-content');

        overlay.addEventListener("click", (event) => {
            if (!modalContent.contains(event.target)) {
                this.close();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                var modalIframe = document.querySelector('#' + this.MODAL_ID + ' iframe');
                if (modalIframe && modalIframe.src && modalIframe.src.indexOf('query_builder') !== -1) {
                    this.closeQueryBuilder();
                } else {
                    this.close();
                }
            }
        }, { once: true });
    },

    close() {
        const modals = document.querySelectorAll('#' + this.MODAL_ID);
        modals.forEach(function(modal) {
            modal.remove();
        });
    },

    // ============================================
    // Fetch Proxy Bridge
    // ============================================
    _handleFetchProxy(event) {
        const messageId = event.data.messageId;
        const payload = event.data.payload;
        const url = payload.url;
        const method = payload.method || 'GET';
        const headers = payload.headers || {};
        const body = payload.body || null;
        var fetchUrl = url;

        if (url.indexOf('://') === -1) {
            var baseUrl = document.querySelector('base').getAttribute('href');
            fetchUrl = baseUrl.replace(/\/+$/, '') + '/' + url.replace(/^\//, '');
        }

        fetch(fetchUrl, {
            method: method,
            headers: headers,
            body: body
        })
        .then(function(response) {
            return response.text().then(function(responseBody) {
                event.source.postMessage({
                    type: 'FLO_RESPONSE',
                    messageId: messageId,
                    payload: {
                        status: response.status,
                        body: responseBody
                    }
                }, event.origin);
            });
        })
        .catch(function(error) {
            event.source.postMessage({
                type: 'FLO_RESPONSE',
                messageId: messageId,
                payload: {
                    status: 0,
                    body: null,
                    error: error.message
                }
            }, event.origin);
        });
    },

    // ============================================
    // Modal Operations
    // ============================================
    reloadIframe(targetUrl, width = null, height = null, templateName = null) {
        this.close();
        this._openIframeModal(targetUrl, width, height, templateName);
    },

    reset() {
        this.close();
        CodeGenerator.openCodeGenerator();
    },

    closeQueryBuilder() {
        this.close();

        var self = this;
        setTimeout(function() {
            CodeGenerator.openCodeGenerator();
        }, 50);
    },

    openUrl(targetUrl, openInNewTab = false) {
        openInNewTab ? window.open(targetUrl, "_blank") : window.location.href = targetUrl;
    }
}

window.CodeGenerator = CodeGenerator;

CodeGenerator.activateTriggers();