<?php

namespace Spatie\Ray\Payloads;

use Exception;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Support\PlainTextDumper;

class LogPayload extends Payload
{
    /** @var array */
    protected $values;

    /** @var array */
    protected $meta = [];

    public static function createForArguments(array $arguments): Payload
    {
        $dumpedArguments = array_map(function ($argument) {
            return ArgumentConverter::convertToPrimitive($argument);
        }, $arguments);

        return new static($dumpedArguments);
    }

    public function __construct($values, $rawValues = [])
    {
        if (! is_array($values)) {
            if (is_int($values) && $values >= 11111111111111111) {
                $values = (string) $values;
            }

            $values = [$values];
        }

        $this->meta = [
            [
                'clipboard_data' => $this->getClipboardData($rawValues),
            ],
        ];

        $this->values = $values;
    }

    public function getType(): string
    {
        return 'log';
    }

    public function getContent(): array
    {
        return [
            'values' => $this->values,
            'meta' => $this->meta,
        ];
    }

    protected function getClipboardData($value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        try {
            return PlainTextDumper::dump($value);
        } catch (Exception $ex) {
            return '';
        }
    }
}
