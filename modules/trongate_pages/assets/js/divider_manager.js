function tgpInsertDivider(targetParentEl) {
  tgpReset([
    "selectedRange",
    "codeviews",
    "customModals",
    "toolbars",
    "activeEl",
  ]);
  const divider = document.createElement("hr");
  trongatePagesObj.targetNewElLocation = "default";
  tgpInsertElement(divider);
}

function tgpAddPointersToHrs() {
  const parentElement = trongatePagesObj.defaultActiveElParent;

  parentElement.addEventListener("mouseover", (event) => {
    if (event.target.matches("hr")) {
      event.target.style.cursor = "pointer";
    }
  });

  parentElement.addEventListener("click", (event) => {
    if (event.target.matches("hr")) {
      trongatePagesObj.activeEl = event.target;
      tgpAddDividerEditor(trongatePagesObj.activeEl);
    }
  });
}

function tgpAddDividerEditor(targetEl) {
  tgpReset(["codeviews", "toolbars", "customModals"]);
  trongatePagesObj.activeEl = targetEl;
  let editor = document.createElement("div");
  let divLeft = document.createElement("div");
  divLeft.setAttribute("id", "trongate-editor-toolbar");
  editor.appendChild(divLeft);
  let divRight = document.createElement("div");
  editor.appendChild(divRight);

  tgpBuildCodeBtn(divLeft);
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
