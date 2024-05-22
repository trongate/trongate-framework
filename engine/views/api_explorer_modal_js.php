<script>
  function setModalTheme() {
    //sets the theme and adds endpoint name to top lhs
    const modalTheme = currentEndpoint['request_type'];
    const lowercaseStr = modalTheme.toLowerCase();
    const modalClass = 'modal-theme-' + lowercaseStr;

    const modal = document.querySelector('.modal');
    modal.className = 'modal ' + modalClass;

    const topLeftModalDiv = document.getElementById('endpoint-name');
    topLeftModalDiv.innerHTML = currentEndpointIndex;
  }

  function buildModalGet(currentEndpointIndex, currentEndpoint, modalBody) {
    // Create the heading
    const heading = document.createElement('h2');
    heading.textContent = 'Test Your API Endpoint';
    modalBody.appendChild(heading);

    // Create the form
    const form = document.createElement('form');
    modalBody.appendChild(form);

    if (currentEndpoint.hasOwnProperty('required_fields')) {
      // Add required fields
      const requiredFields = currentEndpoint['required_fields'];
      createRequiredFields(form, requiredFields);
    }

    if (currentEndpoint['enableParams'] === true) {
      createParamsBuilder(form);
    }

    createServerResponseDiv(form);
    createDivWithCheckboxes(form);
    createSubmitButton(form);
    createOtherActionButtons(form);
    displayEndpointDetails(form);
  }

  function createRequiredFields(form, requiredFields) {
    for (var i = 0; i < requiredFields.length; i++) {

      // Create the label
      const label = document.createElement('label');
      label.innerText = requiredFields[i]['label'];
      form.appendChild(label);

      // Create the input element
      const input = document.createElement('input');
      input.type = 'text';
      input.id = 'required-field-' + requiredFields[i]['name'];
      input.classList.add('required-field');
      input.setAttribute('autocomplete', 'off');
      input.placeholder = 'Enter ' + requiredFields[i]['label'] + ' here';

      // Append the input element to the body of the document
      form.appendChild(input);

      input.addEventListener('blur', (ev) => {
        const value = input.value.trim();

        if (value !== '' && isNaN(value) && input.id === 'required-field-id') {
          alert('This value must be numeric!');
          input.value = '';
        }

      });

    }
  }

  function buildModalPost(currentEndpointIndex, currentEndpoint, modalBody) {
    //this gets used for; 'POST', 'PUT' and 'DELETE' requests

    // Create the heading
    const heading = document.createElement('h2');
    heading.textContent = 'Test Your API Endpoint';
    modalBody.appendChild(heading);

    // Create the form
    const form = document.createElement('form');
    modalBody.appendChild(form);

    if (currentEndpoint.hasOwnProperty('required_fields')) {
      // Add required fields
      const requiredFields = currentEndpoint['required_fields'];
      createRequiredFields(form, requiredFields);
    }

    if (currentEndpoint.enableParams === true) {
      createDoubleTextarea(form);
    } else {
      createServerResponseDiv(form);
    }

    if (currentEndpoint.hasOwnProperty('required_fields')) {
      // Adjust margins
      const targetLabels = document.querySelectorAll('#test-endpoint-modal > div.modal-body > form > div label');
      for (var i = 0; i < targetLabels.length; i++) {
        targetLabels[i].style.marginTop = '1em';
      }
    }

    createDivWithCheckboxes(form);
    createSubmitButton(form);
    createOtherActionButtons(form);
    displayEndpointDetails(form);
  }

  function createParamsBuilder(form) {
    // Create the params builder section
    const paramsBuilder = document.createElement('div');
    paramsBuilder.id = 'params-builder-special';
    paramsBuilder.style.display = 'block';

    // Create the label for the params builder section
    const paramsBuilderLabel = document.createElement('label');
    paramsBuilderLabel.textContent = 'Parameters';
    paramsBuilder.appendChild(paramsBuilderLabel);

    // Create the table for the params builder section
    const paramsBuilderTable = document.createElement('table');
    paramsBuilderTable.id = 'params-builder-tbl';

    // Create the table header for the params builder section
    const paramsBuilderTableHeader = document.createElement('thead');
    paramsBuilderTableHeader.innerHTML = `
      <tr>
        <th>Key</th>
        <th>Operator</th>
        <th>Value</th>
        <th>&nbsp;</th>                                
      </tr>
    `;
    paramsBuilderTable.appendChild(paramsBuilderTableHeader);

    // Create the table body for the params builder section
    const paramsBuilderTableBody = document.createElement('tbody');
    paramsBuilderTable.appendChild(paramsBuilderTableBody);

    // Create new magic row for the params builder table
    addNewMagicRow(paramsBuilderTableBody);

    // Append the params builder table to the params builder section
    paramsBuilder.appendChild(paramsBuilderTable);

    // Append the server response section to the form
    form.appendChild(paramsBuilder);
  }

  function addNewMagicRow(paramsBuilderTableBody) {
    // Create the magic row for the params builder section
    const paramsBuilderMagicRow = document.createElement('tr');
    paramsBuilderMagicRow.classList.add('magic-row');

    const paramsBuilderMagicCell0 = document.createElement('td');
    paramsBuilderMagicCell0.classList.add('magic-cell-0');
    paramsBuilderMagicCell0.textContent = 'Key';

    const paramsBuilderMagicCell1 = document.createElement('td');
    paramsBuilderMagicCell1.classList.add('magic-cell-1');
    paramsBuilderMagicCell1.textContent = 'Operator';

    const paramsBuilderMagicCell2 = document.createElement('td');
    paramsBuilderMagicCell2.classList.add('magic-cell-2');
    paramsBuilderMagicCell2.textContent = 'Value';

    const paramsBuilderMagicCell3 = document.createElement('td');
    paramsBuilderMagicCell3.classList.add('magic-cell-3');
    paramsBuilderMagicCell3.textContent = '\u00A0';

    paramsBuilderMagicRow.appendChild(paramsBuilderMagicCell0);
    paramsBuilderMagicRow.appendChild(paramsBuilderMagicCell1);
    paramsBuilderMagicRow.appendChild(paramsBuilderMagicCell2);
    paramsBuilderMagicRow.appendChild(paramsBuilderMagicCell3);
    paramsBuilderTableBody.appendChild(paramsBuilderMagicRow);
    paramsBuilderMagicRow.setAttribute('onclick', 'transformRow()');
  }

  function transformRow() {
    const tableRows = document.getElementsByClassName('form-builder-row');
    const numTableRows = tableRows.length;
    if (numTableRows > 2) {
      alert("It is not considered good practice to pass lots of parameters in a URL string.  To avoid confusion consider using POST requests, with parameters passed inside a request body, as an alternative!");
    }

    const magicRow = document.getElementsByClassName('magic-row')[0];
    const paramsBuilderTableBody = magicRow.parentNode;

    magicRow.remove();

    // Create new row for the params builder section
    const paramsBuilderFormRow = document.createElement('tr');
    paramsBuilderFormRow.classList.add('form-builder-row');

    const firstTableCell = document.createElement('td');
    createKeyDropdown(firstTableCell);

    const secondTableCell = document.createElement('td');
    createOperatorDropdown(secondTableCell);

    const thirdTableCell = document.createElement('td');

    const valueInput = document.createElement('input');
    valueInput.setAttribute('type', 'text');
    valueInput.classList.add('value-input');
    valueInput.setAttribute('autocomplete', 'off');
    valueInput.setAttribute('placeholder', 'Enter value here...');
    valueInput.value = '';
    thirdTableCell.appendChild(valueInput);

    valueInput.addEventListener('blur', function(event) {
      const target = event.target;
      const formBuilderRow = target.closest('.form-builder-row');
      const keyDropdown = formBuilderRow.querySelector('.key-dropdown');
      const operatorDropdown = formBuilderRow.querySelector('.operator-dropdown');
      const operator = operatorDropdown.value;
      const value = valueInput.value.trim();

      if ((operator !== '=' && operator !== '!=') || ((keyDropdown.value === 'LIMIT' || keyDropdown.value === 'OFFSET'))) {
        if (value !== '' && isNaN(value)) {
          alert('This value must be numeric!');
          valueInput.value = '';
        }
      }
    }, true);

    valueInput.addEventListener('input', () => {
      attemptBuildQueryString();
    });

    const fourthTableCell = document.createElement('td');
    const trashIcon = document.createElement('i');
    trashIcon.setAttribute('class', 'fa fa-trash');
    fourthTableCell.appendChild(trashIcon);

    trashIcon.addEventListener('click', (ev) => {
      deleteRow(ev);
    });

    paramsBuilderFormRow.appendChild(firstTableCell);
    paramsBuilderFormRow.appendChild(secondTableCell);
    paramsBuilderFormRow.appendChild(thirdTableCell);
    paramsBuilderFormRow.appendChild(fourthTableCell);
    paramsBuilderTableBody.appendChild(paramsBuilderFormRow);
    addNewMagicRow(paramsBuilderTableBody);
  }

  function createKeyDropdown(parentElement) {
    // create a select element
    var select = document.createElement("select");
    select.setAttribute('class', 'key-dropdown');
    const tableRows = document.getElementsByClassName('form-builder-row');
    let keyOptions = columns.map(column => column.Field);
    const gotLimit = gotSelectedKey('LIMIT');
    if (gotLimit === false) {
      keyOptions.push('LIMIT');
    }

    const gotOffset = gotSelectedKey('OFFSET');
    if (gotOffset === false) {
      keyOptions.push('OFFSET');
    }

    const gotOrderBy = gotSelectedKey('ORDER BY');
    if (gotOrderBy === false) {
      keyOptions.push('ORDER BY');
    }

    // create the options and add them to the select element
    for (var i = 0; i < keyOptions.length; i++) {
      var option = document.createElement("option");
      option.value = keyOptions[i];
      option.text = keyOptions[i];
      select.appendChild(option);
    }

    // add event listener to the select element
    select.addEventListener('change', (ev) => {
      watchDropdown(ev, select.value);
    });

    // add the select element to the specified parent element
    parentElement.appendChild(select);
  }

  function watchDropdown(ev, selectedValue) {
    const keyDropdown = ev.target;
    const tr = keyDropdown.closest('tr');

    // Get references to the key, operator, and value dropdowns
    const operatorDropdown = tr.querySelector('.operator-dropdown');

    // If the selected key is "LIMIT", "OFFSET", or "ORDER BY"
    if (selectedValue === "LIMIT" || selectedValue === "OFFSET" || selectedValue === "ORDER BY") {
      // Set the operator dropdown to "=" and disable it
      operatorDropdown.value = "=";
      operatorDropdown.disabled = true;
    } else {
      // Otherwise, enable the operator dropdown
      operatorDropdown.disabled = false;
    }

    attemptBuildQueryString();
  }

  function addQueryStringToUrlSegments(queryString) {
    const endpointSegmentsEl = document.getElementById('endpoint-segments');
    const rootEndpointUrl = currentEndpoint['url_segments'];
    const targetUrl = rootEndpointUrl + queryString;
    endpointSegmentsEl.innerText = targetUrl;
    return targetUrl;
  }

  function gotSelectedKey(key) {
    const keyDropdowns = document.querySelectorAll('#params-builder-tbl .key-dropdown');
    let count = 0;

    for (var i = 0; i < keyDropdowns.length; i++) {
      var selectedValue = keyDropdowns[i].value;
      if (selectedValue === key) {
        return true;
      }
    }

    return false;
  }

  function createOperatorDropdown(parentElement) {
    // create a select element
    var select = document.createElement("select");
    select.setAttribute('class', 'operator-dropdown');

    // create the options and add them to the select element
    var options = [{
        value: "=",
        text: "="
      },
      {
        value: "!=",
        text: "!="
      },
      {
        value: "<",
        text: "<"
      },
      {
        value: "<=",
        text: "<="
      },
      {
        value: ">",
        text: ">"
      },
      {
        value: ">=",
        text: ">="
      }
    ];

    for (var i = 0; i < options.length; i++) {
      var option = document.createElement("option");
      option.value = options[i].value;
      option.text = options[i].text;

      if (options[i].value === "=") {
        option.selected = true;
      }

      select.appendChild(option);
    }

    // add the select element to the specified parent element
    parentElement.appendChild(select);

    select.addEventListener('change', () => {
      attemptBuildQueryString();
    });

  }

  function attemptBuildQueryString() {
    const tableRows = document.getElementsByClassName('form-builder-row');
    buildQueryString(tableRows);
  }

  function buildQueryString(tableRows) {
    //build a query string, add to UrlSegments element & return query string
    let queryString = '';
    for (var i = 0; i < tableRows.length; i++) {
      //extract the key, operator and value from each table row
      const thisTableRow = tableRows[i];
      let key = thisTableRow.querySelector('.key-dropdown').value;

      switch (key) {
        case 'ORDER BY':
          key = 'orderBy';
          break;
        case 'LIMIT':
          key = 'limit';
          break;
        case 'OFFSET':
          key = 'offset';
          break;
      }

      let operator = thisTableRow.querySelector('.operator-dropdown').value;

      switch (operator) {
        case "!=":
          operatorEncoded = "%21=";
          break;
        case "<=":
          operatorEncoded = "%3C=";
          break;
        case ">=":
          operatorEncoded = "%3E=";
          break;
        case "<":
          operatorEncoded = "%3C";
          break;
        case ">":
          operatorEncoded = "%3E";
          break;
        default:
          operatorEncoded = "=";
          break;
      }

      const value = thisTableRow.querySelector('.value-input').value;
      const introChar = (i == 0) ? '?' : '&';
      queryString += `${introChar}${key}${operatorEncoded}${encodeURIComponent(value)}`;
    }

    addQueryStringToUrlSegments(queryString);
    return queryString;
  }

  function deleteRow(event) {
    var row = event.target.parentNode.parentNode;
    row.parentNode.removeChild(row);
    attemptBuildQueryString();
  }

  function createServerResponseDiv(form) {
    const divTop = document.createElement('div');
    divTop.setAttribute('class', 'two-col');
    divTop.style.alignItems = 'flex-end';

    const divTopLeft = document.createElement('div');
    const label = document.createElement('label');
    label.textContent = 'Server Response ';
    divTopLeft.appendChild(label);

    const divTopRhs = document.createElement('div');
    divTopRhs.setAttribute('id', 'http-status-code');

    divTop.appendChild(divTopLeft);
    divTop.appendChild(divTopRhs);

    const divBtm = document.createElement('div');

    const textarea = document.createElement('textarea');
    textarea.setAttribute('disabled', 'true');
    textarea.setAttribute('class', 'server-response');
    textarea.setAttribute('id', 'server-response');

    divBtm.appendChild(textarea);

    form.appendChild(divTop);
    form.appendChild(divBtm);
  }

  function createDoubleTextarea(parentEl) {
    const container = document.createElement("div");
    container.classList.add("grid-2");

    // create first grid element
    const element1 = document.createElement("div");

    const divTopLhs = document.createElement('div');
    divTopLhs.setAttribute('class', 'two-col');
    divTopLhs.style.alignItems = 'flex-end';

    const label1 = document.createElement("label");
    label1.textContent = "Parameters";
    divTopLhs.appendChild(label1);
    element1.appendChild(divTopLhs);

    const textarea1 = document.createElement("textarea");
    textarea1.id = "params-input";
    textarea1.setAttribute('placeholder', 'Enter parameters in JSON format');

    if ((currentEndpointIndex === 'Create') || (currentEndpointIndex === 'Update')) {
      if (currentEndpoint.hasOwnProperty('enableParams')) {
        textarea1.setAttribute('onclick', 'enablePopulateOnClick()');
      }
    }

    element1.appendChild(textarea1);
    container.appendChild(element1);

    // create second grid element
    const element2 = document.createElement("div");

    const divTopRhs = document.createElement('div');
    divTopRhs.setAttribute('class', 'two-col');
    divTopRhs.style.alignItems = 'flex-end';

    const divTopLeft = document.createElement('div');
    const label = document.createElement('label');
    label.innerHTML = '<span class="hide-on-sm">Server </span>Response ';
    divTopLeft.appendChild(label);
    divTopRhs.appendChild(divTopLeft);
    element2.appendChild(divTopRhs);

    const divTopStatusCode = document.createElement('div');
    divTopStatusCode.setAttribute('id', 'http-status-code');
    divTopStatusCode.innerText = '';
    divTopRhs.appendChild(divTopStatusCode);

    const textarea2 = document.createElement("textarea");
    textarea2.id = "server-response";
    textarea2.disabled = true;
    element2.appendChild(textarea2);
    container.appendChild(element2);

    // append container to the document
    parentEl.appendChild(container);
  }

  function enablePopulateOnClick() {
    const paramsInput = document.getElementById('params-input');

    if (paramsInput.value !== '') {
      return;
    }

    const defaultValues = {};
    const columns = <?php echo json_encode($columns); ?>;

    columns.forEach(column => {
      if (column.Field !== 'id') {
        if (column.Type.includes('int') || column.Type.includes('float') || column.Type.includes('double') || column.Type.includes('decimal')) {
          defaultValues[column.Field] = 0;
        } else if (column.Type.includes('bool') || column.Type.includes('tinyint(1)')) {
          defaultValues[column.Field] = false;
        } else {
          defaultValues[column.Field] = '';
        }
      }
    });

    const jsonData = JSON.stringify(defaultValues, null, 2);
    paramsInput.value = jsonData;
  }

  function createDivWithCheckboxes(form) {
    // Create parent div
    const parentDiv = document.createElement("div");
    parentDiv.setAttribute('class', 'three-col');

    // Create child divs with inner text
    const firstChildDiv = document.createElement("div");
    firstChildDiv.innerHTML = "Bypass Auth<span class=\"hide-on-sm\">orization</span>";

    const secondChildDiv = document.createElement("div");
    secondChildDiv.innerHTML = "Clear Param<span class=\"hide-on-sm\">eter</span>s";
    const thirdChildDiv = document.createElement("div");
    thirdChildDiv.innerHTML = "Display Response Header<span class=\"hide-on-sm\"> Values</span>";

    // Create checkboxes and attach functions to them
    const firstCheckbox = document.createElement("input");
    firstCheckbox.type = "checkbox";
    firstCheckbox.onchange = function() {
      bypassAuthorization(firstCheckbox.checked);
    };

    const secondCheckbox = document.createElement("input");
    secondCheckbox.type = "checkbox";
    secondCheckbox.onchange = function() {
      setTimeout(() => {
        secondCheckbox.checked = false;
      }, 300);
      clearParameters(secondCheckbox.checked);
    };

    const thirdCheckbox = document.createElement("input");
    thirdCheckbox.type = "checkbox";
    thirdCheckbox.onchange = function() {
      displayResponseHeaders(thirdCheckbox.checked);
    };

    // Add checkboxes to child divs
    firstChildDiv.appendChild(firstCheckbox);
    secondChildDiv.appendChild(secondCheckbox);
    thirdChildDiv.appendChild(thirdCheckbox);

    //add header-info paragraph under the third checkbox
    const headerInfoPara = document.createElement('p');
    headerInfoPara.setAttribute('id', 'header-info');
    thirdChildDiv.appendChild(headerInfoPara);

    // Add child divs to parent div
    parentDiv.appendChild(firstChildDiv);

    if (currentEndpoint['enableParams'] == undefined) {
      // Remove the 'Clear Parameters' checkbox
      while (secondChildDiv.firstChild) {
        secondChildDiv.removeChild(secondChildDiv.lastChild);
      }
      secondChildDiv.innerHTML = '&nbsp;';
    }

    parentDiv.appendChild(secondChildDiv);
    parentDiv.appendChild(thirdChildDiv);

    // Append parent div to document body
    form.appendChild(parentDiv);
  }

  function displayResponseHeaders(checked) {
    // Display header info if checkbox is checked
    const headerInfoPara = document.getElementById('header-info');
    headerInfoPara.style.display = (checked) ? 'block' : 'none';
  }

  function bypassAuthorization(checked) {
    // Check if checkbox is checked or not
    if (checked) {
      initBypassAuth();
    } else {
      console.log("Authorization bypassed: not checked");
    }
  }

  function initBypassAuth() {
    const expiryDate = new Date();
    expiryDate.setHours(expiryDate.getHours() + 4);
    const expiryTimestamp = Date.parse(expiryDate) / 1000;
    const targetUrl = baseUrl + 'trongate_tokens/regenerate/' + specialToken + '/' + expiryTimestamp;
    const http = new XMLHttpRequest();
    http.open('get', targetUrl);
    http.setRequestHeader('Content-type', 'application/json');
    http.send();
    http.onload = () => {
      if (http.responseText === "false") {
        expiredSpecialToken();
      } else {
        specialToken = http.responseText;
        document.getElementById("input-token").value = specialToken;
        document.getElementById("token-value").innerHTML = specialToken;
        token = specialToken;
      }
    }
  }

  function generateNewSpecialToken(currentToken) {
    const expiryDate = new Date();
    expiryDate.setHours(expiryDate.getHours() + 4);
    const expiryTimestamp = Date.parse(expiryDate) / 1000;
    const targetUrl = baseUrl + 'trongate_tokens/regenerate/' + specialToken + '/' + expiryTimestamp;

    const http = new XMLHttpRequest();
    http.open('get', targetUrl);
    http.setRequestHeader('Content-type', 'application/json');
    http.send();
    http.onload = () => {
      if (http.responseText === "false") {
        expiredSpecialToken();
      } else {
        specialToken = http.responseText;
      }
    }
  }

  function createSubmitButton(parentEl) {
    const button = document.createElement('button');
    button.type = 'button';
    button.name = 'submit';
    button.value = 'Submit';
    button.id = 'submit-btn';
    button.textContent = 'Submit';
    button.onclick = submitRequest;
    const paragraph = document.createElement('p');
    paragraph.setAttribute('class', 'submit-para');
    paragraph.appendChild(button);
    parentEl.appendChild(paragraph);
  }

  function copyText() {
    const serverResponseEl = document.getElementById('server-response');
    const textToCopy = serverResponseEl.value;

    navigator.clipboard.writeText(textToCopy)
      .then(() => {
        alert("Server response copied to clipboard");
      })
      .catch((err) => {
        console.error('Error copying text: ', err);
      });
  }

  function viewSettings() {
    alert(JSON.stringify(endpoints[currentEndpointIndex]));
  }

  function displayEndpointDetails(parentEl) {
    const p = document.createElement('p');
    p.setAttribute('class', 'endpoint-details');

    const urlSegments = document.createElement('b');
    urlSegments.textContent = 'URL Segments: ';
    p.appendChild(urlSegments);

    const endpointUrl = document.createElement('span');
    endpointUrl.setAttribute('id', 'endpoint-segments');

    endpointUrl.textContent = getRootEndpointUrl();
    urlSegments.appendChild(document.createTextNode('/'));
    urlSegments.appendChild(endpointUrl);

    p.appendChild(document.createElement('br'));

    const requestType = document.createElement('b');
    requestType.textContent = 'Required HTTP Request Type: ';
    p.appendChild(requestType);

    const requestTypeValue = document.createElement('span');
    requestTypeValue.setAttribute('id', 'request-type');
    requestTypeValue.textContent = currentEndpoint['request_type'];
    requestType.appendChild(requestTypeValue);

    p.appendChild(document.createElement('br'));

    const endpointSettings = document.createElement('b');
    endpointSettings.textContent = 'Endpoint Settings: ';
    p.appendChild(endpointSettings);

    const endpointSettingsValue = document.createElement('span');
    endpointSettingsValue.setAttribute('id', 'endpoint-settings');
    endpointSettingsValue.textContent = '/modules/<?= $target_table ?>/assets/api.json';
    endpointSettings.appendChild(endpointSettingsValue);

    p.appendChild(document.createElement('br'));

    const currentToken = document.createElement('b');
    currentToken.textContent = 'Your Current Token: ';
    p.appendChild(currentToken);

    const tokenValue = document.createElement('span');
    tokenValue.setAttribute('id', 'token-value');
    tokenValue.innerHTML = token;
    currentToken.appendChild(tokenValue);
    parentEl.appendChild(p);
  }

  function createOtherActionButtons(parentElement) {
    const p = document.createElement('p');
    p.setAttribute('class', 'other-action-buttons');

    const copyButton = document.createElement('button');
    copyButton.setAttribute('class', 'alt');
    copyButton.setAttribute('type', 'button');
    copyButton.textContent = 'Copy Response Body';
    copyButton.addEventListener('click', copyText);

    const viewButton = document.createElement('button');
    viewButton.setAttribute('class', 'alt');
    viewButton.setAttribute('type', 'button');
    viewButton.textContent = 'View Endpoint Settings';
    viewButton.addEventListener('click', viewSettings);

    p.appendChild(copyButton);
    p.appendChild(viewButton);

    parentElement.appendChild(p);
  }
</script>