<?php

namespace Spatie\Ray;

use Spatie\Ray\Payloads\Payload;

class Request
{
    /** @var string */
    protected $uuid;

    /** @var array */
    protected $payloads;

    /** @var array */
    protected $meta;

    public function __construct(string $uuid, array $payloads, array $meta = [])
    {
        $this->uuid = $uuid;

        $this->payloads = $payloads;

        $this->meta = $meta;
    }

    public function toArray(): array
    {
        $payloads = array_map(function (Payload $payload) {
            return $payload->toArray();
        }, $this->payloads);

        return [
            'uuid' => $this->uuid,
            'payloads' => $payloads,
            'meta' => $this->meta,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
