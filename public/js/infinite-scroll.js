let loadingMoreRecords = false;

// Function to initialize scroll event listener if the screen is narrow
function initializeInfiniteScroll() {
    if (isPaginationHidden()) {
        window.addEventListener('scroll', onScroll);
    } else {
        window.removeEventListener('scroll', onScroll);
        removeAllLoadingMoreSpinners();
    }
}

// Scroll event handler
function onScroll() {

    // Get the pagination container
    const paginationContainer = document.querySelector('.pagination');

    // Check if the pagination container exists
    if (paginationContainer) {
        // Get the last child element of the pagination container
        const lastChild = paginationContainer.lastElementChild;

        // Check if the last child element exists
        if (lastChild) {
            // Get the position of the last child element relative to the viewport
            const lastChildRect = lastChild.getBoundingClientRect();

            // Check if the last child element is above the fold (visible in the viewport)
            if (lastChildRect.top <= window.innerHeight) {
                // Trigger the function to load more records if not already loading
                if (!loadingMoreRecords) {
                    loadingMoreRecords = true;
                    loadMoreRecords();
                }
            }
        }
    }
}

function identifyPaginationContainer() {
    // Get the first .pagination element.
    const targetPaginationEl = document.querySelector('.pagination');

    // Return the element that occurs immediately after the targetPagination element.
    if (targetPaginationEl) {
        // Return the element that occurs immediately after the targetPagination element.
        const nextSiblingEl = targetPaginationEl.nextElementSibling;
        return nextSiblingEl;
    } else {
        return null;
    }
}

function removeAllLoadingMoreSpinners() {
    loadingMoreRecords = false;
    const allLoadingMoreSpinners = document.querySelectorAll('.loading-more-records');
    allLoadingMoreSpinners.forEach(loadingMoreSpinner => {
        loadingMoreSpinner.remove();
    });
}

function loadMoreRecords() {
    const paginationContainer = identifyPaginationContainer();

    // Create a new spinner element
    const newElement = document.createElement('div');
    newElement.setAttribute('class', 'spinner mb-3 loading-more-records');

    // Insert the new spinner after the pagination container
    paginationContainer.insertAdjacentElement('afterend', newElement);

    // Find all elements with the 'active' class
    const activeElements = document.querySelectorAll('.pagination a.active');

    // Loop through each active element
    let nextUrl = '';
    activeElements.forEach(activeElement => {
        // Check if the active element has a next sibling
        if (activeElement.nextElementSibling) {

            // Remove the 'active' class from the current active link
            activeElement.classList.remove('active');

            // Get the URL from the 'href' attribute of the next sibling
            if (nextUrl === '') {
                nextUrl = activeElement.nextElementSibling.getAttribute('href');
            }

            // Apply the 'active' class to the next sibling link
            activeElement.nextElementSibling.classList.add('active');
        }
    })

    if (nextUrl === '') {
        removeAllLoadingMoreSpinners();
        return;
    }

    const http = new XMLHttpRequest();
    http.open('get', nextUrl);
    http.setRequestHeader('Content-type', 'application/json');
    http.send();
    http.onload = function() {

        if (http.status === 200) {
            // Parse the HTML string
            const parser = new DOMParser();
            const doc = parser.parseFromString(http.responseText, 'text/html');

            // Identify the new rows from within the response.
            const targetPaginationEl = doc.querySelector('.pagination');
            const foundPaginationContainer = targetPaginationEl.nextElementSibling;
            const containerType = foundPaginationContainer.tagName.toLowerCase();
            const newRows = (containerType === 'table') ? foundPaginationContainer.querySelectorAll('tbody tr') : foundPaginationContainer.children;

            // Append the new rows to the paginationRowsContainer
            newRows.forEach(row => {
                paginationContainer.appendChild(row);
            });

            // Swap the pagination on the existing page.
            const existingPaginationEls = document.querySelectorAll('.pagination');
            existingPaginationEls.forEach(paginationEl => {
                paginationEl.innerHTML = targetPaginationEl.innerHTML;
            });

            // Remove any showing statements that are on the page.
            const existingShowingStatements = document.querySelectorAll('.tg-showing-statement');
            existingShowingStatements.forEach(existingShowingStatement => {
                existingShowingStatement.remove();
            });

            removeAllLoadingMoreSpinners();
            
        } else {
            console.error(http.status);
            console.error(http.responseText);
            removeAllLoadingMoreSpinners();
        }

    }

}

// Function to determine if all pagination elements are hidden
function isPaginationHidden() {
    const paginationEls = document.querySelectorAll('.pagination');
    for (let i = 0; i < paginationEls.length; i++) {
        const paginationEl = paginationEls[i];
        const computedDisplay = window.getComputedStyle(paginationEl).display;
        if (computedDisplay !== 'none') {
            return false; // If any pagination element is not hidden, return false
        }
    }
    return true; // All pagination elements are hidden
}


// Initialize infinite scroll on page load
initializeInfiniteScroll();

// Re-check screen width on window resize
window.addEventListener('resize', initializeInfiniteScroll);
window.addEventListener('load', (ev) => {

    const initInfiniteScroll = document.querySelector('#init-infinite-scroll');
    initInfiniteScroll.remove();

    if (isPaginationHidden()) {
        // Simulate scroll since the user may not be able to scroll.
        onScroll();
    }
    
});