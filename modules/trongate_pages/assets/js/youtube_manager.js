function tgpBuildInsertYouTubeVideoModal() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  const modalId = "tgp-youtube-modal";

  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");
  modalHeading.innerHTML =
    '<i class="fa fa-youtube-play"></i> Add YouTube Video';

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

  // Add a submit button
  const submitBtn = document.createElement("button");
  submitBtn.setAttribute("type", "button");
  submitBtn.setAttribute("onclick", "tgpSubmitVideoId()");
  submitBtn.setAttribute("id", "tgp-submit-video-id-btn");
  submitBtn.innerText = "Submit";
  modalFooter.appendChild(submitBtn);

  const modalOptions = {
    modalHeading,
    modalFooter,
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");

  const infoPara = document.createElement("p");
  infoParaText = document.createTextNode(
    "Enter the ID or URL of the target YouTube video then hit `Submit`."
  );
  infoPara.appendChild(infoParaText);
  infoPara.setAttribute("class", "text-center");
  modalBody.appendChild(infoPara);

  const videoForm = document.createElement("form");
  videoForm.setAttribute("class", "video-form");
  modalBody.appendChild(videoForm);

  var videoInputField = document.createElement("input");
  videoInputField.setAttribute("type", "text");
  videoInputField.setAttribute("id", "video-input-field");
  videoInputField.setAttribute("placeholder", "Enter video ID or URL here...");
  videoInputField.setAttribute("autocomplete", "off");
  //videoInputField.value = 'zU83StzOM5U';
  videoForm.appendChild(videoInputField);
}

function tgpSubmitVideoId() {
  //remove the submit button
  const submitBtn = document.getElementById("tgp-submit-video-id-btn");
  submitBtn.remove();

  //read the value from the form field
  const formInput = document.getElementById("video-input-field");
  videoId = formInput.value;

  const modalBody = document.querySelector(
    "#tgp-youtube-modal > div.modal-body"
  );

  //clear the modal body
  while (modalBody.firstChild) {
    modalBody.removeChild(modalBody.lastChild);
  }

  //build a new flashing headline for the modal body
  const newModalHeadline = document.createElement("p");
  newModalHeadline.innerHTML = "Please Wait";
  newModalHeadline.setAttribute("class", "blink");
  newModalHeadline.style.fontSize = "1.2em";
  newModalHeadline.style.fontWeight = "bold";
  newModalHeadline.style.marginTop = "0.6em";
  modalBody.appendChild(newModalHeadline);

  //build a spinner
  const spinner = document.createElement("div");
  spinner.setAttribute("class", "spinner mt-3 mb-3");
  modalBody.appendChild(spinner);

  const params = {
    video_id: videoId,
  };

  const targetUrl =
    trongatePagesObj.baseUrl + "trongate_pages/check_youtube_video_id";
  const http = new XMLHttpRequest();
  http.open("post", targetUrl);
  http.setRequestHeader("Content-type", "application-json");
  http.setRequestHeader("trongateToken", trongatePagesObj.trongatePagesToken);
  http.send(JSON.stringify(params));
  http.onload = function () {
    if (http.status === 200) {
      tgpBuildConfirmVideo(modalBody, http.responseText);
    } else {
      tgpProcValidationErr(
        modalBody,
        http.status,
        "You did not submit a valid YouTube video ID"
      );
    }
  };
}

function tgpBuildConfirmVideo(modalBody, youTubeVideoId) {
  const modalHeadline = document.querySelector(
    "#tgp-youtube-modal > div.modal-body > p"
  );
  modalHeadline.innerHTML = "Does this look okay?";
  modalHeadline.classList.remove("blink");

  const spinnerEl = document.querySelector(
    "#tgp-youtube-modal > div.modal-body > div"
  );
  spinnerEl.remove();

  tgpBuildYouTubeVideoPlayer(modalBody, youTubeVideoId);

  // Build conf form
  const confirmVideoPara = document.createElement("div");
  confirmVideoPara.setAttribute("class", "text-center");

  const confirmBtnYes = document.createElement("button");
  confirmBtnYes.innerHTML = "Yes - That's It!";
  confirmBtnYes.setAttribute(
    "onclick",
    'tgpDestroyModalBuildVideo("' + youTubeVideoId + '")'
  );
  confirmBtnYes.setAttribute("id", "target-youtube-video-id");
  confirmVideoPara.appendChild(confirmBtnYes);

  const confirmBtnNo = document.createElement("button");
  confirmBtnNo.setAttribute("class", "close-window alt");
  confirmBtnNo.setAttribute("type", "button");
  confirmBtnNo.innerHTML = "No";
  confirmBtnNo.setAttribute("onclick", "tgpBuildInsertYouTubeVideoModal()");
  confirmVideoPara.appendChild(confirmBtnNo);
  modalBody.appendChild(confirmVideoPara);
}

function tgpBuildYouTubeVideoPlayer(parentEl, youTubeVideoId) {
  const videoContainerEl = document.createElement("div");
  videoContainerEl.setAttribute("class", "video-container");
  parentEl.appendChild(videoContainerEl);

  const newIframe = document.createElement("iframe");
  newIframe.setAttribute("class", "video");
  newIframe.setAttribute("width", 560);
  newIframe.setAttribute("height", 315);
  newIframe.setAttribute(
    "src",
    "https://www.youtube.com/embed/" + youTubeVideoId
  );
  newIframe.setAttribute("title", "YouTube video player");
  newIframe.setAttribute("frameborder", 0);
  newIframe.setAttribute(
    "allow",
    "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
  );
  newIframe.setAttribute("allowfullscreen", "");
  videoContainerEl.appendChild(newIframe);
}

