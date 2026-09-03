// Shim for TrongateCodeGenerator — provides the functions that
// mothership views reference in mx-after-swap attributes.
window.TrongateCodeGenerator = {
	focusOnInput: function () {
		// Remove cloak from any hidden content
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		// Focus the first text input
		var targetEl = document.querySelector('main > .center-stage input[type=text]');
		if (targetEl) { targetEl.focus(); }
	},

	handleAfterMx: function () {
		// Remove cloak from any hidden content
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		// Focus the first text input in main
		var targetEl = document.querySelector('main input[type=text]');
		if (targetEl) { targetEl.focus(); }
	}
};

function focusOnInput() {
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		var targetEl = document.querySelector('main > .center-stage input[type=text]');
		if (targetEl) { targetEl.focus(); }
	}

	function doReset() {
	var frame = document.querySelector('.blue-frame');
	var main = document.querySelector('main');

	if (!frame || !main) return;

	// 1. Greyscale the entire modal — Atari-style reset
	frame.classList.add('greyscale');

	// 2. Show blinking reset text
	main.innerHTML = '<div class="center-stage mt-3"><span class="blink">~ Resetting ~</span></div>';

	// 3. After 1.2 seconds, restore colour and load home menu
	var baseUrl = document.querySelector('base').getAttribute('href');

	setTimeout(function () {
		frame.classList.remove('greyscale');

		fetch(baseUrl + 'trongate_control-evo/home')
			.then(function (r) { return r.text(); })
			.then(function (html) {
				main.innerHTML = html;
				TrongateCodeGenerator.focusOnInput();
			});

		// Clear server-side wizard state
		fetch(baseUrl + 'trongate_control-evo/reset', { method: 'POST' });
	}, 1200);
}
/**
 * Populate form fields from localStorage using a storage-key -> field-ID map.
 * Shared by Flo features that offer a 'view/confirm details' overlay.
 *
 * @param {Object} [fieldMapping] storageKey -> fieldId map (falls back to window.floFieldMapping)
 */
function populateFormFromLocalStorage(fieldMapping) {
    var mapping = fieldMapping || window.floFieldMapping || {};

    Object.entries(mapping).forEach(function (entry) {
        var storageKey = entry[0];
        var fieldId = entry[1];
        var value = localStorage.getItem(storageKey);

        if (value !== null) {
            var field = document.getElementById(fieldId);
            if (field) {
                if (fieldId === 'properties-input') {
                    // Beautify JSON for the properties textarea
                    try {
                        var parsedJSON = JSON.parse(value);
                        field.value = JSON.stringify(parsedJSON, null, 2);
                    } catch (e) {
                        field.value = value;
                    }
                } else {
                    field.value = value;
                }
            }
        }
    });

    // Populate URL Column and Order By dropdowns based on properties
    populateSelectDropdowns();
}

/**
 * Populate URL Column + Order By dropdowns from the stored properties array.
 *
 * @param {Object} [config] {propertiesKey, urlColumnSelectId, orderBySelectId}
 */
function populateSelectDropdowns(config) {
    var cfg = config || {};
    var propertiesKey = cfg.propertiesKey || 'properties';
    var urlColumnSelectId = cfg.urlColumnSelectId || 'urlColumn-input';
    var orderBySelectId = cfg.orderBySelectId || 'orderBy-input';

    var propertiesValue = localStorage.getItem(propertiesKey);
    if (!propertiesValue) return;

    var properties = [];
    try {
        properties = JSON.parse(propertiesValue);
    } catch (e) {
        return;
    }

    // URL Column dropdown
    var urlColumnSelect = document.getElementById(urlColumnSelectId);
    if (urlColumnSelect) {
        urlColumnSelect.innerHTML = '';

        var noUrlOption = document.createElement('option');
        noUrlOption.value = '';
        noUrlOption.textContent = '-- No URL Column --';
        urlColumnSelect.appendChild(noUrlOption);

        properties.forEach(function (prop) {
            var option = document.createElement('option');
            option.value = prop.propertyName;
            option.textContent = prop.propertyName;
            urlColumnSelect.appendChild(option);
        });

        var storedUrlColumn = localStorage.getItem('urlColumn');
        if (storedUrlColumn && storedUrlColumn.trim() !== '') {
            urlColumnSelect.value = storedUrlColumn;
        } else {
            urlColumnSelect.value = '';
        }
    }

    // Order By dropdown
    var orderBySelect = document.getElementById(orderBySelectId);
    if (orderBySelect) {
        orderBySelect.innerHTML = '';

        var idOption = document.createElement('option');
        idOption.value = 'id';
        idOption.textContent = 'id';
        orderBySelect.appendChild(idOption);

        var idDescOption = document.createElement('option');
        idDescOption.value = 'id DESC';
        idDescOption.textContent = 'id DESC';
        orderBySelect.appendChild(idDescOption);

        properties.forEach(function (prop) {
            var columnName = prop.propertyName.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '_');
            var option = document.createElement('option');
            option.value = columnName;
            option.textContent = prop.propertyName;
            orderBySelect.appendChild(option);

            var descOption = document.createElement('option');
            descOption.value = columnName + ' DESC';
            descOption.textContent = prop.propertyName + ' DESC';
            orderBySelect.appendChild(descOption);
        });

        var storedOrderBy = localStorage.getItem('orderBy');
        if (storedOrderBy && storedOrderBy.trim() !== '') {
            orderBySelect.value = storedOrderBy;
        } else {
            orderBySelect.value = 'id';
        }
    }
}

/**
 * After successful MX validation: sync posted values to localStorage and close overlay.
 *
 * @param {Object} [fieldMapping] postedField -> storageKey map (falls back to window.floPostedToStorageMap)
 */
function afterValidation(fieldMapping) {
    var mapping = fieldMapping || window.floPostedToStorageMap || {};

    try {
        var container = document.querySelector('.posted-items-container');
        if (!container) {
            console.error('posted-items-container div not found');
            alert("Validation was successful");
            return;
        }
        var jsonText = container.textContent.trim();
        var postedData = JSON.parse(jsonText);

        Object.entries(mapping).forEach(function (entry) {
            var postedKey = entry[0];
            var storageKey = entry[1];
            if (postedData.hasOwnProperty(postedKey)) {
                localStorage.setItem(storageKey, postedData[postedKey]);
            }
        });

        var closeBtn = document.querySelector('.close-btn');
        closeBtn.click();
    } catch (e) {
        console.error('Error extracting or parsing posted data:', e);
        alert("Error processing response");
    }
}
