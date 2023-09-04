function tgpReturnToManagePages() {
    window.location.href = trongatePagesObj.baseUrl + 'trongate_pages/manage';
}

function tgpInsertStyleSheet() {
    let newStyleSheet = document.createElement('link');
    newStyleSheet.setAttribute('rel', 'stylesheet');
    newStyleSheet.setAttribute('href', 'trongate_pages' + trongatePagesObj.moduleAssetsTrigger + '/css/trongate_pages_editor.css');
    trongatePagesObj.pageBody.appendChild(newStyleSheet);
}

/*
  this gets automatically invoked after the initial page load.
  //NOTE!  toolbar_manager.js sets mousedown listeners for; btns, text, headlines & hr
  //NOTE #2 toolbar_manager also sets mouseup listeners for addPointersToHrs
*/
function tgpStartPageEditor() {
    tgpInsertStyleSheet();
    tgpDrawDock();
    tgpInitDockPos();
    tgpAddPointersToHrs();
    tgpAddPointersToBtnDivs();
    tgpAddOverlayToYoutubeVideos()
    return;
}

function tgpClickPlusBtn() {
  return new Promise(resolve => {
    const plusBtn = document.querySelector('#tgp-create-page-el');
    plusBtn.click();
    resolve();
  });
}
function tgpWaitAWhile() {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve();
    }, 300);
  });
}
function tgpClickNewHeadline() {
  return new Promise(resolve => {
    const headlineBtn = document.querySelector('#page-el-options-grid > div:nth-child(1) > div:nth-child(1) > img');
    headlineBtn.click();
    resolve();
  });
}
function tgpClickNewTextBlock() {
  return new Promise(resolve => { 
    const headlineBtn = document.querySelector('#page-el-options-grid > div:nth-child(2)'); 
    headlineBtn.click();
    resolve();
  });
}

window.addEventListener("mousedown", (ev) => {

  // Attempt open toolbar or img modal (headline, text, button, divider toolbars are available)
  let clickedEl = ev.target;
  if (!trongatePagesObj.defaultActiveElParent.contains(clickedEl)) {
    return; // outside of editor area
  } else {
    tgpHandleElementClick(clickedEl)
  }
});

function tgpHandleElementClick(clickedEl) {

  if (clickedEl.tagName === 'IMG') {
    // Display image modal here
    buildEditImgModal(clickedEl);
    return null; // No toolbar should be drawn for the image element
  }

  if (clickedEl.closest('.text-div')) {
    tgpAddTextToolbar(clickedEl);
    return 'textToolbar';
  }

  if (clickedEl.closest('.button-div')) {
    return 'buttonToolbar';
  }

  if (clickedEl.closest('hr')) {
    return 'dividerToolbar';
  }

  if (['H1', 'H2', 'H3', 'H4', 'H5', 'H6'].includes(clickedEl.tagName) || clickedEl.closest('h1, h2, h3, h4, h5, h6')) {
    tgpAddHeadlineToolbar(clickedEl);
  }

  // No toolbar should be drawn
  return null;
}

function tgpClearSelection() {
  if (window.getSelection) {
    // Clear the selection using the Selection API
    const selection = window.getSelection();
    selection.removeAllRanges();
  } else if (document.selection) {
    // For older browsers (IE)
    document.selection.empty();
  }
}

function tgpSavePage() {
    tgpReset(['selectedRange', 'codeviews', 'customModals', 'toolbars', 'activeEl']);
    tgpSavingPage = true; //so that pointers do not get added to HRs upon mouseup

    tgpRemoveContentEditables();

    setTimeout(() => {
        const params = {
            page_body: trongatePagesObj.defaultActiveElParent.innerHTML
        }
        
        tgpSendSaveRequest(params);
    }, 1);

    //build a modal that confirms that page has saved
    const modalId = 'tgp-confirm-save-page';
    const customModal = tgpBuildCustomModal(modalId);
    const modalBody = customModal.querySelector('.modal-body');

    const subHeadline = document.createElement('h2');
    subHeadline.style.marginBottom = 0;
    subHeadline.innerText = 'Saving';
    subHeadline.setAttribute('class', 'blink text-center');
    modalBody.appendChild(subHeadline);
    tgpDrawBigTick(modalBody);
}

function tgpDrawBigTick(targetParentEl, closeUponFinish=1) {
    targetParentEl.classList.add('text-center');
    let bigTick = document.createElement('div');
    bigTick.setAttribute('id', 'big-tick');
    bigTick.setAttribute('style', 'display: none');
    let trigger = document.createElement('div');
    trigger.setAttribute('class', 'trigger');
    bigTick.appendChild(trigger);

    let tickSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    tickSvg.setAttribute('version', '1.1');
    tickSvg.setAttribute('id', 'tick');
    tickSvg.setAttribute('style', 'margin:  0 auto; width:  53.7%; transform: scale(0.5)');
    tickSvg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    tickSvg.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
    tickSvg.setAttribute('x', '0px');
    tickSvg.setAttribute('y', '0px');
    tickSvg.setAttribute('viewBox', '0 0 37 37');
    tickSvg.setAttribute('xml:space', 'preserve');
    bigTick.appendChild(tickSvg);

    let tickPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    tickPath.setAttribute('class', 'circ path');
    tickPath.setAttribute('style', 'fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10');
    tickPath.setAttribute('d', 'M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z');
    tickSvg.appendChild(tickPath);

    let polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
    polyline.setAttribute('class', 'tick path');
    polyline.setAttribute('style', 'fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10;');
    polyline.setAttribute('points', '11.6,20 15.9,24.2 26.4,13.8');
    tickSvg.appendChild(polyline);

   targetParentEl.appendChild(bigTick);
    
    bigTick = document.getElementById('big-tick');
    bigTick.style.display = 'block';
    
    setTimeout(() => {
        let things = document.getElementsByClassName('trigger')[0];
        things.classList.add('drawn');
    }, 100);
    
    if(closeUponFinish === 1) {
      setTimeout(() => {
         tgpHideBigTick();
      }, 1300);
    }

}

