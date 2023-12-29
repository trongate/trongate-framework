let tgpEditorTimer; // Variable to store the timer
let removeUponScrollAllowed = true;

function tgpAddHeadlineToolbar(targetEl) {
  const headlineElement = tgpFindAncestorHeadline(targetEl);
  if (headlineElement) {
    tgpReset(["codeviews", "toolbars", "customModals"]);
    trongatePagesObj.activeEl = headlineElement;
    let editor = document.createElement("div");
    let divLeft = document.createElement("div");
    divLeft.setAttribute("id", "trongate-editor-toolbar");
    editor.appendChild(divLeft);
    let divRight = document.createElement("div");
    editor.appendChild(divRight);

    tgpBuildCodeBtn(divLeft);
    tgpBuildItalicBtn(divLeft);
    tgpBuildAlignLeftBtn(divLeft);
    tgpBuildAlignCenterBtn(divLeft);
    tgpBuildAlignRightBtn(divLeft);
    tgpBuildAlignJustifyBtn(divLeft);

    const headings = [
      { value: "", text: "Select tag..." },
      { value: "h1", text: "Heading 1 (h1)" },
      { value: "h2", text: "Heading 2 (h2)" },
      { value: "h3", text: "Heading 3 (h3)" },
      { value: "h4", text: "Heading 4 (h4)" },
      { value: "h5", text: "Heading 5 (h5)" },
    ];

    const select = document.createElement("select");
    select.id = "tgp-heading-select";

    for (const heading of headings) {
      const option = document.createElement("option");
      option.value = heading.value;
      option.text = heading.text;
      select.appendChild(option);
    }

    divLeft.appendChild(select);

    select.addEventListener("change", () => {
      const selectedOption = select.value;
      tgpChangeTagType(selectedOption);
    });

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
}

function tgpInitEditorListeners(editor) {
  // Remove any pre-existing event listeners
  // Remove any pre-existing event listeners
  editor.removeEventListener("mousemove", tgpResetTimer);
  editor.removeEventListener("mousedown", tgpResetTimer);
  editor.removeEventListener("keydown", tgpResetTimer);

  document.removeEventListener("mousemove", tgpResetTimer);
  document.removeEventListener("mouseover", tgpResetTimer);
  document.removeEventListener("mouseout", tgpResetTimer);
  document.removeEventListener("mouseenter", tgpResetTimer);
  document.removeEventListener("mouseleave", tgpResetTimer);

  // Event listeners to track user activity within the editor
  editor.addEventListener("mousemove", tgpResetTimer);
  editor.addEventListener("mousedown", tgpResetTimer);
  editor.addEventListener("keydown", tgpResetTimer);
  editor.addEventListener("keyup", tgpResetTimer); // New condition

  document.addEventListener("mousemove", tgpResetTimer);
  document.addEventListener("mouseover", tgpResetTimer);
  document.addEventListener("mouseout", tgpResetTimer);
  document.addEventListener("mouseenter", tgpResetTimer);
  document.addEventListener("mouseleave", tgpResetTimer);
  document.addEventListener("keydown", tgpResetTimer); // New condition
  document.addEventListener("keyup", tgpResetTimer); // New condition
}

function tgpStartTimer() {
  // Clear any existing timer
  clearTimeout(tgpEditorTimer);

  // Start a new timer with a duration of five seconds
  tgpEditorTimer = setTimeout(tgpDestroyEditor, 5000);
}

function tgpDestroyEditor() {
  const tgpEditor = document.getElementById("trongate-editor");

  if (!tgpEditor) {
    return;
  }

  const codeViewElement = document.getElementById("tgp-code-view");
  const modalContainer = document.getElementById("modal-container");

  if (codeViewElement || modalContainer) {
    return;
  }

  if (!codeViewElement) {
    tgpReset([
      "selectedRange",
      "codeviews",
      "customModals",
      "toolbars",
      "activeEl",
    ]);
  }
}

function tgpResetTimer() {
  // Reset the timer back to three seconds
  tgpStartTimer();
}

function tgpGetAllEditorBtns() {
  let btnsParent = document.querySelector("#trongate-editor > div");
  let allEditorBtns = btnsParent.children;
  return allEditorBtns;
}

function tgpFindAncestorHeadline(element) {
  if (
    element.tagName === "H1" ||
    element.tagName === "H2" ||
    element.tagName === "H3" ||
    element.tagName === "H4" ||
    element.tagName === "H5" ||
    element.tagName === "H6"
  ) {
    return element;
  }
  if (element.parentElement) {
    return tgpFindAncestorHeadline(element.parentElement);
  }
  return null;
}

function tgpRemoveToolbars() {
  const editorElements = document.querySelectorAll("#trongate-editor");
  editorElements.forEach((element) => {
    element.remove();
  });
}

function tgpBuildCodeBtn(containerEl) {
  const codeBtn = document.createElement("button");
  codeBtn.setAttribute("onclick", "tgpToggleCodeView()");
  codeBtn.setAttribute("id", "code-btn");
  codeBtn.innerHTML = "Code <i class='fa fa-code'></i>";
  containerEl.appendChild(codeBtn);
}

function tgpBuildBoldBtn(containerEl) {
  const boldBtn = document.createElement("button");
  boldBtn.setAttribute("onclick", "tgpBoldify()");
  boldBtn.setAttribute("id", "boldify-btn");
  boldBtn.innerHTML = "B";
  containerEl.appendChild(boldBtn);
}

function tgpBuildItalicBtn(containerEl) {
  const italicBtn = document.createElement("button");
  italicBtn.setAttribute("onclick", "tgpItalicify()");
  italicBtn.setAttribute("id", "italicify-btn");
  italicBtn.innerHTML = "I";
  containerEl.appendChild(italicBtn);
}

function tgpAttemptActivateItalicBtn(selectedRange) {
  //get all of the italic nodes within the active element
  let italicNodes = trongatePagesObj.activeEl.getElementsByTagName("i");

  //get an array of all of the italic nodes that intersect the selected range
  let resultObj = tgpIntersectsRange(selectedRange, italicNodes);

  if (resultObj.tgpIntersectsRange == true) {
    let targetBtn = document.getElementById("italicify-btn");
    targetBtn.classList.add("active-editor-btn");
  }
}

function tgpBuildAlignLeftBtn(containerEl) {
  const alignLeftBtn = document.createElement("button");
  alignLeftBtn.setAttribute("onclick", "tgpAlignify('left')");
  alignLeftBtn.setAttribute("id", "alignify-left-btn");
  alignLeftBtn.innerHTML = "<i class='fa fa-align-left'></i>";
  containerEl.appendChild(alignLeftBtn);
}

function tgpBuildAlignCenterBtn(containerEl) {
  const alignCenterBtn = document.createElement("button");
  alignCenterBtn.setAttribute("onclick", "tgpAlignify('center')");
  alignCenterBtn.setAttribute("id", "alignify-center-btn");
  alignCenterBtn.innerHTML = "<i class='fa fa-align-center'></i>";
  containerEl.appendChild(alignCenterBtn);
}

function tgpBuildAlignRightBtn(containerEl) {
  const alignRightBtn = document.createElement("button");
  alignRightBtn.setAttribute("onclick", "tgpAlignify('right')");
  alignRightBtn.setAttribute("id", "alignify-right-btn");
  alignRightBtn.innerHTML = "<i class='fa fa-align-right'></i>";
  containerEl.appendChild(alignRightBtn);
}

function tgpBuildAlignJustifyBtn(containerEl) {
  const alignJustifyBtn = document.createElement("button");
  alignJustifyBtn.setAttribute("onclick", "tgpAlignify('justify')");
  alignJustifyBtn.setAttribute("id", "alignify-justify-btn");
  alignJustifyBtn.innerHTML = "<i class='fa fa-align-justify'></i>";
  containerEl.appendChild(alignJustifyBtn);
}

function tgpBuildTrashifyBtn(containerEl) {
  const trashBtn = document.createElement("button");
  trashBtn.setAttribute("onclick", "tgpTrashify()");
  trashBtn.innerHTML = "<i class='fa fa-trash'></i>";
  containerEl.appendChild(trashBtn);
}

function tgpBuildLinkifyBtn(containerEl) {
  const linkBtn = document.createElement("button");
  linkBtn.setAttribute("onclick", "tgpOpenLinkModal()");
  linkBtn.setAttribute("id", "linkify-btn");
  linkBtn.innerHTML = "<i class='fa fa-link'></i>";
  containerEl.appendChild(linkBtn);
}

function tgpBuildListifyBtns(containerEl) {
  const olBtn = document.createElement("button");
  olBtn.setAttribute("onclick", "tgpListify('ol')");
  olBtn.innerHTML = "<i class='fa fa-list-ol'></i>";
  containerEl.appendChild(olBtn);

  const ulBtn = document.createElement("button");
  ulBtn.setAttribute("onclick", "tgpListify('ul')");
  ulBtn.innerHTML = "<i class='fa fa-list-ul'></i>";
  containerEl.appendChild(ulBtn);
}

function tgpBuildPicBtn(containerEl) {
  const addImageBtn = document.createElement("button");
  addImageBtn.setAttribute("onclick", "tgpAddPageElementInner('Image')");
  addImageBtn.innerHTML = "<i class='fa fa-image'></i>";
  containerEl.appendChild(addImageBtn);
}

function tgpToggleCodeView() {
  removeUponScrollAllowed = false;
  const activeEl = trongatePagesObj.activeEl;
  const codeBtn = document.getElementById("code-btn");
  const editCodeTextarea = document.getElementById("tgp-code-view");

  if (codeBtn && codeBtn.classList.contains("active-editor-btn")) {
    // Remove code view
    tgpRemoveCodeView();
  } else {
    // Init code view
    const html = activeEl.outerHTML;
    tgpInitCodeView(codeBtn);
  }
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

    setTimeout(() => {
      tgpMakeSurePositionsGood(newElement);
    }, 1);
  }
}

