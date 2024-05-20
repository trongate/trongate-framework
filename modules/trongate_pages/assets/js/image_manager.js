const showcaseImgMaxAllowedWidth = 600;
const showcaseImgMaxAllowedHeight = 340;
const targetTable = trongatePagesObj.targetTable;
const imgUploadApi = trongatePagesObj.imgUploadApi;

let tgpIconTypeToSpin = "";
let tgpIconNameToSpin = "";
let tgpDeletePicCode = "";
let tgpImgUpdatePending = false;
let tgpAllowIconEditMode = true;
let tgpImgOrigSrc = "";
let tgpSelectedImg = {};

function tgpOpenTgMediaManager() {
  trongatePagesObj.currentImgDir = "";
  const activeEl = trongatePagesObj.activeEl;
  const defaultActiveElParent = trongatePagesObj.defaultActiveElParent;

  if (activeEl.outerHTML !== defaultActiveElParent.outerHTML) {
    tgpHighlightActiveEl();
  }

  tgpCloseAndDestroyModal("tgp-create-page-el", false);

  const modalId = "tgp-media-manager";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  modalHeading.innerHTML = '<i class="fa fa-image"></i> Image Manager';

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
    "tgpCloseAndDestroyModal('" + modalId + "', true)"
  );
  modalFooter.appendChild(closeModalBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
    maxWidth: "80%",
    width: "100%",
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  tgpClearModalBody(targetModalBody, true);
  tgpFetchUploadedImages();
}

function tgpBuildNewImgFolderBtn(btnContainer) {
  const createNewFolderBtn = document.createElement("button");
  createNewFolderBtn.classList.add("alt");
  createNewFolderBtn.innerHTML = "New Folder";

  createNewFolderBtn.addEventListener("click", (ev) => {
    tgpInitInitCreateNewFolder();
  });

  btnContainer.appendChild(createNewFolderBtn);
}

function tgpBuildUploadImgBtn(btnContainer) {
  const addNewImageBtn = document.createElement("button");
  addNewImageBtn.innerHTML = "Upload Image";

  addNewImageBtn.addEventListener("click", (ev) => {
    tgpInitUploadPic();
  });

  btnContainer.appendChild(addNewImageBtn);
}

function tgpFetchUploadedImages() {
  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/fetch_uploaded_images";

  const params = {
    currentImgDir: trongatePagesObj.currentImgDir,
  };

  const http = new XMLHttpRequest();
  http.open("post", targetUrl);
  http.setRequestHeader("Content-type", "application-json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));
  http.onload = function () {
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );

    if (http.status === 200) {
      tgpClearModalBody(targetModalBody);
      tgpDrawMediaManagerBtns(targetModalBody);
      const uploadedImages = JSON.parse(http.responseText);
      tgpDrawUploadedImages(uploadedImages);
      tgpAllowIconEditMode = true;
    }
  };
}

function tgpRemoveLastSegment(inputString) {
  // Remove any trailing forward slashes
  let trimmedString = inputString.replace(/\/+$/, "");

  // Split the string by '/'
  let segments = trimmedString.split("/");

  // Remove the last segment
  segments.pop();

  // Join the segments back together with '/'
  return segments.join("/");
}

function tgpDrawUploadedImages(uploadedImages) {
  const modalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );

  if (!modalBody) {
    return;
  }

  const modalChildren = modalBody.childNodes;
  for (let i = modalChildren.length - 1; i >= 0; i--) {
    const child = modalChildren[i];
    if (!child.classList.contains("force-flex-para")) {
      modalBody.removeChild(child);
    }
  }

  const uploadedImagesGrid = document.createElement("div");
  uploadedImagesGrid.classList.add("tgp-uploaded-images-grid");

  modalBody.appendChild(uploadedImagesGrid);
  for (let i = 0; i < uploadedImages.length; i++) {
    const elType = uploadedImages[i]["type"];
    if (elType === "directory") {
      tgpDrawNavFolderIcon(uploadedImages[i]["info"], uploadedImagesGrid);
    } else {
      tgpDrawUploadedImgIcon(uploadedImages[i]["info"], uploadedImagesGrid);
    }
  }

  if (tgpIconTypeToSpin !== "") {
    setTimeout(() => {
      tgpSpinBabySpin();
      tgpIconTypeToSpin = "";
      tgpIconNameToSpin = "";
    }, 700);
  }
}

function tgpDrawNavFolderIcon(folderName, uploadedImagesGrid) {
  const gridBox = document.createElement("div");
  uploadedImagesGrid.appendChild(gridBox);
  gridBox.classList.add("tgp-preview-box");
  const upperDiv = document.createElement("div");
  gridBox.appendChild(upperDiv);

  const imgPath =
    trongatePagesObj.baseUrl +
    "trongate_pages" +
    trongatePagesObj.moduleAssetsTrigger +
    "/images/foldericon.png";
  const boxImage = document.createElement("img");
  boxImage.setAttribute("src", imgPath);
  upperDiv.appendChild(boxImage);

  const niceFolderName = tgpTruncateStr(folderName, 11);
  const lowerDiv = document.createElement("div");
  lowerDiv.classList.add("tgp-preview-element-name");
  lowerDiv.innerHTML = niceFolderName;
  gridBox.appendChild(lowerDiv);

  // Make a hidden div that contains the real folder name
  const realFolderNameEl = document.createElement("div");
  realFolderNameEl.innerHTML = folderName;
  realFolderNameEl.setAttribute("class", "tgp-real-entity-name");
  realFolderNameEl.style.display = "none";
  gridBox.appendChild(realFolderNameEl);

  gridBox.addEventListener("dblclick", (ev) => {
    tgpAllowIconEditMode = false;

    // Establish what was clicked.
    const clickedEl = ev.target;
    const previewBox = clickedEl.closest(".tgp-preview-box");

    // Clear the modal body.
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );
    const targetModalBodyChildren = targetModalBody.children;
    for (var i = 0; i < targetModalBodyChildren.length; i++) {
      targetModalBodyChildren[i].style.opacity = 0;
    }

    // Add a spinner.
    const spinnerDiv = document.createElement("div");
    spinnerDiv.setAttribute("class", "spinner");
    spinnerDiv.style.position = "absolute";
    spinnerDiv.style.top = "50%";
    spinnerDiv.style.left = "50%";
    spinnerDiv.style.transform = "translate(-50%, -50%)";
    targetModalBody.appendChild(spinnerDiv);

    const dirNameEl = previewBox.querySelector(".tgp-real-entity-name");
    const dirName = dirNameEl.innerHTML;

    //add this dirName to the currentImgDir
    trongatePagesObj.currentImgDir =
      trongatePagesObj.currentImgDir + "/" + dirName;

    tgpFetchUploadedImages(true);
  });

  gridBox.addEventListener("click", (ev) => {
    setTimeout(() => {
      tgpMakeDirEditable(ev);
    }, 200);
  });
}

