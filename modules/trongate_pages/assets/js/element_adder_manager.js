// Define a global variable to store the current selection range.
let currentSelectedRange = null;

// Function to restore the saved selection.
function tgpRestoreSelection() {
  if (currentSelectedRange) {
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(currentSelectedRange);
  }
}

function tgpDeletePage() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);

  const modalId = "tgp-delete-page-modal";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  modalHeading.classList.add("modal-danger");
  modalHeading.innerHTML = '<i class="fa fa-trash"></i> Delete Page';

  // Add a modal footer
  const modalFooter = document.createElement("div");
  modalFooter.classList.add("modal-footer");

  // Add a cancel button
  const closeModalBtn = document.createElement("button");
  closeModalBtn.setAttribute("class", "alt");
  closeModalBtn.setAttribute("type", "button");
  closeModalBtn.innerText = "Cancel";
  closeModalBtn.setAttribute(
    "onclick",
    "tgpCloseAndDestroyModal('" + modalId + "', false)"
  );
  modalFooter.appendChild(closeModalBtn);

  // Add a submit button
  const submitButton = document.createElement("button");
  submitButton.setAttribute("type", "submit");
  submitButton.setAttribute("name", "submit");
  submitButton.setAttribute("value", "Yes - Delete Now");
  submitButton.classList.add("tgp-trongate-pages-danger");
  submitButton.textContent = "Yes - Delete Now";
  modalFooter.appendChild(submitButton);

  const modalOptions = {
    modalHeading,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");

  const h2 = document.createElement("h2");
  h2.classList.add("text-center");
  h2.textContent = "Are you sure?";
  modalBody.appendChild(h2);

  const p = document.createElement("p");
  p.textContent = "You are about to delete a page! This cannot be undone.";
  p.style.marginBottom = "33px";
  modalBody.appendChild(p);

  // Create the form element
  const modalForm = document.createElement("form");
  modalForm.setAttribute(
    "action",
    trongatePagesObj.baseUrl +
      "trongate_pages/submit_delete/" +
      trongatePagesObj.trongatePagesId
  );
  modalForm.setAttribute("method", "post");
  customModal.appendChild(modalForm);

  modalForm.appendChild(modalBody);
  modalForm.appendChild(modalFooter);
}