function tgpDrawBigCross(targetParentEl) {

  targetParentEl.classList.add('text-center');
  let bigTick = document.createElement('div');
  bigTick.setAttribute('id', 'big-tick');
  bigTick.setAttribute('style', 'display: none');
  let trigger = document.createElement('div');
  trigger.setAttribute('class', 'trigger');
  bigTick.appendChild(trigger);

  let tickSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  tickSvg.setAttribute('version', '1.1');
  tickSvg.setAttribute('id', 'tick');
  tickSvg.setAttribute('style', 'margin:  0 auto; width:  53.7%; transform: scale(0.5)');
  tickSvg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
  tickSvg.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
  tickSvg.setAttribute('x', '0px');
  tickSvg.setAttribute('y', '0px');
  tickSvg.setAttribute('viewBox', '0 0 37 37');
  tickSvg.setAttribute('xml:space', 'preserve');
  bigTick.appendChild(tickSvg);

  let tickPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  tickPath.setAttribute('class', 'circ path');
  tickPath.setAttribute('style', 'fill:none;stroke:#cc0000;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10');
  tickPath.setAttribute('d', 'M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z');
  tickSvg.appendChild(tickPath);

  let polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
  polyline.setAttribute('class', 'tick path');
  polyline.setAttribute('style', 'fill:none;stroke:#cc0000;stroke-width:3;');
  polyline.setAttribute('points', '11.1,10 25.4,27.2');
  tickSvg.appendChild(polyline);

  let polyline2 = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
  polyline2.setAttribute('class', 'tick2 path');
  polyline2.setAttribute('style', 'fill:none;stroke:#cc0000;stroke-width:3;');
  polyline2.setAttribute('points', '24.1,10 12.4,27.2');
  tickSvg.appendChild(polyline2);

  targetParentEl.appendChild(bigTick);

  bigTick = document.getElementById('big-tick');
  bigTick.style.display = 'block';

  setTimeout(() => {
    let things = document.getElementsByClassName('trigger')[0];
    things.classList.add('drawn');
  }, 100);

}

function tgpHideBigTick() {
    let things = document.getElementsByClassName('trigger')[0];
    things.classList.remove('drawn');
    let bigTick = document.getElementById('big-tick');
    bigTick.style.display = 'none'; 
    closeModal();
    tgpReset(['selectedRange', 'codeviews', 'customModals', 'toolbars', 'activeEl']);
}

function tgpRemoveContentEditables() {
  var pageContents = document.getElementsByClassName('page-content');

  for (var i = 0; i < pageContents.length; i++) {
    var pageContent = pageContents[i];
    var editableElements = pageContent.querySelectorAll('[contenteditable=true]');

    for (var j = 0; j < editableElements.length; j++) {
      var editableElement = editableElements[j];
      editableElement.removeAttribute('contenteditable');
    }
  }
  
  tgpRemovePointersFromHrs();
}

function tgpRemovePointersFromHrs() {
  var pageContents = document.getElementsByClassName('page-content');

  for (var i = 0; i < pageContents.length; i++) {
    var pageContent = pageContents[i];
    var hrElements = pageContent.getElementsByTagName('hr');

    for (var j = 0; j < hrElements.length; j++) {
      var hrElement = hrElements[j];
      if (hrElement.style.cursor === 'pointer') {
        hrElement.style.removeProperty('cursor');
      }
      if (!hrElement.getAttribute('style')) {
        hrElement.removeAttribute('style');
      }
    }
  }
}

function tgpSendSaveRequest(params) {
    const targetUrl = trongatePagesObj.baseUrl + 'api/update/trongate_pages/' + trongatePagesObj.trongatePagesId;
    const http = new XMLHttpRequest();
    http.open('put', targetUrl);
    http.setRequestHeader('Content-type', 'application/json');
    http.setRequestHeader('trongateToken', trongatePagesObj.trongatePagesToken);
    http.send(JSON.stringify(params));
    http.onload = (ev) => {
        tgpSavingPage = false;
        if(http.status !== 200) {
            alert(http.responseText); //later!
        }
    };
}

function tgpHighlightActiveEl() {
    const activeEl = trongatePagesObj.activeEl;
    activeEl.classList.add('active-el-highlight');
}

function tgpUnhighlightEl(el) {
    el.classList.remove('active-el-highlight');
}

window.addEventListener("keydown", (e) => {
    if ((e.keyCode == 27) || (e.key == 'Escape')) {
        //destory modals that belong to the Trongate Pages module when escape key pressed
        tgpReset(['selectedRange', 'codeviews', 'customModals', 'toolbars', 'activeEl']);
    }
});

function tgpReset(restoreItems) {

  if(restoreItems.includes('selectedRange')) {
    tgpClearSelection();
  }

  if(restoreItems.includes('codeviews')) {
    tgpRemoveCodeView();
  }

  if (restoreItems.includes('customModals')) {
    tgpRemoveCustomModals();
  }

  if (restoreItems.includes('toolbars')) {
    tgpRemoveToolbars();
  }

  if(restoreItems.includes('activeEl')) {
    tgpUnhighlightEl(trongatePagesObj.activeEl);
    trongatePagesObj.activeElParent = document.getElementsByClassName('page-content')[0];
    trongatePagesObj.activeEl = document.getElementsByClassName('page-content')[0];
  }

}




































function tgpGetStoredRange() {
  return trongatePagesObj.storedRange || null;
}