function tgpInitCodeView(codeBtn = null) {
  const activeEl = trongatePagesObj.activeEl;

  // Get the HTML content of the active element
  const html = trongatePagesObj.activeEl.outerHTML;
  cleanedString = html.replace(/ contenteditable="true"/g, "");

  // Modify the cleaned string for <hr> elements with cursor: pointer
  const tempDiv = document.createElement("div");
  tempDiv.innerHTML = cleanedString;
  const hrElements = tempDiv.getElementsByTagName("hr");
  for (let i = 0; i < hrElements.length; i++) {
    const hrElement = hrElements[i];
    if (hrElement.style.cursor === "pointer") {
      hrElement.style.removeProperty("cursor");
    }
    if (!hrElement.getAttribute("style")) {
      hrElement.removeAttribute("style");
    }
  }
  cleanedString = tempDiv.innerHTML;
  codeBtn.classList.add("active-editor-btn");

  // Create a text area element
  const textarea = document.createElement("textarea");
  textarea.setAttribute("id", "tgp-code-view");
  textarea.setAttribute("rows", "10");
  textarea.setAttribute("cols", "50");

  // Set the text area value to the HTML content
  textarea.value = cleanedString;
  const strLength = cleanedString.length;
  const numRows = tgpCalcTANumRows(strLength);
  textarea.rows = numRows;

  // Replace the active element with the text area
  trongatePagesObj.activeEl.parentNode.replaceChild(
    textarea,
    trongatePagesObj.activeEl
  );

  setTimeout(() => {
    const belowElement = document.getElementById("tgp-code-view");
    tgpMakeSurePositionsGood(belowElement);
  }, 1);
}

