<?php

namespace Spatie\LaravelRay\Payloads;

use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class CachePayload extends Payload
{
    /** @var string */
    protected $type;

    /** @var string[] */
    protected $tags;

    /** @var string */
    protected $key;

    /** @var mixed */
    protected $value;

    /** @var int|null */
    protected $expirationInSeconds;

    public function __construct(string $type, string $key, $tags, $value = null, int $expirationInSeconds = null)
    {
        $this->type = $type;

        $this->key = $key;

        $this->tags = is_array($tags) ? $tags : [$tags];

        $this->value = $value;

        $this->expirationInSeconds = $expirationInSeconds;
    }

    public function getType(): string
    {
        return 'table';
    }

    public function getContent(): array
    {
        $values = array_filter([
            'Event' => '<code>' . $this->type . '</code>',
            'Key' => $this->key,
            'Value' => ArgumentConverter::convertToPrimitive($this->value),
            'Tags' => count($this->tags) ? ArgumentConverter::convertToPrimitive($this->tags) : null,
            'Expiration in seconds' => $this->expirationInSeconds,
        ]);

        return [
            'values' => $values,
            'label' => 'Cache',
        ];
    }
}
