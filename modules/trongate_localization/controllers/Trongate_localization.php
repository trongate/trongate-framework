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

    #region Module scope
    public function __construct(?string $module_name = null)
    {
        parent::__construct($module_name);

        $fileIterator = new FilesystemIterator(self::LANG_PATH);

        foreach ($fileIterator as $file) {
            $this->languages[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }
    }

    public function _load_locale(?string $locale = null, ?string $currency = null): static
    {
        if (empty($locale)) {
            $locale = $this->readLanguageFromHeader() ?? FALLBACK_LOCALE;
        }

        if (!in_array($locale, $this->languages)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'message' => 'Unsupported locale',
                'locale' => $locale,
                'supported' => $this->languages
            ]);
        }

        $this->locale = $locale;

        Locale::setDefault($this->locale);
        $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        $this->currency = $currency ?? $this->inferCurrencyFromLocale();

        // ../assets/lang/en.json
        $localePath = self::LANG_PATH . DIRECTORY_SEPARATOR . $this->locale . '.json';

        if (!file_exists($localePath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'message' => 'Translations not found',
                'locale' => $this->locale,
                'path' => $localePath
            ]);
        }

        $translations = file_get_contents($localePath);

        $this->translations = json_decode($translations, true);

        return $this;
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

    public function translate(string $key, ?string $default = null, ?string $locale = null, ?string $currency = null): string
    {
        if ($locale) {
            $this->_load_locale($locale, $currency);
        }

        return (string) $this->translations[$key] ?? $default;
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
    public function list_languages(): void
    {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($this->languages);
    }

    public function get_translations(): void
    {
        $locale = segment(3, 'string');

        $this->_load_locale($locale);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($this->translations);
    }
    #endregion
}