let usingWebApp = 0;

//fetch data for the lhs and suggestions
function fetch_properties() {
    var target_url = apiBaseUrl + 'trongate_control-properties/get_fields_info';

    const http = new XMLHttpRequest()
    http.open('GET', target_url)
    http.setRequestHeader('Content-type', 'application/json')
    http.send()
    http.onload = function() {
        // Do whatever with response
        fieldsObj = JSON.parse(http.responseText);
        allPropertiesDataFromApi = Object.entries(fieldsObj)
        var btnCode = '<button onclick="clickLeftBtn(\'yyyy\', zzzz)" class="w3-button w3-block w3-white w3-border w3-border-blue">xxxx</button>';
        var propertiesBtnsCode = '';
        for (var i = 0; i < allPropertiesDataFromApi.length; i++) {
            propertiesBtnsCode += btnCode;
            // propertiesBtnsCode = propertiesBtnsCode.replace('xxxx', properties[i]['property_label']);
            // propertiesBtnsCode = propertiesBtnsCode.replace('yyyy', properties[i]['property_name']);
            //var rowData = JSON.stringify(properties[i]);
            var property_label = allPropertiesDataFromApi[i][1]['property_label'];
            propertiesBtnsCode = propertiesBtnsCode.replace('xxxx', property_label);
            var property_name = allPropertiesDataFromApi[i][1]['property_name'];
            propertiesBtnsCode = propertiesBtnsCode.replace('yyyy', property_label);
            propertiesBtnsCode = propertiesBtnsCode.replace('zzzz', i);
        }

        document.getElementById("properties_fields").innerHTML = propertiesBtnsCode;
    }

}

function buildValidationRulesFromApiResponse(availableValidationRules) {

    var initialPropertyValidationRules = [];

    for (var j = 0; j < availableValidationRules.length; j++) {

        if (availableValidationRules[j]['selected'] == 'yes') {
            var newValidationRule = buildValidationRuleFromAvailableRule(availableValidationRules[j]);
            initialPropertyValidationRules.push(newValidationRule);
        }

    }

    return initialPropertyValidationRules;

}

function buildValidationRuleFromAvailableRule(availableValidationRule) {

    var newValidationRule = availableValidationRule.rule_label;

    if (availableValidationRule.rule_requires_value == 'yes') {
        newValidationRule+= '[' + availableValidationRule.rule_default_value + ']';
    }

    return newValidationRule;
}

function addNewProperty() { //the btn on the modal was been clicked (hopefully after a new title was entered)

    initCloseAccordions = true;

    newPropertyTitle = document.getElementById('new-property-title').value;
    newPropertyTitle = newPropertyTitle.replace(/  +/g, ' ');
    var propertyType = allPropertiesDataFromApi[allPropertiesDataActiveIndex][1]['property_label'];
    var errorMsg = '';

    if ((propertyType == 'date range') || (propertyType == 'time range')) {

        var a = newPropertyTitle.includes(" and ");
        var b = newPropertyTitle.includes(" And ");
        
        if ((a == false) && (b == false)) {
            errorMsg = 'This kind of property must contain \'and\' or \'And\'.';
        }

    } else if(newPropertyTitle.length < 2) {
        errorMsg = 'Property title must be at least two characters.';
    }


    if (errorMsg !== '') {
        alert(errorMsg);
    } else {

        var availableCheckboxes = allPropertiesDataFromApi[allPropertiesDataActiveIndex][1]['checkboxes'];
        var availableValidationRules = allPropertiesDataFromApi[allPropertiesDataActiveIndex][1]['validation_rules'];
        var validationRules = buildValidationRulesFromApiResponse(availableValidationRules);

        var newProperty = {
            propertyName: newPropertyTitle,
            propertyType,
            availableCheckboxes,
            availableValidationRules,
            validationRules
        }

        properties.push(newProperty);
        var propertiesLength = properties.length;
        var propertiesTargetIndex = propertiesLength-1;

        for (var i = 0; i < availableCheckboxes.length; i++) {
            var checkboxName = availableCheckboxes[i]['name'];
            var isChecked = availableCheckboxes[i]['checked'];
            properties[propertiesTargetIndex][checkboxName] = isChecked;
        }

        document.getElementById('id01').style.display='none';
        document.getElementById('new-property-title').value = '';
        
        rebuildProperties();
        drawProperties();
    }

}

