<?php

namespace Spatie\Ray\Payloads;

class CustomPayload extends Payload
{
    /** @var string */
    protected $content;

    /** @var string */
    protected $label;

    public function __construct(string $content, string $label = '')
    {
        $this->content = $content;

        $this->label = $label;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        return [
            'content' => $this->content,
            'label' => $this->label,
        ];
    }
}
