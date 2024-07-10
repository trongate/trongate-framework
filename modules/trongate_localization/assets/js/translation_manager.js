var translations_data = {
    locale: 'en',
    currency: 'USD',
    language: 'en',
    languages: ['en'],
    translations: {}
};

fetch("/trongate_localization/get_translations")
    .then((response) => {
        return response.json();
    })
    .then((data) => {
        translations_data = data;

        const event = new Event('translationsLoaded', { bubbles: true, cancelable: true });
        document.dispatchEvent(event);
    })
    .catch((error) => {
        alert("There was an error fetching translations. Please try again.");
        console.error("Error:", error);
    });

function translate(key, fallback = undefined, locale = undefined) {
    var translations = translations_data.translations[locale || translations_data.language];
    var returnValue;

    if (translations.hasOwnProperty(key)) {
        returnValue = translations[key];
    } else {
        returnValue = fallback === undefined ? key : fallback;
    }

    return String(returnValue);
}

var t = translate;

document.addEventListener('translationsLoaded', function () {
    console.log('Translations loaded');
    document.querySelectorAll('[data-translate]').forEach(function (element) {
        const shouldTranslate = element.getAttribute('data-translate').toLowerCase() === 'true';

        if (shouldTranslate) {
            const key = element.innerHTML;
            element.innerHTML = translate(key);
            element.dataset.translateKey = key;
        }
    });
});