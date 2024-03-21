function tgpDrawMediaManagerBtns(targetModalBody) {
  const modalTopPara = document.createElement("p");
  modalTopPara.classList.add("force-flex-para");
  modalTopPara.style.textAlign = "left";
  modalTopPara.style.marginTop = 0;
  targetModalBody.appendChild(modalTopPara);

  // Build nav up one level button.
  tgpBuildNavBackBtn(modalTopPara);

  // Build create new folder button.
  const createNewFolderBtn = document.createElement("button");
  createNewFolderBtn.classList.add("alt");
  createNewFolderBtn.innerHTML = "New Folder";
  createNewFolderBtn.addEventListener("click", (ev) => {
    tgpInitInitCreateNewFolder();
  });

  modalTopPara.appendChild(createNewFolderBtn);
  tgpBuildUploadImgBtn(modalTopPara);
}

function tgpBuildNavBackBtn(btnContainer) {
  const navBackBtn = document.createElement("button");
  navBackBtn.classList.add("alt");
  navBackBtn.innerHTML = "<i class='fa fa-chevron-up'></i>";
  btnContainer.appendChild(navBackBtn);

  if (trongatePagesObj.currentImgDir === "") {
    navBackBtn.style.display = "none";
  }

  navBackBtn.addEventListener("click", (ev) => {
    trongatePagesObj.currentImgDir = tgpRemoveLastSegment(
      trongatePagesObj.currentImgDir
    );
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );
    tgpHideModalBody(targetModalBody, true);
    tgpFetchUploadedImages();
  });
}

function tgpMakeDirEditable(ev) {
  if (tgpAllowIconEditMode === false) {
    console.log("edit mode not allowed");
    return;
  }

  // Get the outer div for the clicked element.
  const clickedEl = ev.target;
  const previewBox = clickedEl.closest(".tgp-preview-box");
  previewBox.classList.add("tgp-preview-box-edit-mode");

  const realFolderNameEl = previewBox.querySelector(".tgp-real-entity-name");
  const elementNameDiv = previewBox.querySelector(".tgp-preview-element-name");
  elementNameDiv.innerHTML = realFolderNameEl.innerHTML;
  elementNameDiv.contentEditable = true;

  const textNode = elementNameDiv.firstChild;
  const range = document.createRange();
  const selection = window.getSelection();

  range.setStart(textNode, 0);
  range.setEnd(textNode, textNode.length);

  selection.removeAllRanges();
  selection.addRange(range);

  // Build delete icon
  const deleteDirBtn = document.createElement("button");
  deleteDirBtn.innerHTML = "<i class='fa fa-trash'></i>";
  deleteDirBtn.setAttribute("class", "tgp-delete-dir-btn");
  previewBox.appendChild(deleteDirBtn);
  previewBox.style.position = "relative";

  const previewBoxShape = previewBox.getBoundingClientRect();
  const topPos = previewBoxShape.top;
  const rightPos = previewBoxShape.right;
  deleteDirBtn.style.top = "0";
  deleteDirBtn.style.right = "0";
  deleteDirBtn.style.zIndex = 12;

  deleteDirBtn.addEventListener("click", (ev) => {
    tgpInitDeleteFolder(ev.target);
  });
}

function tgpInitInitCreateNewFolder() {
  //lock the modal body height
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  const targetModalBodyShape = targetModalBody.getBoundingClientRect();
  const targetModalBodyHeight = targetModalBodyShape.height;
  targetModalBody.style.maxHeight = targetModalBodyHeight + "px";
  targetModalBody.style.overflow = "auto";

  //remove all of the other pictures (drop effect) / use targetModalBody as inactive placeholder el
  tgpDropUnselectedPics(targetModalBody);

  //remove the buttons on the top lhs
  const unwantedModalBtns = document.querySelector(
    "#tgp-media-manager > div.modal-body > p"
  );
  unwantedModalBtns.remove();

  setTimeout(() => {
    tgpDrawAddNewFolder(targetModalBody);
  }, 100);
}