function rebuildProperties() {

    var oldProperties = properties;
    properties = [];

    for (var i = 0; i < oldProperties.length; i++) {
        if (oldProperties[i]['propertyName']) {
            properties.push(oldProperties[i]);
            adjustSubmitBtn();
        }
    }

    addToLocalStorage(properties);

}

function addToLocalStorage(newProperties) {

    var localProperties = [];    

    for (var i = 0; i < newProperties.length; i++) {

        var localPropertiesObj = {}

        localPropertiesObj.propertyName = newProperties[i]['propertyName'];
        localPropertiesObj.propertyType = newProperties[i]['propertyType'];

        if (newProperties[i]['onForm']) {
            localPropertiesObj.onForm = newProperties[i]['onForm'];
        }

        if (newProperties[i]['isSearchable']) {
            localPropertiesObj.isSearchable = newProperties[i]['isSearchable'];
        }

        if (newProperties[i]['onSummaryTable']) {
            localPropertiesObj.onSummaryTable = newProperties[i]['onSummaryTable'];
        }

        if (newProperties[i]['validationRules']) {
            localPropertiesObj.validationRules = newProperties[i]['validationRules'];
        }

        localProperties.push(localPropertiesObj);
    
    }

    localStorage.setItem('properties', JSON.stringify(localProperties));

}

function attemptAddScroll() {

}

function adjustSubmitBtn() {

    var accordions = document.getElementsByClassName("accordion");

    if (accordions.length<2) {
        document.getElementById("submit-btn").innerHTML = 'Submit Property';
    } else {
        document.getElementById("submit-btn").innerHTML = 'Submit Properties';
    }

    var centerStage = document.getElementById("current-properties");
    //  alert(centerStage.scrollHeight);

    // if (centerStage.scrollHeight>700) {
    //     document.body.style.overflow = 'auto';
    // }

    if (properties.length>2) {
        document.body.style.overflowY = 'auto';
    }
}

function buildPropertiesEditorLeft(i) {

    //build up the dropdown
    var targetId = 'validation-rules-dropdown-' + i;
    var targetAddRuleBtnId = 'add-new-rule-btn-' + i;
    var availableValidationRules = properties[i]['availableValidationRules'];

    var count = 0;
    var validationDropdownHTML = '<select onclick="disableDeletes()" onchange="disableDeletes()" class="w3-select w3-border" id="new-option-dropdown-' + i + '">';
    validationDropdownHTML+= '<option value="" disabled selected>Select Validation Rule...</option>';
    for (var x = 0; x < availableValidationRules.length; x++) {
        var isSelected = availableValidationRules[x]['selected'];

        if (isSelected == 'no') {
            count++;
            var rule_label = availableValidationRules[x]['rule_label'];
            validationDropdownHTML+= '<option value="' + rule_label + '">' + rule_label + '</option>';               
        }

    }

    validationDropdownHTML+= '</select>';
    
    if (count<1) {
        validationDropdownHTML = '&nbsp;';
        document.getElementById(targetAddRuleBtnId).style.display = 'none';
    } else {
        document.getElementById(targetAddRuleBtnId).style.display = 'block';
    }

    document.getElementById(targetId).innerHTML = validationDropdownHTML;

}

function clearSelectedValidationRules(d, i) {

    while (d.firstChild) {
        d.removeChild(d.firstChild);
    }

    //search, new modules, popular modules

}

