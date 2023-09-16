<?php

namespace Spatie\Ray\Payloads;

use Carbon\CarbonInterface;

class CarbonPayload extends Payload
{
    /** @var \Carbon\CarbonInterface|null */
    protected $carbon;

    /** @var string */
    protected $format;

    public function __construct(?CarbonInterface $carbon, string $format = 'Y-m-d H:i:s')
    {
        $this->carbon = $carbon;

        $this->format = $format;
    }

    public function getType(): string
    {
        return 'carbon';
    }

    public function getContent(): array
    {
        return [
            'formatted' => $this->carbon ? $this->carbon->format($this->format) : null,
            'timestamp' => $this->carbon ? $this->carbon->timestamp : null,
            'timezone' => $this->carbon ? $this->carbon->timezone->getName() : null,
        ];
    }
}
