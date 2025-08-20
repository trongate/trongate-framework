const TG_ADMIN = {
    MODAL_CONSTANTS: {
        Z_INDEX: 1000,
        Z_INDEX_HIDDEN: -4,
        Z_INDEX_CONTAINER: 999,
        Z_INDEX_OVERLAY: 998,
        DEFAULT_MARGIN_TOP: "12vh",
        OPENING_DELAY: 100
    },

    openingModal: false,
    mousedownEl: null,
    mouseupEl: null,

    init() {
        // Track mouse events for proper modal closing behavior
        document.addEventListener("mousedown", (event) => {
            TG_ADMIN.mousedownEl = event.target;
        });

        document.addEventListener("mouseup", (event) => {
            TG_ADMIN.mouseupEl = event.target;
        });

        // Handle clicks to close modal when clicking outside
        document.addEventListener("click", (event) => {
            if (TG_ADMIN.openingModal) {
                return;
            }

            // Handle iframe modal clicks first
            const iframeModalContainer = document.getElementById("trongate-iframe-modal");
            if (iframeModalContainer) {
                const iframeModal = iframeModalContainer.querySelector(".trongate-iframe-modal-content");
                const clickedOutside = iframeModal && !iframeModal.contains(event.target);
                const mousedownInsideModal = iframeModal && iframeModal.contains(TG_ADMIN.mousedownEl);
                const mouseupInsideModal = iframeModal && iframeModal.contains(TG_ADMIN.mouseupEl);

                if (clickedOutside && !mousedownInsideModal && !mouseupInsideModal) {
                    TG_ADMIN._closeModal();
                }
                return;
            }

            // Handle regular modal clicks
            const modalContainer = document.getElementById("modal-container");
            if (modalContainer) {
                const modal = modalContainer.querySelector(".modal");
                const clickedOutside = modal && !modal.contains(event.target);
                const mousedownInsideModal = modal && modal.contains(TG_ADMIN.mousedownEl);
                const mouseupInsideModal = modal && modal.contains(TG_ADMIN.mouseupEl);

                if (clickedOutside && !mousedownInsideModal && !mouseupInsideModal) {
                    TG_ADMIN._closeModal();
                }
            }
        });

        // Handle Escape key
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                // Check for iframe modal first
                const iframeModalContainer = document.getElementById("trongate-iframe-modal");
                if (iframeModalContainer) {
                    TG_ADMIN._closeModal();
                    return;
                }

                // Then check for regular modal
                const modalContainer = document.getElementById("modal-container");
                if (modalContainer) {
                    TG_ADMIN._closeModal();
                }
            }
        });
    },

    _openModal(modalIdOrUrl, width = null, height = null) {
        // If width and height are provided, treat as iframe modal
        if (width !== null && height !== null) {
            TG_ADMIN._openIframeModal(modalIdOrUrl, width, height);
            return;
        }

        // Original modal functionality for DOM elements
        TG_ADMIN.openingModal = true;
        setTimeout(() => {
            TG_ADMIN.openingModal = false;
        }, TG_ADMIN.MODAL_CONSTANTS.OPENING_DELAY);

        // Don't create if already exists
        let pageOverlay = document.getElementById("overlay");
        if (pageOverlay) {
            return;
        }

        // Create modal container
        const modalContainer = document.createElement("div");
        modalContainer.setAttribute("id", "modal-container");
        modalContainer.style.zIndex = TG_ADMIN.MODAL_CONSTANTS.Z_INDEX_CONTAINER;
        document.body.appendChild(modalContainer);

        // Create overlay
        pageOverlay = document.createElement("div");
        pageOverlay.setAttribute("id", "overlay");
        pageOverlay.style.zIndex = TG_ADMIN.MODAL_CONSTANTS.Z_INDEX_OVERLAY;
        document.body.appendChild(pageOverlay);

        // Get the target modal (support both CSS selector and ID)
        const targetModal = modalIdOrUrl.startsWith(".")
            ? document.querySelector(modalIdOrUrl)
            : document.getElementById(modalIdOrUrl);

        if (!targetModal) return;

        // Store the original modal content and remove it from DOM
        const targetModalContent = targetModal.innerHTML;
        targetModal.remove();

        // Create new modal in the container
        const newModal = document.createElement("div");
        newModal.className = "modal";
        newModal.id = modalIdOrUrl;
        newModal.style.zIndex = TG_ADMIN.MODAL_CONSTANTS.Z_INDEX;
        newModal.innerHTML = targetModalContent;
        modalContainer.appendChild(newModal);

        // Animate the modal in
        requestAnimationFrame(() => {
            newModal.style.display = "block";
            newModal.style.opacity = 1;
            
            // Use CSS variable for margin-top if available, otherwise use default
            const marginTop = getComputedStyle(document.documentElement)
                .getPropertyValue("--modal-margin-top").trim() || TG_ADMIN.MODAL_CONSTANTS.DEFAULT_MARGIN_TOP;
            newModal.style.marginTop = marginTop;
        });
    },

    _openIframeModal(targetUrl, width, height) {
        TG_ADMIN.openingModal = true;
        setTimeout(() => {
            TG_ADMIN.openingModal = false;
        }, TG_ADMIN.MODAL_CONSTANTS.OPENING_DELAY);

        // Create iframe modal overlay
        const iframeModalOverlay = document.createElement("div");
        iframeModalOverlay.setAttribute("id", "trongate-iframe-modal");
        iframeModalOverlay.style.cssText = `
            display: block;
            position: fixed;
            z-index: ${TG_ADMIN.MODAL_CONSTANTS.Z_INDEX_CONTAINER + 1};
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        `;

        // Create modal content container with responsive width and height
        const modalContent = document.createElement("div");
        modalContent.className = "trongate-iframe-modal-content";
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

        // Create iframe (fills entire modal)
        const modalIframe = document.createElement("iframe");
        modalIframe.style.cssText = `
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
            display: block;
        `;
        modalIframe.src = targetUrl;
        modalIframe.title = "Modal Content";

        // Handle iframe errors
        modalIframe.onerror = function() {
            modalIframe.srcdoc = `
                <div style="padding: 20px; text-align: center; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; font-family: Arial, sans-serif; font-size: 14px;">
                    <h3>Unable to load content</h3>
                    <p>The URL "${targetUrl}" could not be loaded.</p>
                    <p>This might be due to CORS restrictions or the site not allowing embedding.</p>
                </div>
            `;
        };

        // Assemble the modal (just iframe, no header)
        modalContent.appendChild(modalIframe);
        iframeModalOverlay.appendChild(modalContent);

        // Add to page
        document.body.appendChild(iframeModalOverlay);
    },

    _closeModal() {
        // Check for iframe modal first (higher priority)
        const iframeModalContainer = document.getElementById("trongate-iframe-modal");
        if (iframeModalContainer) {
            iframeModalContainer.remove();
            const event = new Event("modalClosed", { bubbles: true, cancelable: true });
            document.dispatchEvent(event);
            return;
        }

        // Handle regular modal
        const modalContainer = document.getElementById("modal-container");
        if (!modalContainer) return;

        const openModal = modalContainer.querySelector(".modal");
        if (openModal) {
            // Reset modal styles and move it back to body
            openModal.style.zIndex = TG_ADMIN.MODAL_CONSTANTS.Z_INDEX_HIDDEN;
            openModal.style.opacity = 0;
            openModal.style.marginTop = "-160px"; // Trongate's default hidden position
            openModal.style.display = "none";
            document.body.appendChild(openModal);
        }

        // Remove modal container and overlay
        modalContainer.remove();
        
        const overlay = document.getElementById("overlay");
        if (overlay) {
            overlay.remove();
        }

        // Dispatch custom event (like Trongate does)
        const event = new Event("modalClosed", { bubbles: true, cancelable: true });
        document.dispatchEvent(event);
    }
};

// Initialize the app
TG_ADMIN.init();

// Make functions globally available
window.openModal = window.openModal || TG_ADMIN._openModal;
window.closeModal = window.closeModal || TG_ADMIN._closeModal;