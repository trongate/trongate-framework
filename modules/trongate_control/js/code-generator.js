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

        if (templateName) {
            iframe.src += '?template=' + templateName;
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

        // Create Trongate CSS spinner container
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
                // Check if the Query Builder iframe is open
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
        // Close all modals first, then reopen Flo home
        this.close();

        // Use setTimeout to ensure DOM is clean before creating the new modal
        var self = this;
        setTimeout(function() {
            CodeGenerator.openCodeGenerator();
        }, 50);
    },


    openUrl(targetUrl, openInNewTab = false) {
        openInNewTab ? window.open(targetUrl, "_blank") : window.location.href = targetUrl;
    }

}

// Make CodeGenerator globally accessible
window.CodeGenerator = CodeGenerator;

CodeGenerator.activateTriggers();