function buildPropertiesEditorCenter(i) {

    var targetId = 'selected-validation-rules-' + i;
    var validationRules = properties[i]['validationRules'];
    var d = document.getElementById(targetId);

    clearSelectedValidationRules(d, i);
    
    for (var y = 0; y < validationRules.length; y++) {
        var selectedValidationRuleHTML = '';
        var thisRuleStr = validationRules[y];
        var gotMaxLength = thisRuleStr.indexOf("max length");

        if (gotMaxLength == 0) {
            var colSizeIndicatorValue = thisRuleStr.replace('max length[', '(');
            colSizeIndicatorValue = colSizeIndicatorValue.replace(']', ')');
            var colSizeIndicatorId = 'col-size-' + i;
            document.getElementById(colSizeIndicatorId).innerHTML = colSizeIndicatorValue;
        }

        var replace1 = '[<span onblur="checkValue(\'' + i + '\', \'' + y + '\')" contenteditable="true" id="validation-value-' + i + '-' + y + '">';
        var removeStr = '<span onclick="removeValitionRule(\'' + i + '\', \'' + y + '\')" class="remove-remove-rule">&#10008;</span>';
        thisRuleStr = thisRuleStr.replace(/\[/g, replace1);
        thisRuleStr = thisRuleStr.replace(/\]/g, '</span>]');
        thisRuleStr+= ' ' + removeStr;
        selectedValidationRuleHTML+= '<div>' + thisRuleStr + '</div>';
        d.append(selectedValidationRuleHTML);

    }

    document.getElementById(targetId).innerHTML = decodeHtml(document.getElementById(targetId).innerHTML);

}

function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function toggleCheckbox(propertiesTargetIndex, checkboxCode) {
    //the propertiesTargetIndex refers to the actual property that the checkboxes belong to

    //the checkboxCode will be (something like) onForm, isSearchable etc

    //get the current value
    var currentCheckboxValue = properties[propertiesTargetIndex][checkboxCode];

    if (currentCheckboxValue == 'yes') {
        newCheckboxValue = 'no';
    } else {
        newCheckboxValue = 'yes';
    }

    properties[propertiesTargetIndex][checkboxCode] = newCheckboxValue; 

    rebuildAccordionRow(propertiesTargetIndex);
    rebuildProperties();
}

function buildPropertiesEditorRight(i) {

    var checkboxesHTML = '';

    var checkboxCode = '<label class="control control-checkbox">';
        checkboxCode+= 'xxxx';
        checkboxCode+= '<input onclick="toggleCheckbox(\'' + i + '\', \'zzzz\')" type="checkbox"yyyy />';
        checkboxCode+= '<div class="control_indicator"></div>';
        checkboxCode+= '</label>';

    var checkboxLabel = '';

    if (properties[i]['onForm']) {
        checkboxLabel = 'On (Create) Form';
        checkboxesHTML+= checkboxCode;
        checkboxesHTML = checkboxesHTML.replace('xxxx', checkboxLabel);
        checkboxesHTML = checkboxesHTML.replace('zzzz', 'onForm');

        if (properties[i]['onForm'] == 'yes') {
            checkboxesHTML = checkboxesHTML.replace('yyyy', ' checked');
        } else {
            checkboxesHTML = checkboxesHTML.replace('yyyy', '');
        }
    }

    if (properties[i]['onSummaryTable']) {
        checkboxLabel = 'On Summary Table';
        checkboxesHTML+= checkboxCode;
        checkboxesHTML = checkboxesHTML.replace('xxxx', checkboxLabel);
        checkboxesHTML = checkboxesHTML.replace('zzzz', 'onSummaryTable');

        if (properties[i]['onSummaryTable'] == 'yes') {
            checkboxesHTML = checkboxesHTML.replace('yyyy', ' checked');
        } else {
            checkboxesHTML = checkboxesHTML.replace('yyyy', '');
        }
    }

    if (properties[i]['isSearchable']) {
        checkboxLabel = 'Searchable';
        checkboxesHTML+= checkboxCode;
        checkboxesHTML = checkboxesHTML.replace('xxxx', checkboxLabel);
        checkboxesHTML = checkboxesHTML.replace('zzzz', 'isSearchable');

        if (properties[i]['isSearchable'] == 'yes') {
            checkboxesHTML = checkboxesHTML.replace('yyyy', ' checked');
        } else {
            checkboxesHTML = checkboxesHTML.replace('yyyy', '');
        }
    }

    var targetId = 'checkboxes-' + i;

    document.getElementById(targetId).innerHTML = checkboxesHTML;

}

