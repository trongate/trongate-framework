<?php

namespace Spatie\Ray\Payloads;

class HidePayload extends Payload
{
    public function getType(): string
    {
        return 'hide';
    }
}
