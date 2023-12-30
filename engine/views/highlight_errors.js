window.addEventListener("load", (ev) => {
  let inlineValidationForms =
    document.getElementsByClassName("highlight-errors");
  let validationErrors = JSON.parse(validationErrorsJson);

  if (inlineValidationForms.length > 0) {
    for (let i = 0; i < inlineValidationForms.length; i++) {
      attemptHighlightErrorFields(inlineValidationForms[i], validationErrors);
      drawValidationErrorsAlert(inlineValidationForms[i]);
    }
  }
  destroyInlineValidationBuilder();
});

function drawValidationErrorsAlert(targetForm) {
  let alertDiv = document.createElement("div");
  alertDiv.classList.add("validation-error-alert");
  alertDiv.classList.add("form-field-validation-error");
  let alertHeadline = document.createElement("h3");
  let gotFontAwesome = findCss("font-awesome");
  let iconCode =
    '<i class="fa fa-warning" style="font-size: 1.4em; margin-right: 0.2em;"></i> ';
  alertHeadline.innerHTML = gotFontAwesome == true ? iconCode : "";
  alertHeadline.innerHTML += "Ooops!  There was a problem.";

  let infoPara = document.createElement("p");
  let infoParaText = document.createTextNode(
    "You'll find more details highlighted below."
  );
  infoPara.appendChild(infoParaText);

  alertDiv.appendChild(alertHeadline);
  alertDiv.appendChild(infoPara);
  targetForm.prepend(alertDiv);
}

function findCss(fileName) {
  var finderRe = new RegExp(fileName + ".*?.css", "i");
  var linkElems = document.getElementsByTagName("link");
  for (var i = 0, il = linkElems.length; i < il; i++) {
    if (linkElems[i].href && finderRe.test(linkElems[i].href)) {
      return true;
    }
  }
  return false;
}

function attemptHighlightErrorFields(targetForm, validationErrors) {
  let allFormFields = targetForm.elements;
  for (const [key, value] of Object.entries(validationErrors)) {
    addErrorClasses(key, allFormFields);
  }
}

function addErrorClasses(key, allFormFields) {
  for (var i = 0; i < allFormFields.length; i++) {
    if (allFormFields[i]["name"] == key) {
      let formFieldType = allFormFields[i]["type"];
      if (formFieldType === "checkbox" || formFieldType === "radio") {
        let parentContainer = allFormFields[i].closest("div");
        parentContainer.classList.add("form-field-validation-error");
        parentContainer.style.textIndent = "7px";

        let previousSibling = parentContainer.previousSibling;
        if (previousSibling.classList.contains("validation-error-report")) {
          previousSibling.style.marginTop = "21px";
        }
      } else {
        allFormFields[i].classList.add("form-field-validation-error");
      }
    }
  }
}

function destroyInlineValidationBuilder() {
  let inlineValidationBuilders = document.getElementsByClassName(
    "inline-validation-builder"
  );
  for (let i = 0; i < inlineValidationBuilders.length; i++) {
    inlineValidationBuilders[i].remove();
  }
}
