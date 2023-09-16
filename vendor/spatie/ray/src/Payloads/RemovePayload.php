<?php

namespace Spatie\Ray\Payloads;

class RemovePayload extends Payload
{
    public function getType(): string
    {
        return 'remove';
    }
}
