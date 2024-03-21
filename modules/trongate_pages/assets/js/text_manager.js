let selectedRangeShadow;

function tgpInsertText() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  const textDiv = document.createElement("div");
  textDiv.setAttribute("class", "text-div");
  const samplePara = document.createElement("p");
  const textDivSampleText = trongatePagesObj.textDivSampleText;
  samplePara.innerHTML = textDivSampleText;
  textDiv.appendChild(samplePara);
  trongatePagesObj.targetNewElLocation = "default";
  tgpInsertElement(textDiv);
}

function tgpOpenLinkModal() {
  let clickedEditorBtn = document.getElementById("linkify-btn");

  if (clickedEditorBtn == null) {
    return;
  }

  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    currentSelectedRange = selection.getRangeAt(0).cloneRange();
  }

  let selectedRange = selection.getRangeAt(0);

  if (clickedEditorBtn.classList.contains("active-editor-btn")) {
    //find italic nodes that intersect the selected range...
    let linkNodes = trongatePagesObj.activeEl.getElementsByTagName("a");
    let resultObj = tgpIntersectsRange(selectedRange, linkNodes);

    //if we found an intersecting
    if (resultObj.tgpIntersectsRange == true) {
      //loop through each of the intersecting nodes and remove the offending tags...
      var tgpIntersectsRangeIndexes = resultObj.tgpIntersectsRangeIndexes;
      for (let i = 0; i < tgpIntersectsRangeIndexes.length; i++) {
        tgpUnwrapNode(linkNodes[tgpIntersectsRangeIndexes[i]]);
      }

      clickedEditorBtn.classList.remove("active-editor-btn");
    }
  } else {
    selectedRangeShadow = selectedRange;
    tgpBuildLinkModal();
  }
}

function tgpBuildLinkModal() {
  tgpReset(["codeviews", "customModals", "toolbars"]);

  const modalId = "tgp-link-modal";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");

  const icon = document.createElement("i");
  icon.classList.add("fa", "fa-link");

  const headingText = document.createTextNode(" Create Text Link");
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
  const submitBtn = document.createElement("button");
  submitBtn.setAttribute("type", "button");
  submitBtn.innerText = "Submit";
  submitBtn.setAttribute("onclick", "tgpLinkify()");
  modalFooter.appendChild(submitBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
    maxWidth: 570,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");
  const infoPara = document.createElement("p");
  infoParaText = document.createTextNode(
    "Enter link text and target URL below, then hit Submit."
  );
  infoPara.appendChild(infoParaText);
  infoPara.setAttribute("class", "text-center sm");
  modalBody.appendChild(infoPara);

  const linkFormPara = document.createElement("p");
  linkFormPara.setAttribute("class", "text-center sm");
  modalBody.appendChild(linkFormPara);

  let formLabel = document.createElement("label");
  formLabel.innerHTML = "Link Text";
  linkFormPara.appendChild(formLabel);

  let inputField = document.createElement("input");
  inputField.setAttribute("type", "text");
  inputField.setAttribute("id", "tgp-link-text");
  inputField.setAttribute("placeholder", "Enter link text here...");
  linkFormPara.appendChild(inputField);

  //add selected text to form input field
  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    currentSelectedRange = selection.getRangeAt(0).cloneRange();
  }

  const selectedObj = window.getSelection();
  inputField.value = selection.toString();

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Target URL";
  linkFormPara.appendChild(formLabel);

  inputField = document.createElement("input");
  inputField.setAttribute("type", "text");
  inputField.setAttribute("id", "tgp-link-input");
  inputField.setAttribute("placeholder", "Enter target URL here...");
  inputField.setAttribute("autocomplete", "off");
  linkFormPara.appendChild(inputField);

  const checkboxLabel = document.createElement("label");
  checkboxLabel.innerHTML = "Open link in new tab/window";
  linkFormPara.appendChild(checkboxLabel);

  const checkbox = document.createElement("input");
  checkbox.setAttribute("type", "checkbox");
  checkbox.setAttribute("id", "tgp-link-target");
  checkboxLabel.insertBefore(checkbox, checkboxLabel.firstChild);
}

function tgpLinkify() {
  //get the value of the link text
  const tgpLinkText = document.getElementById("tgp-link-text");
  const linkText = tgpLinkText.value;
  const linkTextLen = linkText.length;
  const linkTarget = document.getElementById("tgp-link-target").checked
    ? "_blank"
    : "_self";

  if (linkTextLen < 1) {
    alert("The Link Text field cannot be empty!");
    return;
  }

  //get the value of the link target URL
  const tgpLinkInput = document.getElementById("tgp-link-input");
  let linkDestination = tgpLinkInput.value;
  linkDestination = linkDestination.trim();
  const linkDestinationLen = linkDestination.length;

  if (linkDestinationLen < 1) {
    alert("The Target URL field cannot be empty!");
    return;
  }

  //create a new link element
  const newLink = document.createElement("a");
  const newLinkText = document.createTextNode(linkText);
  newLink.appendChild(newLinkText);
  newLink.setAttribute("href", linkDestination);

  if (linkTarget !== "_self") {
    newLink.setAttribute("target", linkTarget);
  }

  //add the link
  selectedRangeShadow.deleteContents();
  selectedRangeShadow.insertNode(newLink);
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
}

function tgpListify(listType) {
  var ul = document.createElement(listType);
  var li = document.createElement("li");
  li.innerHTML = "First Item";
  ul.appendChild(li);

  li = document.createElement("li");
  li.innerHTML = "Second Item";
  ul.appendChild(li);

  li = document.createElement("li");
  li.innerHTML = "Third Item";
  ul.appendChild(li);

  trongatePagesObj.activeEl.appendChild(ul);
}
