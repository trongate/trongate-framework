<?php

namespace Spatie\Ray\Payloads;

class XmlPayload extends Payload
{
    /** @var string */
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        $content = $this->formatXmlForDisplay($this->value);

        return [
            'content' => $content,
            'label' => 'XML',
        ];
    }

    protected function formatXmlForDisplay(string $xml): string
    {
        $content = $this->formatAndIndentXml($xml);

        return $this->encodeXml(trim($content));
    }

    protected function encodeXml(string $xml): string
    {
        $result = htmlentities($xml);

        return str_replace([PHP_EOL, "\n", ' '], ['<br>', '<br>', '&nbsp;'], $result);
    }

    protected function formatAndIndentXml(string $xml): string
    {
        if (! class_exists(\DOMDocument::class)) {
            return $xml;
        }

        $dom = new \DOMDocument();

        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->formatOutput = true;

        if (! $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $xml;
        }

        return $dom->saveXML();
    }
}
