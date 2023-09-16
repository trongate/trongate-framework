<?php

namespace Spatie\Ray\Payloads;

class SeparatorPayload extends Payload
{
    public function getType(): string
    {
        return 'separator';
    }
}
