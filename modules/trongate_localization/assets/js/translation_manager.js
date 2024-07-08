
var translations;

fetch("/trongate_localization/get_translations")
    .then((response) => {
        return response.json()
    })
    .then((data) => {
        translations = data;
    })
    .catch((error) => {
        alert("There was an error fetching translations. Please try again.");
        console.error("Error:", error);
    });

function translate(key, fallback = undefined) {
    var returnValue;

    if (translations.hasOwnProperty(key)) {
        returnValue = translations[key];
    } else {
        returnValue = fallback === undefined ? key : fallback;
    }

    return String(returnValue);
}

var t = translate;