function tgpTruncateStr(str, maxLength) {
  if (str.length > maxLength) {
    return str.slice(0, maxLength) + "...";
  } else {
    return str;
  }
}

function tgpDrawUploadedImgIcon(imageInfo, uploadedImagesGrid) {
  const uploadedImageName = imageInfo["file_name"];
  const gridBox = document.createElement("div");
  uploadedImagesGrid.appendChild(gridBox);
  gridBox.classList.add("tgp-preview-box");
  const upperDiv = document.createElement("div");
  gridBox.appendChild(upperDiv);

  const imgPath = imageInfo["url"];

  const boxImage = document.createElement("img");
  boxImage.setAttribute("src", imgPath);
  boxImage.setAttribute("class", "tgp-preview-pic-icon");
  upperDiv.appendChild(boxImage);

  const lowerDiv = document.createElement("div");
  lowerDiv.innerHTML = tgpTruncateStr(imageInfo["file_name"], 11);
  gridBox.appendChild(lowerDiv);

  gridBox.addEventListener("click", (ev) => {
    tgpChooseThisPicture(ev);
  });
}

function tgpDropUnselectedPics(clickedBox) {
  // Move all other boxes down with a transition
  const tgUploadedImagesGrid = document.querySelector(
    ".tgp-uploaded-images-grid"
  );
  const viewportHeight = window.innerHeight;
  const divTop = tgUploadedImagesGrid.getBoundingClientRect().top;
  const divHeight = viewportHeight - divTop;

  tgUploadedImagesGrid.style.height = divHeight + "px";

  const boxes = tgUploadedImagesGrid.children;
  for (const box of boxes) {
    if (box !== clickedBox) {
      box.style.transition = "transform 0.6s ease-in";
      box.style.transform = `translateY(${window.innerHeight}px)`;
      box.classList.add("fall");

      setTimeout(() => {
        box.remove();
      }, 600);
    }
  }
}

function tgpRestartImageAdder(runOtherEvents = false) {
  //remove all of the items inside the modal body
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  const targetModalBodyChildren = targetModalBody.children;

  for (let i = targetModalBodyChildren.length - 1; i >= 0; i--) {
    const targetModalBodyChildEl = targetModalBodyChildren[i];
    targetModalBodyChildEl.style.transition = "transform 0.3s ease-in";
    targetModalBodyChildEl.style.transform = `translateY(${window.innerHeight}px)`;
    targetModalBodyChildEl.classList.add("fall");
  }

  setTimeout(() => {
    tgpClearModalBody(targetModalBody, true);
    document.getElementById("tgp-media-manager").style.width = "100%";
    document.getElementById("tgp-media-manager").style.maxWidth = "80%";

    setTimeout(() => {
      tgpFetchUploadedImages();
    }, 600);

    if (runOtherEvents == true) {
      const okayBtn = document.querySelector(
        "#tgp-media-manager > div.modal-footer > button.alt"
      );
      const tryAgainBtn = document.querySelector(
        "#tgp-media-manager > div.modal-footer > button:nth-child(2)"
      );
      okayBtn.innerText = "Cancel";
      tryAgainBtn.remove();
    }
  }, 600);
}

function tgpSpinBabySpin() {
  // Initialize box and boxParent variables
  let foundSpinnableIcon = 0;
  let box = document.querySelector("#tgp-media-manager > div.modal-body img");
  let boxParent = box.closest(".tgp-preview-box");

  const allUploadedPicIcons = document.querySelectorAll(
    "#tgp-media-manager > div.modal-body .tgp-preview-box img"
  );
  for (var i = 0; i < allUploadedPicIcons.length; i++) {
    const thisIcon = allUploadedPicIcons[i];
    if (tgpIconTypeToSpin === "image") {
      if (thisIcon.src === tgpIconNameToSpin) {
        box = thisIcon;
        boxParent = thisIcon.closest(".tgp-preview-box");
        foundSpinnableIcon = 1;
        break;
      }
    } else {
      // Attempting to find a folder to spin
      const thisPreviewBox = thisIcon.closest(".tgp-preview-box");
      const realFolderNameEl = thisPreviewBox.querySelector(
        ".tgp-real-entity-name"
      );

      if (realFolderNameEl) {
        if (realFolderNameEl.innerHTML === tgpIconNameToSpin) {
          box = thisIcon;
          boxParent = thisPreviewBox;
          foundSpinnableIcon = 1;
          break;
        }
      }
    }
  }

  if (foundSpinnableIcon === 1) {
    boxParent.classList.add("blinking-border");
    box.classList.add("spin-clockwise");

    setTimeout(() => {
      box.classList.remove("spin-clockwise");
      box.classList.add("pulse");
      setTimeout(() => {
        box.classList.remove("pulse");
        boxParent.classList.remove("blinking-border");
      }, 1000);
    }, 1000);
  }
}