function tgpCalcTANumRows(strLength) {
  // Calculate number of rows required for div
  let numRows;
  switch (true) {
    case strLength <= 76:
      numRows = 1;
      break;
    case strLength <= 130:
      numRows = 2;
      break;
    case strLength <= 250:
      numRows = 3;
      break;
    case strLength <= 400:
      numRows = 5;
      break;
    case strLength <= 500:
      numRows = 6;
      break;
    case strLength <= 600:
      numRows = 8;
      break;
    default:
      numRows = 11;
  }
  return numRows;
}

function tgpMakeSurePositionsGood(belowElement) {
  const editorElement = document.getElementById("trongate-editor");
  //const belowElement = document.getElementById('tgp-code-view');

  if (editorElement && belowElement) {
    const codeViewRect = belowElement.getBoundingClientRect();

    editorElement.style.position = "fixed";

    const newTop = Math.max(0, codeViewRect.top - editorElement.offsetHeight);
    editorElement.style.top = `${newTop}px`;
  }

  removeUponScrollAllowed = true;
}

function tgpMakeSurePositionsGoodORIG() {
  const editorElement = document.getElementById("trongate-editor");
  const codeViewElement = document.getElementById("tgp-code-view");

  if (editorElement && codeViewElement) {
    const editorBottom = editorElement.getBoundingClientRect().bottom;
    const codeViewTop = codeViewElement.getBoundingClientRect().top;

    if (editorBottom > codeViewTop) {
      const overlap = editorBottom - codeViewTop;
      codeViewElement.style.transition = "0.1s";
      codeViewElement.style.marginTop = overlap + "px";
    }
  }
}

