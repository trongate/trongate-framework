const UI_CONSTANTS = {
    SLIDE_NAV: {
        WIDTH: "250px",
        WIDTH_CLOSED: "0",
        TRANSITION_DELAY: 500,
        Z_INDEX: 2,
        Z_INDEX_HIDDEN: -1
    },
    MODAL: {
        Z_INDEX: 4,
        Z_INDEX_HIDDEN: -4,
        Z_INDEX_CONTAINER: 9999,
        DEFAULT_MARGIN_TOP: "12vh",
        OPENING_DELAY: 100
    },
    OVERLAY: {
        Z_INDEX: 2
    }
};

const TGUI = (() => {
    const body = document.querySelector("body");
    const slideNav = document.getElementById("slide-nav");
    const main = document.querySelector("main");
    let slideNavOpen = false;
    let openingModal = false;

    // Private functions
    function handleSlideNavClick(event) {
        if (slideNavOpen && event.target.id !== "open-btn" && !slideNav.contains(event.target)) {
            closeSlideNav();
        }
    }

    function handleEscapeKey(event) {
        if (event.key === 'Escape') {
            const modalContainer = document.getElementById("modal-container");
            if (modalContainer) {
                closeModal();
            }
        }
    }

    function handleModalClick(event) {
        if (openingModal === true) {
            return;
        }

        const modalContainer = document.getElementById("modal-container");
        if (modalContainer) {
            const modal = modalContainer.querySelector('.modal');
            if (modal && !modal.contains(event.target)) {
                closeModal();
            }
        }
    }

    function autoPopulateSlideNav() {
        const slideNavLinks = document.querySelector("#slide-nav ul");
        if (slideNavLinks && slideNavLinks.getAttribute("auto-populate") === "true") {
            const navLinks = document.querySelector("#top-nav");
            if (navLinks) {
                slideNavLinks.innerHTML = navLinks.innerHTML;
            }
        }
    }

    // Initialize
    autoPopulateSlideNav();

    // Add event listeners
    body.addEventListener("click", (event) => {
        handleSlideNavClick(event);
        handleModalClick(event);
    });

    document.addEventListener('keydown', handleEscapeKey);

    return {
        body,
        slideNav,
        main,
        getSlideNavOpen: () => slideNavOpen,
        setSlideNavOpen: (value) => { slideNavOpen = value },
        getOpeningModal: () => openingModal,
        setOpeningModal: (value) => { openingModal = value }
    };
})();

// Define the global functions
const _openSlideNav = function() {
    TGUI.slideNav.style.opacity = 1;
    TGUI.slideNav.style.width = UI_CONSTANTS.SLIDE_NAV.WIDTH;
    TGUI.slideNav.style.zIndex = UI_CONSTANTS.SLIDE_NAV.Z_INDEX;
    setTimeout(() => {
        TGUI.setSlideNavOpen(true);
    }, UI_CONSTANTS.SLIDE_NAV.TRANSITION_DELAY);
};

const _closeSlideNav = function() {
    TGUI.slideNav.style.opacity = 0;
    TGUI.slideNav.style.width = UI_CONSTANTS.SLIDE_NAV.WIDTH_CLOSED;
    TGUI.slideNav.style.zIndex = UI_CONSTANTS.SLIDE_NAV.Z_INDEX_HIDDEN;
    TGUI.setSlideNavOpen(false);
};

const _openModal = function(modalId) {
    TGUI.setOpeningModal(true);
    setTimeout(() => {
        TGUI.setOpeningModal(false);
    }, UI_CONSTANTS.MODAL.OPENING_DELAY);
    
    let pageOverlay = document.getElementById("overlay");
    if (!pageOverlay) {
        const modalContainer = document.createElement("div");
        modalContainer.setAttribute("id", "modal-container");
        modalContainer.setAttribute("style", `z-index: ${UI_CONSTANTS.MODAL.Z_INDEX_CONTAINER};`);
        TGUI.body.append(modalContainer);
        
        pageOverlay = document.createElement("div");
        pageOverlay.setAttribute("id", "overlay");
        pageOverlay.setAttribute("style", `z-index: ${UI_CONSTANTS.OVERLAY.Z_INDEX}`);
        TGUI.body.append(pageOverlay);
        
        const firstChar = modalId.substring(0, 1);
        const targetModal = firstChar === "." ? document.querySelector(modalId) : document.getElementById(modalId);
        
        if (!targetModal) return;
        const targetModalContent = targetModal.innerHTML;
        targetModal.remove();
        
        const newModal = document.createElement("div");
        newModal.setAttribute("class", "modal");
        newModal.setAttribute("id", modalId);
        newModal.style.zIndex = UI_CONSTANTS.MODAL.Z_INDEX;
        newModal.innerHTML = targetModalContent;
        modalContainer.appendChild(newModal);
        
        requestAnimationFrame(() => {
            newModal.style.display = 'block';
            newModal.style.opacity = 1;
            
            const style = getComputedStyle(newModal);
            const marginTop = style.getPropertyValue('--modal-margin-top').trim() || UI_CONSTANTS.MODAL.DEFAULT_MARGIN_TOP;
            
            newModal.style.marginTop = marginTop;
        });
        return newModal;
    }
    return null;
};

const _closeModal = function() {
    const modalContainer = document.getElementById("modal-container");
    if (modalContainer) {
        const openModal = modalContainer.firstChild;
        openModal.style.zIndex = UI_CONSTANTS.MODAL.Z_INDEX_HIDDEN;
        openModal.style.opacity = 0;
        openModal.style.marginTop = UI_CONSTANTS.MODAL.DEFAULT_MARGIN_TOP;
        openModal.style.display = "none";
        TGUI.body.appendChild(openModal);
        modalContainer.remove();

        const overlay = document.getElementById("overlay");
        if (overlay) {
            overlay.remove();
        }

        const event = new Event('modalClosed', { bubbles: true, cancelable: true });
        document.dispatchEvent(event);
    }
};

// Safely expose the global functions only if they haven't been defined
window.openSlideNav = window.openSlideNav || _openSlideNav;
window.closeSlideNav = window.closeSlideNav || _closeSlideNav;
window.openModal = window.openModal || _openModal;
window.closeModal = window.closeModal || _closeModal;