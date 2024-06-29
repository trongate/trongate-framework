var body = document.querySelector("body");

function _(elRef) {
  var firstChar = elRef.substring(0, 1);

  if (firstChar == ".") {
    elRef = elRef.replace(/\./g, "");
    return document.getElementsByClassName(elRef);
  } else {
    return document.getElementById(elRef);
  }
}

function setPerPage() {
  var perPageSelector = document.querySelector("#results-tbl select");
  var lastSegment = window.location.pathname.split("/").pop();
  var selectedIndex = perPageSelector.value;
  var targetUrl =
    window.location.protocol +
    "//" +
    window.location.hostname +
    window.location.pathname;
  targetUrl = targetUrl.replace(
    "/manage/",
    "/set_per_page/" + selectedIndex + "/"
  );
  targetUrl = targetUrl.replace(
    "/manage",
    "/set_per_page/" + selectedIndex + "/"
  );
  window.location.href = targetUrl;
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

function attemptEscCloseModal() {
  document.onkeydown = function (e) {
    var modalContainer = _("modal-container");

    if (e.key == "Escape" && modalContainer) {
      closeModal();
    }
  };
}

function fetchAssociatedRecords(relationName, updateId) {
  var params = {
    relationName,
    updateId,
    callingModule: segment1,
  };

  var targetUrl = baseUrl + "module_relations/fetch_associated_records";
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
  var targetTblId = relationName + "-records";
  var targetTbl = document.getElementById(targetTblId);

  while (targetTbl.firstChild) {
    targetTbl.removeChild(targetTbl.lastChild);
  }

  for (var i = 0; i < results.length; i++) {
    var recordId = results[i]["id"];
    var newTr = document.createElement("tr");
    var newTd = document.createElement("td");
    var tdText = document.createTextNode(results[i]["value"]);
    newTd.appendChild(tdText);
    newTr.appendChild(newTd);
    var btnCell = document.createElement("td");

    var disBtn = document.createElement("button");
    disBtn.innerHTML = '<i class="fa fa-ban"></i> disassociate';
    disBtn.setAttribute(
      "onclick",
      "openDisassociateModal('" + relationName + "', " + recordId + ")"
    );
    disBtn.setAttribute("class", "danger");

    btnCell.appendChild(disBtn);
    newTr.appendChild(btnCell);
    targetTbl.appendChild(newTr);
  }

  populatePotentialAssociations(relationName, results);
}

function populatePotentialAssociations(relationName, results) {
  var params = {
    updateId: updateId,
    relationName,
    results,
    callingModule: segment1,
  };

  var fetchAvailableOptionsUrl =
    baseUrl + "module_relations/fetch_available_options";

  const http = new XMLHttpRequest();
  http.open("post", fetchAvailableOptionsUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));
  http.onload = function () {
    //repopulate available records
    var results = JSON.parse(http.responseText);
    var associateBtnId = relationName + "-create";
    var associateBtn = document.getElementById(associateBtnId);

    if (results.length > 0) {
      associateBtn.style.display = "block";
      var dropdownId = relationName + "-dropdown";
      var targetDropdown = document.getElementById(dropdownId);

      while (targetDropdown.firstChild) {
        targetDropdown.removeChild(targetDropdown.lastChild);
      }

      for (var i = 0; i < results.length; i++) {
        var newOption = document.createElement("option");
        newOption.setAttribute("value", results[i]["key"]);
        newOption.innerHTML = results[i]["value"];
        targetDropdown.appendChild(newOption);
      }
    } else {
      associateBtn.style.display = "none";
    }
  };
}

function openDisassociateModal(relationName, recordId) {
  setTimeout(() => {
    var elId = relationName + "-record-to-go";
    document.getElementById(elId).value = recordId;
  }, 100);

  var targetModalId = relationName + "-disassociate-modal";
  openModal(targetModalId);
}

function disassociate(relationName) {
  closeModal();

  //get the id of the record to go
  var elId = relationName + "-record-to-go";

  var params = {
    updateId: document.getElementById(elId).value,
    relationName,
  };

  var disassociateUrl = baseUrl + "module_relations/disassociate";

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
  var dropdownId = relationName + "-dropdown";
  var dropdown = document.getElementById(dropdownId);

  var params = {
    updateId,
    relationName,
    callingModule: segment1,
    value: dropdown.value,
  };

  closeModal();
  var createUrl = baseUrl + "module_relations/submit";

  const http = new XMLHttpRequest();
  http.open("post", createUrl);
  http.setRequestHeader("Content-type", "application/json");
  http.setRequestHeader("trongateToken", token);
  http.send(JSON.stringify(params));

  http.onload = function () {
    fetchAssociatedRecords(params.relationName, params.updateId);
  };
}