function tgpChangeTagType(tagType) {
  //e.g., swap out an H1 for an H2
  if (tagType === "") {
    return;
  }

  if (
    trongatePagesObj.activeEl &&
    trongatePagesObj.activeEl.tagName.startsWith("H")
  ) {
    const newElement = document.createElement(tagType);
    newElement.innerHTML = trongatePagesObj.activeEl.innerHTML;
    trongatePagesObj.activeEl.parentNode.replaceChild(
      newElement,
      trongatePagesObj.activeEl
    );
    trongatePagesObj.activeEl = newElement;
    newElement.click();
  }
}

function tgpAttemptActivateToolBarBtns() {
  //make sure we have a selection that is INSIDE the active element?
  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    currentSelectedRange = selection.getRangeAt(0).cloneRange();
  }

  if (!selection || selection.rangeCount === 0) {
    return;
  }

  let selectedRange = selection.getRangeAt(0);
  var intersectsActiveEl = selectedRange.intersectsNode(
    trongatePagesObj.activeEl
  );

  if (intersectsActiveEl !== true) {
    return;
  }

  let allEditorBtns = tgpGetAllEditorBtns();
  for (let i = 0; i < allEditorBtns.length; i++) {
    const targetBtn = allEditorBtns[i];
    const btnId = targetBtn["id"];

    switch (btnId) {
      case "boldify-btn":
        tgpAttemptActivateBoldBtn(selectedRange);
        break;
      case "italicify-btn":
        tgpAttemptActivateItalicBtn(selectedRange);
        break;
      case "alignify-justify-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-left-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-right-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-center-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "linkify-btn":
        tgpAttemptActivateLinkifyBtn(selectedRange);
        break;
    }
  }
}

function tgpGetAllEditorBtns() {
  let btnsParent = document.querySelector("#trongate-editor > div");
  let allEditorBtns = btnsParent.children;
  return allEditorBtns;
}

function tgpAttemptActivateAlignifyBtn(selectedRange, btnId) {
  //get all of the relevant alignified nodes within the active element
  let alignType = btnId.replace("alignify-", "");
  alignType = alignType.replace("-btn", "");
  let styleAttr = tgpGetStyleAttr(alignType);
  let alignNodes = tgpGetAlignNodes(selectedRange, styleAttr);

  //get an array of all of the italic nodes that intersect the selected range
  let resultObj = tgpIntersectsRange(selectedRange, alignNodes);

  if (resultObj.tgpIntersectsRange == true) {
    let targetBtn = document.getElementById(btnId);
    targetBtn.classList.add("active-editor-btn");
  }
}

function tgpGetStyleAttr(alignType) {
  switch (alignType) {
    case "left":
      var styleAttr = "text-align: left";
      break;
    case "right":
      var styleAttr = "text-align: right";
      break;
    case "center":
      var styleAttr = "text-align: center";
      break;
    case "justify":
      var styleAttr = "text-align: justify";
      break;
  }
  return styleAttr;
}

function tgpGetAlignNodes(selectedRange, styleAttr) {
  let targetTextAlign = styleAttr.replace("text-align: ", "");
  let divNodes = trongatePagesObj.activeEl.getElementsByTagName("div");
  let alignNodes = [];
  for (var i = 0; i < divNodes.length; i++) {
    if (divNodes[i].style) {
      var targetNode = divNodes[i];
      var thisNodeTextAlign = targetNode.style.textAlign;
      if (thisNodeTextAlign == targetTextAlign) {
        //this node is one of the aligned nodes
        alignNodes.push(targetNode);
      }
    }
  }
  return alignNodes;
}