function tgpDrawAddNewFolder(targetModalBody) {
  //create a div to contain the 'showcase' picture
  const tgpShowcaseDiv = document.createElement("div");
  tgpShowcaseDiv.style.opacity = 0;
  tgpShowcaseDiv.setAttribute("class", "tgp-showcase-img-div");
  targetModalBody.appendChild(tgpShowcaseDiv);
  targetModalBody.appendChild(tgpShowcaseDiv);

  setTimeout(() => {
    const tgUploadedImagesGrid = document.querySelector(
      ".tgp-uploaded-images-grid"
    );
    tgUploadedImagesGrid.remove();
    setTimeout(() => {
      //reduce the modal width
      document.getElementById("tgp-media-manager").style.maxWidth = "640px";
      tgpDrawAddNewFolderForm(tgpShowcaseDiv);
    }, 300);
  }, 400);
}

function tgpDrawAddNewFolderForm(parentContainer) {
  const infoPara = document.createElement("p");
  infoPara.innerText = "Enter the folder name below, then hit 'Submit'.";
  parentContainer.appendChild(infoPara);

  const tgpFormContainer = document.createElement("div");
  tgpFormContainer.setAttribute("id", "tgp-create-new-folder-form");

  const input = document.createElement("input");
  input.type = "text";
  input.id = "tgp-create-folder-input";
  input.autocomplete = "off";
  input.style.borderRadius = "4px 0px 0px 4px";
  input.style.textTransform = "none";
  input.style.fontWeight = "normal";

  const safeChars = /^[a-zA-Z0-9-_]+$/; // Regular expression for safe characters

  input.addEventListener("input", function () {
    // Add event listener for input event
    const value = input.value; // Get the current value of the input field

    if (!safeChars.test(value)) {
      // Test if the value contains any unsafe characters
      const newValue = value.replace(/[^a-zA-Z0-9-_]/g, ""); // Remove any unsafe characters
      input.value = newValue; // Set the new value of the input field
      alert(
        "Only letters, numbers, hyphens, and underscores are allowed for folder names."
      ); // Alert the user
    }
  });

  input.addEventListener("paste", function (event) {
    // Add event listener for paste event
    event.preventDefault(); // Prevent the default paste behavior
    const clipboardData = event.clipboardData || window.clipboardData; // Get the clipboard data
    const pastedData = clipboardData.getData("text/plain"); // Get the pasted text
    const newValue = pastedData.replace(/[^a-zA-Z0-9-_]/g, ""); // Remove any unsafe characters from the pasted text
    document.execCommand("insertText", false, newValue); // Insert the cleaned text into the input field
  });

  const button = document.createElement("button");
  button.id = "tgp-create-folder-now-btn";
  button.style.borderRadius = "0px 4px 4px 0px";
  button.textContent = "Submit";

  button.setAttribute("onclick", "tgpAttemptCreateNewFolder()");

  tgpFormContainer.appendChild(input);
  tgpFormContainer.appendChild(button);

  parentContainer.appendChild(tgpFormContainer);

  const newPara = document.createElement("p");
  newPara.innerText = "Go Back";
  newPara.classList.add("tgp-fake-link");
  newPara.style.marginTop = "33px";
  newPara.addEventListener("click", (ev) => {
    tgpRestartImageAdder();
  });

  parentContainer.appendChild(newPara);

  parentContainer.style.opacity = 1;
  input.focus();
}

function tgpAttemptCreateNewFolder() {
  const formInput = document.getElementById("tgp-create-folder-input");
  const newFolderName = formInput.value;

  const params = {
    newFolderName,
    currentImgDir: trongatePagesObj.currentImgDir,
  };

  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  tgpClearModalBody(targetModalBody, true);

  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/submit_create_new_img_folder";

  const http = new XMLHttpRequest();
  http.open("post", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));
  http.onload = function () {
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );
    tgpClearModalBody(targetModalBody);

    if (http.status !== 200) {
      tgpProcValidationErr(targetModalBody, http.status, http.responseText);
    } else {
      tgpIconNameToSpin = newFolderName;

      const newPara = document.createElement("p");
      newPara.setAttribute("class", "blink");
      newPara.style.fontWeight = "bold";
      newPara.innerText = "Folder Created";
      targetModalBody.appendChild(newPara);
      tgpIconTypeToSpin = "folder";

      tgpDrawBigTick(targetModalBody, 0);
      setTimeout(() => {
        tgpRestartImageAdder();
      }, 1533);
    }
  };
}