if (typeof drawComments == "boolean") {
  var commentsBlock = document.getElementById("comments-block");
  var commentsTbl = document.querySelector("#comments-block > table");

  function submitComment() {
    var textarea = document.querySelector(
      "#comment-modal > div.modal-body > p:nth-child(1) > textarea"
    );
    var comment = textarea.value.trim();

    if (comment == "") {
      return;
    } else {
      textarea.value = "";
      closeModal();

      var params = {
        comment,
        target_table: segment1,
        update_id: updateId,
      };

      var targetUrl = baseUrl + "api/create/trongate_comments";
      const http = new XMLHttpRequest();
      http.open("post", targetUrl);
      http.setRequestHeader("Content-type", "application/json");
      http.setRequestHeader("trongateToken", token);
      http.send(JSON.stringify(params));

      http.onload = function () {
        if (http.status == 401) {
          //invalid token!
          window.location.href = baseUrl + "trongate_administrators/login";
        } else if (http.status == 200) {
          fetchComments();
        }
      };
    }
  }

  function fetchComments() {
    var commentsTbl = document.querySelector("#comments-block > table");
    if (commentsTbl == null) {
      return;
    }

    var params = {
      target_table: segment1,
      update_id: updateId,
      orderBy: "date_created",
    };

    var targetUrl = baseUrl + "api/get/trongate_comments";
    const http = new XMLHttpRequest();
    http.open("post", targetUrl);
    http.setRequestHeader("Content-type", "application/json");
    http.setRequestHeader("trongateToken", token);
    http.send(JSON.stringify(params));

    http.onload = function () {
      if (http.status == 401) {
        //invalid token!
        window.location.href = baseUrl + "trongate_administrators/login";
      } else if (http.status == 200) {
        while (commentsTbl.firstChild) {
          commentsTbl.removeChild(commentsTbl.lastChild);
        }

        var comments = JSON.parse(http.responseText);
        for (var i = 0; i < comments.length; i++) {
          var tblRow = document.createElement("tr");
          var tblCell = document.createElement("td");
          var pDate = document.createElement("p");
          var pText = document.createTextNode(comments[i]["date_created"]);
          pDate.appendChild(pText);
          var pComment = document.createElement("p");
          var commentText = comments[i]["comment"];
          pComment.innerHTML = commentText;

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
    var targetEl = document.getElementById("preview-pic");
    while (targetEl.firstChild) {
      targetEl.removeChild(targetEl.lastChild);
    }

    var imgPreview = document.createElement("img");
    imgPreview.setAttribute("src", picPath);
    targetEl.appendChild(imgPreview);

    var ditchPicBtn = document.getElementById("ditch-pic-btn");
    var ditchPicBtnText = ditchPicBtn.innerHTML;
    var iconCode = '<i class="fa fa-trash"></i>';
    ditchPicBtn.innerHTML = ditchPicBtnText.replace(iconCode, "");
    ditchPicBtn.innerHTML = iconCode + ditchPicBtn.innerHTML;
  }

  function ditchPreviewPic() {
    var el = document.querySelector("div.user-panel.main input[name='login']");
    var previewPic = document.querySelector("#preview-pic img");
    var picPath = previewPic.src;
    var removePicUrl =
      baseUrl + "trongate_filezone/upload/" + segment1 + "/" + updateId;
    var lastSegment = picPath.split("/").pop();
    var elId = "gallery-preview-" + lastSegment.replace(".", "-");

    const http = new XMLHttpRequest();
    http.open("DELETE", removePicUrl);
    http.setRequestHeader("Content-type", "application/json");
    http.setRequestHeader("trongateToken", token);
    http.send(picPath);
    http.onload = function () {
      if (http.status == 200) {
        document.getElementById(elId).remove();
      }
    };
    closeModal();
  }

  function refreshPictures(pictures) {
    console.log("response is " + pictures);
    var pics = JSON.parse(pictures);
    var galleryPicsGrid = document.getElementById("gallery-pics");
    while (galleryPicsGrid.firstChild) {
      galleryPicsGrid.removeChild(galleryPicsGrid.lastChild);
    }

    if (pics.length < 1) {
      var para = document.createElement("p");
      para.setAttribute("class", "text-center");
      var paraTxt = "There are currently no gallery pictures for this record.";
      var picsInfo = document.createTextNode(paraTxt);
      para.appendChild(picsInfo);
      galleryPicsGrid.appendChild(para);
      galleryPicsGrid.style.gridTemplateColumns = "repeat(1, 1fr)";
    } else {
      galleryPicsGrid.style.gridTemplateColumns = "repeat(4, 1fr)";
      var targetDirectory = baseUrl + segment1 + "_pictures/" + updateId + "/";
      for (var i = 0; i < pics.length; i++) {
        var picPath = targetDirectory + pics[i];
        var newDiv = document.createElement("div");
        newDiv.setAttribute(
          "onclick",
          "openPicPreview('preview-pic-modal', '" + picPath + "')"
        );
        var thumb = document.createElement("img");
        thumb.setAttribute("src", picPath);
        newDiv.appendChild(thumb);
        galleryPicsGrid.appendChild(newDiv);
      }
    }
  }

  fetchComments();
}

var slideNavOpen = false;
var slideNav = document.getElementById("slide-nav");
var main = document.getElementsByTagName("main")[0];

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

var slideNavLinks = document.querySelector("#slide-nav ul");
var autoPopulateSlideNav = slideNavLinks.getAttribute("auto-populate");
if (autoPopulateSlideNav == "true") {
  var leftNavLinks = document.querySelector("#left-nav ul");
  if (leftNavLinks !== null) {
    slideNavLinks.innerHTML = leftNavLinks.innerHTML;
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

attemptEscCloseModal();
