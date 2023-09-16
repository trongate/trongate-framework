<?php

namespace Spatie\Ray\Payloads;

class NewScreenPayload extends Payload
{
    /** @var mixed */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return 'new_screen';
    }

    public function getContent(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
