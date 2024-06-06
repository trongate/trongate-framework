var body = document.querySelector("body");
var slideNavOpen = false;
var slideNav = document.getElementById("slide-nav");
var main = document.getElementsByTagName("main")[0];

function _(elRef) {
  var firstChar = elRef.substring(0, 1);

  if (firstChar == ".") {
    elRef = elRef.replace(/\./g, "");
    return document.getElementsByClassName(elRef);
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
  slideNav.style.zIndex = -1;
  slideNavOpen = false;
}

function openModal(modalId) {
  var pageOverlay = document.getElementById("overlay");

  if (typeof pageOverlay == "undefined" || pageOverlay == null) {
    var modalContainer = document.createElement("div");
    modalContainer.setAttribute("id", "modal-container");
    modalContainer.setAttribute("style", "z-index: 3;");
    body.prepend(modalContainer);

    var overlay = document.createElement("div");
    overlay.setAttribute("id", "overlay");
    overlay.setAttribute("style", "z-index: 2");

    body.prepend(overlay);

    var targetModal = _(modalId);
    targetModalContent = targetModal.innerHTML;
    targetModal.remove();

    //create a new model
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
}

function closeModal() {
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
    // Dispatch a custom event indicating modal closure
    var event = new Event('modalClosed', { bubbles: true, cancelable: true });
    document.dispatchEvent(event);
  }
}

var slideNavLinks = document.querySelector("#slide-nav ul");

if (slideNavLinks !== null) {
  var autoPopulateSlideNav = slideNavLinks.getAttribute("auto-populate");
  if (autoPopulateSlideNav == "true") {
    var navLinks = document.querySelector("#top-nav");
    if (navLinks !== null) {
      slideNavLinks.innerHTML = navLinks.innerHTML;
    }
  }

  body.addEventListener("click", (ev) => {
    if (slideNavOpen == true && ev.target.id !== "open-btn") {
      if (slideNav.contains(ev.target)) {
        return true;
      } else {
        closeSlideNav();
      }
    }
  });
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