function tgpInitDeleteFolder(clickedBtn) {
  //lock the modal body height
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  const targetModalBodyShape = targetModalBody.getBoundingClientRect();
  const targetModalBodyHeight = targetModalBodyShape.height;
  targetModalBody.style.maxHeight = targetModalBodyHeight + "px";
  targetModalBody.style.overflow = "auto";

  //remove all of the other pictures (drop effect)
  tgpDropUnselectedPics(clickedBtn);

  //remove the buttons on the top lhs
  const unwantedModalBtns = document.querySelector(
    "#tgp-media-manager > div.modal-body > p"
  );
  unwantedModalBtns.remove();

  setTimeout(() => {
    tgpDrawConfDeleteFolder(clickedBtn, targetModalBody);
  }, 100);
}

function tgpDrawConfDeleteFolder(clickedBtn, targetModalBody) {
  // Empty modal body
  tgpClearModalBody(targetModalBody);

  // Reduce with of modal
  const targetModal = document.getElementById("tgp-media-manager");
  targetModal.style.maxWidth = "640px";

  // Draw a conf delete form
  tgpInitConfDeleteFolder(clickedBtn, targetModalBody);
}

function tgpInitConfDeleteFolder(clickedBtn, targetModalBody) {
  const targetPreviewBox = clickedBtn.closest(".tgp-preview-box");

  const warningPara = document.createElement("p");
  warningPara.style.color = "red";
  warningPara.style.fontWeight = "bold";
  warningPara.innerText = "WARNING: YOU ARE ABOUT TO DELETE AN ENTIRE FOLDER!";
  warningPara.classList.add("blink");
  targetModalBody.appendChild(warningPara);

  const realFolderNameEl = targetPreviewBox.querySelector(
    ".tgp-real-entity-name"
  );

  const directoryInfoPara = document.createElement("p");
  directoryInfoPara.innerHTML =
    'Folder Name:  <span class="tgp-grey-label">' +
    realFolderNameEl.innerHTML +
    "</span>";
  targetModalBody.appendChild(directoryInfoPara);

  const formWrapper = document.createElement("div");
  formWrapper.setAttribute("id", "tg-conf-delete-img-form");

  const confDeleteFolderInstructions = document.createElement("p");
  tgpDeletePicCode = tgpGenerateRandomCode(4).toUpperCase();
  confDeleteFolderInstructions.innerHTML = `To delete, please enter <b>${tgpDeletePicCode}</b> below, then hit 'Delete Now'.`;

  const codeInput = document.createElement("input");
  codeInput.type = "text";
  codeInput.setAttribute("placeholder", "- - - -");
  codeInput.setAttribute("maxlength", 4);
  codeInput.setAttribute("id", "tgp-delete-folder-code-input");
  codeInput.setAttribute("autocomplete", "off");
  codeInput.style.width = "4em";
  codeInput.style.borderRadius = "4px 0 0 4px";

  const deleteBtn = document.createElement("button");
  deleteBtn.innerHTML = '<i class="fa fa-trash"></i> Delete Now';
  deleteBtn.style.borderRadius = "0 4px 4px 0";
  deleteBtn.setAttribute("id", "tg-delete-img-now-btn");
  deleteBtn.classList.add("tgp-trongate-pages-danger");
  deleteBtn.addEventListener("click", (ev) => {
    tgpExecuteDeleteFolder(realFolderNameEl.innerHTML);
  });

  formWrapper.appendChild(codeInput);
  formWrapper.appendChild(deleteBtn);

  const goBackPara = document.createElement("p");
  goBackPara.innerText = "Go Back";
  goBackPara.classList.add("tgp-fake-link");
  goBackPara.style.marginTop = "33px";
  goBackPara.addEventListener("click", (ev) => {
    tgpRestartImageAdder();
  });

  targetModalBody.appendChild(confDeleteFolderInstructions);
  targetModalBody.appendChild(formWrapper);
  targetModalBody.appendChild(goBackPara);
}

