<?php

namespace Spatie\Ray\Payloads;

class TextPayload extends Payload
{
    /** @var string */
    protected $text;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        return [
            'content' => $this->formatContent(),
            'label' => 'Text',
        ];
    }

    protected function formatContent(): string
    {
        $result = htmlspecialchars($this->text, ENT_QUOTES | ENT_HTML5);

        return str_replace([' ', PHP_EOL], ['&nbsp;', '<br>'], $result);
    }
}
