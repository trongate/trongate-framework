function tgpOpenCameraModal() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);

  const modalId = "tgp-camera-modal";
  const customModal = tgpBuildCustomModal(modalId);
  const modalBody = customModal.querySelector(".modal-body");

  // Create the form element
  const formElement = document.createElement("form");
  formElement.setAttribute("id", "image-upload-form");

  const cameraIconParagraph = document.createElement("p");
  cameraIconParagraph.classList.add("blink");
  cameraIconParagraph.classList.add("text-center");

  // Create Font Awesome icon element
  const tgpCameraIcon = document.createElement("i");
  tgpCameraIcon.classList.add("fa", "fa-camera");
  tgpCameraIcon.style.fontSize = "3.3em";
  cameraIconParagraph.appendChild(tgpCameraIcon);

  // Create the input element
  const inputElement = document.createElement("input");
  inputElement.type = "file";
  inputElement.id = "file1";
  inputElement.name = "tgp_camera_file";
  inputElement.style.display = "none";

  // Create the submit button
  const submitButtonContainer = document.createElement("div");

  const submitButton = document.createElement("button");
  submitButton.type = "button";
  submitButton.classList.add("button");
  submitButtonContainer.style.display = "none";
  submitButton.style.minWidth = "100%";
  submitButton.style.marginTop = "3em";
  submitButtonContainer.appendChild(submitButton);

  // Create the text span element for the button label
  const buttonTextSpan = document.createElement("span");
  buttonTextSpan.textContent = "Upload Photo";

  // Create the Font Awesome icon element for the button
  const buttonIcon = document.createElement("i");
  buttonIcon.classList.add("fa", "fa-upload");
  buttonIcon.style.marginLeft = "1em";

  // Append the icon element to the button text span
  buttonTextSpan.appendChild(buttonIcon);

  // Append the button text span to the submit button
  submitButton.appendChild(buttonTextSpan);
  submitButton.setAttribute("onclick", "tgpUploadImg()");

  // Append elements to the form
  formElement.appendChild(cameraIconParagraph);
  formElement.appendChild(inputElement);
  formElement.appendChild(submitButtonContainer);

  // Create the close button (cross) container
  const closeButtonContainer = document.createElement("div");
  closeButtonContainer.classList.add("tgp-close-button-container");

  // Create the close button (cross)
  const closeButton = document.createElement("span");
  closeButton.classList.add("tgp-close-cross");
  closeButton.innerHTML = "&#215;"; // Times symbol

  // Add hover effect and click event to the close button
  closeButton.style.cursor = "pointer";
  closeButton.addEventListener("click", () => {
    tgpReset([
      "selectedRange",
      "codeviews",
      "customModals",
      "toolbars",
      "activeEl",
    ]);
  });

  // Append the close button to the close button container
  closeButtonContainer.appendChild(closeButton);

  // Append the form to the modal body
  modalBody.appendChild(formElement);

  const targetCameraModal = document.getElementById("tgp-camera-modal");
  const firstChild = targetCameraModal.firstChild;
  targetCameraModal.insertBefore(closeButtonContainer, firstChild);

  // Add event listener to inputElement for change event
  inputElement.addEventListener("change", () => {
    // Make the hidden form elements appear
    // and hide the camera icon
    cameraIconParagraph.style.display = "none";
    submitButtonContainer.style.display = "block";
    inputElement.style.display = "inline-block";
  });

  // Trigger the click event on the input element
  inputElement.click();
}

function tgpAfterUploadEventsMobi(response) {
  //remove all of the items inside the modal body
  const targetModalBody = document.querySelector(
    `#tgp-camera-modal > div.modal-body`
  );

  while (targetModalBody.firstChild) {
    targetModalBody.removeChild(targetModalBody.lastChild);
  }

  tgpDrawBigTick(targetModalBody, 0);

  setTimeout(() => {
    while (targetModalBody.firstChild) {
      targetModalBody.removeChild(targetModalBody.lastChild);
    }

    const infoPara = document.createElement("p");
    infoPara.style.fontSize = "1.2em";
    infoPara.style.fontWeight = "bold";
    infoPara.innerHTML = "Upload Success!";
    infoPara.style.color = "green";
    infoPara.style.marginTop = 0;
    infoPara.style.paddingTop = 0;
    targetModalBody.appendChild(infoPara);
    targetModalBody.style.overflow = "autoscroll"; // "autoscroll" enables scrolling only when necessary

    const newPicDiv = document.createElement("div");
    targetModalBody.appendChild(newPicDiv);

    const newPicPath = response.replace("|||..", "");
    const newImg = document.createElement("img");
    newImg.src = newPicPath;
    newPicDiv.appendChild(newImg);

    const questionPara = document.createElement("p");
    questionPara.style.fontSize = ".8em";
    questionPara.innerText = "Would you like to add the picture to the page?";
    questionPara.style.marginTop = "1em";
    targetModalBody.appendChild(questionPara);

    const whatNextPara = document.createElement("p");

    targetModalBody.appendChild(whatNextPara);
    const addPicToPageBtn = document.createElement("button");
    addPicToPageBtn.innerText = "Yes";

    addPicToPageBtn.addEventListener("click", (ev) => {
      trongatePagesObj.targetNewElLocation = "default";
      tgpDestroyImgModalAddImg(newPicPath, targetModalBody);
    });

    whatNextPara.appendChild(addPicToPageBtn);

    const notNowBtn = document.createElement("button");
    notNowBtn.innerText = "Not Now";
    notNowBtn.classList.add("alt");
    notNowBtn.style.marginTop = ".3em";
    notNowBtn.addEventListener("click", (ev) => {
      tgpReset([
        "selectedRange",
        "codeviews",
        "customModals",
        "toolbars",
        "activeEl",
      ]);
    });

    whatNextPara.appendChild(notNowBtn);
  }, 1300);

  return;
}

function tgpDrawImageErrorMobi(status, response) {
  console.log(
    "running tgpDrawImageErrorMobi with status of " +
      status +
      " and response of " +
      response
  );
}
