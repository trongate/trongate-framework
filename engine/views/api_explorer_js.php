<script src="<?= BASE_URL ?>js/app.js"></script>
<script>
  let token = '';
  let currentEndpoint;
  let currentEndpointIndex = '';
  let headerInfo = '';
  let currentModalType = 'GET';
  let specialToken = '<?= $special_token ?>';

  const baseUrl = '<?= BASE_URL ?>';
  const HTTP_STATUS_CODES = <?= json_encode($http_status_codes) ?>

  const endpoints = <?= json_encode($endpoints) ?>

  const columns = <?= json_encode($columns) ?>

  function setToken() {
    const inputTokenEl = document.getElementById('input-token');
    token = inputTokenEl.value;
    inputTokenEl.value = '';
    const tokenStatusEl = document.getElementById('token-status');
    const tokenStatusContent = (token == '') ? '' : 'Token is set.';
    const setTokenBtn = document.getElementById('set-token-btn');
    setTokenBtn.innerText = (token == '') ? 'Set Token' : 'Unset Token';
    tokenStatusEl.innerHTML = tokenStatusContent;
  }

  function openApiTester(ev) {
    const clickedBtn = ev.target;
    const btnText = clickedBtn.innerText;
    const btnId = ev.target.id;
    const endpointIndex = parseInt(event.target.getAttribute('data-row'));
    const endpointKeys = Object.keys(endpoints);
    currentEndpointIndex = endpointKeys[endpointIndex];
    currentEndpoint = endpoints[currentEndpointIndex];

    const destinationUrl = baseUrl + currentEndpoint['url_segments'];
    currentModalType = (btnText === 'GET') ? 'GET' : 'POST';

    if ((currentModalType === 'GET') && (currentEndpoint['enableParams'] === true)) {
      openModal('test-endpoint-modal', '5vh');
    } else {
      openModal('test-endpoint-modal');
    }


    setModalTheme();

    const modalBody = document.querySelector('.modal-body');

    if (currentModalType === 'GET') {
      buildModalGet(currentEndpointIndex, currentEndpoint, modalBody);
    } else {
      buildModalPost(currentEndpointIndex, currentEndpoint, modalBody);
    }
  }

  function getParamsFromTextarea() {
    const paramsInput = document.getElementById('params-input');
    if (paramsInput === null) {
      return '';
    }
    const paramsValue = paramsInput.value.trim();

    if (!paramsValue) {
      return '';
    }

    try {
      const parsedJSON = JSON.parse(paramsValue);
      return parsedJSON;
    } catch (error) {
      return false;
    }
  }

  function modifyForRequiredFields(targetUrl) {
    const requiredFields = document.getElementsByClassName('required-field');
    for (var i = 0; i < requiredFields.length; i++) {
      const elId = requiredFields[i]['id'];
      const elValue = requiredFields[i]['value'];
      const targetCol = elId.replace('required-field-', '');
      const strToReplace = '{' + targetCol + '}';
      targetUrl = targetUrl.replace(strToReplace, elValue);
    }

    setTimeout(() => {
      const endpointSegmentsEl = document.getElementById('endpoint-segments');
      endpointSegmentsEl.innerText = targetUrl.replace(baseUrl, '');
    }, 10);

    return targetUrl;
  }

  function submitRequest() {
    const requiredFields = document.getElementsByClassName('required-field');
    let numFieldErrors = 0;
    for (var i = 0; i < requiredFields.length; i++) {
      const requiredFieldValue = requiredFields[i].value.trim();
      if (requiredFieldValue.length === 0) {
        numFieldErrors++;
        const requiredFieldPlaceholder = requiredFields[i]['placeholder'];
        let requiredFieldErr;

        if (
          requiredFieldPlaceholder &&
          requiredFieldPlaceholder.length >= 12 &&
          requiredFieldPlaceholder.substring(0, 6) === "Enter " &&
          requiredFieldPlaceholder.substring(requiredFieldPlaceholder.length - 5) === " here"
        ) {
          requiredFieldErr = "The " + requiredFieldPlaceholder.substring(6, requiredFieldPlaceholder.length - 5) + " field cannot be left empty!";
        } else {
          requiredFieldErr = "At least one required field was left empty!";
        }

        alert(requiredFieldErr);
        requiredFields[i].value = '';
        break;
      }
    }

    if (numFieldErrors > 0) {
      return;
    }

    let targetUrl = baseUrl + currentEndpoint['url_segments'];
    targetUrl = modifyForRequiredFields(targetUrl);

    //get the request type
    let requestType = currentEndpoint['request_type'].toUpperCase();

    if (requestType !== 'GET') {
      let requestType = 'POST';

      //attempt build params JSON obj
      var paramsObj = getParamsFromTextarea();
      if ((typeof paramsObj === 'boolean') && (paramsObj === false)) {
        alert("Invalid JSON!");
        return;
      }

    } else {
      let requestType = 'GET';

      //attempt to build query string based on the params builder table rows
      const tableRows = document.getElementsByClassName('form-builder-row');
      const queryString = buildQueryString(tableRows);
      targetUrl += queryString;
    }

    setTimeout(() => {
      const bypassAuthCheckbox = document.querySelector('#test-endpoint-modal > div.modal-body > form > div.three-col > div:nth-child(1) > input[type=checkbox]');

      //uncheck the bypass auth checkbox
      bypassAuthCheckbox.checked = false;
      if (token === specialToken) {
        //make token empty (special tokens are only for single use cases)
        const tokenInputEl = document.getElementById('input-token');
        tokenInputEl.value = '';

        setToken();

        const tokenValueEl = document.getElementById('token-value');
        tokenValueEl.innerHTML = '';
        generateNewSpecialToken(specialToken);
      }
    }, 600);

    //build up the http request obj
    const http = new XMLHttpRequest();
    http.open(requestType, targetUrl);
    http.setRequestHeader('Content-type', 'application/json');

    if (token !== '') {
      http.setRequestHeader('trongateToken', token);
    }

    if (paramsObj !== '') {
      http.send(JSON.stringify(paramsObj));
    } else {
      http.send();
    }

    http.onload = () => {
      // Get the header information from the response
      headerInfo = '<p style="font-weight: bold; text-align: left">HTTP Header Values </p>';
      var headerValues = http.getAllResponseHeaders();
      headerValues = headerValues.replace(/(?:\r\n|\r|\n)/g, '<br>');
      headerInfo += '<span style="font-size: 0.8em;">' + headerValues + '</span>';

      //populate the header-info paragraphs...
      document.getElementById('header-info').innerHTML = headerInfo;

      //display status code and response body
      displayResponse(http.status, http.responseText);
    }
  }

  function clearParameters(checked) {
    // Check if checkbox is checked or not
    if (checked) {
      const formBuilderRows = document.getElementsByClassName('form-builder-row');
      const numFormBuilderRows = formBuilderRows.length;

      for (var i = formBuilderRows.length - 1; i >= 0; i--) {
        formBuilderRows[i].remove();
      }

      if (numFormBuilderRows > 0) {
        attemptBuildQueryString();
      }

      const paramsInput = document.getElementById('params-input');
      if (paramsInput) {
        paramsInput.value = '';
      }

    }
  }

  function displayResponse(status, responseText) {
    const serverResponseEl = document.getElementById('server-response');
    serverResponseEl.value = responseText;

    //const httpStatusText = HTTP_STATUS_CODES[`CODE_${status}`];

    // Retrieve the HTTP status text based on the status code
    let httpStatusText;
    if (HTTP_STATUS_CODES.hasOwnProperty(`${status}`)) {
      httpStatusText = HTTP_STATUS_CODES[`${status}`];
    } else {
      httpStatusText = 'Unknown HTTP Response Code';
    }

    const httpStatusDisplay = (httpStatusText && httpStatusText.length > 20) ? status.toString() : `${status} ${httpStatusText}`;

    const statusEl = document.getElementById('http-status-code');
    statusEl.innerHTML = httpStatusDisplay;

    if (status >= 200 && status < 300) {
      statusEl.style.color = 'green';
    } else {
      statusEl.style.color = 'purple';
    }
  }

  function getRootEndpointUrl() {
    const rootEndpointUrl = currentEndpoint['url_segments'];
    return rootEndpointUrl;
  }

  function attemptEscCloseModal() {
    document.onkeydown = function(e) {
      var modalContainer = _("modal-container");
      if ((e.key === "Escape") && (modalContainer)) {
        destroyModal();
      }
    };
  }

  function destroyModal() {
    //clear the inner contents from the modal body
    const modalBody = document.querySelector('#test-endpoint-modal > div.modal-body');
    while (modalBody.firstChild) {
      modalBody.removeChild(modalBody.lastChild);
    }
    closeModal();
  }

  window.addEventListener('load', (ev) => {
    const openModalBtns = document.getElementsByClassName('open-api-tester');
    for (var i = 0; i < openModalBtns.length; i++) {
      openModalBtns[i].addEventListener('click', (ev) => {
        openApiTester(ev);
      });
    }

    attemptEscCloseModal();
  });
</script>