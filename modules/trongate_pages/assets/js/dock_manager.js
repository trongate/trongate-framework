function tgpInitDockPos() {
  let dockPos = localStorage.getItem("dockPos");
  if (!dockPos) {
    dockPos = "top";
  }
  tgpChangeDockPos(dockPos);
}

function tgpChangeDockPos(newPos) {
  const dockIcons = document.getElementsByClassName("dock-icon");
  const targetClass = "dock-to-" + newPos;

  Array.from(dockIcons).forEach((dockIcon) => {
    dockIcon.classList.toggle(
      "dock-active",
      dockIcon.classList.contains(targetClass)
    );
  });

  const editorDock = document.getElementById("trongate-pages-dock");
  const dockPositions = ["dock-top", "dock-left", "dock-btm", "dock-right"];
  editorDock.classList.remove(...dockPositions);
  editorDock.classList.add("dock-" + newPos);

  dockPos = newPos;
  if (dockPos === "top") {
    localStorage.removeItem("dockPos");
  } else {
    localStorage.setItem("dockPos", dockPos);
  }
}

function tgpAddDockIcons(rhsDiv) {
  const icons = [
    {
      class: "dock-icon dock-to-top dock-active",
      onclick: 'tgpChangeDockPos("top")',
    },
    { class: "dock-icon dock-to-left", onclick: 'tgpChangeDockPos("left")' },
    { class: "dock-icon dock-to-btm", onclick: 'tgpChangeDockPos("btm")' },
    { class: "dock-icon dock-to-right", onclick: 'tgpChangeDockPos("right")' },
  ];

  const shadedDiv = document.createElement("div");
  shadedDiv.innerHTML = "&nbsp;";
  const unshadedDiv = document.createElement("div");
  unshadedDiv.innerHTML = "&nbsp;";

  icons.forEach((icon) => {
    const iconElement = document.createElement("div");
    iconElement.className = icon.class;
    iconElement.onclick = () => eval(icon.onclick);
    iconElement.appendChild(shadedDiv.cloneNode(true));
    iconElement.appendChild(unshadedDiv.cloneNode(true));
    rhsDiv.appendChild(iconElement);
  });
}

function tgpDrawDock() {
  const editorDock = document.createElement("div");
  editorDock.id = "trongate-pages-dock";
  editorDock.style.opacity = 0;
  editorDock.style.display = "none";

  const pageChildren = trongatePagesObj.pageBody.children;
  const firstPageChild = pageChildren[0];
  trongatePagesObj.pageBody.insertBefore(editorDock, firstPageChild);

  const lhsDiv = document.createElement("div");
  lhsDiv.id = "toolbar-lhs";
  editorDock.appendChild(lhsDiv);

  lhsDiv.innerHTML = `
    <div>
      <button id="tgp-go-back-el" onclick="tgpReturnToManagePages()">
        <i class="fa fa-arrow-circle-left"></i>
      </button>
    </div>
    <div>
      <button id="tgp-create-page-el-btn" onclick="tgpOpenCreatePageEl()">
        <i class="fa fa-plus-circle"></i>
      </button>
    </div>
    <div>
      <button id="tgp-save-page-btn" onmousedown="tgpSavePage()">
        <i class="fa fa-save"></i>
      </button>
    </div>
    <div>
      <button id="tgp-settings-btn" onclick="tgpOpenSettings()">
        <i class="fa fa-gears"></i>
      </button>
    </div>
    <div>
      <button id="tgp-code-view-btn" onclick="tgpOpenCodeViewModal()">
        <i class="fa fa-code"></i>
      </button>
    </div>
    <div>
      <button id="tgp-delete-page-btn" onclick="tgpDeletePage()">
        <i class="fa fa-trash"></i>
      </button>
    </div>
  `;

  const rhsDiv = document.createElement("div");
  rhsDiv.id = "toolbar-rhs";
  editorDock.appendChild(rhsDiv);

  tgpAddDockIcons(rhsDiv);

  const topBarShape = editorDock.getBoundingClientRect();
  tgpShowTopBar(editorDock);
  tgpDrawDockMobi();
}