function tgpGenerateRandomCode(length) {
  const chars = "abcdefghjkmnpqrtuvwxyz2346789"; // removed l, 1, 0, and O
  let result = "";
  for (let i = length; i > 0; --i) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
}

function tgpInitUploadPic() {
  //remove all of the items inside the modal body
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  const targetModalBodyChildren = targetModalBody.children;

  for (let i = targetModalBodyChildren.length - 1; i >= 0; i--) {
    const targetModalBodyChildEl = targetModalBodyChildren[i];
    targetModalBodyChildEl.style.transition = "transform 0.3s ease-in";
    targetModalBodyChildEl.style.transform = `translateY(${window.innerHeight}px)`;
    targetModalBodyChildEl.classList.add("fall");
  }

  setTimeout(() => {
    tgpClearModalBody(targetModalBody);
    tgpBuildInsertImageForm(targetModalBody);
  }, 600);
}

function tgpBuildInsertImageForm(targetModalBody) {
  document.getElementById("tgp-media-manager").style.maxWidth = "640px";
  targetModalBody.style.opacity = 0;

  const modalHeadline = document.createElement("p");
  const modalHeadlineText = document.createTextNode(
    "Drag an image into the area below or click 'Choose Image'."
  );
  modalHeadline.appendChild(modalHeadlineText);
  modalHeadline.setAttribute("class", "text-center");
  targetModalBody.appendChild(modalHeadline);

  let dropZone = document.createElement("div");
  dropZone.innerHTML = "&nbsp;";
  dropZone.setAttribute("id", "drop-zone");
  targetModalBody.appendChild(dropZone);
  tgpActivateDropzoneListeners(dropZone);

  let btnPara = document.createElement("p");
  btnPara.setAttribute("id", "images-modal-btn-para");
  btnPara.classList.add("text-left");
  btnPara.style.display = "flex";
  btnPara.style.flexDirection = "row";
  btnPara.style.alignItems = "center";
  btnPara.style.justifyContent = "space-between";
  targetModalBody.appendChild(btnPara);

  let lhsDiv = document.createElement("div");
  lhsDiv.style.marginTop = ".6em";
  btnPara.appendChild(lhsDiv);

  let addImgBtn = document.createElement("button");
  addImgBtn.innerHTML = 'Choose Image <i class="fa fa-plus"></i>';
  addImgBtn.classList.add("alt");
  addImgBtn.setAttribute("onclick", "tgpChooseImgFile()");
  addImgBtn.setAttribute("id", "tgp-add-img-btn");
  lhsDiv.appendChild(addImgBtn);

  let removeImgPreviewBtn = document.createElement("button");
  removeImgPreviewBtn.innerHTML =
    '<i class="fa fa-trash"></i> Choose Another Image';
  removeImgPreviewBtn.style.display = "none";
  removeImgPreviewBtn.setAttribute("id", "tgp-choose-another-image-btn");
  removeImgPreviewBtn.setAttribute("onclick", "tgpRestartImageAdder()");
  lhsDiv.appendChild(removeImgPreviewBtn);

  //build an upload form
  const uploadForm = document.createElement("form");
  uploadForm.setAttribute("enctype", "multipart/form-data");
  uploadForm.setAttribute("id", "image-upload-form");
  uploadForm.setAttribute("method", "post");
  uploadForm.style.display = "none";
  lhsDiv.appendChild(uploadForm);

  let hiddenFileSelect = document.createElement("input");
  hiddenFileSelect.setAttribute("type", "file");
  hiddenFileSelect.setAttribute("id", "file1");
  hiddenFileSelect.setAttribute("accept", "image/*");
  hiddenFileSelect.setAttribute("onchange", "tgpInstantFilePreview(this)");
  hiddenFileSelect.setAttribute("hidden", true);
  uploadForm.appendChild(hiddenFileSelect);

  let rhsDiv = document.createElement("div");
  btnPara.appendChild(rhsDiv);

  let tgUploadImageBtn = document.createElement("button");
  tgUploadImageBtn.innerHTML = "Upload Image";
  tgUploadImageBtn.classList.add("tgp-trongate-pages-success");
  tgUploadImageBtn.style.display = "none";
  tgUploadImageBtn.setAttribute("id", "tgp-image-upload-btn");
  tgUploadImageBtn.setAttribute("onclick", "tgpUploadImg()");
  rhsDiv.appendChild(tgUploadImageBtn);

  const newPara = document.createElement("p");
  newPara.innerText = "Go Back";
  newPara.classList.add("tgp-fake-link");
  newPara.addEventListener("click", (ev) => {
    tgpRestartImageAdder();
  });

  targetModalBody.appendChild(newPara);

  setTimeout(() => {
    targetModalBody.style.transition = ".6s cubic-bezier(.4, 0, .2, 1)";
    targetModalBody.style.opacity = 1;
  }, 1);
}

function tgpActivateDropzoneListeners(dropZone) {
  // Event listeners for drag and drop functionality
  dropZone.addEventListener("dragover", (event) => {
    event.preventDefault();
    dropZone.style.border = "3px var(--primary-darker) dashed";
    dropZone.style.backgroundColor = "#fffbdf";
  });

  dropZone.addEventListener("dragleave", (event) => {
    event.preventDefault();
    dropZone.style.border = "3px #4682b4 dashed";
    dropZone.style.backgroundColor = "#eee";
  });

  dropZone.addEventListener("drop", (event) => {
    event.preventDefault();
    const files = event.dataTransfer.files;

    if (files.length > 0) {
      const targetFile = files[0]; // Get the first file
      tgpHandleFileDrop(targetFile);
    }
  });
}

function tgpChooseImgFile() {
  let dropZone = document.getElementById("drop-zone");

  if (dropZone.children.length > 0) {
    alert("Only one image can be added at a time!");
    return false;
  } else {
    //click the hidden form field
    let hiddenFileSelect = document.getElementById("file1");
    hiddenFileSelect.click();
  }
}

