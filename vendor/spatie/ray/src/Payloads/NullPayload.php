<?php

namespace Spatie\Ray\Payloads;

class NullPayload extends Payload
{
    /** @var bool */
    protected $bool;

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        return [
            'content' => null,
            'label' => 'Null',
        ];
    }
}
