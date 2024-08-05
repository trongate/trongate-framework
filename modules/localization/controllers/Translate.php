<?php

trait Translate
{
    /**
     * Maps a string of locales to their respective languages.
     *
     * Example: 'da:da_DK,en:en_US,...' => ['da' => 'da_DK', 'en' => 'en_US', ...]
     * @var array
     */
    public array $localeMappings = [];

    /**
     * The fallback locale to use when no locale is specified.
     *
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * INTL NumberFormatter instance for currency formatting.
     *
     * @var NumberFormatter
     */
    public NumberFormatter $currencyFormatter;

    /**
     * The currency code to use for currency formatting.
     *
     * @var string|null
     */
    public ?string $currency = null;

    /**
     * The translations for the current locale.
     *
     * @var array
     */
    public array $translations = [];

    /**
     * The locales available for translations.
     *
     * @var array
     */
    public array $locales = [];

    #region Methods
    public function translate(string $key, ?string $default = null, ?string $locale = null): string
    {
        if ($default === null) {
            $default = $key;
        }

        if (empty($key)) {
            return $default;
        }

        $this->locale($locale);

        $query_result = $this->model->query_bind(
            sql: 'SELECT value
                FROM `localization`
                WHERE `locale` = :locale
                AND `key` = :key
                ORDER BY `id` DESC
                LIMIT 1',
            data: [
                'locale' => $this->locale,
                'key' => $key,
            ]
        );

        $result = $query_result ? $query_result['value'] : $default;

        return (string) $result;
    }

    public function currency(float $value, ?string $currency = null): string
    {
        return $this->currencyFormatter->formatCurrency(
            amount: $value,
            currency: $currency ?? $this->currency
        );
    }
    #endregion

    #region Getters
    public function translations(?string $locale = null): array
    {
        if (empty($this->translations)) {
            $this->translations = $this->model->query(
                sql: 'SELECT `key`, `value` FROM `localization`',
                return_type: 'object'
            );
        }

        if ($locale) {
            return $this->translations[$this->locale($locale)] ?? $this->translations[$locale];
        }

        return $this->translations;
    }

    /**
     * The locale to use for translations.
     *
     * @param string|null $locale
     * @return string
     */
    public function locale(?string $locale = null): string
    {
        if ($locale) {
            $locale = $this->localeMappings()[$locale] ?? $locale;
        }

        if ($this->locale === null || $locale !== $this->locale) {
            if (!empty($locale)) {
                $this->locale = $locale;
            } elseif ($fromHttpHeader = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $this->locale = $fromHttpHeader;
            } else {
                $this->locale = FALLBACK_LOCALE;
            }

            // Attempt to expand to a full locale string.
            if (isset($this->localeMappings()[$this->locale])) {
                $this->locale = $this->localeMappings()[$this->locale];
            }

            $this->currencyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
            $this->currency = $this->currencyFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        }

        return $this->locale;
    }

    /**
     * Get or resolve the locale mappings.
     *
     * @return array
     */
    public function localeMappings(): array
    {
        if (empty($this->localeMappings)) {
            $mappings = explode(',', LOCALE_MAPPINGS);

            foreach ($mappings as $mapping) {
                $pair = explode(':', $mapping, 2);

                if (count($pair) === 2) {
                    $this->localeMappings[$pair[0]] = $pair[1];
                } else {
                    $this->localeMappings[$pair[0]] = $pair[0];
                }
            }
        }

        return $this->localeMappings;
    }
    #endregion
}