function tgpAlignify(alignType) {
  tgpAttemptDealignClicks(alignType);

  let styleAttr = tgpGetStyleAttr(alignType);
  let btnId = tgpGetAlignBtnId(alignType);
  var clickedEditorBtn = document.getElementById(btnId);

  if (clickedEditorBtn == null) {
    return; //make sure toolbar is open
  }

  if (clickedEditorBtn.classList.contains("active-editor-btn")) {
    //START OF remove align center
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      currentSelectedRange = selection.getRangeAt(0).cloneRange();
    }

    let selectedRange = selection.getRangeAt(0);

    //find italic nodes that intersect the selected range...
    let alignNodes = tgpGetAlignNodes(selectedRange, styleAttr);
    let resultObj = tgpIntersectsRange(selectedRange, alignNodes);

    //if we found an intersecting
    if (resultObj.tgpIntersectsRange == true) {
      //loop through each of the intersecting nodes and remove the offending tags...
      var tgpIntersectsRangeIndexes = resultObj.tgpIntersectsRangeIndexes;
      for (let i = 0; i < tgpIntersectsRangeIndexes.length; i++) {
        tgpUnwrapNode(alignNodes[tgpIntersectsRangeIndexes[i]]);
      }

      clickedEditorBtn.classList.remove("active-editor-btn");
    }

    //END OF remove align center
  } else {
    //align the text (normal button behaviour)
    let newEl = document.createElement("div");
    newEl.setAttribute("style", styleAttr);
    tgpAddTags(newEl);
    clickedEditorBtn.classList.add("active-editor-btn");
  }
}

function tgpAttemptDealignClicks(alignType) {
  const alignBtns = [
    document.getElementById("alignify-left-btn"),
    document.getElementById("alignify-center-btn"),
    document.getElementById("alignify-right-btn"),
    document.getElementById("alignify-justify-btn"),
  ];

  alignBtns.forEach((button) => {
    if (button !== null) {
      if (
        !button.id.includes(alignType) &&
        button.classList.contains("active-editor-btn")
      ) {
        button.click();
      }
    }
  });
}

function tgpGetAlignBtnId(alignType) {
  switch (alignType) {
    case "left":
      var btnId = "alignify-left-btn";
      break;
    case "right":
      var btnId = "alignify-right-btn";
      break;
    case "center":
      var btnId = "alignify-center-btn";
      break;
    case "justify":
      var btnId = "alignify-justify-btn";
      break;
  }
  return btnId;
}

function tgpUnwrapNode(nodeToUnwrap) {
  // get the element's parent node
  var parent = nodeToUnwrap.parentNode;

  // move all children out of the element
  while (nodeToUnwrap.firstChild)
    parent.insertBefore(nodeToUnwrap.firstChild, nodeToUnwrap);

  // remove the empty element
  parent.removeChild(nodeToUnwrap);
}

function tgpAttemptActivateLinkifyBtn(selectedRange) {
  //get all of the a (href) nodes within the active element
  let linkNodes = trongatePagesObj.activeEl.getElementsByTagName("a");

  //get an array of all of the a (href) nodes that intersect the selected range
  let resultObj = tgpIntersectsRange(selectedRange, linkNodes);

  if (resultObj.tgpIntersectsRange == true) {
    let targetBtn = document.getElementById("linkify-btn");
    targetBtn.classList.add("active-editor-btn");
  }
}

function tgpItalicify() {
  let clickedEditorBtn = document.getElementById("italicify-btn");

  if (clickedEditorBtn == null) {
    return;
  }

  if (clickedEditorBtn.classList.contains("active-editor-btn")) {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      currentSelectedRange = selection.getRangeAt(0).cloneRange();
    }

    let selectedRange = selection.getRangeAt(0);

    //find italic nodes that intersect the selected range...
    let italicNodes = trongatePagesObj.activeEl.getElementsByTagName("i");
    let resultObj = tgpIntersectsRange(selectedRange, italicNodes);

    //if we found an intersecting
    if (resultObj.tgpIntersectsRange == true) {
      //loop through each of the intersecting nodes and remove the offending tags...
      var tgpIntersectsRangeIndexes = resultObj.tgpIntersectsRangeIndexes;
      for (let i = 0; i < tgpIntersectsRangeIndexes.length; i++) {
        tgpUnwrapNode(italicNodes[tgpIntersectsRangeIndexes[i]]);
      }

      clickedEditorBtn.classList.remove("active-editor-btn");
    }
  } else {
    let newTag = document.createElement("i");
    tgpAddTags(newTag);
    tgpToggleEditorToolbartn("italicify-btn");
  }
}

