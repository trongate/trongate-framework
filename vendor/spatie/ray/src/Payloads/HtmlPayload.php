<?php

namespace Spatie\Ray\Payloads;

class HtmlPayload extends Payload
{
    /** @var string */
    protected $html;

    public function __construct(string $html = '')
    {
        $this->html = $html;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        return [
            'content' => $this->html,
            'label' => 'HTML',
        ];
    }
}
