function tgpBuildInsertButtonModal() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  const modalId = "tgp-button-modal";

  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  modalHeading.innerHTML = '<i class="fa fa-plus"></i> Add Button(s)';

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
  submitBtn.setAttribute("onclick", "tgpInsertButton()");
  submitBtn.setAttribute("id", "tgp-submit-video-id-btn");
  submitBtn.innerText = "Submit";
  modalFooter.appendChild(submitBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");
  modalBody.classList.add("xs");

  let formLabel = document.createElement("label");
  formLabel.innerHTML = "Primary Button";
  formLabel.style.textAlign = "left";
  formLabel.style.fontSize = "1.2em";
  formLabel.style.fontWeight = "bold";
  modalBody.appendChild(formLabel);

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Button Text:";
  formLabel.style.textAlign = "left";
  modalBody.appendChild(formLabel);

  var buttonForm = document.createElement("form");
  buttonForm.setAttribute("class", "button-form");
  modalBody.appendChild(buttonForm);

  var buttonInputField = document.createElement("input");
  buttonInputField.setAttribute("type", "text");
  buttonInputField.setAttribute("id", "button-input-field-text1");
  buttonInputField.setAttribute("placeholder", "Enter button text here...");
  buttonInputField.setAttribute("autocomplete", "off");
  buttonInputField.value = "Primary Button";
  buttonInputField.style.fontSize = "1.2em";
  buttonForm.appendChild(buttonInputField);

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Target URL:";
  formLabel.style.textAlign = "left";
  buttonForm.appendChild(formLabel);

  buttonInputField = document.createElement("input");
  buttonInputField.setAttribute("type", "text");
  buttonInputField.setAttribute("id", "button-input-field-url1");
  buttonInputField.setAttribute("placeholder", "Enter target URL here...");
  buttonInputField.setAttribute("autocomplete", "off");
  buttonInputField.style.fontSize = "1.2em";
  buttonInputField.value = window.location.origin + "/";
  buttonForm.appendChild(buttonInputField);

  let secondaryBtnPara = document.createElement("p");
  secondaryBtnPara.style.textAlign = "left";

  buttonForm.appendChild(secondaryBtnPara);

  let ignoreCheckBox = document.createElement("input");
  ignoreCheckBox.type = "checkbox";
  ignoreCheckBox.checked = true;
  ignoreCheckBox.id = "ignore_secondary_btn_checkbox";
  ignoreCheckBox.style.marginLeft = 0;
  ignoreCheckBox.style.marginRight = "7px";
  ignoreCheckBox.addEventListener("click", (ev) => {
    tgpToggleSecondaryBtnDiv();
  });

  secondaryBtnPara.appendChild(ignoreCheckBox);
  let ignoreText = document.createTextNode("No Secondary Button");
  secondaryBtnPara.appendChild(ignoreText);

  //SECONDARY BTN START
  let secondaryBtnDiv = document.createElement("div");
  secondaryBtnDiv.id = "secondary_btn_div";
  secondaryBtnDiv.style.opacity = "0.6";
  buttonForm.appendChild(secondaryBtnDiv);

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Secondary Button";
  formLabel.style.textAlign = "left";
  formLabel.style.fontSize = "1.2em";
  formLabel.style.fontWeight = "bold";
  secondaryBtnDiv.appendChild(formLabel);

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Button Text:";
  formLabel.style.textAlign = "left";
  secondaryBtnDiv.appendChild(formLabel);

  buttonInputField = document.createElement("input");
  buttonInputField.setAttribute("type", "text");
  buttonInputField.setAttribute("id", "button-input-field-text2");
  buttonInputField.setAttribute(
    "placeholder",
    "Enter button ID or URL here..."
  );
  buttonInputField.setAttribute("autocomplete", "off");
  buttonInputField.setAttribute("onblur", "resetIgnoreSecondary()");
  buttonInputField.value = "Secondary Button";
  secondaryBtnDiv.appendChild(buttonInputField);

  formLabel = document.createElement("label");
  formLabel.innerHTML = "Target URL:";
  formLabel.style.textAlign = "left";
  secondaryBtnDiv.appendChild(formLabel);

  buttonInputField = document.createElement("input");
  buttonInputField.setAttribute("type", "text");
  buttonInputField.setAttribute("id", "button-input-field-url2");
  buttonInputField.setAttribute("placeholder", "Enter target URL here...");
  buttonInputField.setAttribute("autocomplete", "off");
  buttonInputField.setAttribute("onblur", "resetIgnoreSecondary()");
  secondaryBtnDiv.appendChild(buttonInputField);
}

function tgpToggleSecondaryBtnDiv() {
  //is the checkbox checked?
  const targetCheckbox = document.getElementById(
    "ignore_secondary_btn_checkbox"
  );
  const secondaryBtnEl = document.getElementById("secondary_btn_div");

  if (targetCheckbox.checked == true) {
    secondaryBtnEl.style.opacity = 0.6;
  } else {
    secondaryBtnEl.style.opacity = 1;
  }
}

