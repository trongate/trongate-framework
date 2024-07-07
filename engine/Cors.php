<?php

class Cors
{
    const ALLOWED_ORIGINS = 'Access-Control-Allow-Origin';
    const ALLOWED_METHODS = 'Access-Control-Allow-Methods';
    const ALLOWED_HEADERS = 'Access-Control-Allow-Headers';
    const ALLOWED_CREDENTIALS = 'Access-Control-Allow-Credentials';

    protected array $allowedOrigins = [];
    protected array $allowedOriginsPatterns = [];

    public function __construct(
        protected string $allowedOriginsString = '',
        protected string $allowedMethodsString = '',
        protected string $allowedHeadersString = '',
        protected bool $allowedCredentials = false
    ) {
        $this->gatherAllowedOrigins();
        $this->setAllowedMethods();
        $this->setAllowedHeaders();
    }

    public function defineHeaders(string $origin): void
    {
        $headers = $this->match($origin);

        foreach ($headers as $header => $value) {
            header(sprintf('%s: %s', $header, $value));
        }
    }

    public function match(string $origin): array
    {
        $headers = [
            self::ALLOWED_HEADERS => $this->allowedHeadersString,
            self::ALLOWED_METHODS => $this->allowedMethodsString,
        ];

        if ($matchedOrigin = $this->matchOrigin($origin)) {
            $headers[self::ALLOWED_ORIGINS] = $matchedOrigin;

            if ($this->allowedCredentials) {
                $headers[self::ALLOWED_CREDENTIALS] = 'true';
            }
        }

        return $headers;
    }

    private function matchOrigin(string $origin): ?string
    {
        // Exact match
        if (in_array($origin, $this->allowedOrigins)) {
            return $origin;
        }

        // Wildcard pattern match
        foreach ($this->allowedOriginsPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return $origin;
            }
        }

        return null;
    }

    /**
     * Gathers the provided allowed origins string and converts it to an array of exact origins and wildcard patterns.
     *
     * @return void
     */
    private function gatherAllowedOrigins(): void
    {
        if (!empty($this->allowedOriginsString)) {
            $this->allowedOrigins = explode(',', $this->allowedOriginsString);

            foreach ($this->allowedOrigins as $origin) {
                if (str_contains($origin, '*')) {
                    $this->allowedOriginsPatterns[] = $this->convertWildcardToPattern($origin);
                }
            }
        }
    }


    /**
     * Convert a wildcard pattern to a valid regex pattern.
     * e.g. "*.example.com" is converted to "#^.*\.example\.com\z#u"
     * which matches "www.example.com", "sub.example.com", "example.com", etc.
     *
     * @param string $pattern
     * @return string
     */
    private function convertWildcardToPattern(string $pattern): string
    {
        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "*.example.com", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return '#^' . $pattern . '\z#u';
    }

    private function setAllowedMethods(): void
    {
        if (!empty($this->allowedMethodsString)) {
            header(
                sprintf(
                    'Access-Control-Allow-Methods: %s',
                    $this->allowedMethodsString
                )
            );
        }
    }

    private function setAllowedHeaders(): void
    {
        if (!empty($this->allowedHeadersString)) {
            header(
                sprintf(
                    'Access-Control-Allow-Headers: %s',
                    $this->allowedHeadersString
                )
            );
        }
    }
}