function tgpShowTopBar(editorDock) {
  setTimeout(() => {
    editorDock.removeAttribute("style");
    editorDock.style.opacity = 0;
    setTimeout(() => {
      editorDock.style.opacity = 1;
    }, 1);
  }, 200);
}

function tgpDrawDockMobi() {
  const tgpDockMobi = document.createElement("div");
  tgpDockMobi.setAttribute("id", "trongate-pages-dock-mobi");
  tgpDockMobi.style.opacity = 0;
  const pageBody = document.getElementsByTagName("body")[0];
  pageBody.appendChild(tgpDockMobi);

  // Create button element
  const tgpEditBtnMobi = document.createElement("button");

  // Add class to button element
  tgpEditBtnMobi.classList.add("round-button");

  // Create Font Awesome icon element
  const iconElement = document.createElement("i");
  iconElement.classList.add("fa", "fa-edit");
  iconElement.style.fontSize = "1.4em";

  // Append icon element to button
  tgpEditBtnMobi.appendChild(iconElement);

  tgpEditBtnMobi.addEventListener("click", (ev) => {
    tgpEditBtnMobi.setAttribute("onclick", "tgpOpenMobiOptions()");
  });

  // Append button to the document body
  tgpDockMobi.appendChild(tgpEditBtnMobi);

  // Create button element
  const cameraBtn = document.createElement("button");

  // Add class to button element
  cameraBtn.classList.add("round-button");

  cameraBtn.addEventListener("click", (ev) => {
    tgpOpenCameraModal();
  });

  // Create Font Awesome icon element
  const tgpCameraIcon = document.createElement("i");
  tgpCameraIcon.classList.add("fa", "fa-camera");
  tgpCameraIcon.style.fontSize = "1.4em";

  // Append icon element to button
  cameraBtn.appendChild(tgpCameraIcon);

  // Append button to the document body
  tgpDockMobi.appendChild(cameraBtn);

  setTimeout(() => {
    tgpDockMobi.style.opacity = 1;
  }, 1000);
}

function tgpOpenMobiOptions() {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);

  const modalId = "tgp-mobi-options";

  const modalOptions = {
    maxWidth: "100%",
  };

  const customModal = tgpBuildCustomModal(modalId, modalOptions);
  const modalBody = customModal.querySelector(".modal-body");

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
    tgpCloseAndDestroyModal(modalId, true);
  });

  // Append the close button to the close button container
  closeButtonContainer.appendChild(closeButton);
  modalBody.appendChild(closeButtonContainer);

  modalBody.style.textAlign = "center";
  modalBody.style.fontSize = "0.7em";
  modalBody.setAttribute("id", "tgp-mobi-options-grid");

  // Create an array of button configurations
  var buttonConfigs = [
    {
      id: "tgp-go-back-el-mobi",
      onclick: "tgpReturnToManagePages()",
      iconClass: "fa fa-arrow-circle-left",
    },
    {
      id: "tgp-create-page-el-mobi",
      onclick: "tgpOpenCreatePageEl()",
      iconClass: "fa fa-plus-circle",
    },
    {
      id: "tgp-save-page-btn-mobi",
      onclick: "tgpSavePage()",
      iconClass: "fa fa-save",
    },
    {
      id: "tgp-settings-btn-mobi",
      onclick: "tgpOpenSettings()",
      iconClass: "fa fa-gears",
    },
    {
      id: "tgp-code-view-btn-mobi",
      onclick: "tgpOpenCodeViewModal()",
      iconClass: "fa fa-code",
    },
    {
      id: "tgp-delete-page-btn-mobi",
      onclick: "tgpDeletePage()",
      iconClass: "fa fa-trash",
    },
  ];

  // Iterate over the button configurations and create the corresponding elements
  buttonConfigs.forEach(function (config) {
    var buttonDiv = document.createElement("div");
    var button = document.createElement("button");
    button.id = config.id;
    button.setAttribute("onclick", config.onclick);
    var icon = document.createElement("i");
    icon.className = config.iconClass;
    button.appendChild(icon);
    buttonDiv.appendChild(button);
    modalBody.appendChild(buttonDiv);
  });
}