function tgpInsertButton() {
  const targetCheckbox = document.getElementById(
    "ignore_secondary_btn_checkbox"
  );
  const buttonInputFieldText1 = document.getElementById(
    "button-input-field-text1"
  );
  const buttonInputFieldUrl1 = document.getElementById(
    "button-input-field-url1"
  );
  const valueField1Text = buttonInputFieldText1.value.trim();
  const valueField1Url = buttonInputFieldUrl1.value.trim();

  const buttonInputFieldText2 = document.getElementById(
    "button-input-field-text2"
  );
  const buttonInputFieldUrl2 = document.getElementById(
    "button-input-field-url2"
  );
  const valueField2Text = buttonInputFieldText2.value.trim();
  const valueField2Url = buttonInputFieldUrl2.value.trim();

  //build the first button
  const button1 = document.createElement("button");
  button1.innerHTML = valueField1Text;

  if (valueField1Url !== "") {
    button1.setAttribute(
      "onclick",
      "window.location='" + valueField1Url + "';"
    );
  }

  if (targetCheckbox.checked !== true) {
    var button2 = document.createElement("button");
    button2.innerHTML = valueField2Text;
    button2.setAttribute("class", "alt");

    if (valueField2Url !== "") {
      button2.setAttribute(
        "onclick",
        "window.location='" + valueField2Url + "';"
      );
    }
  }

  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  let textDiv = document.createElement("div");
  textDiv.setAttribute("class", "button-div");

  textDiv.appendChild(button1);

  if (targetCheckbox.checked !== true) {
    textDiv.appendChild(button2);
  }

  trongatePagesObj.targetNewElLocation = "default";
  tgpInsertElement(textDiv);
}

function tgpAddPointersToBtnDivs() {
  const parentElement = trongatePagesObj.defaultActiveElParent;

  parentElement.addEventListener("mouseover", (event) => {
    if (event.target.closest("div.button-div")) {
      event.target.style.cursor = "pointer";
    }
  });

  parentElement.addEventListener("click", (event) => {
    if (event.target.closest("div.button-div")) {
      trongatePagesObj.activeEl = event.target.closest("div.button-div");
      tgpAddButtonEditor(trongatePagesObj.activeEl);
    }
  });
}

function tgpAddButtonEditor(targetEl) {
  tgpReset(["codeviews", "toolbars", "customModals"]);
  trongatePagesObj.activeEl = targetEl;
  let editor = document.createElement("div");
  let divLeft = document.createElement("div");
  divLeft.setAttribute("id", "trongate-editor-toolbar");
  editor.appendChild(divLeft);
  let divRight = document.createElement("div");
  editor.appendChild(divRight);

  tgpBuildCodeBtn(divLeft);
  tgpBuildAlignLeftBtn(divLeft);
  tgpBuildAlignCenterBtn(divLeft);
  tgpBuildAlignRightBtn(divLeft);

  // Create a button with plus symbol
  let plusBtn = document.createElement("button");
  plusBtn.setAttribute("onclick", "handlePlusButtonClick()");
  plusBtn.innerHTML = "<i class='fa fa-plus'></i>";
  divLeft.appendChild(plusBtn);

  // Create a button with minus symbol
  let minusBtn = document.createElement("button");
  minusBtn.setAttribute("onclick", "handleMinusButtonClick()");
  minusBtn.innerHTML = "<i class='fa fa-minus'></i>";
  divLeft.appendChild(minusBtn);

  tgpBuildTrashifyBtn(divRight);

  editor.setAttribute("id", "trongate-editor");
  let targetElTagname = targetEl.tagName;

  targetEl.setAttribute("contenteditable", "true");

  const body = trongatePagesObj.pageBody;
  body.append(editor);
  let rect = targetEl.getBoundingClientRect();
  let editorRect = editor.getBoundingClientRect();
  let targetYPos = rect.top - editorRect.height - 7;

  if (targetYPos < 0) {
    targetYPos = 0;
  }

  editor.style.top = targetYPos + "px";
  tgpInitEditorListeners(editor);
  tgpStartTimer();
}

// Function to handle the plus button click
function handlePlusButtonClick() {
  const activeEl = trongatePagesObj.activeEl;

  if (activeEl) {
    let currentFontSize = parseFloat(activeEl.style.fontSize) || 1.0;
    currentFontSize += 0.2;
    activeEl.style.fontSize = `${currentFontSize}em`;
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      currentSelectedRange = selection.getRangeAt(0).cloneRange();
    }
    selection.removeAllRanges();
  }
}

// Function to handle the minus button click
function handleMinusButtonClick() {
  const activeEl = trongatePagesObj.activeEl;

  if (activeEl) {
    let currentFontSize = parseFloat(activeEl.style.fontSize) || 1.0;
    currentFontSize -= 0.2;
    activeEl.style.fontSize = `${currentFontSize}em`;
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      currentSelectedRange = selection.getRangeAt(0).cloneRange();
    }
    selection.removeAllRanges();
  }
}