function tgpExecuteDeleteFolder(folderName) {
  const tgpDeleteImgCodeInput = document.getElementById(
    "tgp-delete-folder-code-input"
  );
  const tgpSubmitedCodeValue = tgpDeleteImgCodeInput.value.toUpperCase();

  if (tgpSubmitedCodeValue === tgpDeletePicCode) {
    tgpSendDeleteFolderRequest(folderName);
  } else {
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );

    while (targetModalBody.firstChild) {
      targetModalBody.removeChild(targetModalBody.lastChild);
    }

    // create a new paragraph element
    const p = document.createElement("p");

    // set the style attribute of the paragraph element
    p.style.fontSize = "1.2em";
    p.style.fontWeight = "bold";

    // create a text node and add it to the paragraph element
    const text = document.createTextNode("Incorrect Code!");
    p.appendChild(text);
    p.style.color = "red";
    p.classList.add("blink");

    // add the paragraph element to the document body
    targetModalBody.appendChild(p);
    tgpDrawBigCross(targetModalBody);

    setTimeout(() => {
      tgpRestartImageAdder();
    }, 1533);
  }
}

function tgpSendDeleteFolderRequest(folderName) {
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  tgpClearModalBody(targetModalBody);

  // create a new paragraph element
  const p = document.createElement("p");

  // set the style attribute of the paragraph element
  p.style.fontSize = "1.2em";
  p.style.fontWeight = "bold";

  // create a text node and add it to the paragraph element
  const text = document.createTextNode("*** PLEASE WAIT ***");
  p.appendChild(text);
  p.classList.add("blink");

  targetModalBody.appendChild(p);

  //add a spinner
  const spinnerDiv = document.createElement("div");
  spinnerDiv.setAttribute("class", "spinner");
  spinnerDiv.style.marginTop = "3em";
  spinnerDiv.style.marginBottom = "5em";
  targetModalBody.appendChild(spinnerDiv);

  const params = {
    folderName,
    currentImgDir: trongatePagesObj.currentImgDir,
  };

  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/submit_delete_folder";
  const http = new XMLHttpRequest();
  http.open("delete", targetUrl);
  http.setRequestHeader("Content-type", "application-json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));

  http.onload = function () {
    tgpClearModalBody(targetModalBody);

    if (http.status === 200) {
      // create a new paragraph element
      const p = document.createElement("p");

      // set the style attribute of the paragraph element
      p.style.fontSize = "1.2em";
      p.style.fontWeight = "bold";

      // create a text node and add it to the paragraph element
      const text = document.createTextNode("*** DELETING ***");
      p.appendChild(text);
      p.classList.add("blink");
      p.style.color = "green";

      targetModalBody.appendChild(p);
      tgpDrawBigTick(targetModalBody, 0);

      setTimeout(() => {
        tgpRestartImageAdder();
      }, 1533);
    } else {
      // create a new paragraph element
      const p = document.createElement("p");

      // set the style attribute of the paragraph element
      p.style.fontSize = "1.2em";
      p.style.fontWeight = "bold";

      // create a text node and add it to the paragraph element
      const text = document.createTextNode("Delete Folder Failed!");
      p.appendChild(text);
      p.style.color = "red";

      // add the paragraph element to the document body
      targetModalBody.appendChild(p);

      const errorInfoPara = document.createElement("p");
      errorInfoPara.innerText = http.responseText;
      targetModalBody.appendChild(errorInfoPara);
      tgpDrawBigCross(targetModalBody);
    }
  };
}

function tgpRestoreElement(previewBox, attemptRenameFolder = true) {
  if (previewBox.classList.contains("tgp-preview-box-edit-mode")) {
    previewBox.classList.remove("tgp-preview-box-edit-mode");
    const elementNamePreviewDiv = previewBox.querySelector(
      ".tgp-preview-element-name"
    );
    elementNamePreviewDiv.contentEditable = false;
    elementNamePreviewDiv.blur();

    // Remove delete icon
    const deleteDirBtn = previewBox.querySelector(".tgp-delete-dir-btn");
    deleteDirBtn.remove();

    const newFolderName = elementNamePreviewDiv.innerHTML;
    const oldFolderNameEl = previewBox.querySelector(".tgp-real-entity-name");
    const oldFolderName = oldFolderNameEl.innerHTML;

    while (previewBox.firstChild) {
      previewBox.removeChild(previewBox.lastChild);
    }

    const spinnerEl = document.createElement("div");
    spinnerEl.setAttribute("class", "spinner");
    previewBox.appendChild(spinnerEl);

    const args = {
      previewBox,
      oldFolderName,
      newFolderName,
    };

    if (attemptRenameFolder === true) {
      tgpAttemptRenameFolder(previewBox, oldFolderName, newFolderName);
    } else {
      tgpRestorePreviewBox(previewBox, oldFolderName);
    }
  }
}

function tgpAttemptRenameFolder(previewBox, oldFolderName, newFolderName) {
  let renameAllowed = true;

  // make sure there no other elements with the same newFolderName
  const tgpPreviewBoxes = document.getElementsByClassName("tgp-preview-box");
  for (var i = tgpPreviewBoxes.length - 1; i >= 0; i--) {
    if (!tgpPreviewBoxes[i].classList.contains("tgp-preview-box-edit-mode")) {
      const realEntityNameEl = tgpPreviewBoxes[i].querySelector(
        ".tgp-real-entity-name"
      );
      if (realEntityNameEl) {
        const realEntityName = realEntityNameEl.innerText;
        if (newFolderName === realEntityNameEl.textContent) {
          renameAllowed = false;
          break;
        }
      }
    }
  }

  if (renameAllowed) {
    tgpSubmitRequestRenameFolder(previewBox, oldFolderName, newFolderName);
  } else {
    tgpRestorePreviewBox(previewBox, oldFolderName);
  }
}

function tgpSubmitRequestRenameFolder(
  previewBox,
  oldFolderName,
  newFolderName
) {
  // Submit to an endpoint - if all is well, use the httpResponsecode as the folder name
  // otherwise, use the oldFoldername

  const params = {
    oldFolderName,
    newFolderName,
    currentImgDir: trongatePagesObj.currentImgDir,
  };

  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/submit_rename_img_folder";
  const http = new XMLHttpRequest();
  http.open("post", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));
  http.onload = function () {
    if (http.status === 200) {
      tgpRestorePreviewBox(previewBox, http.responseText);
    } else {
      tgpRestorePreviewBox(previewBox, params["oldFolderName"]);
    }
  };
}

