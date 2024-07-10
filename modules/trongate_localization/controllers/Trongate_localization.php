<?php

class Trongate_localization extends Trongate
{
    // ../assets/lang
    const LANG_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'lang';

    public array $languages = [];

    public string $locale;

    public NumberFormatter $currencyFormatter;

    public string $currency;

    public array $translations = [];

    /**
     * Maps a string of language codes to their respective locales.
     *
     * Example: 'da:da_DK,en:en_US,...' => ['da' => 'da_DK', 'en' => 'en_US', ...]
     * @var array
     */
    public array $languageMappings = [];

    #region Module scope
    public function __construct(?string $module_name = null)
    {
        parent::__construct($module_name);

        $fileIterator = new FilesystemIterator(self::LANG_PATH);

        foreach ($fileIterator as $file) {
            $language = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $this->languages[] = $language;

            // ../assets/lang/en.json
            $localePath = self::LANG_PATH . DIRECTORY_SEPARATOR . $language . '.json';
            $translations = file_get_contents($localePath);

            $this->translations[$language] = json_decode($translations, true);
        }

        $languageMappings = explode(',', LOCALE_MAPPINGS);

        foreach ($languageMappings as $mapping) {
            $pair = explode(':', $mapping, 2);

            if (count($pair) === 2) {
                $this->languageMappings[$pair[0]] = $pair[1];
            } else {
                $this->languageMappings[$pair[0]] = $pair[0];
            }
        }
    }

    public function _load_language(?string $language = null, ?string $currency = null): static
    {
        if (empty($language)) {
            $language = $this->readLanguageFromHeader() ?? FALLBACK_LOCALE;
        }

        if (!in_array($language, $this->languages)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'message' => 'Unsupported language',
                'locale' => $language,
                'supported' => $this->languages
            ]);
        }

        $this->locale = $this->_compose_locale($language) ?? $this->_compose_locale(FALLBACK_LOCALE);
        $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        $this->currency = $currency ?? $this->inferCurrencyFromLocale();

        return $this;
    }

    public function _compose_locale(string $language): ?string
    {
        if (isset($this->languageMappings[$language])) {
            return $this->languageMappings[$language];
        }

        $locale = Locale::composeLocale([
            'language' => Locale::getPrimaryLanguage($language),
            'script' => Locale::getScript($language),
            'region' => Locale::getRegion($language),
        ]);

        return $locale ?: null;
    }

    public function readLanguageFromHeader(): ?string
    {
        $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        return $locale ?: null;
    }

    public function inferCurrencyFromLocale(): string
    {
        return $this->currencyFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
    }

    public function translate(string $key, ?string $default = null, ?string $locale = null): string
    {
        $translations = (array) $this->translations[$locale ?? $this->locale] ?? $this->translations[FALLBACK_LOCALE];

        return (string) $translations[$key] ?? $default;
    }

    public function currency(float $value, ?string $currency = null): string
    {
        return $this->currencyFormatter->formatCurrency(
            amount: $value,
            currency: $currency ?? $this->currency
        );
    }
    #endregion

    #region Endpoints
    public function get_translations(): void
    {
        $this->_load_language();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'locale' => $this->locale,
            'currency' => $this->currency,
            'languages' => $this->languages,
            'translations' => $this->translations,
        ]);
    }

    public function update_translations(): void
    {
        api_auth();

        $data = json_decode(file_get_contents('php://input'), true);
        $key = $data['translation_string'];

        foreach($this->languages as $language) {
            $localePath = self::LANG_PATH . DIRECTORY_SEPARATOR . $language . '.json';
            $translations = $this->translations[$language];
            $value = $data[$language] ?? '';

            $translations[$key] = $value;
            file_put_contents($localePath, json_encode($translations, JSON_PRETTY_PRINT));
            $this->translations[$language] = $translations;
        }

        http_response_code(200);
    }
    #endregion
}