function checkValue(i, y) {
    //propertyId, validationRuleId

    var errorCount = 0;
    var oldValidationRule = properties[i]['validationRules'][y];

    var propertyType = properties[i]['propertyType'];
    var validationRule = properties[i]['validationRules'][y];
    var gotMaxLength = validationRule.indexOf("max length");
    var z1 = /^[0-9]*$/;
    var targetId = 'validation-value-' + i + '-' + y;
    var currentValue = document.getElementById(targetId).innerHTML;

    if ((propertyType == 'decimal') && (gotMaxLength == 0)) {

        var colTypeErr = '';

        if (currentValue.indexOf(",") == -1) {
            colTypeErr = 'The value needs to contain a comma.';
        } else {

            var valueBits = currentValue.split(",");

            if (valueBits.length !== 2) {
                colTypeErr = 'The value should be two numbers separated by a comma.';
            } else {

                for (var k = 0; k < valueBits.length; k++) {

                    if (valueBits[k].length == 0) {
                        colTypeErr = 'The col length should be two numbers separated by a comma.';
                    }

                    if (!z1.test(valueBits[k])) {

                        if (k == 0) {
                            colTypeErr = 'The string on the left hand side of the comma was not numeric.';
                        } else {
                            colTypeErr = 'The string on the right hand side of the comma was not numeric.';
                        }

                    }
                }

            }

        }

        if (colTypeErr !== '') {
            errorCount++;
            alert(colTypeErr);
            document.getElementById(targetId).innerHTML = '7,2';
        }

        if (errorCount == 0) {
            var newValidationRule = 'max length[' + currentValue + ']';
            properties[i]['validationRules'][y] = newValidationRule;
            //drawProperties();
        }

    } else {
        //ordinary numeric check
        if (!z1.test(currentValue)) { 
            errorCount++;
            alert('The value must numeric.');
            document.getElementById(targetId).innerHTML = currentValue.replace(/\D/g,'');
        }

        if (currentValue == '') {
            errorCount++;
            alert('The value must numeric.');
            document.getElementById(targetId).innerHTML = properties[i]['availableValidationRules'][y]['rule_default_value'];
        }

        if (errorCount == 0) {
            //update the properties array (validation rules)
            // javascript remove numbers from string
            var newValidationRule = oldValidationRule.replace(/[0-9]/g, '');
            var ditchStr = '[]';
            var replaceStr = '[' + currentValue + ']';
            newValidationRule = newValidationRule.replace(ditchStr, replaceStr);
            properties[i]['validationRules'][y] = newValidationRule;
            //drawProperties();
        }

    }

    rebuildAccordionRow(i);
    rebuildProperties();
}

function drawProperties() {

    var htmlStr = '';

    for (var i = 0; i < properties.length; i++) {

        var x = properties.length-1;
        if (i<x) {
            var accodionIcon = '⊕';
        } else {
            var accodionIcon = '⊖';
        }

    var btnCode = '<button onclick="clickAccordion()" class="accordion w3-blue-grey" id="upper-' + i + '">';
        btnCode+= properties[i]['propertyName'];
        btnCode+= '<span class="type-def">' + properties[i]['propertyType'] + '</span>';
        btnCode+= '<span class="col-size-def" id="col-size-' + i + '"></span>';
        btnCode+= '<span class="accordion-top-rhs-icon">' + accodionIcon + '</span>'; 
        btnCode+= '</button>';
        btnCode+= '<div class="accordion-content w3-light-grey" style="max-height: 999px;" id="lower-' + i + '">';
        btnCode+= '<div class="w3-row" style="font-size: 1.2em;">';

        btnCode+= '<div class="w3-quarter">';
        btnCode+= '<div id="validation-rules-dropdown-' + i + '"></div>';
        btnCode+= '<button onclick="addValidationRule(' + i + ')" id="add-new-rule-btn-' + i + '" class="w3-btn w3-small w3-blue-grey" id="add-new-rule-btn">Add New Rule</button>';
        btnCode+= '<button class="w3-btn w3-small w3-red" onclick="removeProperty(\'' + i + '\')">Delete This Property</button>';
        btnCode+= '</div>';
        btnCode+= '<div class="w3-half wrapper" id="selected-validation-rules-' + i + '" style="font-size: 1.05em;"></div>';
        btnCode+= '<div class="w3-quarter control-group" id="checkboxes-' + i + '"></div>';
        btnCode+= '</div>';
        btnCode+= '</div>';
        htmlStr+= btnCode;
    }

    document.getElementById("current-properties").innerHTML = htmlStr;

    for (var i = 0; i < properties.length; i++) {
        buildPropertiesEditorLeft(i);
        buildPropertiesEditorCenter(i);
        buildPropertiesEditorRight(i);
    }

    if (initCloseAccordions == true) {
        closeAccordions();
    }

    if (properties.length>0) {
        showCloseBtns();
    }

    initCloseAccordions = true; 

    adjustSubmitBtn();
    
}

