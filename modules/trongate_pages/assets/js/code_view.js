function tgpOpenCodeViewModal() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  tgpRemoveContentEditables();

  const oldCodeViewModal = document.getElementById("tgp-code-view-modal");
  if (oldCodeViewModal) {
    oldCodeViewModal.parentNode.removeChild(oldCodeViewModal);
  }

  const codeViewModal = document.createElement("div");
  codeViewModal.classList.add("modal");
  codeViewModal.setAttribute("id", "tgp-code-view-modal");
  codeViewModal.setAttribute(
    "style",
    "z-index: 4; opacity: 1; margin-top: 12vh;"
  );

  // Create the modal heading element with icon
  const modalHeading = document.createElement("div");
  modalHeading.classList.add("modal-heading");

  const icon = document.createElement("i");
  icon.classList.add("fa", "fa-code");

  const headingText = document.createTextNode(" Code View");
  modalHeading.appendChild(icon);
  modalHeading.appendChild(headingText);

  // Create the form element with action and method attributes
  const formElement = document.createElement("form");

  // Create the modal body element with style and content
  const modalBody = document.createElement("div");
  modalBody.classList.add("modal-body");
  modalBody.setAttribute("style", "text-align: center; font-size: 0.7em;");

  const contentAsCode = document.createElement("textarea");
  contentAsCode.setAttribute("id", "content-as-code");
  contentAsCode.rows = 18;
  contentAsCode.style.opacity = 0;

  setTimeout(() => {
    let pageContent = document.getElementsByClassName("page-content")[0];
    let htmlContent = pageContent.innerHTML;
    htmlContent = htmlContent.replace(/ style=""/g, "");
    htmlContent = htmlContent.replace(/style=""/g, "");

    const params = {
      page_body: htmlContent,
    };

    const targetUrl =
      trongatePagesObj.baseUrl + "trongate_pages/submit_beautify";
    const http = new XMLHttpRequest();
    http.open("post", targetUrl);
    http.setRequestHeader('trongateToken', trongatePagesObj.trongatePagesToken);
    http.setRequestHeader("Content-type", "application-json");
    http.send(JSON.stringify(params));
    http.onload = function () {
      if (http.status === 200) {
        const newTextarea = document.getElementById("content-as-code");
        newTextarea.value = http.responseText;
        newTextarea.style.opacity = 1;
      } else {
        const newTextarea = document.getElementById("content-as-code");
        newTextarea.value =
          document.getElementsByClassName("page-content")[0].innerHTML;
        newTextarea.style.opacity = 1;
      }
    };
  }, 300);

  modalBody.appendChild(contentAsCode);

  // Create the modal footer element with buttons
  const modalFooter = document.createElement("div");
  modalFooter.classList.add("modal-footer", "text-right");

  const cancelButton = document.createElement("button");
  cancelButton.classList.add("close-window", "alt");
  cancelButton.setAttribute("type", "button");
  cancelButton.setAttribute(
    "onclick",
    "closeAndDestoryModal('tgp-code-view-modal')"
  );
  cancelButton.setAttribute(
    "onclick",
    "tgpCloseAndDestroyModal('tgp-code-view-modal', 0)"
  );
  const cancelText = document.createTextNode("Cancel");
  cancelButton.appendChild(cancelText);

  const updatePageContentBtn = document.createElement("button");
  updatePageContentBtn.setAttribute("type", "button");
  updatePageContentBtn.setAttribute("name", "submit");
  updatePageContentBtn.setAttribute("value", "Delete Now");
  updatePageContentBtn.setAttribute("onclick", "tgpUpdatePageContent()");

  const updateBtnText = document.createTextNode("Update Page Content");
  updatePageContentBtn.appendChild(updateBtnText);

  modalFooter.appendChild(cancelButton);
  modalFooter.appendChild(updatePageContentBtn);

  // Add all the elements to the DOM in the proper order
  formElement.appendChild(modalBody);
  formElement.appendChild(modalFooter);

  codeViewModal.appendChild(modalHeading);
  codeViewModal.appendChild(formElement);

  document.body.appendChild(codeViewModal);
  openModal("tgp-code-view-modal");
}

function tgpUpdatePageContent() {
  const contentAsCode = document.getElementById("content-as-code");
  const newHtml = contentAsCode.value;
  const pageContentEl = document.getElementsByClassName("page-content")[0];
  pageContentEl.innerHTML = newHtml;
  closeModal("tgp-code-view-modal");
}

function tgpRemoveCodeView() {
  if (
    document.getElementById("code-btn") &&
    document.getElementById("code-btn").classList.contains("active-editor-btn")
  ) {
    const codeBtn = document.getElementById("code-btn");
    const newActiveEl = trongatePagesObj.activeEl;
    codeBtn.classList.remove("active-editor-btn");
    const editCodeTextarea = document.getElementById("tgp-code-view");
    const textareaValue = editCodeTextarea.value;

    // Create a temporary container element
    const tempContainer = document.createElement("div");
    tempContainer.innerHTML = textareaValue;
    const newElement = tempContainer.firstChild;

    // Replace the parent node's child with the new element
    const parentNode = editCodeTextarea.parentNode;
    parentNode.replaceChild(newElement, editCodeTextarea);
    trongatePagesObj.activeEl = newElement;
    newElement.click();
  }
}
