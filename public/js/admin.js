const body = document.querySelector("body");

function _(elRef) {
  const firstChar = elRef.substring(0, 1);

  if (firstChar === ".") {
    elRef = elRef.replace(/\./g, "");
    return document.getElementsByClassName(elRef);
  } else {
    return document.getElementById(elRef);
  }
}

function setPerPage() {
  const perPageSelector = document.querySelector("#results-tbl select");
  const lastSegment = window.location.pathname.split("/").pop();
  const selectedIndex = perPageSelector.value;
  let targetUrl = `${window.location.protocol}//${window.location.hostname}${window.location.pathname}`;
  targetUrl = targetUrl.replace("/manage/", `/set_per_page/${selectedIndex}/`);
  targetUrl = targetUrl.replace("/manage", `/set_per_page/${selectedIndex}/`);
  window.location.href = targetUrl;
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

    openModal.style.zIndex = -4;
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

function fetchAssociatedRecords(relationName, updateId) {
  const params = {
    relationName,
    updateId,
    callingModule: segment1,
  };

  const targetUrl = `${baseUrl}module_relations/fetch_associated_records`;
  const http = new XMLHttpRequest();
  http.open("post", targetUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));

  http.onload = function () {
    drawAssociatedRecords(params.relationName, JSON.parse(http.responseText));
  };
}

function drawAssociatedRecords(relationName, results) {
  const targetTblId = `${relationName}-records`;
  const targetTbl = document.getElementById(targetTblId);

  while (targetTbl.firstChild) {
    targetTbl.removeChild(targetTbl.lastChild);
  }

  for (const result of results) {
    const recordId = result.id;
    const newTr = document.createElement("tr");
    const newTd = document.createElement("td");
    const tdText = document.createTextNode(result.value);
    newTd.appendChild(tdText);
    newTr.appendChild(newTd);
    const btnCell = document.createElement("td");

    const disBtn = document.createElement("button");
    disBtn.innerHTML = '<i class="fa fa-ban"></i> disassociate';
    disBtn.setAttribute(
      "onclick",
      `openDisassociateModal('${relationName}', ${recordId})`
    );
    disBtn.setAttribute("class", "danger");

    btnCell.appendChild(disBtn);
    newTr.appendChild(btnCell);
    targetTbl.appendChild(newTr);
  }

  populatePotentialAssociations(relationName, results);
}

function populatePotentialAssociations(relationName, results) {
  const params = {
    updateId,
    relationName,
    results,
    callingModule: segment1,
  };

  const fetchAvailableOptionsUrl = `${baseUrl}module_relations/fetch_available_options`;

  const http = new XMLHttpRequest();
  http.open("post", fetchAvailableOptionsUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));
  http.onload = function () {
    const results = JSON.parse(http.responseText);
    const associateBtnId = `${relationName}-create`;
    const associateBtn = document.getElementById(associateBtnId);

    if (results.length > 0) {
      associateBtn.style.display = "block";
      const dropdownId = `${relationName}-dropdown`;
      const targetDropdown = document.getElementById(dropdownId);

      while (targetDropdown.firstChild) {
        targetDropdown.removeChild(targetDropdown.lastChild);
      }

      for (const result of results) {
        const newOption = document.createElement("option");
        newOption.setAttribute("value", result.key);
        newOption.innerHTML = result.value;
        targetDropdown.appendChild(newOption);
      }
    } else {
      associateBtn.style.display = "none";
    }
  };
}

function openDisassociateModal(relationName, recordId) {
  setTimeout(() => {
    const elId = `${relationName}-record-to-go`;
    document.getElementById(elId).value = recordId;
  }, 100);

  const targetModalId = `${relationName}-disassociate-modal`;
  openModal(targetModalId);
}

function disassociate(relationName) {
  closeModal();

  const elId = `${relationName}-record-to-go`;

  const params = {
    updateId: document.getElementById(elId).value,
    relationName,
  };

  const disassociateUrl = `${baseUrl}module_relations/disassociate`;

  const http = new XMLHttpRequest();
  http.open("post", disassociateUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));

  http.onload = function () {
    fetchAssociatedRecords(params.relationName, updateId);
  };
}

function submitCreateAssociation(relationName) {
  const dropdownId = `${relationName}-dropdown`;
  const dropdown = document.getElementById(dropdownId);

  const params = {
    updateId,
    relationName,
    callingModule: segment1,
    value: dropdown.value,
  };

  closeModal();
  const createUrl = `${baseUrl}module_relations/submit`;

  const http = new XMLHttpRequest();
  http.open("post", createUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));

  http.onload = function () {
    fetchAssociatedRecords(params.relationName, params.updateId);
  };
}