function showCloseBtns() {

    var el = document.getElementById("close-btns");
    el.style.opacity = 1;
    el.style.marginLeft = 0;
}

function hideCloseBtns() {
    properties = [];
    document.getElementById("current-properties").innerHTML = '';
    var el = document.getElementById("close-btns");
    el.style.opacity = 0;
    el.style.marginLeft = '600px';
    document.body.style.overflow = 'hidden';
}

function autocomplete(inp, arr) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
      var a, b, i, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);
      /*for each item in the array...*/
      var suggestionCount = 0;
      for (i = 0; i < arr.length; i++) {
        /*check if the item starts with the same letters as the text field value:*/
        if ((arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) && (suggestionCount<15)) {
          suggestionCount++;

          /*create a DIV element for each matching element:*/
          b = document.createElement("DIV");
          /*make the matching letters bold:*/
          b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
          b.innerHTML += arr[i].substr(val.length);
          /*insert a input field that will hold the current array item's value:*/
          b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
          /*execute a function when someone clicks on the item value (DIV element):*/
          b.addEventListener("click", function(e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
              (or any other open lists of autocompleted values:*/
              closeAllLists();
          });
          a.appendChild(b);
        }
      }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
        x[i].parentNode.removeChild(x[i]);
      }
    }
  }
  /*execute a function when someone clicks in the document:*/
  document.addEventListener("click", function (e) {
      closeAllLists(e.target);
  });
}

function clickAccordion() {

    var thisEl = event.srcElement;

    if (thisEl.className !== 'accordion w3-blue-grey') {

        var parent = event.srcElement.parentNode;
        var parentInnerHtml = parent.innerHTML;
        var content = parent.nextElementSibling;

        if (content.style.maxHeight) {
            content.style.maxHeight = null;
            parent.innerHTML = parent.innerHTML.replace('⊖', '⊕');
        } else {
            content.style.maxHeight = content.scrollHeight + 'em';
            parent.innerHTML = parent.innerHTML.replace('⊕', '⊖');
        }

    } else {

        var parent = event.srcElement;
        var content = event.srcElement.nextElementSibling;

        if (content.style.maxHeight) {
            content.style.maxHeight = null;
            parent.innerHTML = parent.innerHTML.replace('⊖', '⊕');
        } else {
            content.style.maxHeight = content.scrollHeight + 'em';
            parent.innerHTML = parent.innerHTML.replace('⊕', '⊖');
        }

    }

}

function closeAccordions() {

    var accordions = document.getElementsByClassName("accordion");
    var content = '';

    for (var i = 0; i < accordions.length; i++) {

        var targetLen = accordions.length-1;
        var content = accordions[i].nextElementSibling;

        if (i<targetLen) {
            content.style.maxHeight = null;
        } else {
            content.style.maxHeight = content.scrollHeight + 'em';
        }
        
    }

}