function tgpDestroyModalBuildVideo(youTubeVideoId) {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  tgpBuildYouTubeVideoPlayer(
    trongatePagesObj.defaultActiveElParent,
    youTubeVideoId
  );
}

function tgpAddOverlayToYoutubeVideos() {
  const parentElement = document.body;
  parentElement.addEventListener("mouseover", (event) => {
    const isInsideModalBody = event.target.closest(".modal-body");
    if (
      !isInsideModalBody &&
      (event.target.matches(".video-container") ||
        event.target.matches(".video"))
    ) {
      tgpBuildVideoOverlay(event.target);
    }
  });

  // Add a scroll event listener to the window object
  window.addEventListener("scroll", () => {
    // Update the position of the overlay to be above the video
    const overlay = document.querySelector("#tgp-video-overlay");
    if (overlay) {
      overlay.remove();
    }
  });

  // Add a resize event listener to the window object
  window.addEventListener("resize", () => {
    // Update the size of the overlay to match the video
    const overlay = document.querySelector("#tgp-video-overlay");
    if (overlay) {
      overlay.remove();
    }
  });
}

function tgpBuildVideoOverlay(divA) {
  trongatePagesObj.targetVideoDiv = divA.parentNode;

  const targetVideoDiv = trongatePagesObj.targetVideoDiv;

  const videoOverlay = document.getElementById("tgp-video-overlay");
  if (videoOverlay) {
    videoOverlay.remove();
  }

  const divAShape = divA.getBoundingClientRect();
  const divATop = divAShape.top;
  const divALeft = divAShape.left;

  // Create a new div element
  const divB = document.createElement("div");
  divB.setAttribute("id", "tgp-video-overlay");

  // Set the width and height of divB to be the same as divA
  divB.style.width = divA.offsetWidth + "px";
  divB.style.height = divA.offsetHeight + "px";

  // Set the background color of divB to black with an opacity of .7
  divB.style.backgroundColor = "black";
  divB.style.opacity = "0.7";
  divB.style.cursor = "pointer";

  // Set the position of divB to be absolute and above divA with a high z-index
  divB.style.position = "fixed";
  divB.style.top = divATop + "px";
  divB.style.left = divALeft + "px";
  divB.style.zIndex = "999";

  // Add divB to the document body
  document.body.appendChild(divB);

  divB.addEventListener("click", (event) => {
    tgpAddVideoToolbar(event.target);
  });

  divB.addEventListener("mouseout", (event) => {
    divB.remove();
  });
}

function tgpAddVideoToolbar(targetEl) {
  tgpReset(["codeviews", "toolbars", "customModals"]);
  trongatePagesObj.activeEl = trongatePagesObj.targetVideoDiv;
  let editor = document.createElement("div");
  editor.setAttribute("id", "trongate-editor");

  let divLeft = document.createElement("div");
  divLeft.setAttribute("id", "trongate-editor-toolbar");
  editor.appendChild(divLeft);
  let divRight = document.createElement("div");
  editor.appendChild(divRight);
  tgpBuildCodeBtn(divLeft);

  let youtubeRedirectBtn = document.createElement("button");
  youtubeRedirectBtn.addEventListener("click", (ev) => {
    // Extract the video ID from the src attribute
    const targetVideoDiv = trongatePagesObj.targetVideoDiv;
    const iframe = targetVideoDiv.querySelector("iframe");
    const videoSrc = iframe.getAttribute("src");
    const videoIdMatch = videoSrc.match(/\/embed\/([a-zA-Z0-9_-]{11})/);
    const targetVideoId = videoIdMatch ? videoIdMatch[1] : null;

    if (targetVideoId) {
      // Redirect the user to the YouTube video URL
      const youtubeUrl = "https://www.youtube.com/watch?v=" + targetVideoId;
      window.location.href = youtubeUrl;
    } else {
      tgpReset([
        "selectedRange",
        "codeviews",
        "customModals",
        "toolbars",
        "activeEl",
      ]);
    }
  });

  youtubeRedirectBtn.setAttribute("id", "youtube-redirect-btn");
  youtubeRedirectBtn.innerHTML = "Code <i class='fa fa-code'></i>";
  youtubeRedirectBtn.style.fontWeight = 100;
  youtubeRedirectBtn.style.fontSize = "17px";
  youtubeRedirectBtn.style.height = "100%";
  youtubeRedirectBtn.innerHTML =
    "Watch On YouTube <i class='fa fa-youtube-play'></i>";
  divLeft.appendChild(youtubeRedirectBtn);

  const activeEl = trongatePagesObj.activeEl;
  tgpBuildTrashifyBtn(divRight);

  const body = trongatePagesObj.pageBody;
  body.append(editor);

  const elementHTML = targetEl.outerHTML;
  const topIndex = elementHTML.indexOf("top:");
  const semicolonIndex = elementHTML.indexOf(";", topIndex);
  const topValue = elementHTML.slice(topIndex + 4, semicolonIndex).trim();
  const topNumericValue = parseFloat(topValue);

  let editorRect = editor.getBoundingClientRect();
  let targetYPos = topNumericValue;

  if (targetYPos < 0) {
    targetYPos = 0;
  }

  editor.style.top = targetYPos + "px";
  tgpInitEditorListeners(editor);
  tgpStartTimer();
}
