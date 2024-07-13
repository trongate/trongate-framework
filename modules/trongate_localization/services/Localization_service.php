<?php
class Localization_service
{
    public ?Localization_driver $driver = null;

    public string $locale;

    public NumberFormatter $currencyFormatter;

    public string $currency;

    /**
     * Maps a string of language codes to their respective locales.
     *
     * Example: 'da:da_DK,en:en_US,...' => ['da' => 'da_DK', 'en' => 'en_US', ...]
     * @var array
     */
    public array $languageMappings = [];

    public function __construct()
    {
        $this->useConfiguredLocaleMappings();
    }

    public function driver(): Localization_driver
    {
        if (!$this->driver) {
            switch (LOCALIZATION_DRIVER) {
                case 'Filesystem_driver':
                default:
                    require_once __DIR__ . '/../drivers/Filesystem_driver.php';
                    $this->driver = new Filesystem_driver();
                    $this->driver->read();
                    break;
            }
        }

        return $this->driver;
    }

    /**
     * @return void
     */
    public function useConfiguredLocaleMappings(): void
    {
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

    public function supports(string $language)
    {
        return in_array($language, $this->driver()->languages());
    }

    public function load(?string $language = null, ?string $currency = null): static
    {
        if (empty($language)) {
            $language = $this->readLanguageFromHeader() ?? FALLBACK_LOCALE;
        }

        $this->locale = $this->compose_locale($language) ?? $this->compose_locale(FALLBACK_LOCALE);
        $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        $this->currency = $currency ?? $this->inferCurrencyFromLocale();

        return $this;
    }

    private function compose_locale(string $language): ?string
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

    private function readLanguageFromHeader(): ?string
    {
        $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        return $locale ?: null;
    }

    private function inferCurrencyFromLocale(): string
    {
        return $this->currencyFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
    }

    public function translations(?string $locale = null): array
    {
        $translations = $this->driver()->translations();

        return $translations[$locale ?? $this->locale] ?? $translations[FALLBACK_LOCALE];
    }

    public function translate(string $key, ?string $default = null, ?string $locale = null): string
    {
        if (empty($key)) {
            return $default;
        }

        if ($locale) {
            $locale = $this->compose_locale($locale);
        } else {
            $locale = $this->locale;
        }

        $language = array_flip($this->languageMappings)[$locale] ?? $locale;

        $translations = $this->translations($language);

        return (string) $translations[$key] ?? $default;
    }

    public function currency(float $value, ?string $currency = null): string
    {
        return $this->currencyFormatter->formatCurrency(
            amount: $value,
            currency: $currency ?? $this->currency
        );
    }
}