function removeValitionRule(propertiesDataActiveIndex, validationRulesIndex) {

    allowDeletes = true;

    var targetRule = properties[propertiesDataActiveIndex]['validationRules'][validationRulesIndex];
    properties[propertiesDataActiveIndex]['validationRules'].splice(validationRulesIndex, 1);

    //let's add the thing onto the rules dropdown
    targetRule = targetRule.replace(/\d+/g, '');
    targetRule = targetRule.replace('[]', '');
    targetRule = targetRule.replace('[,]', '');

    //find the available validation rule that has this as the rule label
    var availableValidationRules = properties[propertiesDataActiveIndex]['availableValidationRules'];

    for (var x = 0; x < availableValidationRules.length; x++) {
        var rule_label = availableValidationRules[x]['rule_label'];

        if (rule_label == targetRule) {
            properties[propertiesDataActiveIndex]['availableValidationRules'][x]['selected'] = 'no';
        }
    }

    rebuildAccordionRow(propertiesDataActiveIndex);
    rebuildProperties();
}

function addValidationRule(i) {

    allowDeletes = true;

    // javascript get value of currently selected dropdown option on select menu
    var targetId = 'new-option-dropdown-' + i;
    var e = document.getElementById(targetId);
    var selectedRule = e.options[e.selectedIndex].value;

    if (selectedRule == '') {
        alert("You did not select a validation rule to add.");
    } else {


        var propertyValidationRules = properties[i]['availableValidationRules'];

        for (var w = 0; w < propertyValidationRules.length; w++) {

            var rule_label = propertyValidationRules[w]['rule_label'];

            if (rule_label == selectedRule) {

                var newValidationRule = buildValidationRuleFromAvailableRule(propertyValidationRules[w]);
                properties[i]['validationRules'].push(newValidationRule);
                properties[i]['availableValidationRules'][w]['selected'] = 'yes';

            }

        }


    }

    rebuildAccordionRow(i);
    //drawProperties();
    rebuildProperties();
}

function shutdownPropertiesBuilder() {

    document.getElementsByClassName('properties-selector')[0].style.opacity = 0;

    var delayTime = 0;
    for (var i = properties.length - 1; i >= 0; i--) {
        delayTime = delayTime + 24;            
        var upperEl = document.getElementById("upper-" + i);
        var lowerEl = document.getElementById("lower-" + i);
        delayedRemove(upperEl, lowerEl, delayTime, i)
    }
}

function delayedRemove(upperEl, lowerEl, delayTime, i) {
    setTimeout(() => {
    var d = document.getElementById("current-properties");
    var throwawayNode = d.removeChild(upperEl);
    var throwawayNode = d.removeChild(lowerEl);

    if (i==0) {
        hideCloseBtns();
    }

    }, delayTime);

}

function removeProperty(i) {
    //remove from array by index
    if (allowDeletes == true) {
        initCloseAccordions = false; 

        var removedProperty = false;

        while (removedProperty == false) {

            if (properties[i] !== null) {
                properties.splice(i, 1, properties);
                var d = document.getElementById("current-properties");
                var upperEl = document.getElementById("upper-" + i);
                var throwawayNode = d.removeChild(upperEl);
                var lowerEl = document.getElementById("lower-" + i);
                var throwawayNode = d.removeChild(lowerEl);

                removedProperty = true;
            }

          i--;

        }
 
    }

    rebuildProperties('deleting from');

    if ((properties.length<1) || (d.innerHTML == '')) {
        hideCloseBtns();
    }

}


function rebuildAccordionRow(i) {
    buildPropertiesEditorLeft(i);
    buildPropertiesEditorCenter(i);
    buildPropertiesEditorRight(i);
}

function disableDeletes() {

    allowDeletes = false;

    setTimeout(function(){
      allowDeletes = true;
    }, 4000);

}