if (typeof drawComments === "boolean") {
  const commentsBlock = document.getElementById("comments-block");
  const commentsTbl = document.querySelector("#comments-block > table");

  function submitComment() {
    const textarea = document.querySelector(
      "#comment-modal > div.modal-body > p:nth-child(1) > textarea"
    );
    const comment = textarea.value.trim();

    if (comment === "") {
      return;
    } else {
      textarea.value = "";
      closeModal();

      const params = {
        comment,
        target_table: segment1,
        update_id: updateId,
      };

      const targetUrl = `${baseUrl}api/create/trongate_comments`;
      const http = new XMLHttpRequest();
      http.open("post", targetUrl);
      http.setRequestHeader("Content-type", "application/json");
      http.setRequestHeader("trongateToken", token);
      http.send(JSON.stringify(params));

      http.onload = function () {
        if (http.status === 401) {
          window.location.href = `${baseUrl}trongate_administrators/login`;
        } else if (http.status === 200) {
          fetchComments();
        }
      };
    }
  }

  function fetchComments() {
    const commentsTbl = document.querySelector("#comments-block > table");
    if (commentsTbl === null) {
      return;
    }

    const params = {
      target_table: segment1,
      update_id: updateId,
      orderBy: "date_created",
    };

    const targetUrl = `${baseUrl}api/get/trongate_comments`;
    const http = new XMLHttpRequest();
    http.open("post", targetUrl);
    http.setRequestHeader("Content-type", "application/json");
    http.setRequestHeader("trongateToken", token);
    http.send(JSON.stringify(params));

    http.onload = function () {
      if (http.status === 401) {
        window.location.href = `${baseUrl}trongate_administrators/login`;
      } else if (http.status === 200) {
        while (commentsTbl.firstChild) {
          commentsTbl.removeChild(commentsTbl.lastChild);
        }

        const comments = JSON.parse(http.responseText);
        for (const comment of comments) {
          const tblRow = document.createElement("tr");
          const tblCell = document.createElement("td");
          const pDate = document.createElement("p");
          const pText = document.createTextNode(comment.date_created);
          pDate.appendChild(pText);
          const pComment = document.createElement("p");
          pComment.innerHTML = comment.comment;

          tblCell.appendChild(pDate);
          tblCell.appendChild(pComment);
          tblRow.appendChild(tblCell);
          commentsTbl.appendChild(tblRow);
          commentsBlock.appendChild(commentsTbl);
        }
      }
    };
  }

  function openPicPreview(modalId, picPath) {
    openModal(modalId);
    const targetEl = document.getElementById("preview-pic");
    while (targetEl.firstChild) {
      targetEl.removeChild(targetEl.lastChild);
    }

    const imgPreview = document.createElement("img");
    imgPreview.setAttribute("src", picPath);
    targetEl.appendChild(imgPreview);

    const ditchPicBtn = document.getElementById("ditch-pic-btn");
    let ditchPicBtnText = ditchPicBtn.innerHTML;
    const iconCode = '<i class="fa fa-trash"></i>';
    ditchPicBtnText = ditchPicBtnText.replace(iconCode, "");
    ditchPicBtn.innerHTML = iconCode + ditchPicBtnText;
  }

  function ditchPreviewPic() {
    const el = document.querySelector("div.user-panel.main input[name='login']");
    const previewPic = document.querySelector("#preview-pic img");
    const picPath = previewPic.src;
    const removePicUrl = `${baseUrl}trongate_filezone/upload/${segment1}/${updateId}`;
    const lastSegment = picPath.split("/").pop();
    const elId = `gallery-preview-${lastSegment.replace(".", "-")}`;

    const http = new XMLHttpRequest();
    http.open("DELETE", removePicUrl);
    http.setRequestHeader("Content-type", "application/json");
    http.setRequestHeader("trongateToken", token);
    http.send(picPath);
    http.onload = function () {
      if (http.status === 200) {
        document.getElementById(elId).remove();
      }
    };
    closeModal();
  }

  function refreshPictures(pictures) {
    console.log("response is " + pictures);
    const pics = JSON.parse(pictures);
    const galleryPicsGrid = document.getElementById("gallery-pics");
    while (galleryPicsGrid.firstChild) {
      galleryPicsGrid.removeChild(galleryPicsGrid.lastChild);
    }

    if (pics.length < 1) {
      const para = document.createElement("p");
      para.setAttribute("class", "text-center");
      const paraTxt = "There are currently no gallery pictures for this record.";
      const picsInfo = document.createTextNode(paraTxt);
      para.appendChild(picsInfo);
      galleryPicsGrid.appendChild(para);
      galleryPicsGrid.style.gridTemplateColumns = "repeat(1, 1fr)";
    } else {
      galleryPicsGrid.style.gridTemplateColumns = "repeat(4, 1fr)";
      const targetDirectory = `${baseUrl}${segment1}_pictures/${updateId}/`;
      for (const pic of pics) {
        const picPath = targetDirectory + pic;
        const newDiv = document.createElement("div");
        newDiv.setAttribute(
          "onclick",
          `openPicPreview('preview-pic-modal', '${picPath}')`
        );
        const thumb = document.createElement("img");
        thumb.setAttribute("src", picPath);
        newDiv.appendChild(thumb);
        galleryPicsGrid.appendChild(newDiv);
      }
    }
  }

  fetchComments();
}

let slideNavOpen = false;
const slideNav = document.getElementById("slide-nav");
const main = document.querySelector("main");

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

const slideNavLinks = document.querySelector("#slide-nav ul");
const autoPopulateSlideNav = slideNavLinks.getAttribute("auto-populate");
if (autoPopulateSlideNav === "true") {
  const leftNavLinks = document.querySelector("#left-nav ul");
  if (leftNavLinks !== null) {
    slideNavLinks.innerHTML = leftNavLinks.innerHTML;
  }
}

body.addEventListener("click", (ev) => {
  if (slideNavOpen && ev.target.id !== "open-btn") {
    if (!slideNav.contains(ev.target)) {
      closeSlideNav();
    }
  }
});

document.addEventListener('keydown', (e) => {
  const modalContainer = _("modal-container");
  if (e.key === "Escape" && modalContainer) {
    closeModal();
  }
});