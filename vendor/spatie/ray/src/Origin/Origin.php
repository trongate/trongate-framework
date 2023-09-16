<?php

namespace Spatie\Ray\Origin;

class Origin
{
    /** @var string|null */
    public $file;

    /** @var string|null */
    public $lineNumber;

    /** @var string|null */
    public $hostname;

    /**
     * @param string|null $file
     * @param int|null $lineNumber
     * @param string|null $hostname
     */
    public function __construct($file, $lineNumber, $hostname = null)
    {
        $this->file = $file;

        $this->lineNumber = $lineNumber;

        $this->hostname = $hostname ?? Hostname::get();
    }

    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line_number' => $this->lineNumber,
            'hostname' => $this->hostname,
        ];
    }

    public function fingerPrint(): string
    {
        return md5(print_r($this->toArray(), true));
    }
}