function clickLeftBtn(propertyType, x) {

    if ((propertyType == 'date range') && (localStorage.hasOwnProperty("properties"))) {
        //restrict user to no more than one date range
        var selectedPropertiesStr = localStorage.getItem('properties');
        var selectedProperties = JSON.parse(selectedPropertiesStr);
        for (var i = 0; i < selectedProperties.length; i++) {
            var propertyType = selectedProperties[i]['propertyType'];
            if (propertyType == 'date range') {
                alert("Only one date range may be generated for a single module!");
                return;
            }
        }
    }

    allPropertiesDataActiveIndex = x;
    current_suggestions = allPropertiesDataFromApi[x][1]['suggestions'];

    document.getElementById('id01').style.display='block';
    document.getElementById('new-property-headline').innerHTML = `${propertyType}`;
    document.getElementById('new-property-title').value = '';
    document.getElementById("new-property-title").focus();
    document.getElementById("modal-instructions").innerHTML = 'Enter property title then hit \'Add New Property\'';

    autocomplete(document.getElementById("new-property-title"), current_suggestions);

    document.getElementById("add-property-form").style.display = 'block';
    document.getElementById("btn-add-new-property").style.display = 'block';
    document.getElementById("btn-add-address-dd").style.display = 'none';
    document.getElementById("btn-add-address").style.display = 'none';

}

function clickAddressBtn() {

    document.getElementById('id01').style.display='block';
    document.getElementById('new-property-headline').innerHTML = `Add Address`;
    document.getElementById('new-property-title').value = '';
    document.getElementById("new-property-title").focus();

    document.getElementById("modal-instructions").innerHTML = '<span class="blink" style="top: 3em; position: relative;">Fetching Address Types...</span>';

    document.getElementById("add-property-form").style.display = 'none';
    document.getElementById("btn-add-new-property").style.display = 'none';
    document.getElementById("btn-add-address").style.display = 'none';
    document.getElementById("btn-add-address-dd").style.display = 'none';

    fetchAddressTypes();

}

function requestAddressProperties(selectedAddressType) {

    var params = {
        addressType: selectedAddressType
    }

    document.getElementById("modal-instructions").innerHTML = '<span class="blink" style="top: 3em; position: relative;">Fetching Address Properties...</span>';

    document.getElementById("add-property-form").style.display = 'none';
    document.getElementById("btn-add-new-property").style.display = 'none';
    document.getElementById("btn-add-address").style.display = 'none';
    document.getElementById("btn-add-address-dd").style.display = 'none';

    var target_url = apiBaseUrl + 'trongate_control-properties/get_address_data';
    const http = new XMLHttpRequest()
    http.open('POST', target_url)
    http.setRequestHeader('Content-type', 'application/json')
    http.send(JSON.stringify(params))
    http.onload = function() {
        var addressProperties = JSON.parse(http.responseText);

        for (var i = 0; i < addressProperties.length; i++) {
            properties.push(addressProperties[i]);
        }

        document.getElementById('id01').style.display='none';
        document.getElementById('new-property-title').value = '';
        drawProperties();
        rebuildProperties();
        adjustSubmitBtn();
    }

}

function fetchAddressTypes() {

    var target_url = apiBaseUrl + 'trongate_control-properties/get_address_types';
    const http = new XMLHttpRequest()
    http.open('GET', target_url)
    http.setRequestHeader('Content-type', 'application/json')
    http.send()
    http.onload = function() {

        document.getElementById("modal-instructions").innerHTML = 'Choose an address type then hit \'Add Address\'.';
        document.getElementById("btn-add-address").style.display = 'block';
        document.getElementById("btn-add-address-dd").style.display = 'block';

        var addressTypes = JSON.parse(http.responseText);
        var optionsStr = '<option>Select Address Type...</option>';

        for (var i = 0; i < addressTypes.length; i++) {
            optionsStr+= '<option>' + addressTypes[i] + '</option>';
        }

        document.getElementById("address-type-selector").innerHTML = optionsStr;

    }

}

function addAddress() {
    initCloseAccordions = true;
    var selectedAddressType = document.getElementById("address-type-selector").value;

    if (selectedAddressType !== 'Select Address Type...') {
        requestAddressProperties(selectedAddressType);
    }

}

