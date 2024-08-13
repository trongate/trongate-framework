const body = document.querySelector("body");
const slideNav = document.getElementById("slide-nav");
const main = document.querySelector("main");
let slideNavOpen = false;
let openingModal = false;

function getElement(elRef) {
  const firstChar = elRef.substring(0, 1);
  if (firstChar === ".") {
    return document.querySelector(elRef); // Changed to querySelector for consistency
  } else {
    return document.getElementById(elRef);
  }
}

function openSlideNav() {
  slideNav.style.opacity = 1;
  slideNav.style.width = "250px";
  slideNav.style.zIndex = 2;
  setTimeout(() => {
    slideNavOpen = true;
  }, 500);
}

function closeSlideNav() {
  slideNav.style.opacity = 0;
  slideNav.style.width = "0";
  slideNav.style.zIndex = "-1";
  slideNavOpen = false;
}

function openModal(modalId) {
  openingModal = true;
  setTimeout(() => {
    openingModal = false;
  }, 100);
  let pageOverlay = document.getElementById("overlay");
  if (!pageOverlay) {
    const modalContainer = document.createElement("div");
    modalContainer.setAttribute("id", "modal-container");
    modalContainer.setAttribute("style", "z-index: 9999;");
    body.append(modalContainer);
    pageOverlay = document.createElement("div");
    pageOverlay.setAttribute("id", "overlay");
    pageOverlay.setAttribute("style", "z-index: 2");
    body.append(pageOverlay);
    const targetModal = getElement(modalId);
    if (!targetModal) return;
    const targetModalContent = targetModal.innerHTML;
    targetModal.remove();
    const newModal = document.createElement("div");
    newModal.setAttribute("class", "modal");
    newModal.setAttribute("id", modalId);
    newModal.style.zIndex = 4;
    newModal.innerHTML = targetModalContent;
    modalContainer.appendChild(newModal);
    
    // Use requestAnimationFrame to ensure the modal is in the DOM before we try to show it
    requestAnimationFrame(() => {
      newModal.style.display = 'block';
      newModal.style.opacity = 1;
      
      // Get the computed style of the modal
      const style = getComputedStyle(newModal);
      
      // Use the custom property if it's set, otherwise fall back to the default
      const marginTop = style.getPropertyValue('--modal-margin-top').trim() || '12vh';
      
      newModal.style.marginTop = marginTop;
    });
    return newModal;
  }
  return null;
}

function closeModal() {
  const modalContainer = document.getElementById("modal-container");
  if (modalContainer) {
    const openModal = modalContainer.firstChild;
    openModal.style.zIndex = "-4";
    openModal.style.opacity = 0;
    openModal.style.marginTop = "12vh";
    openModal.style.display = "none";
    document.body.appendChild(openModal);
    modalContainer.remove();

    const overlay = document.getElementById("overlay");
    if (overlay) {
      overlay.remove();
    }

    // Dispatch a custom event indicating modal closure
    const event = new Event('modalClosed', { bubbles: true, cancelable: true });
    document.dispatchEvent(event);
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

// Initialize
autoPopulateSlideNav();

// Add event listeners
body.addEventListener("click", (event) => {
  handleSlideNavClick(event);
  handleModalClick(event);
});

document.addEventListener('keydown', handleEscapeKey);