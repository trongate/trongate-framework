<?php

namespace Spatie\Ray\Payloads;

use Spatie\Ray\ArgumentConverter;

class TablePayload extends Payload
{
    /** @var array */
    protected $values;

    /** @var string */
    protected $label;

    public function __construct(array $values, string $label = 'Table')
    {
        $this->values = $values;

        $this->label = $label;
    }

    public function getType(): string
    {
        return 'table';
    }

    public function getContent(): array
    {
        $values = array_map(function ($value) {
            return ArgumentConverter::convertToPrimitive($value);
        }, $this->values);

        return [
            'values' => $values,
            'label' => $this->label,
        ];
    }
}
