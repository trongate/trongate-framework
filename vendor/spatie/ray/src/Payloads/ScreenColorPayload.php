<?php

namespace Spatie\Ray\Payloads;

class ScreenColorPayload extends Payload
{
    /** @var string */
    protected $color;

    public function __construct(string $color)
    {
        $this->color = $color;
    }

    public function getType(): string
    {
        return 'screen_color';
    }

    public function getContent(): array
    {
        return [
            'color' => $this->color,
        ];
    }
}
