<?php

namespace Spatie\LaravelRay\Payloads;

use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class EventPayload extends Payload
{
    /** @var string */
    protected $eventName;

    /** @var object|mixed|null */
    protected $event = null;

    /** @var array */
    protected $payload = [];

    public function __construct(string $eventName, array $payload)
    {
        $this->eventName = $eventName;

        class_exists($eventName)
            ? $this->event = $payload[0]
            : $this->payload = $payload;
    }

    public function getType(): string
    {
        return 'event';
    }

    public function getContent(): array
    {
        return [
            'name' => $this->eventName,
            'event' => $this->event ? ArgumentConverter::convertToPrimitive($this->event) : null,
            'payload' => count($this->payload) ? ArgumentConverter::convertToPrimitive($this->payload) : null,
            'class_based_event' => ! is_null($this->event),
        ];
    }
}