function tgpInstantFilePreview(input) {
  const preview = document.createElement("img");
  preview.src = URL.createObjectURL(input.files[0]);
  preview.style.maxHeight = "345px";
  let dropZone = document.getElementById("drop-zone");
  dropZone.innerHTML = "";
  dropZone.appendChild(preview);

  let chooseAnotherImageBtn = document.getElementById(
    "tgp-choose-another-image-btn"
  );
  chooseAnotherImageBtn.style.display = "inline-block";

  let tgUploadImageBtn = document.getElementById("tgp-image-upload-btn");
  tgUploadImageBtn.style.display = "inline-block";

  let addImgBtn = document.getElementById("tgp-add-img-btn");
  addImgBtn.style.display = "none";
}

function tgpUploadImg() {
  const targetModalId = document.getElementById("tgp-media-manager")
    ? "tgp-media-manager"
    : "tgp-camera-modal";
  const targetModalBody = document.querySelector(
    `#${targetModalId} > div.modal-body`
  );
  const uploadForm = document.getElementById("image-upload-form");

  tgpClearModalBody(targetModalBody);

  if (targetModalId === "tgp-camera-modal") {
    uploadForm.style.display = "none";
  }

  targetModalBody.appendChild(uploadForm);

  const modalHeadline = document.createElement("p");
  const modalHeadlineText = document.createTextNode("Uploading");
  modalHeadline.appendChild(modalHeadlineText);
  modalHeadline.setAttribute("class", "text-center blink");
  modalHeadline.style.fontSize = "1.2em";
  modalHeadline.style.fontWeight = "bold";
  targetModalBody.appendChild(modalHeadline);

  const spinnerDiv = document.createElement("div");
  spinnerDiv.setAttribute("class", "spinner");
  spinnerDiv.style.margin = "100px auto 66px auto";
  targetModalBody.appendChild(spinnerDiv);

  const progressBar = document.createElement("progress");
  progressBar.setAttribute("id", "progress-bar");
  progressBar.setAttribute("value", 0);
  progressBar.setAttribute("max", 100);
  progressBar.style.width = "300px";
  progressBar.style.marginBottom = "46px";
  targetModalBody.appendChild(progressBar);

  const statusInfo = document.createElement("h3");
  statusInfo.setAttribute("id", "upload-status");
  statusInfo.innerHTML = "0% uploaded... please wait";
  targetModalBody.appendChild(statusInfo);

  const loadedInfo = document.createElement("p");
  loadedInfo.setAttribute("id", "loaded-n-total");
  loadedInfo.style.textAlign = "left";
  loadedInfo.innerHTML = "Loaded 0 bytes of ?";
  targetModalBody.appendChild(loadedInfo);

  const file = document.getElementById("file1").files[0];
  const formData = new FormData();

  formData.append("file1", file);

  const http = new XMLHttpRequest();
  http.upload.addEventListener("progress", tgpProgressHandler, false);
  http.addEventListener("load", tgpCompleteHandler, false);
  http.addEventListener("error", tgpErrorHandler, false);
  http.addEventListener("abort", tgpAbortHandler, false);
  http.open(
    "POST",
    imgUploadApi + "/" + targetTable + "/" + trongatePagesObj.trongatePagesId
  );
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);

  formData.append("currentImgDir", trongatePagesObj.currentImgDir);
  http.send(formData);

  http.onload = function () {

    const response = http.responseText;
    const status = http.status;

    if (targetModalId === "tgp-camera-modal") {
      http.status === 200
        ? tgpAfterUploadEventsMobi(response)
        : tgpDrawImageErrorMobi(status, response);
    } else {
      http.status === 200
        ? tgpAfterUploadEvents(response)
        : tgpDrawImageError(status, response);
    }
  };
}

function tgpProgressHandler(event) {
  document.getElementById("loaded-n-total").innerHTML =
    "Uploaded " + event.loaded + " bytes of " + event.total;
  const percent = (event.loaded / event.total) * 100;
  document.getElementById("progress-bar").value = Math.round(percent);
  document.getElementById("upload-status").innerHTML =
    Math.round(percent) + "% uploaded... please wait";
}

function tgpCompleteHandler(event) {
  document.getElementById("upload-status").innerHTML =
    event.target.responseText;
  document.getElementById("progress-bar").value = 0;
}

function tgpErrorHandler(event) {
  document.getElementById("upload-status").innerHTML = "Upload Failed";
}

function tgpAbortHandler(event) {
  document.getElementById("upload-status").innerHTML = "Upload Aborted";
}

function tgpDrawImageError(httpStatus, errorMsg) {
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  tgpProcValidationErr(targetModalBody, httpStatus, errorMsg);

  const restartBtn = document.createElement("button");
  restartBtn.innerText = "Try Again";
  restartBtn.setAttribute("onclick", "tgpRestartImageAdder(1)");

  const modalFooter = document.querySelector(
    "#tgp-media-manager > div.modal-footer"
  );
  modalFooter.appendChild(restartBtn);
  return;
}

function tgpAfterUploadEvents(picPath) {
  //remove all of the items inside the modal body
  const tgpMediaManager = document.getElementById("tgp-media-manager");
  const modalId = tgpMediaManager ? "tgp-media-manager" : "tgp-camera-modal";
  const targetModalBody = document.querySelector(
    `#${modalId} > div.modal-body`
  );

  tgpClearModalBody(targetModalBody);
  tgpDrawBigTick(targetModalBody, 0);
  tgpIconTypeToSpin = "image";
  tgpIconNameToSpin = picPath;
  setTimeout(() => {
    tgpRestartImageAdder();
  }, 1533);

  return;
}