function tgpRestorePreviewBox(previewBox, realFolderName) {
  const spinnerEl = previewBox.querySelector(".spinner");
  spinnerEl.remove();

  const upperDiv = document.createElement("div");
  previewBox.appendChild(upperDiv);

  // Get a nice (short) version of the realFolderName
  const imgPath =
    trongatePagesObj.baseUrl +
    "trongate_pages" +
    trongatePagesObj.moduleAssetsTrigger +
    "/images/foldericon.png";
  const boxImage = document.createElement("img");
  boxImage.setAttribute("src", imgPath);
  upperDiv.appendChild(boxImage);

  const niceFolderName = tgpTruncateStr(realFolderName, 11);
  const lowerDiv = document.createElement("div");
  lowerDiv.classList.add("tgp-preview-element-name");
  lowerDiv.innerHTML = niceFolderName;
  previewBox.appendChild(lowerDiv);

  // Make a hidden div that contains the real folder name
  const realFolderNameEl = document.createElement("div");
  realFolderNameEl.innerHTML = realFolderName;
  realFolderNameEl.setAttribute("class", "tgp-real-entity-name");
  realFolderNameEl.style.display = "none";
  previewBox.appendChild(realFolderNameEl);
}

document.addEventListener("click", (ev) => {
  const tgpPreviewBoxesInEditMode = document.getElementsByClassName(
    "tgp-preview-box-edit-mode"
  );
  if (tgpPreviewBoxesInEditMode.length > 0) {
    const clickedEl = ev.target;
    const containingPreviewBoxInEditMode = clickedEl.closest(
      ".tgp-preview-box-edit-mode"
    );
    if (!containingPreviewBoxInEditMode) {
      for (var i = tgpPreviewBoxesInEditMode.length - 1; i >= 0; i--) {
        tgpRestoreElement(tgpPreviewBoxesInEditMode[i]);
      }
    }
  }
});
