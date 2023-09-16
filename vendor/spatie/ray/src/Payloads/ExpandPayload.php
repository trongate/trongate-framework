<?php

namespace Spatie\Ray\Payloads;

class ExpandPayload extends Payload
{
    /** @var array */
    protected $keys = [];

    /** @var int|null */
    protected $level = null;

    public function __construct(array $values = [])
    {
        foreach($values as $value) {
            if (is_numeric($value)) {
                $this->level = max($this->level, $value);

                continue;
            }

            $this->keys[] = $value;
        }
    }

    public function getType(): string
    {
        return 'expand';
    }

    public function getContent(): array
    {
        return [
            'keys' => $this->keys,
            'level' => $this->level,
        ];
    }
}