function tgpChooseThisPicture(event) {
  //lock the modal body height
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );
  const targetModalBodyShape = targetModalBody.getBoundingClientRect();
  const targetModalBodyHeight = targetModalBodyShape.height;
  targetModalBody.style.maxHeight = targetModalBodyHeight + "px";
  targetModalBody.style.overflow = "auto";

  //make the picture border go white
  const clickedBox = event.currentTarget;
  clickedBox.style.borderColor = "#fff";
  clickedBox.style.color = "#fff";

  //lock the position and size of the picture
  const selectedPic = clickedBox.querySelector("img");
  const selectedPicShape = selectedPic.getBoundingClientRect();
  const selectedPicTop = selectedPicShape.top + window.scrollY;
  const selectedPicLeft = selectedPicShape.left + window.scrollX;
  const selectedPicWidth = selectedPicShape.width;
  const selectedPicHeight = selectedPicShape.height;

  selectedPic.style.position = "fixed";
  selectedPic.style.top = `${selectedPicTop}px`;
  selectedPic.style.left = `${selectedPicLeft}px`;
  selectedPic.style.width = `${selectedPicWidth}px`;
  selectedPic.style.height = `${selectedPicHeight}px`;

  //remove all of the other pictures (drop effect)
  tgpDropUnselectedPics(clickedBox);

  //remove the buttons on the top lhs
  const unwantedModalBtns = document.querySelector(
    "#tgp-media-manager > div.modal-body > p"
  );
  unwantedModalBtns.remove();

  setTimeout(() => {
    tgpAddNewShowcasePic(selectedPic, targetModalBody, selectedPicShape);
  }, 100);
}

function tgpAddNewShowcasePic(selectedPic, targetModalBody, selectedPicShape) {
  //create a div to contain the 'showcase' picture
  const tgpShowcaseDiv = document.createElement("div");
  tgpShowcaseDiv.style.opacity = 0;
  tgpShowcaseDiv.setAttribute("class", "tgp-showcase-img-div");
  targetModalBody.appendChild(tgpShowcaseDiv);
  targetModalBody.appendChild(tgpShowcaseDiv);

  const picNamePara = document.createElement("p");

  const picUrl = selectedPic.src;
  const segments = picUrl.split("/");
  const fileName = segments[segments.length - 1];

  picNamePara.innerText = fileName;
  picNamePara.style.opacity = 0;
  targetModalBody.appendChild(picNamePara);

  const newImage = document.createElement("img");
  newImage.src = picUrl;
  newImage.style.opacity = 0;
  newImage.style.position = "relative";
  newImage.style.maxWidth = showcaseImgMaxAllowedWidth + "px";
  newImage.style.maxHeight = showcaseImgMaxAllowedHeight + "px";

  tgpShowcaseDiv.appendChild(newImage);

  //when the picture loads, delete the image preview grid and everything inside it
  newImage.addEventListener("load", (ev) => {
    //remember the 'natural' position and size of the pic
    const tgUploadedImagesGrid = document.querySelector(
      ".tgp-uploaded-images-grid"
    );
    const tgUploadedImagesGridShape =
      tgUploadedImagesGrid.getBoundingClientRect();
    const naturalPicPosTop = tgUploadedImagesGridShape.top + window.scrollY;
    const naturalPicShape = newImage.getBoundingClientRect();
    const naturalPicPosLeft = naturalPicShape.left + window.scrollX;
    const naturalPicPosWidth = naturalPicShape.width;
    const naturalPicPosHeight = naturalPicShape.height;
    const selectedPicTop = selectedPicShape.top + window.scrollY;
    const selectedPicLeft = selectedPicShape.left + window.scrollX;
    const selectedPicWidth = selectedPicShape.width;
    const selectedPicHeight = selectedPicShape.height;

    newImage.style.position = "fixed";
    newImage.style.top = selectedPicTop + "px";
    newImage.style.left = selectedPicLeft + "px";
    newImage.style.width = selectedPicWidth + "px";
    newImage.style.height = selectedPicHeight + "px";
    tgpShowcaseDiv.style.opacity = 1;
    newImage.style.opacity = 1;
    newImage.style.transition = ".6s cubic-bezier(.4, 0, .2, 1)";

    setTimeout(() => {
      tgUploadedImagesGrid.remove();

      //move the new pic into its 'natural' size and position
      newImage.style.top = naturalPicPosTop + "px";
      newImage.style.left = naturalPicPosLeft + "px";
      newImage.style.width = naturalPicPosWidth + "px";
      newImage.style.height = naturalPicPosHeight + "px";

      setTimeout(() => {
        //reduce the modal width
        document.getElementById("tgp-media-manager").style.maxWidth = "640px";
      }, 300);

      setTimeout(() => {
        newImage.removeAttribute("style");
        newImage.style.position = "relative";
        tgpAddChoosePicBtns(targetModalBody);
        picNamePara.style.transition = "3.6s";
        picNamePara.style.opacity = 1;
      }, 1000);
    }, 400);
  });
}

function tgpAddChoosePicBtns(targetModalBody) {
  const choosePicBtnsPara = document.createElement("p");
  choosePicBtnsPara.style.opacity = 0;
  const deletePicBtn = document.createElement("button");
  deletePicBtn.innerHTML = '<i class="fa fa-trash"></i> Delete';
  deletePicBtn.classList.add("tgp-trongate-pages-danger");
  choosePicBtnsPara.appendChild(deletePicBtn);

  deletePicBtn.addEventListener("click", (ev) => {
    tgpInitConfDeletePic(ev);
  });

  const addPicToPageBtn = document.createElement("button");
  addPicToPageBtn.innerHTML = 'Add To Page <i class="fa fa-plus-circle"></i>';
  addPicToPageBtn.classList.add("tgp-trongate-pages-success");
  choosePicBtnsPara.appendChild(addPicToPageBtn);

  addPicToPageBtn.addEventListener("click", (ev) => {
    tgpRestoreSelection();

    const showcaseImg = document.querySelector(
      "#tgp-media-manager .tgp-showcase-img-div img"
    );
    trongatePagesObj.targetNewElLocation = "default";

    const activeEl = trongatePagesObj.activeEl;
    if (activeEl.classList.contains("active-el-highlight")) {
      //image to be added within pre-existing div
      tgpInterceptAddPageElement(showcaseImg.src, "image");
      return;
    } else {
      //add image to page btm
      tgpDestroyImgModalAddImg(showcaseImg.src, targetModalBody);
    }
  });

  targetModalBody.appendChild(choosePicBtnsPara);

  const newPara = document.createElement("p");
  newPara.style.opacity = 0;
  newPara.innerText = "Go Back";
  newPara.classList.add("tgp-fake-link");
  newPara.addEventListener("click", (ev) => {
    tgpRestartImageAdder();
  });

  targetModalBody.appendChild(newPara);

  setTimeout(() => {
    choosePicBtnsPara.style.transition = ".6s cubic-bezier(.4, 0, .2, 1)";
    choosePicBtnsPara.style.opacity = 1;
    newPara.style.transition = ".6s cubic-bezier(.4, 0, .2, 1)";
    newPara.style.opacity = 1;
  }, 1);
}