function tgpAddTags(tagEl) {
  let selection = window.getSelection();
  if (selection.rangeCount > 0) {
    currentSelectedRange = selection.getRangeAt(0).cloneRange();
  }

  let selectedRange = selection.getRangeAt(0);

  if (selectedRange.toString().length === 0) {
    let selectableArea = trongatePagesObj.activeEl;
    let newRange = document.createRange();
    newRange.selectNodeContents(selectableArea);
    selection.removeAllRanges();
    selection.addRange(newRange);
    selectedRange = newRange;
  }

  if (
    selectedRange.startContainer.parentNode !==
    selectedRange.endContainer.parentNode
  ) {
    let startRange = document.createRange();
    startRange.setStart(
      selectedRange.startContainer,
      selectedRange.startOffset
    );
    startRange.setEndAfter(selectedRange.startContainer);

    let endRange = document.createRange();
    endRange.setStartBefore(selectedRange.endContainer);
    endRange.setEnd(selectedRange.endContainer, selectedRange.endOffset);

    startRange.surroundContents(tagEl);
    endRange.surroundContents(tagEl);
  } else {
    selectedRange.surroundContents(tagEl);
  }
}

function tgpToggleEditorToolbartn(btnId) {
  let targetBtn = document.getElementById(btnId);
  targetBtn.classList.toggle("active-editor-btn");
}

function tgpIntersectsRange(selectedRange, els) {
  //does an element of this TYPE intersect the selected range?
  let tgpIntersectsRange = false;
  let tgpIntersectsRangeIndex = 0;

  var result = {
    tgpIntersectsRange: false,
    tgpIntersectsRangeIndexes: [],
  };

  for (let i = 0; i < els.length; i++) {
    tgpIntersectsRange = selectedRange.intersectsNode(els[i]);
    if (tgpIntersectsRange == true) {
      result.tgpIntersectsRange = true;
      result.tgpIntersectsRangeIndexes.push(i);
    }
  }

  return result;
}

function tgpAttemptActivateToolBarBtns() {
  //make sure we have a selection that is INSIDE the active element?

  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    currentSelectedRange = selection.getRangeAt(0).cloneRange();
  }

  if (!selection || selection.rangeCount === 0) {
    return;
  }

  let selectedRange = selection.getRangeAt(0);
  var intersectsActiveEl = selectedRange.intersectsNode(
    trongatePagesObj.activeEl
  );

  if (intersectsActiveEl !== true) {
    return;
  }

  let allEditorBtns = tgpGetAllEditorBtns();
  for (var i = 0; i < allEditorBtns.length; i++) {
    var targetBtn = allEditorBtns[i];
    var btnId = targetBtn["id"];

    switch (btnId) {
      case "boldify-btn":
        tgpAttemptActivateBoldBtn(selectedRange);
        break;
      case "italicify-btn":
        tgpAttemptActivateItalicBtn(selectedRange);
        break;
      case "alignify-justify-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-left-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-right-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "alignify-center-btn":
        tgpAttemptActivateAlignifyBtn(selectedRange, btnId);
        break;
      case "linkify-btn":
        tgpAttemptActivateLinkifyBtn(selectedRange);
        break;
    }
  }
}

function tgpAddTextToolbar(targetEl) {
  const textElement = tgpFindAncestorTextDiv(targetEl);
  if (textElement) {
    tgpReset(["codeviews", "toolbars", "customModals"]);
    trongatePagesObj.activeEl = textElement;
    let editor = document.createElement("div");
    let divLeft = document.createElement("div");
    divLeft.setAttribute("id", "trongate-editor-toolbar");
    editor.appendChild(divLeft);
    let divRight = document.createElement("div");
    editor.appendChild(divRight);

    tgpBuildCodeBtn(divLeft);
    tgpBuildBoldBtn(divLeft);
    tgpBuildItalicBtn(divLeft);
    tgpBuildAlignLeftBtn(divLeft);
    tgpBuildAlignCenterBtn(divLeft);
    tgpBuildAlignRightBtn(divLeft);
    tgpBuildAlignJustifyBtn(divLeft);
    tgpBuildLinkifyBtn(divLeft);
    tgpBuildListifyBtns(divLeft);
    tgpBuildPicBtn(divLeft);
    tgpBuildTrashifyBtn(divRight);

    editor.setAttribute("id", "trongate-editor");
    let targetElTagname = textElement.tagName;

    textElement.setAttribute("contenteditable", "true");

    const body = trongatePagesObj.pageBody;
    body.append(editor);
    let rect = textElement.getBoundingClientRect();
    let editorRect = editor.getBoundingClientRect();
    let targetYPos = rect.top - editorRect.height - 7;

    if (targetYPos < 0) {
      targetYPos = 0;
    }

    editor.style.top = targetYPos + "px";
    tgpInitEditorListeners(editor);
    tgpStartTimer();
  }
}

