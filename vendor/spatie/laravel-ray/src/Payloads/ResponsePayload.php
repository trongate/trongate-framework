<?php

namespace Spatie\LaravelRay\Payloads;

use Illuminate\Testing\TestResponse;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class ResponsePayload extends Payload
{
    /** @var int */
    protected $statusCode;

    /** @var array */
    protected $headers;

    /** @var string|null */
    protected $content;

    /** @var array|null */
    protected $json;

    public static function fromTestResponse(TestResponse $testResponse): self
    {
        return new self(
            $testResponse->getStatusCode(),
            $testResponse->headers->all(),
            $testResponse->content(),
            $json = rescue(function () use ($testResponse) {
                return $testResponse->json();
            }, null, false)
        );
    }

    public function __construct(int $statusCode, array $headers, string $content, ?array $json = null)
    {
        $this->statusCode = $statusCode;

        $this->headers = $this->normalizeHeaders($headers);

        $this->content = $content;

        $this->json = $json;
    }

    public function getType(): string
    {
        return 'response';
    }

    public function getContent(): array
    {
        return [
            'status_code' => $this->statusCode,
            'headers' => ArgumentConverter::convertToPrimitive($this->headers),
            'content' => $this->content,
            'json' => ArgumentConverter::convertToPrimitive($this->json),
        ];
    }

    protected function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(function (array $values) {
                return $values[0] ?? null;
            })
            ->filter()
            ->toArray();
    }
}