function tgpInitConfDeletePic(ev) {
  const tgpPicBtnsPara = document.querySelector(
    "#tgp-media-manager > div.modal-body > p:nth-child(3)"
  );
  const targetModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body"
  );

  tgpPicBtnsPara.style.display = "none";

  const formWrapper = document.createElement("div");
  formWrapper.setAttribute("id", "tg-conf-delete-img-form");

  const codeInput = document.createElement("input");
  codeInput.type = "text";
  codeInput.setAttribute("placeholder", "- - - -");
  codeInput.setAttribute("maxlength", 4);
  codeInput.setAttribute("id", "tgp-delete-img-code-input");
  codeInput.setAttribute("autocomplete", "off");
  codeInput.style.width = "4em";
  codeInput.style.borderRadius = "4px 0 0 4px";

  const deleteBtn = document.createElement("button");
  deleteBtn.innerHTML = '<i class="fa fa-trash"></i> Delete Now';
  deleteBtn.style.borderRadius = "0 4px 4px 0";
  deleteBtn.setAttribute("id", "tg-delete-img-now-btn");
  deleteBtn.classList.add("tgp-trongate-pages-danger");
  deleteBtn.addEventListener("click", (ev) => {
    tgpExecuteDeleteImg();
  });

  formWrapper.appendChild(codeInput);
  formWrapper.appendChild(deleteBtn);

  const confDeleteImgInstructions = document.createElement("p");
  tgpDeletePicCode = tgpGenerateRandomCode(4).toUpperCase();
  confDeleteImgInstructions.innerHTML = `To delete, please enter <b>${tgpDeletePicCode}</b> below, then hit 'Delete Now'.`;

  targetModalBody.insertBefore(confDeleteImgInstructions, tgpPicBtnsPara);
  targetModalBody.insertBefore(formWrapper, tgpPicBtnsPara);
}