function tgpFindAncestorHeadline(element) {
  if (
    element.tagName === "H1" ||
    element.tagName === "H2" ||
    element.tagName === "H3" ||
    element.tagName === "H4" ||
    element.tagName === "H5" ||
    element.tagName === "H6"
  ) {
    return element;
  }
  if (element.parentElement) {
    return tgpFindAncestorHeadline(element.parentElement);
  }
  return null;
}

function tgpFindAncestorTextDiv(element) {
  if (element.classList.contains("text-div")) {
    return element;
  }
  if (element.parentElement) {
    return tgpFindAncestorTextDiv(element.parentElement);
  }
  return null;
}

function tgpAttemptActivateBoldBtn(selectedRange) {
  //get all of the italic nodes within the active element

  //get all of the italic nodes within the active element
  let boldNodes = trongatePagesObj.activeEl.getElementsByTagName("b");

  //get an array of all of the italic nodes that intersect the selected range
  let resultObj = tgpIntersectsRange(selectedRange, boldNodes);

  if (resultObj.tgpIntersectsRange == true) {
    let targetBtn = document.getElementById("boldify-btn");
    targetBtn.classList.add("active-editor-btn");
  }
}

function tgpAttemptActivateItalicBtn(selectedRange) {
  //get all of the italic nodes within the active element
  let italicNodes = trongatePagesObj.activeEl.getElementsByTagName("i");

  //get an array of all of the italic nodes that intersect the selected range
  let resultObj = tgpIntersectsRange(selectedRange, italicNodes);

  if (resultObj.tgpIntersectsRange == true) {
    let targetBtn = document.getElementById("italicify-btn");
    targetBtn.classList.add("active-editor-btn");
  }
}

function tgpBoldify() {
  let clickedEditorBtn = document.getElementById("boldify-btn");

  if (clickedEditorBtn == null) {
    return;
  }

  if (clickedEditorBtn.classList.contains("active-editor-btn")) {
    let selection = window.getSelection();
    if (selection.rangeCount > 0) {
      currentSelectedRange = selection.getRangeAt(0).cloneRange();
    }

    let selectedRange = selection.getRangeAt(0);

    // Find italic nodes that intersect the selected range...
    let boldNodes = trongatePagesObj.activeEl.getElementsByTagName("b");
    let resultObj = tgpIntersectsRange(selectedRange, boldNodes);

    // If we found an intersecting
    if (resultObj.tgpIntersectsRange == true) {
      // Loop through each of the intersecting nodes and remove the offending tags...
      var tgpIntersectsRangeIndexes = resultObj.tgpIntersectsRangeIndexes;
      for (let i = 0; i < tgpIntersectsRangeIndexes.length; i++) {
        tgpUnwrapNode(boldNodes[tgpIntersectsRangeIndexes[i]]);
      }

      clickedEditorBtn.classList.remove("active-editor-btn");
    }
  } else {
    let newTag = document.createElement("b");
    tgpAddTags(newTag);
    tgpToggleEditorToolbarBtn("boldify-btn");
  }
}

function tgpToggleEditorToolbarBtn(btnId) {
  let targetBtn = document.getElementById(btnId);
  targetBtn.classList.toggle("active-editor-btn");
}

window.addEventListener("mouseup", (ev) => {
  //NEVER SET THE ACTIVE EL UPON MOUSE UP!!!!
  //attempt to set buttons to 'active' IF toolbar open
  //for example, if user has clicked on italic text, 'italic' button become 'active'
  let activeToolBar = document.getElementById("trongate-editor");
  if (activeToolBar) {
    tgpAttemptActivateToolBarBtns();
  }
});

window.addEventListener("scroll", tgpHandleScroll);

// Remove toolbar when page is being scrolled
function tgpHandleScroll(event) {
  const targetEl = document.getElementById("trongate-editor");
  if (targetEl) {
    // Remove the target element from its parent
    if (removeUponScrollAllowed === true) {
      tgpReset(["codeviews", "toolbars"]);
    }
  }
}