function areValuesOkay() {

    var result = true;

    for (var i = 0; i < properties.length; i++) {
        var propertyName = properties[i]['propertyName'];
        var numValidationRules = properties[i]['validationRules'].length;
        var validationRulesStr = JSON.stringify(properties[i]['validationRules']);

        var containsBadStr = validationRulesStr.indexOf("[]");

        if ((containsBadStr !== -1) && (numValidationRules>0)) {
            alert(propertyName + " contains a validation rule that has a missing value.");
            result = false;
        }

    }

    return result;

}

function attemptAdjustInnerContent() {
alert("running attemptAdjustInnerContent");
return;
    // Get parent viewport dimensions
    const parentWidth = window.parent.innerWidth;
    const parentHeight = window.parent.innerHeight;
    
    // Define viewport thresholds
    const minWidth = 1280;
    const minHeight = 720;
    const idealWidth = 1600;
    const idealHeight = 900;
    
    // Check if viewport is below minimum (not allowed - handled elsewhere)
    if ((parentWidth < minWidth) || (parentHeight < minHeight)) {
        return false;
    }
    
    // Check if viewport is at or above ideal size (no adjustment needed)
    if ((parentWidth >= idealWidth) && (parentHeight >= idealHeight)) {
        return true;
    }
    
    // Determine scale based on viewport width
    let scale;
    let origin;
    
    switch (true) {
        case (parentWidth >= 1599):
            scale = 1.0;
            origin = 'center center';
            break;
        case (parentWidth >= 1500):
            scale = 0.95;
            origin = 'left center';
            break;
        case (parentWidth >= 1400):
            scale = 0.90;
            origin = 'left center';
            break;
        case (parentWidth >= 1300):
            scale = 0.87;
            origin = 'left center';
            break;
        case (parentWidth >= 1280):
            scale = 0.85;
            origin = 'left center';
            break;
        default:
            scale = 0.85;
            origin = 'left center';
    }
    
    // Apply the scaling to the body
    const body = document.querySelector('body');
    body.style.zoom = scale;
    body.style.transform = `scale(${scale})`;
    body.style.transformOrigin = origin;
    
    return true;
}


function submitProperties(btnType) {

  shutdownPropertiesBuilder();
  var tempParams = {
    previousAction: 'submitProperties'
  }

  var tempParamsStr = JSON.stringify(tempParams);
  localStorage.setItem('tempParams', tempParamsStr);
  localStorage.setItem('action', 'closeSecondaryWindow');
  document.title = 1;  

  if (usingWebApp === 1) {

    if (btnType === 'submit') {
        // Properties are in localStorage — the parent will convert them to session
        setTimeout(function() {
            window.parent.postMessage('properties_submitted', '*');
        }, 1200);
    } else {
        setTimeout(() => {
            window.parent.postMessage('close', '*');
        }, 1200);
    }

  }
}

function getLocalStorage() {

    var json = {}

    var values = [],
        keys = Object.keys(localStorage),
        i = keys.length;

    while ( i-- ) {
        var thisKey = keys[i];
        var thisValue = localStorage.getItem(keys[i]);
        json[thisKey] = thisValue;
    }

    return json;
}

function getLastSegment() {
    // Get the current URL
    const url = window.location.href;
    
    // Extract the path from the URL
    const path = new URL(url).pathname;
    
    // Remove leading/trailing slashes and split by '/'
    const segments = path.replace(/^\/+|\/+$/g, '').split('/');
    
    // Return the last segment, or empty string if no segments
    return segments.length > 0 ? segments[segments.length - 1] : '';
}

window.addEventListener('load', function() {
    // attemptAdjustInnerContent();
    document.body.classList.add("showbg");
    document.getElementById("particles-div").style.opacity = 1;
    
    var cloakedElements = document.getElementsByClassName("cloak");
    for (var i = 0; i < cloakedElements.length; i++) {
        cloakedElements[i].style.opacity = 1;
    }

    const lastSegmentValue = getLastSegment();
    if (lastSegmentValue === 'web') {
        usingWebApp = 1;
    }

});
