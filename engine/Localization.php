<?php

class Translations_not_found_exception extends RuntimeException {
    public function __construct(
        public string $locale,
        public string $path
    )
    {
        parent::__construct(
            sprintf(
                'Locale file not found: %s',
                $this->locale
            )
        );
    }
}

class Localization
{
    public static WeakMap $instances;

    /**
     * Retrieve a shared instance of the Localization class.
     *
     * @param string|null $locale
     * @param string|null $currency
     * @return self
     */
    public static function getInstance(?string $locale = null, ?string $currency = null): self
    {
        if (!isset(self::$instances)) {
            self::$instances = new WeakMap();
        }

        if (!self::$instances->offsetExists($locale)) {
            self::$instances->offsetSet($locale, new self($locale, $currency));
        }

        return self::$instances->offsetGet($locale);
    }

    public string $locale;

    public NumberFormatter $currencyFormatter;

    public string $currency;

    public array $translations = [];

    public function __construct(?string $locale = null, ?string $currency = null)
    {
        $this->setLocale(
            $locale
                ?? $this->readLanguageFromHeader()
                ?? FALLBACK_LOCALE
        );

        $this->setCurrency(
            $currency
                ?? $this->inferCurrencyFromLocale()
        );

        $this->attemptLoadTranslations();
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        Locale::setDefault($locale);

        $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        return $this;
    }

    private function readLanguageFromHeader(): ?string
    {
        $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        return $locale ?: null;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    private function inferCurrencyFromLocale(): string
    {
        return $this->currencyFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
    }

    /**
     * Attempt to load the translations from the json file into memory.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function attemptLoadTranslations(): void
    {
        $localePath = sprintf(
            '%s%s%s%s%s',
            APPPATH,
            'lang',
            DIRECTORY_SEPARATOR,
            $this->locale,
            '.json'
        );

        if (!file_exists($localePath)) {
            throw new Translations_not_found_exception(
                $this->locale,
                $localePath
            );
        }

        $translations = file_get_contents($localePath);

        $this->translations = json_decode($translations, true);
    }

    public function translate(string $key, ?string $default = null): string
    {
        return (string) $this->translations[$key] ?? $default;
    }

    public function formatCurrency(float $value, ?string $currency = null): string
    {
        return $this->currencyFormatter->formatCurrency(
            amount: $value,
            currency: $currency ?? $this->currency
        );
    }
}