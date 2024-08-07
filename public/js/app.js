const body = document.querySelector("body");
const slideNav = document.getElementById("slide-nav");
const main = document.querySelector("main");
let slideNavOpen = false;

function _(elRef) {
  const firstChar = elRef.substring(0, 1);
  if (firstChar === ".") {
    return document.getElementsByClassName(elRef.replace(/\./g, ""));
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
  let pageOverlay = document.getElementById("overlay");
  if (!pageOverlay) {
    const modalContainer = document.createElement("div");
    modalContainer.setAttribute("id", "modal-container");
    modalContainer.setAttribute("style", "z-index: 3;");
    body.prepend(modalContainer);

    pageOverlay = document.createElement("div");
    pageOverlay.setAttribute("id", "overlay");
    pageOverlay.setAttribute("style", "z-index: 2");
    body.prepend(pageOverlay);

    const targetModal = _(modalId);
    const targetModalContent = targetModal.innerHTML;
    targetModal.remove();

    const newModal = document.createElement("div");
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

const slideNavLinks = document.querySelector("#slide-nav ul");
if (slideNavLinks) {
  const autoPopulateSlideNav = slideNavLinks.getAttribute("auto-populate");
  if (autoPopulateSlideNav === "true") {
    const navLinks = document.querySelector("#top-nav");
    if (navLinks) {
      slideNavLinks.innerHTML = navLinks.innerHTML;
    }
  }

  body.addEventListener("click", (ev) => {
    if (slideNavOpen && ev.target.id !== "open-btn") {
      if (!slideNav.contains(ev.target)) {
        closeSlideNav();
      }
    }
  });
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeModal();
  }
});