function tgpOpenCreatePageEl() {
  tgpReset(["selectedRange", "codeviews", "customModals", "toolbars"]);

  const modalId = "tgp-create-page-el";
  const modalOptions = {
    maxWidth: 760,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");

  let headlinePara = document.createElement("p");
  infoParaText = document.createTextNode("Select a page element:");
  headlinePara.appendChild(infoParaText);
  headlinePara.setAttribute("class", "text-center");
  headlinePara.style.marginTop = 0;
  modalBody.appendChild(headlinePara);

  const targetUrl = trongatePagesObj.baseUrl + "tgp_element_adder";

  const http = new XMLHttpRequest();
  http.open("get", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.send();
  http.onload = function () {
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = http.responseText;
    modalBody.appendChild(tempDiv.firstChild);

    const closePara = document.createElement("p");
    modalBody.appendChild(closePara);

    // Add a cancel button
    const closeModalBtn = document.createElement("button");
    closeModalBtn.setAttribute("class", "alt");
    closeModalBtn.setAttribute("type", "button");
    closeModalBtn.innerText = "Cancel";
    closeModalBtn.setAttribute(
      "onclick",
      "tgpCloseAndDestroyModal('" + modalId + "')"
    );
    closePara.appendChild(closeModalBtn);
  };
}

function tgpBuildCustomModal(modalId, options = {}) {
  const modalHeading = options.modalHeading || "";
  const modalFooter = options.modalFooter || "";
  const maxWidth = options.maxWidth ?? "570px";
  const width = options.width ?? "90%";

  const maxWidthValue =
    typeof maxWidth === "number" ? `${maxWidth}px` : maxWidth;
  const widthValue = typeof width === "number" ? `${width}px` : width;

  tgpCloseAndDestroyModal(modalId, false);

  let customModal = document.createElement("div");
  customModal.setAttribute("id", modalId);
  customModal.setAttribute("class", "modal");
  customModal.style.display = "none";

  // Append modalHeading if it is an HTML element
  if (modalHeading instanceof HTMLElement) {
    customModal.appendChild(modalHeading);
  }

  let newModalBody = document.createElement("div");
  newModalBody.setAttribute("class", "modal-body");
  customModal.appendChild(newModalBody);

  // Append modalFooter if it is an HTML element
  if (modalFooter instanceof HTMLElement) {
    customModal.appendChild(modalFooter);
  }

  document.body.appendChild(customModal);
  openModal(modalId); //removes placeholder modal & creates new

  let newModal = document.getElementById(modalId);

  newModal.style.maxWidth = maxWidthValue;
  newModal.style.width = widthValue;
  return newModal;
}

function tgpHideModalBody(targetModalBody, addSpinner = false) {
  const targetModalBodyChildren = targetModalBody.children;
  for (var i = 0; i < targetModalBodyChildren.length; i++) {
    targetModalBodyChildren[i].style.opacity = 0;
  }

  if (addSpinner === true) {
    // Add a spinner.
    const spinnerDiv = document.createElement("div");
    spinnerDiv.setAttribute("class", "spinner");

    // Apply CSS to center the spinner
    spinnerDiv.style.position = "absolute";
    spinnerDiv.style.top = "50%";
    spinnerDiv.style.left = "50%";
    spinnerDiv.style.transform = "translate(-50%, -50%)";
    targetModalBody.appendChild(spinnerDiv);
  }
}

function tgpClearModalBody(targetModalBody, addSpinner = false) {
  while (targetModalBody.firstChild) {
    targetModalBody.removeChild(targetModalBody.lastChild);
  }

  if (addSpinner === true) {
    // Add a spinner.
    const spinnerDiv = document.createElement("div");
    spinnerDiv.setAttribute("class", "spinner");

    // Apply CSS to center the spinner
    spinnerDiv.style.position = "absolute";
    spinnerDiv.style.top = "50%";
    spinnerDiv.style.left = "50%";
    spinnerDiv.style.transform = "translate(-50%, -50%)";
    targetModalBody.appendChild(spinnerDiv);
  }
}

function tgpRemoveCustomModals() {
  let targetModals = [];
  for (let i = tgpModals.length - 1; i >= 0; i--) {
    const modalId = tgpModals[i];
    const targetModal = document.getElementById(modalId);
    if (targetModal) {
      targetModals.push(targetModal);
    }
  }
  closeModal();
  targetModals.forEach((modal) => tgpCloseAndDestroyModal(modal, false));
}

function tgpCloseAndDestroyModal(targetModal, initReset = false) {
  if (typeof targetModal === "string") {
    targetModal = document.getElementById(targetModal);
  }

  closeModal();
  targetModal?.remove();

  if (initReset == true) {
    tgpReset([
      "selectedRange",
      "codeviews",
      "customModals",
      "toolbars",
      "activeEl",
    ]);
  }
}

function tgpAddPageElement(elType) {
  switch (elType) {
    case "Headline":
      tgpInsertHeadline();
      break;
    case "Text Block":
      tgpInsertText();
      break;
    case "Divider":
      tgpInsertDivider();
      break;
    case "YouTube Video":
      tgpBuildInsertYouTubeVideoModal();
      break;
    case "Button":
      tgpBuildInsertButtonModal();
      break;
    case "Image":
      tgpOpenTgMediaManager();
      break;
    case "Code":
      insertCode();
      break;
  }
}

function tgpInsertElement(newEl) {
  let imgPath = "";

  if (typeof newEl === "string") {
    // This must be an image path!
    imgPath = newEl;
    newEl = document.createElement("img");
    newEl.src = imgPath;
    newEl.addEventListener("click", (ev) => {
      tgpBuildEditImgModal(ev.target);
    });
  }

  const {
    targetNewElLocation,
    defaultActiveElParent,
    activeElParent,
    activeEl,
  } = trongatePagesObj;

  switch (targetNewElLocation) {
    case "page-top":
      defaultActiveElParent.prepend(newEl);
      break;
    case "above-selected":
      activeElParent.insertBefore(newEl, activeEl);
      break;
    case "inside-selected":
      const range = tgpGetStoredRange();
      const selection = window.getSelection();
      if (range && selection.rangeCount > 0) {
        const selectedNode = window.getSelection().anchorNode;
        const selectedOffset = window.getSelection().anchorOffset;
        const newNode = selectedNode.splitText(selectedOffset);
        const newImg = newEl.querySelector("img");
        selectedNode.parentNode.insertBefore(newImg, newNode);
      } else {
        defaultActiveElParent.appendChild(newEl);
        trongatePagesObj.storedRange = null;
      }
      break;
    case "below-selected":
      tgpInsertAfter(newEl, activeEl);
      break;
    default:
      defaultActiveElParent.appendChild(newEl);
  }
}

function tgpTrashify() {
  tgpReset(["selectedRange", "codeviews", "customModals", "toolbars"]);

  const modalId = "tgp-conf-trashify-modal";
  const customModal = tgpBuildCustomModal(modalId);
  const modalBody = customModal.querySelector(".modal-body");

  const modalHeadline = document.createElement("p");
  const modalHeadlineText = document.createTextNode("Confirm Remove");
  modalHeadline.appendChild(modalHeadlineText);
  modalHeadline.setAttribute("class", "text-center");
  modalHeadline.style.fontSize = "1.2em";
  modalHeadline.style.fontWeight = "bold";
  modalBody.appendChild(modalHeadline);

  let infoPara = document.createElement("p");
  const infoParaText = document.createTextNode(
    "Are you sure you want to remove the selected element?"
  );
  infoPara.appendChild(infoParaText);
  infoPara.setAttribute("class", "text-center");

  modalBody.appendChild(infoPara);

  const modalForm = document.createElement("form");
  modalForm.setAttribute("class", "text-center mb-2");
  modalBody.appendChild(modalForm);

  const modalSubmitBtn = document.createElement("button");
  modalSubmitBtn.setAttribute("id", "tgp-confirm-delete-btn");
  modalSubmitBtn.setAttribute("type", "button");
  modalSubmitBtn.innerHTML = "Yes - Remove Now";
  modalSubmitBtn.setAttribute("onclick", "tgpRemoveActiveEl()");

  modalForm.appendChild(modalSubmitBtn);

  const closeBtn = document.createElement("button");
  closeBtn.setAttribute("class", "close-window alt");
  closeBtn.setAttribute("type", "button");
  closeBtn.innerHTML = "Cancel";
  closeBtn.setAttribute(
    "onclick",
    "tgpCloseAndDestroyModal('" + modalId + "', false)"
  );
  modalForm.appendChild(closeBtn);
}

function tgpRemoveActiveEl() {
  trongatePagesObj.activeEl.remove();
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
}

function tgpOpenSettings() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);

  const modalId = "tgp-settings-modal";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  const icon = document.createElement("i");
  icon.classList.add("fa", "fa-gears");
  const headingText = document.createTextNode(" Page Settings");
  modalHeading.appendChild(icon);
  modalHeading.appendChild(headingText);

  // Add a modal footer
  const modalFooter = document.createElement("div");
  modalFooter.classList.add("modal-footer");

  // Add a cancel button
  const closeModalBtn = document.createElement("button");
  closeModalBtn.setAttribute("class", "alt");
  closeModalBtn.setAttribute("type", "button");
  closeModalBtn.innerText = "Cancel";
  closeModalBtn.setAttribute(
    "onclick",
    "tgpCloseAndDestroyModal('" + modalId + "', false)"
  );
  modalFooter.appendChild(closeModalBtn);

  // Add a submit button
  const submitButton = document.createElement("button");
  submitButton.setAttribute("type", "button");
  submitButton.setAttribute("name", "submit");
  submitButton.setAttribute("value", "Submit");
  submitButton.textContent = "Submit";
  submitButton.setAttribute("onclick", "tgpSaveSettings()");
  submitButton.setAttribute("disabled", true);
  modalFooter.appendChild(submitButton);

  const modalOptions = {
    modalHeading,
    modalFooter,
    maxWidth: 570,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");
  const spinner = document.createElement("div");
  spinner.setAttribute("class", "spinner mt-3 mb-3");
  modalBody.appendChild(spinner);
  tgpFetchSettings();
}

function tgpFetchSettings() {
  const targetUrl =
    trongatePagesObj.baseUrl +
    "api/get/trongate_pages/" +
    trongatePagesObj.trongatePagesId;
  const http = new XMLHttpRequest();
  http.open("get", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send();
  http.onload = (ev) => {
    if (http.status === 200) {
      const responseObj = JSON.parse(http.responseText);
      const pageSettings = {
        url_string: responseObj.url_string,
        page_title: responseObj.page_title,
        meta_keywords: responseObj.meta_keywords,
        meta_description: responseObj.meta_description,
        published: responseObj.published,
      };

      const spinnerEl = document.querySelector(
        "#tgp-settings-modal > div.modal-body > div"
      );

      if (spinnerEl) {
        spinnerEl.remove();
      }

      tgpCreateSettingsForm(pageSettings);
    }
  };
}

function tgpCreateSettingsForm(pageSettings = {}) {
  //Delete settings form, if exists
  const settingsForm = document.getElementById("tgp-settings-form");
  if (settingsForm) {
    settingsForm.remove();
  }

  // Create the form element
  var form = document.createElement("form");
  form.setAttribute("id", "tgp-settings-form");
  form.setAttribute("class", "sm");

  // Create the URL String (slug) input field
  var urlStringLabel = document.createElement("label");
  urlStringLabel.className = "text-left sm";
  urlStringLabel.textContent = "URL String (slug)";
  var urlStringInput = document.createElement("input");
  urlStringInput.type = "text";
  urlStringInput.name = "url_string";
  urlStringInput.value = pageSettings.url_string ? pageSettings.url_string : "";
  urlStringInput.id = "url_string";
  urlStringInput.placeholder = "Enter URL string here...";
  urlStringInput.autocomplete = 'off';
  form.appendChild(urlStringLabel);
  form.appendChild(urlStringInput);

  // Create the Page Title input field
  var pageTitleLabel = document.createElement("label");
  pageTitleLabel.className = "text-left sm";
  pageTitleLabel.textContent = "Page Title";
  var pageTitleInput = document.createElement("input");
  pageTitleInput.type = "text";
  pageTitleInput.name = "page_title";
  pageTitleInput.value = pageSettings.page_title ? pageSettings.page_title : "";
  pageTitleInput.id = "page_title";
  pageTitleInput.placeholder = "Enter page title here...";
  pageTitleInput.autocomplete = 'off';
  form.appendChild(pageTitleLabel);
  form.appendChild(pageTitleInput);

  // Create the Meta Keywords input field
  var metaKeywordsLabel = document.createElement("label");
  metaKeywordsLabel.className = "text-left sm";
  metaKeywordsLabel.textContent = "Meta Keywords";
  var metaKeywordsInput = document.createElement("input");
  metaKeywordsInput.type = "text";
  metaKeywordsInput.name = "meta_keywords";
  metaKeywordsInput.value = pageSettings.meta_keywords
    ? pageSettings.meta_keywords
    : "";
  metaKeywordsInput.id = "meta_keywords";
  metaKeywordsInput.placeholder = "Enter keywords here...";
  metaKeywordsInput.autocomplete = 'off';
  form.appendChild(metaKeywordsLabel);
  form.appendChild(metaKeywordsInput);

  // Create the Meta Description textarea
  var metaDescriptionLabel = document.createElement("label");
  metaDescriptionLabel.className = "text-left sm";
  metaDescriptionLabel.textContent = "Meta Description";
  var metaDescriptionTextarea = document.createElement("textarea");
  metaDescriptionTextarea.name = "meta_description";
  metaDescriptionTextarea.value = pageSettings.meta_description
    ? pageSettings.meta_description
    : "";
  metaDescriptionTextarea.id = "meta_description";
  metaDescriptionTextarea.placeholder = "Enter description here...";
  form.appendChild(metaDescriptionLabel);
  form.appendChild(metaDescriptionTextarea);

  // Create the "Publish Page" checkbox
  var publishLabel = document.createElement("label");
  publishLabel.style.textAlign = "left";
  publishLabel.style.margin = "0";
  publishLabel.textContent = "Publish Page";
  var publishCheckbox = document.createElement("input");
  publishCheckbox.type = "checkbox";
  publishCheckbox.name = "published";
  publishCheckbox.value = "1";
  publishCheckbox.id = "published";
  publishCheckbox.checked = pageSettings.published ? pageSettings.published : 0;
  publishLabel.appendChild(publishCheckbox);
  form.appendChild(publishLabel);

  const modalBody = document.querySelector(
    "#tgp-settings-modal > div.modal-body"
  );

  // Append the form to the document body or another container element
  modalBody.appendChild(form);

  const submitBtn = document.querySelector(
    "#tgp-settings-modal > div.modal-footer > button:nth-child(2)"
  );
  submitBtn.disabled = false;
}

function tgpSaveSettings() {

  tgpReset([
    "selectedRange",
    "codeviews",
    "toolbars",
    "activeEl",
  ]);

  tgpSavingPage = true; //so that pointers do not get added to HRs upon mouseup

  tgpRemoveContentEditables();

  // Get the form element
  const form = document.getElementById("tgp-settings-form");

  const params = {
    url_string: form.elements.url_string.value,
    page_title: form.elements.page_title.value,
    meta_keywords: form.elements.meta_keywords.value,
    meta_description: form.elements.meta_description.value,
    published: form.elements.published.checked ? 1 : 0,
    page_body: trongatePagesObj.defaultActiveElParent.innerHTML
  };

  // Clear the modal body contents
  const modalId = "tgp-settings-modal";
  const modalBody = document.querySelector(
    "#tgp-settings-modal > div.modal-body"
  );

  while (modalBody.firstChild) {
    modalBody.removeChild(modalBody.lastChild);
  }

  const submitBtn = document.querySelector(
    "#tgp-settings-modal > div.modal-footer > button:nth-child(2)"
  );
  submitBtn.remove();

  const subHeadline = document.createElement("h2");
  subHeadline.innerText = "Saving";
  subHeadline.setAttribute("class", "blink text-center");
  modalBody.appendChild(subHeadline);

  // Send the request
  const targetUrl =
    trongatePagesObj.baseUrl +
    "api/update/trongate_pages/" +
    trongatePagesObj.trongatePagesId;
  const http = new XMLHttpRequest();
  http.open("put", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));

  http.onload = (ev) => {
    if (http.status === 200) {
      tgpDrawBigTick(modalBody);
    } else {
      tgpProcValidationErr(modalBody, http.status, http.responseText);
    }
  };
}

function tgpProcValidationErr(modalBody, status, responseText) {
  //clear the modal body
  while (modalBody.firstChild) {
    modalBody.removeChild(modalBody.lastChild);
  }

  //draw a new headline
  const h2 = document.createElement("h2");
  h2.classList.add("text-center");
  h2.textContent = "Error!";
  h2.style.color = "red";
  modalBody.appendChild(h2);

  const errorPara = document.createElement("div");
  errorPara.innerText = responseText;
  modalBody.appendChild(errorPara);

  tgpDrawBigCross(modalBody);

  const targetModal = modalBody.parentNode;
  const modalId = targetModal.id;
  const theQuerySelector = `#${modalId} div.modal-footer > button.alt`;
  const cancelBtn = document.querySelector(theQuerySelector);
  cancelBtn.innerHTML = "Okay";
}

function tgpInterceptAddPageElement(el, newElType) {
  tgpReset(["codeviews", "customModals", "toolbars"]);

  const modalId = "tgp-intercept-add-el";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");

  const icon = document.createElement("i");
  icon.classList.add("fa", "fa-plus");

  const headingText = document.createTextNode(" Add Element");
  modalHeading.appendChild(icon);
  modalHeading.appendChild(headingText);

  // Add a modal footer
  const modalFooter = document.createElement("div");
  modalFooter.classList.add("modal-footer");

  // Add a cancel button
  const closeModalBtn = document.createElement("button");
  closeModalBtn.setAttribute("class", "alt");
  closeModalBtn.setAttribute("type", "button");
  closeModalBtn.innerText = "Cancel";
  closeModalBtn.setAttribute(
    "onclick",
    "tgpCloseAndDestroyModal('" + modalId + "', false)"
  );
  modalFooter.appendChild(closeModalBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
    maxWidth: 570,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");

  let infoPara = document.createElement("p");
  infoParaText = document.createTextNode(
    "Where would you like to add the new " + newElType + "?"
  );
  infoPara.appendChild(infoParaText);
  infoPara.setAttribute("class", "text-center sm");
  modalBody.appendChild(infoPara);

  let elLocationSelectorDiv = document.createElement("div");
  elLocationSelectorDiv.setAttribute("class", "el-location-selector");
  modalBody.appendChild(elLocationSelectorDiv);

  let optionRow = document.createElement("div");
  let optionBtn = document.createElement("button");
  optionBtn.innerHTML = "At The Top Of The Page";
  optionBtn.setAttribute("id", "el-location-btn-page-top");
  optionBtn.setAttribute(
    "onclick",
    'tgpChooseImgLocation("' + el + '", "' + optionBtn.id + '")'
  );
  optionRow.appendChild(optionBtn);
  elLocationSelectorDiv.appendChild(optionRow);

  optionRow = document.createElement("div");
  optionBtn = document.createElement("button");
  optionBtn.innerHTML = "Before The Selected Element";
  optionBtn.setAttribute("id", "el-location-btn-above-selected");
  optionBtn.setAttribute(
    "onclick",
    'tgpChooseImgLocation("' + el + '", "' + optionBtn.id + '")'
  );
  optionRow.appendChild(optionBtn);
  elLocationSelectorDiv.appendChild(optionRow);

  const selection = window.getSelection();
  const selectedNode = selection.anchorNode;

  if (selectedNode.nodeType === Node.TEXT_NODE) {
    optionRow = document.createElement("div");
    optionBtn = document.createElement("button");
    optionBtn.innerHTML = "Inside The Selected Element";
    optionBtn.setAttribute("id", "el-location-btn-inside-selected");
    optionBtn.setAttribute(
      "onclick",
      'tgpChooseImgLocation("' + el + '", "' + optionBtn.id + '")'
    );
    optionRow.appendChild(optionBtn);
    elLocationSelectorDiv.appendChild(optionRow);
  }

  optionRow = document.createElement("div");
  optionBtn = document.createElement("button");
  optionBtn.innerHTML = "After The Selected Element";
  optionBtn.setAttribute("id", "el-location-btn-below-selected");
  optionBtn.setAttribute(
    "onclick",
    'tgpChooseImgLocation("' + el + '", "' + optionBtn.id + '")'
  );
  optionRow.appendChild(optionBtn);
  elLocationSelectorDiv.appendChild(optionRow);

  optionRow = document.createElement("div");
  optionBtn = document.createElement("button");
  optionBtn.innerHTML = "At The Bottom Of The Page";
  optionBtn.setAttribute("id", "el-location-btn-page-btm");
  optionBtn.setAttribute(
    "onclick",
    'tgpChooseImgLocation("' + el + '", "' + optionBtn.id + '")'
  );
  optionRow.appendChild(optionBtn);
  elLocationSelectorDiv.appendChild(optionRow);
}

function tgpInsertAfter(newEl, activeEl) {
  const activeElParent = activeEl.parentNode;
  if (activeElParent.lastChild === activeEl) {
    activeElParent.appendChild(newEl);
  } else {
    activeElParent.insertBefore(newEl, activeEl.nextSibling);
  }
}
