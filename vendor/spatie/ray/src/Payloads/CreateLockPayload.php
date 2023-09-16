<?php

namespace Spatie\Ray\Payloads;

class CreateLockPayload extends Payload
{
    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return 'create_lock';
    }

    public function getContent(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
