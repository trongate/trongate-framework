<?php

namespace Spatie\Ray\Payloads;

class LabelPayload extends Payload
{
    /** @var string */
    protected $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getType(): string
    {
        return 'label';
    }

    public function getContent(): array
    {
        return [
            'label' => $this->label,
        ];
    }
}