function tgpExecuteDeleteImg() {
  const tgpDeleteImgCodeInput = document.getElementById(
    "tgp-delete-img-code-input"
  );
  const tgpSubmitedCodeValue = tgpDeleteImgCodeInput.value.toUpperCase();

  if (tgpSubmitedCodeValue === tgpDeletePicCode) {
    tgpSendDeleteImageRequest();
  } else {
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

function tgpSendDeleteImageRequest() {
  //delete image from server
  const tgImgToGo = document.querySelector(
    "#tgp-media-manager > div.modal-body > div > img"
  );
  const tgSrcArray = tgImgToGo.src.split("/");
  const tgFilenameToGo = tgSrcArray.pop();

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
    fileName: tgFilenameToGo,
    currentImgDir: trongatePagesObj.currentImgDir,
  };

  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/submit_delete_image";
  const http = new XMLHttpRequest();
  http.open("delete", targetUrl);
  http.setRequestHeader("Content-type", "application-json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));
  http.onload = function () {
    if (http.status === 200) {
      tgpClearModalBody(targetModalBody);

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
      tgpDrawImageError(http.status, http.responseText, "Delete Failed!");
    }
  };
}

function tgpChooseImgLocation(imgPath, btnPos) {
  switch (btnPos) {
    case "el-location-btn-page-top":
      trongatePagesObj.targetNewElLocation = "page-top";
      break;
    case "el-location-btn-above-selected":
      trongatePagesObj.targetNewElLocation = "above-selected";
      break;
    case "el-location-btn-inside-selected":
      trongatePagesObj.targetNewElLocation = "inside-selected";
      break;
    case "el-location-btn-below-selected":
      trongatePagesObj.targetNewElLocation = "below-selected";
      break;
    case "el-location-btn-page-btm":
      trongatePagesObj.targetNewElLocation = "default";
      break;
    default:
      trongatePagesObj.targetNewElLocation = "default";
  }

  const targetModalBody = document.querySelector(
    "#tgp-intercept-add-el > div.modal-body"
  );
  tgpDestroyImgModalAddImg(imgPath, targetModalBody);
}

function tgpDestroyImgModalAddImg(imgPath, targetModalBody) {
  while (targetModalBody.firstChild) {
    targetModalBody.removeChild(targetModalBody.lastChild);
  }

  let targetModalFooter = document.querySelector(
    "#tgp-media-manager > div.modal-footer"
  );
  if (targetModalFooter) {
    targetModalFooter.remove();
  }

  tgpDrawBigTick(targetModalBody);
  tgpInsertImage(imgPath, 1533);

  const activeEl = trongatePagesObj.activeEl;
  tgpUnhighlightEl(activeEl);
  currentSelectedRange = null;
}

function tgpInsertImage(imgPath, delayTime = 1300) {
  const imgDiv = document.createElement("div");
  const newImg = document.createElement("img");
  newImg.src = imgPath;
  imgDiv.className = "text-div";
  imgDiv.appendChild(newImg);
  tgpInsertElement(imgDiv);
}

function tgpBuildEditImgModal(clickedImg) {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);

  const targetImgSrc = clickedImg.src;
  trongatePagesObj.activeEl = clickedImg;

  const modalId = "tgp-media-manager";
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  modalHeading.innerHTML = '<i class="fa fa-image"></i> Edit Image';

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
    "tgpCloseAndDestroyModal('" + modalId + "', true)"
  );
  modalFooter.appendChild(closeModalBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
    maxWidth: 570,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");
  const imgDiv = document.createElement("div");
  imgDiv.className = "tgp-showcase-img-div";
  imgDiv.style.opacity = 1;

  const img = document.createElement("img");
  img.src = targetImgSrc;
  img.style.position = "relative";

  imgDiv.appendChild(img);

  const p = document.createElement("p");
  p.style.opacity = 1;
  p.style.transition = "all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0s";
  p.style.marginTop = "1em";

  const removeBtn = document.createElement("button");
  removeBtn.className = "tgp-trongate-pages-danger";
  removeBtn.innerHTML = '<i class="fa fa-trash"></i> Remove';
  p.appendChild(removeBtn);

  removeBtn.addEventListener("click", (ev) => {
    //get the parent container for the clickedImg
    const parentNode = clickedImg.parentNode;
    clickedImg.remove();

    //remove parentNode if nothing inside
    if (parentNode.innerHTML === "") {
      parentNode.remove();
    }

    tgpReset([
      "selectedRange",
      "codeviews",
      "customModals",
      "toolbars",
      "activeEl",
    ]);
  });

  const propertiesBtn = document.createElement("button");
  propertiesBtn.innerHTML = 'Properties <i class="fa fa-gears"></i>';
  p.appendChild(propertiesBtn);

  propertiesBtn.addEventListener("click", (ev) => {
    const targetModalBody = document.querySelector(
      "#tgp-media-manager > div.modal-body"
    );
    tgpBuildImagePropertiesForm(targetModalBody);
  });

  const viewCodeBtn = document.createElement("button");
  viewCodeBtn.className = "tgp-neutral";
  viewCodeBtn.innerHTML = 'Code <i class="fa fa-code"></i>';
  p.appendChild(viewCodeBtn);

  viewCodeBtn.addEventListener("click", (ev) => {
    tgpInitCodeViewImg();
  });

  modalBody.appendChild(imgDiv);
  modalBody.appendChild(p);
}

function tgpInitCodeViewImg() {
  const codeBtn = document.querySelector(
    "#tgp-media-manager > div.modal-body > p > button.tgp-neutral"
  );
  const picContainer = document.querySelector(
    "#tgp-media-manager > div.modal-body > div"
  );

  if (codeBtn.classList.contains("active-code-btn-img")) {
    //remove the code view textarea and display the preview pic

    //remove the code view textarea
    const codeViewTextArea = document.querySelector("#tgp-code-view");
    codeViewTextArea.remove();

    //remove the 'active' class from the button
    codeBtn.classList.remove("active-code-btn-img");

    //display the picture again
    picContainer.style.display = "flex";

    //change the width of the modal
    document.getElementById("tgp-media-manager").style.maxWidth = "640px";
  } else {
    //hide the picture that is inside the modal (and create a textarea with code)
    picContainer.style.display = "none";

    //add the textarea
    const newTextArea = document.createElement("textarea");
    newTextArea.id = "tgp-code-view";
    newTextArea.rows = "11";
    newTextArea.cols = "50";
    newTextArea.style.fontSize = "15px !important";

    const styleAttributesToGo = [];
    styleAttributesToGo.push("cursor");

    let activeHTML = tgpReturnCleanHtml(
      trongatePagesObj.activeEl,
      styleAttributesToGo
    );
    newTextArea.value = activeHTML;
    tgpImgOrigSrc = activeHTML;

    document.getElementById("tgp-media-manager").style.maxWidth = "80%";
    picContainer.insertAdjacentElement("afterend", newTextArea);

    newTextArea.addEventListener("input", (ev) =>
      tgpHandleTextareaChange(ev.target)
    );
    newTextArea.addEventListener("change", (ev) =>
      tgpHandleTextareaChange(ev.target)
    );

    //make the code button 'active'
    codeBtn.classList.add("active-code-btn-img");
  }
}

function tgpReturnCleanHtml(targetEl, styleAttributes) {
  const tempEl = targetEl.cloneNode(true); // create a deep clone of the target element to avoid modifying the original

  // loop through the styleAttributes array and remove each attribute if it exists on the temporary element
  for (let i = 0; i < styleAttributes.length; i++) {
    const attr = styleAttributes[i];
    if (tempEl.style[attr]) {
      tempEl.style[attr] = "";
    }
  }

  // remove the style attribute completely if there are no remaining styles defined
  if (
    tempEl.getAttribute("style") === null ||
    tempEl.getAttribute("style") === ""
  ) {
    tempEl.removeAttribute("style");
  }

  return tempEl.outerHTML;
}

function tgpBuildImagePropertiesForm(targetModalBody) {
  //make sure the code is closed
  const codeViewBtn = document.querySelector(
    "#tgp-media-manager > div.modal-body > p > button.tgp-neutral"
  );
  if (codeViewBtn !== null) {
    if (codeViewBtn.classList.contains("active-code-btn-img")) {
      codeViewBtn.click();
    }
  }

  //hide the existing modal content
  const targetModal = targetModalBody.parentNode;
  targetModalBody.classList.add("tgp-hide-element");

  //build some temp modal body content with a properties form
  const tempFormContainer = document.createElement("div");
  tempFormContainer.classList.add("modal-body");
  tempFormContainer.classList.add("modal-body-temp");

  const form = document.createElement("form");
  form.setAttribute("id", "tgp-img-properties-form");

  const alignLabel = document.createElement("label");
  alignLabel.textContent = "Align:";
  form.appendChild(alignLabel);

  const alignSelect = document.createElement("select");
  alignSelect.id = "align";
  alignSelect.name = "align";
  ["left", "right", "none"].forEach((option) => {
    const alignOption = document.createElement("option");
    alignOption.value = option;
    alignOption.textContent = option.charAt(0).toUpperCase() + option.slice(1);
    alignSelect.appendChild(alignOption);
  });

  form.appendChild(alignSelect);

  const marginLabel = document.createElement("label");
  marginLabel.textContent = "Margin:";
  form.appendChild(marginLabel);

  const marginInput = document.createElement("input");
  marginInput.type = "number";
  marginInput.id = "margin";
  marginInput.name = "margin";
  marginInput.setAttribute("autocomplete", "off");
  marginInput.placeholder = "Enter margin size";
  marginInput.required = true;
  const targetEl = trongatePagesObj.activeEl;

  //Set the value of the margin input, based on the current image margin
  if (targetEl && targetEl instanceof HTMLElement && targetEl.style.margin) {
    const marginValue = targetEl.style.margin.match(/\d+/);
    marginInput.value = marginValue ? marginValue[0] : "";
  }
  form.appendChild(marginInput);

  const value = parseInt(marginInput.value);
  if (isNaN(value) || value < 0) {
    marginInput.value = 0;
  }

  marginInput.addEventListener("input", function () {
    if (marginInput.value < 0) {
      marginInput.value = 0;
    }
  });

  const unitLabel = document.createElement("label");
  unitLabel.textContent = "Unit:";
  form.appendChild(unitLabel);

  const unitSelect = document.createElement("select");
  unitSelect.id = "unit";
  unitSelect.name = "unit";
  ["em", "px", "rem"].forEach((option) => {
    const unitOption = document.createElement("option");
    unitOption.value = option;
    unitOption.textContent = option;
    unitSelect.appendChild(unitOption);
  });
  form.appendChild(unitSelect);

  const submitBtnPara = document.createElement("p");
  submitBtnPara.setAttribute("class", "text-right");
  submitBtnPara.style.textAlign = "right";
  form.appendChild(submitBtnPara);

  const goBackButton = document.createElement("button");
  goBackButton.type = "button";
  goBackButton.classList.add("alt");
  goBackButton.innerHTML = '<i class="fa fa-arrow-left"></i> Back';
  submitBtnPara.appendChild(goBackButton);

  goBackButton.addEventListener("click", (ev) => {
    tgpCloseAndCancelImagePropertiesForm();
  });

  const submitButton = document.createElement("button");
  submitButton.type = "button";
  submitButton.innerHTML = '<i class="fa fa-check"></i> Apply Changes';
  submitBtnPara.appendChild(submitButton);
  submitButton.classList.add("tgp-trongate-pages-success");

  submitButton.addEventListener("click", (ev) => {
    tgpApplyImgProperties(targetEl);
  });

  tempFormContainer.appendChild(form);
  targetModalBody.insertAdjacentElement("afterend", tempFormContainer);
}

function tgpCloseAndCancelImagePropertiesForm() {
  const modalBodyTemps = document.getElementsByClassName("modal-body-temp");
  for (var i = modalBodyTemps.length - 1; i >= 0; i--) {
    modalBodyTemps[i].remove();
  }

  const originalModalBody = document.querySelector(
    "#tgp-media-manager > div.modal-body.tgp-hide-element"
  );
  originalModalBody.classList.remove("tgp-hide-element");
}

function tgpApplyImgProperties(targetEl) {
  const form = document.getElementById("tgp-img-properties-form");

  // Read the values from the form
  const align = form.align.value;
  const margin = form.margin.value;
  const unit = form.unit.value;

  // Find the closest parent element with a class of 'text-div'
  const parentContainer = targetEl.closest(".text-div");
  parentContainer.style.overflow = "hidden";

  targetEl.style.float = align;

  // Apply any other rules expressed in the form to the target element (i.e., the image)
  targetEl.style.margin = `${margin}${unit}`;

  if (align === "left") {
    targetEl.style.margin = `0 ${margin}${unit} ${margin}${unit} 0`;
  } else if (align === "right") {
    targetEl.style.margin = `0 0 ${margin}${unit} ${margin}${unit}`;
  } else {
    targetEl.style.margin = `${margin}${unit}`;
  }

  tgpCloseAndDestroyModal("tgp-media-manager", true);
}

function tgpHandleTextareaChange(targetTextarea) {
  // Handle textarea change event
  const targetTextareaValue = targetTextarea.value;

  if (targetTextareaValue === tgpImgOrigSrc) {
    const submitImgChangesBtn = document.getElementById(
      "submit-update-image-changes"
    );
    if (submitImgChangesBtn !== null) {
      submitImgChangesBtn.remove();
    }
  } else {
    tgpAddSubmitImgChangesBtn();
  }
}

function tgpAddSubmitImgChangesBtn() {
  const submitImgChangesBtn = document.getElementById(
    "submit-update-image-changes"
  );

  if (submitImgChangesBtn !== null) {
    return;
  }

  const targetContainer = document.querySelector(
    "#tgp-media-manager > div.modal-body > p"
  );
  const button = document.createElement("button");
  button.className = "tgp-trongate-pages-success";
  button.textContent = "Submit ";
  button.setAttribute("id", "submit-update-image-changes");
  const icon = document.createElement("i");
  icon.className = "fa fa-check";
  button.appendChild(icon);
  targetContainer.appendChild(button);

  button.addEventListener("click", (ev) => {
    tgpImgUpdateAhoy();
  });
}

function tgpAddPageElementInner(elType) {
  //remember the selected range
  const selection = window.getSelection();
  trongatePagesObj.storedRange = selection.getRangeAt(0);
  tgpReset(["toolbars"]);
  tgpAddPageElement(elType);
}

function tgpImgUpdateAhoy(closeTgModal = true) {
  const codeViewTextArea = document.querySelector(
    "#tgp-media-manager #tgp-code-view"
  );
  const newSource = codeViewTextArea.value;
  closeModal();
  const activeEl = trongatePagesObj.activeEl;
  activeEl.outerHTML = newSource;
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
}
